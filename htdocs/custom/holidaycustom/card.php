<?php
/* Copyright (C) 2011		Dimitri Mouillard	<dmouillard@teclib.com>
 * Copyright (C) 2012-2016	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2016	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2013		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2017		Alexandre Spangaro	<aspangaro@open-dsi.fr>
 * Copyright (C) 2014-2017  Ferran Marcet		<fmarcet@2byte.es>
 * Copyright (C) 2018       Frédéric France     <frederic.france@netlogic.fr>
 * Copyright (C) 2020-2021  Udo Tamm            <dev@dolibit.de>
 * Copyright (C) 2022		Anthony Berton      <anthony.berton@bb2a.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, orwrite
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
 *   	\file       htdocs/holiday/card.php
 *		\ingroup    holiday
 *		\brief      Form and file creation of paid holiday.
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/holidaycustom/lib/holiday.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/holidaycustom/class/holiday.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/holidaycustom/class/extendeduser.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/holidaycustom/class/extendedTask.class.php';

// Get parameters
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$fuserid = (GETPOST('fuserid', 'int') ?GETPOST('fuserid', 'int') : $user->id);

// Load translation files required by the page
$langs->loadLangs(array("other", "holiday", "mails", "trips", "holidaycustom@holidaycustom"));

$error = 0;

$now = dol_now();

$childids = $user->getAllChildIds(1);

$morefilter = '';
if (!empty($conf->global->HOLIDAY_HIDE_FOR_NON_SALARIES)) {
	$morefilter = 'AND employee = 1';
}

$object = new Holiday($db);

$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$user_static = new User($db);
if (($id > 0) || $ref) {
	$object->fetch($id, $ref);

	$user_static->fetch($object->fk_user);

	// Check current user can read this leave request
	$canread = 0;
	if (!empty($user->rights->holidaycustom->readall)) {
		$canread = 1;
	}
	if (!empty($user->rights->holidaycustom->read) && in_array($object->fk_user, $childids)) {
		$canread = 1;
	}
	if(in_array($user->id, $object->listApprover1[0]) || in_array($user->id, $object->listApprover2[0]) && !$conf->global->HOLIDAY_FDT_APPROVER) {
		$canread = 1;
	}
	if(in_array($user->id, explode(',', $user_static->array_options['options_approbateurfdt'])) && $conf->global->HOLIDAY_FDT_APPROVER) {
		$canread = 1;
	}
	if (!$canread) {
		accessforbidden();
	}
}
elseif(GETPOST('fuserid', 'int')) {
	$user_static->fetch(GETPOST('fuserid', 'int'));
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('holidaycard', 'globalcard'));

$cancreate = 0;
$cancreateall = 0;
if (!empty($user->rights->holidaycustom->write) && in_array($fuserid, $childids)) {
	$cancreate = 1;
}
if(!empty($user->rights->holidaycustom->write) && in_array($user->id, explode(',', $user_static->array_options['options_approbateurfdt'])) && $conf->global->HOLIDAY_FDT_APPROVER) {
	$cancreate = 1;
}
if (!empty($user->rights->holidaycustom->writeall)) {
	$cancreate = 1;
	$cancreateall = 1;
}

$candelete = 0;
if (!empty($user->rights->holidaycustom->delete)) {
	$candelete = 1;
}
// if ($object->statut == Holiday::STATUS_DRAFT && $user->rights->holidaycustom->write && in_array($object->fk_user, $childids)) {
// 	$candelete = 1;
// }

// Protection if external user
if ($user->socid) {
	$socid = $user->socid;
}

$permissiontovalidate1 = 0;
$permissiontovalidate2 = 0;
if($object->id > 0) {
	if(!$conf->global->HOLIDAY_FDT_APPROVER && (in_array($object->fk_type, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)) || in_array(-1, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)))) {
		$permissiontovalidate1 = in_array($user->id, $object->listApprover1[0]) && $object->listApprover1[1][$user->id] == 0;
		$permissiontovalidate2 = in_array($user->id, $object->listApprover2[0]) && $object->listApprover2[1][$user->id] == 0;
	}
	elseif($conf->global->HOLIDAY_FDT_APPROVER && (in_array($object->fk_type, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)) || in_array(-1, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)))) {
		$permissiontovalidate1 = in_array($user->id, explode(',', $user_static->array_options['options_approbateurfdt']));
	}
}

if (empty($conf->holidaycustom->enabled)) accessforbidden();
//$result = restrictedArea($user, 'holidaycustom', $object->id, 'holiday', '', '', 'rowid', $object->statut);

if($conf->feuilledetemps->enabled && $conf->global->FDT_STANDARD_WEEK_FOR_HOLIDAY) {
	$usertoprocess = new User($db);
	if($object->fk_user > 0) {
		$usertoprocess->fetch($object->fk_user);
	}
	elseif($fuserid > 0) {
		$usertoprocess->fetch($fuserid);
	}

 	if($conf->donneesrh->enabled) {
		$extrafields->fetch_name_optionals_label('donneesrh_Positionetcoefficient');
		$userField = new UserField($db);
		$userField->id = $usertoprocess->id;
		$userField->table_element = 'donneesrh_Positionetcoefficient';
		$userField->fetch_optionals();

		$heure_semaine = (!empty($userField->array_options['options_pasdroitrtt']) ?  $conf->global->HEURE_SEMAINE_NO_RTT : $conf->global->HEURE_SEMAINE);
		$heure_semaine = (!empty($userField->array_options['options_horairehebdomadaire']) ? $userField->array_options['options_horairehebdomadaire'] : $heure_semaine);
		$heure_semaine_hs = (!empty($userField->array_options['options_pasdroitrtt']) ? $conf->global->HEURE_SEMAINE_NO_RTT : $conf->global->HEURE_SEMAINE);
	}
	else {
		$heure_semaine = (!empty($usertoprocess->array_options['options_pasdroitrtt']) ?  $conf->global->HEURE_SEMAINE_NO_RTT : $conf->global->HEURE_SEMAINE);
		$heure_semaine = (!empty($usertoprocess->array_options['options_horairehebdomadaire']) ? $usertoprocess->array_options['options_horairehebdomadaire'] : $heure_semaine);
		$heure_semaine_hs = (!empty($usertoprocess->array_options['options_pasdroitrtt']) ? $conf->global->HEURE_SEMAINE_NO_RTT : $conf->global->HEURE_SEMAINE);
	}

	// Semaine type
	$standard_week_hour = array();
	if($usertoprocess->array_options['options_semaine_type_lundi'] || $usertoprocess->array_options['options_semaine_type_mardi'] || $usertoprocess->array_options['options_semaine_type_mercredi'] || 
	$usertoprocess->array_options['options_semaine_type_jeudi'] || $usertoprocess->array_options['options_semaine_type_vendredi'] || $usertoprocess->array_options['options_semaine_type_samedi'] || 
	$usertoprocess->array_options['options_semaine_type_dimanche']) {
		$standard_week_hour['Lundi'] = $usertoprocess->array_options['options_semaine_type_lundi'] * 3600;
		$standard_week_hour['Mardi'] = $usertoprocess->array_options['options_semaine_type_mardi'] * 3600;
		$standard_week_hour['Mercredi'] = $usertoprocess->array_options['options_semaine_type_mercredi'] * 3600;
		$standard_week_hour['Jeudi'] = $usertoprocess->array_options['options_semaine_type_jeudi'] * 3600;
		$standard_week_hour['Vendredi'] = $usertoprocess->array_options['options_semaine_type_vendredi'] * 3600;
		$standard_week_hour['Samedi'] = $usertoprocess->array_options['options_semaine_type_samedi'] * 3600;
		$standard_week_hour['Dimanche'] = $usertoprocess->array_options['options_semaine_type_dimanche'] * 3600;
	}
	elseif($heure_semaine == $conf->global->HEURE_SEMAINE_NO_RTT) {
		$standard_week_hour['Lundi'] = $conf->global->FDT_STANDARD_WEEK_MONDAY_NO_RTT * 3600;
		$standard_week_hour['Mardi'] = $conf->global->FDT_STANDARD_WEEK_TUESDAY_NO_RTT * 3600;
		$standard_week_hour['Mercredi'] = $conf->global->FDT_STANDARD_WEEK_WEDNESDAY_NO_RTT * 3600;
		$standard_week_hour['Jeudi'] = $conf->global->FDT_STANDARD_WEEK_THURSDAY_NO_RTT * 3600;
		$standard_week_hour['Vendredi'] = $conf->global->FDT_STANDARD_WEEK_FRIDAY_NO_RTT * 3600;
		$standard_week_hour['Samedi'] = $conf->global->FDT_STANDARD_WEEK_SATURDAY_NO_RTT * 3600;
		$standard_week_hour['Dimanche'] = $conf->global->FDT_STANDARD_WEEK_SUNDAY_NO_RTT * 3600;
	}
	else {
		$standard_week_hour['Lundi'] = $conf->global->FDT_STANDARD_WEEK_MONDAY_WITH_RTT * 3600;
		$standard_week_hour['Mardi'] = $conf->global->FDT_STANDARD_WEEK_TUESDAY_WITH_RTT * 3600;
		$standard_week_hour['Mercredi'] = $conf->global->FDT_STANDARD_WEEK_WEDNESDAY_WITH_RTT * 3600;
		$standard_week_hour['Jeudi'] = $conf->global->FDT_STANDARD_WEEK_THURSDAY_WITH_RTT * 3600;
		$standard_week_hour['Vendredi'] = $conf->global->FDT_STANDARD_WEEK_FRIDAY_WITH_RTT * 3600;
		$standard_week_hour['Samedi'] = $conf->global->FDT_STANDARD_WEEK_SATURDAY_WITH_RTT * 3600;
		$standard_week_hour['Dimanche'] = $conf->global->FDT_STANDARD_WEEK_SUNDAY_WITH_RTT * 3600;
	}
}

/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$backurlforlist = DOL_URL_ROOT.'/custom/holidaycustom/list.php';

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/custom/holidaycustom/card.php?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	if ($cancel) {
		if (!empty($backtopageforcancel)) {
			header("Location: ".$backtopageforcancel);
			exit;
		} elseif (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
		$action = '';
	}

	// Add leave request
	if ($action == 'add') {
		// If no right to create a request
		if (!$cancreate) {
			$error++;
			setEventMessages($langs->trans('CantCreateCP'), null, 'errors');
			$action = 'create';
		}


		if (!$error) {
			$object = new Holiday($db);

			$db->begin();

			$date_debut = dol_mktime(0, 0, 0, GETPOST('date_debut_month'), GETPOST('date_debut_day'), GETPOST('date_debut_year'));
			$date_fin = dol_mktime(0, 0, 0, GETPOST('date_fin_month'), GETPOST('date_fin_day'), GETPOST('date_fin_year'));
			$date_debut_gmt = dol_mktime(0, 0, 0, GETPOST('date_debut_month'), GETPOST('date_debut_day'), GETPOST('date_debut_year'), 1);
			$date_fin_gmt = dol_mktime(0, 0, 0, GETPOST('date_fin_month'), GETPOST('date_fin_day'), GETPOST('date_fin_year'), 1);
			$duration_hour = (GETPOSTINT('hourhour') ? GETPOSTINT('hourhour') : 0) * 60 * 60;
			$duration_hour += (GETPOSTINT('hourmin') ? GETPOSTINT('hourmin') : 0) * 60;
			$starthalfday = GETPOST('starthalfday');
			$endhalfday = GETPOST('endhalfday');
			$type = GETPOST('type');
			$halfday = 0;
			if ($starthalfday == 'afternoon' && $endhalfday == 'morning') {
				$halfday = 2;
			} elseif ($starthalfday == 'afternoon') {
				$halfday = -1;
			} elseif ($endhalfday == 'morning') {
				$halfday = 1;
			}

			$approverid = GETPOST('valideur', 'int');
			$description = trim(GETPOST('description', 'restricthtml'));

			// Check that leave is for a user inside the hierarchy or advanced permission for all is set
			if (!$cancreateall) {
				if (empty($conf->global->MAIN_USE_ADVANCED_PERMS)) {
					if (empty($user->rights->holidaycustom->write)) {
						$error++;
						setEventMessages($langs->trans("NotEnoughPermissions"), null, 'errors');
					} elseif (!in_array($fuserid, $childids) && (!in_array($user->id, explode(',', $user_static->array_options['options_approbateurfdt'])) || !$conf->global->HOLIDAY_FDT_APPROVER)) {
						$error++;
						setEventMessages($langs->trans("UserNotInHierachy"), null, 'errors');
						$action = 'create';
					}
				} else {
					if (empty($user->rights->holidaycustom->write) && empty($user->rights->holidaycustom->writeall)) {
						$error++;
						setEventMessages($langs->trans("NotEnoughPermissions"), null, 'errors');
					} elseif (empty($user->rights->holidaycustom->writeall) && !in_array($fuserid, $childids) && (!in_array($user->id, explode(',', $user_static->array_options['options_approbateurfdt'])) || !$conf->global->HOLIDAY_FDT_APPROVER)) {
						$error++;
						setEventMessages($langs->trans("UserNotInHierachy"), null, 'errors');
						$action = 'create';
					}
				}
			}

			// If no type
			if ($type <= 0) {
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
				$error++;
				$action = 'create';
			}

			// If no start date
			if (empty($date_debut)) {
				setEventMessages($langs->trans("NoDateDebut"), null, 'errors');
				$error++;
				$action = 'create';
			}
			// If no end date
			if (empty($date_fin)) {
				setEventMessages($langs->trans("NoDateFin"), null, 'errors');
				$error++;
				$action = 'create';
			}
			// If start date after end date
			if ($date_debut > $date_fin) {
				setEventMessages($langs->trans("ErrorEndDateCP"), null, 'errors');
				$error++;
				$action = 'create';
			}
			if($conf->global->HOLIDAY_ONLY_CURRENT_MONTH) {
				$now = dol_now();
				if($conf->feuilledetemps->enabled && $conf->global->FDT_DAY_FOR_NEXT_FDT > 0 && dol_print_date($now, '%d') < $conf->global->FDT_DAY_FOR_NEXT_FDT) {
					$firstdaymonth = dol_get_first_day(dol_print_date(dol_time_plus_duree($now, -1, 'm'), '%Y'), dol_print_date(dol_time_plus_duree($now, -1, 'm'), '%m'));
				}
				else {
					$firstdaymonth = dol_get_first_day(dol_print_date($now, '%Y'), dol_print_date($now, '%m'));
				}
				$firstdayweek = dol_get_first_day_week(dol_print_date($firstdaymonth, '%d'), dol_print_date($firstdaymonth, '%m'), dol_print_date($firstdaymonth, '%Y'));
				$firstdayweek = dol_mktime(-1, -1, -1, $firstdayweek['first_month'], $firstdayweek['first_day'], $firstdayweek['first_year']);
				if (!empty($date_fin) && $date_fin < $firstdayweek) {
					setEventMessages($langs->trans("ErrorDateBeforeMonth"), null, 'errors');
					$error++;
					$action = 'create';
				}
			}

			// Check if there is already holiday for this period
			$verifCP = $object->verifDateHolidayCP($fuserid, $date_debut, $date_fin, $halfday);
			if (!$verifCP) {
				setEventMessages($langs->trans("alreadyCPexist"), null, 'errors');
				$error++;
				$action = 'create';
			}

			// If there is no Business Days within request
			$nbopenedday = num_open_day($date_debut_gmt, $date_fin_gmt, 0, 1, $halfday);
			if ($nbopenedday < 0.5) {
				setEventMessages($langs->trans("ErrorDureeCP"), null, 'errors'); // No working day
				$error++;
				$action = 'create';
			}

			$needHour = $object->holidayTypeNeedHour($type);
			$canHalfday = $object->holidayTypeCanHalfday($type);

			// If no hour and hour is required
			if (empty($duration_hour) && $needHour == 1 && $date_debut == $date_fin && (!$conf->global->HOIDAY_AUTO_HOUR_WHOLE_DAY || $halfday != 0)) {
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Hour")), null, 'errors');
				$error++;
				$action = 'create';
			}

			// if($needHour && date("W", $date_debut) != date("W", $date_fin)) {
			// 	setEventMessages($langs->trans("ErrorWeekHoliday"), null, 'errors');
			// 	$error++;
			// 	$action = 'create';
			// }

			if($needHour && $date_debut != $date_fin && $halfday != 0) {
				setEventMessages($langs->trans("ErrorHalfdayHoliday"), null, 'errors');
				$error++;
				$action = 'create';
			}

			if($needHour && !empty($duration_hour) && (GETPOSTINT('hourhour') < 0 || GETPOSTINT('hourhour') > 8)) {
				setEventMessages($langs->trans("ErrorNbHourHoliday"), null, 'errors');
				$error++;
				$action = 'create';
			}

			if($needHour && !empty($duration_hour) && (GETPOSTINT('hourmin') != 0 && GETPOSTINT('hourmin') != 30)) {
				setEventMessages($langs->trans("ErrorNbMinHoliday"), null, 'errors');
				$error++;
				$action = 'create';
			}

			if ($needHour && !empty($duration_hour) && !empty($conf->global->HOLIDAY_INHOUR_MAX_HOUR) && $duration_hour > ($conf->global->HOLIDAY_INHOUR_MAX_HOUR * 3600)) {
				setEventMessages($langs->trans("ErrorMaxHourHoliday", $conf->global->HOLIDAY_INHOUR_MAX_HOUR), null, 'errors');
				$error++;
				$action = 'create';
			}

			// if($needHour && !empty($duration_hour) && $duration_hour > 3600*7) {
			// 	setEventMessages($langs->trans("ErrorMaxHourHoliday"), null, 'errors');
			// 	$error++;
			// 	$action = 'create';
			// }

			// If no validator designated
			/*if ($approverid < 1) {
				setEventMessages($langs->transnoentitiesnoconv('InvalidValidatorCP'), null, 'errors');
				$error++;
			}*/

			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost(null, $object);
			if ($ret < 0) {
				$error++;
			}

			$result = 0;

			if (!$error) {
				$userstatic = new User($db);
				$userstatic->fetch($fuserid);

				$approver1id = array();
				$approver2id = array();

				$object->fk_user = $fuserid;
				$object->description = $description;
				//$object->fk_validator = $approverid;
				$object->fk_type = $type;
				$object->date_debut = $date_debut;
				$object->date_fin = $date_fin;

				if($canHalfday) {
					$object->halfday = $halfday;
				}
				else {
					$object->halfday = 0;
				}

				// Gestion des approbateurs
				if(!$conf->global->HOLIDAY_FDT_APPROVER && (in_array($type, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)) || in_array(-1, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)))) {
					$projectstatic = new Project($db);
					$taskstatic = new extendedTaskHoliday($db);
					$filter = " AND dateo <= '".$db->idate($date_fin)."' AND (datee >= '".$db->idate($date_debut)."' OR datee IS NULL) AND fk_statut = 1 AND ec.fk_c_type_contact = 161";		
					$liste_projet = $projectstatic->getProjectsAuthorizedForUser($userstatic, 1, 0, 0, $filter); 
					foreach($liste_projet as $projetid => $ref) {
						$projectstatic->fetch($projetid);
						if(!$projectstatic->array_options['options_projetstructurel']) {
							// Responsables de projet
							$liste_resp_projet = $projectstatic->liste_contact(-1, 'internal', 1, 'PROJECTLEADER', 1);
							foreach($liste_resp_projet as $userid) {
								if (!in_array($userid, $approver2id)) {
									$approver2id[] = $userid;
								}
							}

							$filter = " AND t.dateo <= '".$db->idate($date_fin)."' AND (t.datee >= '".$db->idate($date_debut)."' OR t.datee IS NULL) AND ctc2.code = 'TASKCONTRIBUTOR'";
							$liste_taches = $taskstatic->getTasksArrayCorrect(0, $userstatic, $projectstatic->id, 0, 0, '',  '-1', $filter, 0, $userstatic->id, array(),  0,  array(),  0,  1);
							foreach($liste_taches as $task_static) {
								// Responsables de tâche
								$liste_resp_tache = $task_static->liste_contact(-1, 'internal', 1, 'TASKEXECUTIVE', 1);
								foreach($liste_resp_tache as $userid) {
									if (!in_array($userid, $approver1id) && !in_array($userid, $approver2id)) {
										$approver1id[] = $userid;
									}
								}
							}
						}
					}

					if(empty($approver2id)) {
						$user_validation_1 = new User($db);
						if(!empty($userstatic->fk_user)){
							$user_validation_1->fetch($userstatic->fk_user);
						}
						else {
							$user_validation_1->fetch(16);
						}
						$approver1id[] = $user_validation_1->id;
						
						//if(empty($approver2id)) {
							if(!empty($user_validation_1->fk_user) && $user_validation_1->fk_user != 16){
								$approver2id[] = $user_validation_1->fk_user;
							}
						//}
					}
				}

				if($needHour && $date_debut == $date_fin && !empty($duration_hour)) {
					$object->array_options['options_hour'] = $duration_hour;
				}
				elseif($needHour && ($date_debut != $date_fin || ($date_debut == $date_fin && $conf->global->HOIDAY_AUTO_HOUR_WHOLE_DAY && $halfday == 0))) {
					if($conf->feuilledetemps->enabled && $conf->global->FDT_STANDARD_WEEK_FOR_HOLIDAY) {
						$nbDay = num_between_day($date_debut_gmt, $date_fin_gmt, 0) + 1;
						$duration_hour = 0;
						for($i = 0; $i < $nbDay; $i++) {
							$tmpday = dol_time_plus_duree($date_debut_gmt, $i, 'd');
							$tmpdaygmt = dol_mktime(0, 0, 0, dol_print_date($tmpday, '%m'), dol_print_date($tmpday, '%d'), dol_print_date($tmpday, '%Y'), 'gmt');

							if(num_public_holiday($tmpdaygmt, $tmpdaygmt, '', 1) == 0) {
								if((($holiday->halfday == 1 || $holiday->halfday == 2) && $i == $nbDay - 1) || (($holiday->halfday == -1 || $holiday->halfday == 2) && $i == 0)) { // gestion des demi journées
									$duration_hour += 0.5 * $standard_week_hour[dol_print_date($tmpday, '%A')];
								}
								else {
									$duration_hour += $standard_week_hour[dol_print_date($tmpday, '%A')];
								}
							}
						}
					}
					else {
						$nbDay = num_open_day($date_debut_gmt, $date_fin_gmt, 0, 1, $holiday->halfday);
						$duration_hour = $nbDay * 7 * 3600;
					}
					$object->array_options['options_hour'] = $duration_hour;
				}

				if($conf->global->FDT_STATUT_HOLIDAY) {
					$form = new Form($db);
					$userstatic->fetch($object->fk_user);
					if($object->fk_type == 4 || ($userstatic->array_options['options_employeur'] != 1 && $conf->global->FDT_MANAGE_EMPLOYER)) {
						$object->array_options['options_statutfdt'] = 4;
					}
					elseif(in_array(array_search('Exclusion FDT', $form->select_all_categories(Categorie::TYPE_USER, null, null, null, null, 1)), $userstatic->getCategoriesCommon(Categorie::TYPE_USER))) {
						$object->array_options['options_statutfdt'] = 2;
					}
				}

				$result = $object->create($user);

				if ($result <= 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				}
				else {
					if(!in_array($type, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)) && !in_array(-1, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE))) {
						$object->statut = $object::STATUS_APPROVED2;
						$res = $object->validate($user);
						
						if ($res <= 0) {
							$error++;
						}
					}	
				}
			}

			// Ajout des approbateurs : responsables de tâches et de projets
			if(!$error && !$conf->global->HOLIDAY_FDT_APPROVER && (in_array($type, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)) || in_array(-1, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)))) {
				foreach($approver1id as $userid) {
					$res = $object->createApprobation($userid, 1, 1);

					if ($res <= 0) {
						$error++;
					}
				}

				foreach($approver2id as $userid) {
					$res = $object->createApprobation($userid, 1, 2);

					if ($res <= 0) {
						$error++;
					}
				}
			}

			// If no SQL error we redirect to the request card
			if (!$error) {
				$db->commit();

				header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
				exit;
			} else {
				$db->rollback();
			}
		}
	}

	if ($action == 'update' && GETPOSTISSET('savevalidator1') && !empty($user->rights->holidaycustom->changeappro) && !$conf->global->HOLIDAY_FDT_APPROVER && (in_array($object->fk_type, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)) || in_array(-1, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)))) {
		$object->fetch($id);
		$db->begin();

		$object->oldcopy = dol_clone($object);
		$emailTo = '';
		$userstatic = new User($db);

		//$object->fk_validator = GETPOST('valideur', 'int');

		$approver1id = GETPOST('fk_user_approbation1');
		$list_validation1 = $object->listApprover('', 1);

		if (empty($approver1id) && empty($object->listApprover2)) {
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&token='.newToken().'&error=Approbateur');
			exit;
		}

		if($object->fk_validator > 0) {
			$object->createApprobation($object->fk_validator, 1, 1); 
			$object->fk_validator = 0;
			$object->update($user->id);
		}
		if(!empty($object->array_options['options_fk_validator2'])) {
			$object->createApprobation($object->array_options['options_fk_validator2'], 1, 2); 
			$object->array_options['options_fk_validator2'] = null;
			$object->update($user->id);
		}

		// 1ere étape : Supprimer les 1er validateur nécéssaire
		foreach($list_validation1[2] as $id_user => $user_static){
			if(!in_array($id_user, $approver1id)){
				$res = $object->deleteApprobation($id_user, 1);

				if($res < 0) {
					$error++;
				}
			}
		}

		// 2e étape : On ajoute les 1er validateur nécéssaire
		foreach($approver1id as $id_user){
			if($id_user > 0 && !array_key_exists($id_user, $list_validation1[0])){
				$res = $object->createApprobation($id_user, 1, 1); 
				$userstatic->fetch($id_user);

				if(!empty($userstatic->email)){
					$emailTo .= $userstatic->email.', ';
				}

				if($res < 0) {
					$error++;
				}
			}
		}

		if (!$error && !empty($emailTo) && $object->statut == Holiday::STATUS_VALIDATED) {
			$emailTo = rtrim($emailTo, ", ");

			// From
			$expediteur = new User($db);
			$expediteur->fetch($object->fk_user);
			//$emailFrom = $expediteur->email;		Email of user can be an email into another company. Sending will fails, we must use the generic email.
			$emailFrom = $conf->global->MAIN_MAIL_EMAIL_FROM;

			// Subject
			$societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
			if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
				$societeName = $conf->global->MAIN_APPLICATION_TITLE;
			}

			$subject = $societeName." - ".$langs->transnoentitiesnoconv("HolidaysToValidate");

			// Content
			$message = "<p>".$langs->transnoentitiesnoconv("Hello").",</p>\n";

			$message .= "<p>".$langs->transnoentities("HolidaysToValidateBody")."</p>\n";


			// option to warn the validator in case of too short delay
			if (empty($conf->global->HOLIDAY_HIDE_APPROVER_ABOUT_TOO_LOW_DELAY)) {
				$delayForRequest = 0;		// TODO Set delay depending of holiday leave type
				if ($delayForRequest) {
					$nowplusdelay = dol_time_plus_duree($now, $delayForRequest, 'd');

					if ($object->date_debut < $nowplusdelay) {
						$message = "<p>".$langs->transnoentities("HolidaysToValidateDelay", $delayForRequest)."</p>\n";
					}
				}
			}

			// option to notify the validator if the balance is less than the request
			if (empty($conf->global->HOLIDAY_HIDE_APPROVER_ABOUT_NEGATIVE_BALANCE)) {
				$nbopenedday = num_open_day($object->date_debut_gmt, $object->date_fin_gmt, 0, 1, $object->halfday);

				$ACP = $object->getTypesCP(1, 'ACP');
				if ($object->fk_type == $ACP['rowid'] && $nbopenedday > $object->getCPforUser($object->fk_user, $object->fk_type)) {
					$message .= "<p>".$langs->transnoentities("HolidaysToValidateAlertSolde")."</p>\n";
				}
			}

			$type = $object->getTypeWithID($object->fk_type);
			$link = dol_buildpath("/custom/holidaycustom/card.php", 3) . '?id='.$object->id;

			$message .= "<ul>";
			$message .= "<li>".$langs->transnoentitiesnoconv("Name")." : ".dolGetFirstLastname($expediteur->firstname, $expediteur->lastname)."</li>\n";
			$message .= "<li>".$langs->transnoentitiesnoconv("Period")." : ".dol_print_date($object->date_debut, 'day')." ".$langs->transnoentitiesnoconv("To")." ".dol_print_date($object->date_fin, 'day')."</li>\n";
			$message .= "<li>".$langs->transnoentitiesnoconv("Type")." : ".$type['label']."</li>\n";
			$message .= "<li>".$langs->transnoentitiesnoconv("Link").' : <a href="'.$link.'" target="_blank">'.$link."</a></li>\n";
			$message .= "</ul>\n";

			$trackid = 'leav'.$object->id;

			$mail = new CMailFile($subject, $emailTo, $emailFrom, $message, array(), array(), array(), '', '', 0, 1, '', '', $trackid);

			// Sending the email
			$result = $mail->sendfile();

			if (!$result) {
				setEventMessages($mail->error, $mail->errors, 'warnings'); // Show error, but do no make rollback, so $error is not set to 1
				$action = '';
			}
		}
		
		if (!$error) {
			$db->commit();
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		} else {
			$db->rollback();
			setEventMessages($object->error, $object->errors, 'warnings');
			$action = 'editvalidator1';
		}
	}

	if ($action == 'update' && GETPOSTISSET('savevalidator2') && !empty($user->rights->holidaycustom->changeappro) && !$conf->global->HOLIDAY_FDT_APPROVER && (in_array($object->fk_type, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)) || in_array(-1, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)))) {
		$object->fetch($id);
		$db->begin();

		$object->oldcopy = dol_clone($object);
		$emailTo = '';
		$userstatic = new User($db);

		//$object->fk_validator = GETPOST('valideur', 'int');

		$approver2id = GETPOST('fk_user_approbation2');
		$list_validation2 = $object->listApprover('', 2);

		if (empty($approver2id) && empty($object->listApprover1)) {
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&token='.newToken().'&error=Approbateur');
			exit;
		}

		if($object->fk_validator > 0) {
			$object->createApprobation($object->fk_validator, 1, 1); 
			$object->fk_validator = 0;
			$object->update($user->id);
		}
		if(!empty($object->array_options['options_fk_validator2'])) {
			$object->createApprobation($object->array_options['options_fk_validator2'], 1, 2); 
			$object->array_options['options_fk_validator2'] = null;
			$object->update($user->id);
		}

		// 1ere étape : Supprimer les 2nd validateur nécéssaire
		foreach($list_validation2[2] as $id_user => $user_static){
			if(!in_array($id_user, $approver2id)){
				$res = $object->deleteApprobation($id_user, 2);

				if($res < 0) {
					$error++;
				}
			}
		}

		// 2e étape : On ajoute les 2nd validateur nécéssaire
		foreach($approver2id as $id_user){
			if($id_user > 0 && !array_key_exists($id_user, $list_validation2[0])){
				$res = $object->createApprobation($id_user, 1, 2); 
				$userstatic->fetch($id_user);

				if(!empty($userstatic->email)){
					$emailTo .= $userstatic->email.', ';
				}

				if($res < 0) {
					$error++;
				}
			}
		}

		if (!$error && !empty($emailTo) && $object->statut == Holiday::STATUS_APPROVED1) {
			$emailTo = rtrim($emailTo, ", ");

			// From
			$expediteur = new User($db);
			$expediteur->fetch($object->fk_user);
			//$emailFrom = $expediteur->email;		Email of user can be an email into another company. Sending will fails, we must use the generic email.
			$emailFrom = $conf->global->MAIN_MAIL_EMAIL_FROM;

			// Subject
			$societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
			if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
				$societeName = $conf->global->MAIN_APPLICATION_TITLE;
			}

			$subject = $societeName." - ".$langs->transnoentitiesnoconv("HolidaysToValidate");

			// Content
			$message = "<p>".$langs->transnoentitiesnoconv("Hello").",</p>\n";

			$message .= "<p>".$langs->transnoentities("HolidaysToValidateBody")."</p>\n";


			// option to warn the validator in case of too short delay
			if (empty($conf->global->HOLIDAY_HIDE_APPROVER_ABOUT_TOO_LOW_DELAY)) {
				$delayForRequest = 0;		// TODO Set delay depending of holiday leave type
				if ($delayForRequest) {
					$nowplusdelay = dol_time_plus_duree($now, $delayForRequest, 'd');

					if ($object->date_debut < $nowplusdelay) {
						$message = "<p>".$langs->transnoentities("HolidaysToValidateDelay", $delayForRequest)."</p>\n";
					}
				}
			}

			// option to notify the validator if the balance is less than the request
			if (empty($conf->global->HOLIDAY_HIDE_APPROVER_ABOUT_NEGATIVE_BALANCE)) {
				$nbopenedday = num_open_day($object->date_debut_gmt, $object->date_fin_gmt, 0, 1, $object->halfday);

				$ACP = $object->getTypesCP(1, 'ACP');
				if ($object->fk_type == $ACP['rowid'] && $nbopenedday > $object->getCPforUser($object->fk_user, $object->fk_type)) {
					$message .= "<p>".$langs->transnoentities("HolidaysToValidateAlertSolde")."</p>\n";
				}
			}

			$type = $object->getTypeWithID($object->fk_type);
			$link = dol_buildpath("/custom/holidaycustom/card.php", 3) . '?id='.$object->id;

			$message .= "<ul>";
			$message .= "<li>".$langs->transnoentitiesnoconv("Name")." : ".dolGetFirstLastname($expediteur->firstname, $expediteur->lastname)."</li>\n";
			$message .= "<li>".$langs->transnoentitiesnoconv("Period")." : ".dol_print_date($object->date_debut, 'day')." ".$langs->transnoentitiesnoconv("To")." ".dol_print_date($object->date_fin, 'day')."</li>\n";
			$message .= "<li>".$langs->transnoentitiesnoconv("Type")." : ".$type['label']."</li>\n";
			$message .= "<li>".$langs->transnoentitiesnoconv("Link").' : <a href="'.$link.'" target="_blank">'.$link."</a></li>\n";
			$message .= "</ul>\n";

			$trackid = 'leav'.$object->id;

			$mail = new CMailFile($subject, $emailTo, $emailFrom, $message, array(), array(), array(), '', '', 0, 1, '', '', $trackid);

			// Sending the email
			$result = $mail->sendfile();

			if (!$result) {
				setEventMessages($mail->error, $mail->errors, 'warnings'); // Show error, but do no make rollback, so $error is not set to 1
				$action = '';
			}
		}
		
		if (!$error) {
			$db->commit();
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		} else {
			$db->rollback();
			setEventMessages($object->error, $object->errors, 'warnings');
			$action = 'editvalidator2';
		}
	}

	if ($action == 'update' && !GETPOSTISSET('savevalidator1') && !GETPOSTISSET('savevalidator2')) {
		$date_debut = dol_mktime(0, 0, 0, GETPOST('date_debut_month'), GETPOST('date_debut_day'), GETPOST('date_debut_year'));
		$date_fin = dol_mktime(0, 0, 0, GETPOST('date_fin_month'), GETPOST('date_fin_day'), GETPOST('date_fin_year'));
		$date_debut_gmt = dol_mktime(0, 0, 0, GETPOST('date_debut_month'), GETPOST('date_debut_day'), GETPOST('date_debut_year'), 1);
		$date_fin_gmt = dol_mktime(0, 0, 0, GETPOST('date_fin_month'), GETPOST('date_fin_day'), GETPOST('date_fin_year'), 1);
		$starthalfday = GETPOST('starthalfday');
		$endhalfday = GETPOST('endhalfday');
		$duration_hour = (GETPOSTINT('hourhour') ? GETPOSTINT('hourhour') : 0) * 60 * 60;
		$duration_hour += (GETPOSTINT('hourmin') ? GETPOSTINT('hourmin') : 0) * 60;		
		$halfday = 0;
		if ($starthalfday == 'afternoon' && $endhalfday == 'morning') {
			$halfday = 2;
		} elseif ($starthalfday == 'afternoon') {
			$halfday = -1;
		} elseif ($endhalfday == 'morning') {
			$halfday = 1;
		}

		// If no right to modify a request
		if (!$cancreateall) {
			if ($cancreate) {
				// if (!in_array($fuserid, $childids)) {
				// 	setEventMessages($langs->trans("UserNotInHierachy"), null, 'errors');
				// 	header('Location: '.$_SERVER["PHP_SELF"].'?action=create');
				// 	exit;
				// }
			} else {
				setEventMessages($langs->trans("NotEnoughPermissions"), null, 'errors');
				header('Location: '.$_SERVER["PHP_SELF"].'?action=create');
				exit;
			}
		}

		$object->fetch($id);
	
		// If under validation
		if ($object->statut == Holiday::STATUS_DRAFT || ($object->array_options['options_statutfdt'] == 1 && $object->statut == Holiday::STATUS_APPROVED2 && !in_array($object->fk_type, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)) && !in_array(-1, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)))) {
			// If this is the requestor or has read/write rights
			if ($cancreate) {
				//$approverid = GETPOST('valideur', 'int');

				// TODO Check this approver user id has the permission for approval

				$description = trim(GETPOST('description', 'restricthtml'));

				// If no start date
				if (!GETPOST('date_debut_')) {
					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken().'&error=nodatedebut');
					exit;
				}

				// If no end date
				if (!GETPOST('date_fin_')) {
					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken().'&error=nodatefin');
					exit;
				}

				// If start date after end date
				if ($date_debut > $date_fin) {
					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken().'&error=datefin');
					exit;
				}

				// If no validator designated
				// if ($approverid < 1) {
				// 	header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken().'&error=Valideur');
				// 	exit;
				// }
				// if (empty(GETPOST('fk_user_approbation1'))) {
				// 	header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken().'&error=Approbateur');
				// 	exit;
				// }

				// If there is no Business Days within request
				$nbopenedday = num_open_day($date_debut_gmt, $date_fin_gmt, 0, 1, $halfday);
				if ($nbopenedday < 0.5) {
					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&error=DureeHoliday');
					exit;
				}

				// Check if there is already holiday for this period
				$verifCP = $object->verifDateHolidayCP($object->fk_user, $date_debut, $date_fin, $halfday, " AND cp.rowid NOT IN ($object->id)");
				if (!$verifCP) {
					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&error=alreadyCP');
					exit;
				}

				if(GETPOST('options_client_informe') == 1 && empty(GETPOST('options_nom_client'))){
					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&error=NomClient');
					exit;
				}

				$needHour = $object->holidayTypeNeedHour((int)$object->fk_type);
				$canHalfday = $object->holidayTypeCanHalfday($type);

				// If no hour and hour is required
				if (empty($duration_hour) && $needHour == 1 && $date_debut == $date_fin && (!$conf->global->HOIDAY_AUTO_HOUR_WHOLE_DAY || $halfday != 0)) {
					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&error=Hour');
					exit;
				}

				// if($needHour && date("W", $date_debut) != date("W", $date_fin)) {
				// 	header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&error=ErrorWeekHoliday');
				// 	exit;
				// }
	
				if($needHour && $date_debut != $date_fin && $halfday != 0) {
					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&error=ErrorHalfdayHoliday');
					exit;
				}

				if($needHour && !empty($duration_hour) && (GETPOSTINT('hourhour') < 0 || GETPOSTINT('hourhour') > 8)) {
					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&error=ErrorNbHourHoliday');
					exit;
				}
	
				if($needHour && !empty($duration_hour) && (GETPOSTINT('hourmin') != 0 && GETPOSTINT('hourmin') != 30)) {
					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&error=ErrorNbMinHoliday');
					exit;
				}
	
				// if($needHour && !empty($duration_hour) && $duration_hour > 3600*7) {
				// 	header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&error=ErrorMaxHourHoliday');
				// 	exit;
				// }

				if ($needHour && !empty($duration_hour) && !empty($conf->global->HOLIDAY_INHOUR_MAX_HOUR) && $duration_hour > ($conf->global->HOLIDAY_INHOUR_MAX_HOUR * 3600)) {
					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&error=ErrorMaxHourHoliday');
					exit;
				}

				if(($object->date_debut != $date_debut || $object->date_fin != $date_fin) && !$conf->global->HOLIDAY_FDT_APPROVER && (in_array($object->fk_type, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)) || in_array(-1, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)))) {
					$res = $object->deleteAllApprobation();
					if ($res <= 0) {
						$error++;
					}

					// Gestion des approbateurs
					$projectstatic = new Project($db);
					$userstatic = new User($db);
					$taskstatic = new extendedTaskHoliday($db);
					$userstatic->fetch($object->fk_user);
					$filter = " AND dateo <= '".$db->idate($date_fin)."' AND (datee >= '".$db->idate($date_debut)."' OR datee IS NULL) AND fk_statut = 1 AND ec.fk_c_type_contact = 161";		
					$liste_projet = $projectstatic->getProjectsAuthorizedForUser($userstatic, 1, 0, 0, $filter); 
					foreach($liste_projet as $projetid => $ref) {
						$projectstatic->fetch($projetid);
						if(!$projectstatic->array_options['options_projetstructurel']) {
							// Responsables de projet
							$liste_resp_projet = $projectstatic->liste_contact(-1, 'internal', 1, 'PROJECTLEADER', 1);
							foreach($liste_resp_projet as $userid) {
								if (!in_array($userid, $approver2id)) {
									$approver2id[] = $userid;
								}
							}

							$filter = " AND t.dateo <= '".$db->idate($date_fin)."' AND (t.datee >= '".$db->idate($date_debut)."' OR t.datee IS NULL) AND ctc2.code = 'TASKCONTRIBUTOR'";
							$liste_taches = $taskstatic->getTasksArrayCorrect(0, $userstatic, $projectstatic->id, 0, 0, '',  '-1', $filter, 0, $userstatic->id, array(),  0,  array(),  0,  1);
							foreach($liste_taches as $task_static) {
								// Responsables de tâche
								$liste_resp_tache = $task_static->liste_contact(-1, 'internal', 1, 'TASKEXECUTIVE', 1);
								foreach($liste_resp_tache as $userid) {
									if (!in_array($userid, $approver1id) && !in_array($userid, $approver2id)) {
										$approver1id[] = $userid;
									}
								}
							}
						}
					}

					if(empty($approver2id)) {
						$user_validation_1 = new User($db);
						if(!empty($userstatic->fk_user)){
							$user_validation_1->fetch($userstatic->fk_user);
						}
						else {
							$user_validation_1->fetch(16);
						}
						$approver1id[] = $user_validation_1->id;
						
						//if(empty($approver2id)) {
							if(!empty($user_validation_1->fk_user) && $user_validation_1->fk_user != 16){
								$approver2id[] = $user_validation_1->fk_user;
							}
						//}
					}

					foreach($approver1id as $userid) {
						$res = $object->createApprobation($userid, 1, 1);
	
						if ($res <= 0) {
							$error++;
						}
					}
	
					foreach($approver2id as $userid) {
						$res = $object->createApprobation($userid, 1, 2);
	
						if ($res <= 0) {
							$error++;
						}
					}
				}

				$object->description = $description;
				$object->date_debut = $date_debut;
				$object->date_fin = $date_fin;
				//if(!empty($user->rights->holidaycustom->approve)) {
				if(!$conf->global->HOLIDAY_FDT_APPROVER && (in_array($object->fk_type, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)) || in_array(-1, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)))) {
					if($object->fk_validator > 0) {
						$object->createApprobation($object->fk_validator, 1, 1); 
						$object->fk_validator = 0;
					}
					if(!empty($object->array_options['options_fk_validator2'])) {
						$object->createApprobation($object->array_options['options_fk_validator2'], 1, 2); 
						$object->array_options['options_fk_validator2'] = null;
					}
				}

				if($canHalfday) {
					$object->halfday = $halfday;
				}
				else {
					$object->halfday = 0;
				}

				// Fill array 'array_options' with data from add form
				$ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
				if($object->array_options['options_client_informe'] != 1){
					$object->array_options['options_nom_client'] = "";
				}

				if($needHour && $date_debut == $date_fin && !empty($duration_hour)){
					$object->array_options['options_hour'] = $duration_hour;
				}
				elseif($needHour && ($date_debut != $date_fin || ($date_debut == $date_fin && $conf->global->HOIDAY_AUTO_HOUR_WHOLE_DAY && $halfday == 0))) {
					if($conf->feuilledetemps->enabled && $conf->global->FDT_STANDARD_WEEK_FOR_HOLIDAY) {
						$nbDay = num_between_day($date_debut_gmt, $date_fin_gmt, 0) + 1;
						$duration_hour = 0;
						for($i = 0; $i < $nbDay; $i++) {
							$tmpday = dol_time_plus_duree($date_debut_gmt, $i, 'd');
							$tmpdaygmt = dol_mktime(0, 0, 0, dol_print_date($tmpday, '%m'), dol_print_date($tmpday, '%d'), dol_print_date($tmpday, '%Y'), 'gmt');

							if(num_public_holiday($tmpdaygmt, $tmpdaygmt, '', 1) == 0) {
								if((($holiday->halfday == 1 || $holiday->halfday == 2) && $i == $nbDay - 1) || (($holiday->halfday == -1 || $holiday->halfday == 2) && $i == 0)) { // gestion des demi journées
									$duration_hour += 0.5 * $standard_week_hour[dol_print_date($tmpday, '%A')];
								}
								else {
									$duration_hour += $standard_week_hour[dol_print_date($tmpday, '%A')];
								}
							}
						}
					}
					else {
						$nbDay = num_open_day($date_debut_gmt, $date_fin_gmt, 0, 1, $holiday->halfday);
						$duration_hour = $nbDay * 7 * 3600;
					}
					$object->array_options['options_hour'] = $duration_hour;
				}

				if ($ret < 0) {
					$error++;
				}
				if (!$error) {
					// Actions on extra fields
					$result = $object->insertExtraFields('HOLIDAY_MODIFY');
					if ($result < 0) {
						setEventMessages($object->error, $object->errors, 'errors');
						$error++;
					}
				}


				// Update
				$verif = $object->update($user);

				if(!$error && $conf->feuilledetemps->enabled && $verif && $conf->global->FDT_STATUT_HOLIDAY && ($object->array_options['options_statutfdt'] == 2 || $object->array_options['options_statutfdt'] == 3)) {
					global $dolibarr_main_url_root;
					$subject = '[OPTIM Industries] Notification automatique Congés à réguler';
					$from = 'erp@optim-industries.fr';
					$to = $conf->global->HOLIDAY_MAIL_TO;

					$user_static = new User($db);
					$user_static->fetch($object->fk_user);

					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/holidaycustom/card.php?id='.$object->id.'">'.$object->ref.'</a>';
					$msg = $langs->transnoentitiesnoconv(($object->array_options['options_statutfdt'] == 2 ? "EMailTextCongesRegulerModify2" : "EMailTextCongesRegulerModify3"), $user_static->firstname.' '.$user_static->lastname, dol_print_date($object->date_debut, '%d/%m/%Y'), dol_print_date($object->date_fin, '%d/%m/%Y'), $link);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)) {
						$res = $mail->sendfile();
					}
				}

				if ($verif <= 0) {
					setEventMessages($object->error, $object->errors, 'warnings');
					$action = 'edit';
				} else {
					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
					exit;
				}
			} else {
				setEventMessages($langs->trans("NotEnoughPermissions"), null, 'errors');
				$action = '';
			}
		} else {
			setEventMessages($langs->trans("ErrorBadStatus"), null, 'errors');
			$action = '';
		}
	}

	// If delete of request
	if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes' && $candelete) {
		$error = 0;

		$db->begin();

		$object->fetch($id);

		// If this is a rough draft, canceled or refused
		if ($object->statut == Holiday::STATUS_DRAFT || $object->statut == Holiday::STATUS_CANCELED || $object->statut == Holiday::STATUS_REFUSED || ($object->array_options['options_statutfdt'] == 1 && $object->statut == Holiday::STATUS_APPROVED2 && !in_array($object->fk_type, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)) && !in_array(-1, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)))) {
			if($conf->global->FDT_STATUT_HOLIDAY) {
				$options_statutfdt =  $object->array_options['options_statutfdt'];
			}
			$date_debut = $object->date_debut;
			$date_fin = $object->date_fin;
			$object_fk_user = $object->fk_user;
			$object_ref = $object->ref;

			$result = $object->delete($user);

			if(!$error && $conf->feuilledetemps->enabled && $result && $conf->global->FDT_STATUT_HOLIDAY && ($options_statutfdt == 2 || $options_statutfdt == 3)) {
				global $dolibarr_main_url_root;
				$subject = '[OPTIM Industries] Notification automatique Congés à réguler';
				$from = 'erp@optim-industries.fr';
				$to = $conf->global->HOLIDAY_MAIL_TO;;

				$user_static = new User($db);
				$user_static->fetch($object_fk_user);

				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = $object_ref;
				$msg = $langs->transnoentitiesnoconv(($options_statutfdt == 2 ? "EMailTextCongesRegulerDelete2" : "EMailTextCongesRegulerDelete3"), $user_static->firstname.' '.$user_static->lastname, dol_print_date($date_debut, '%d/%m/%Y'), dol_print_date($date_fin, '%d/%m/%Y'), $link);
				$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
				if(!empty($to)) {
					$res = $mail->sendfile();
				}
			}

		} else {
			$error++;
			setEventMessages($langs->trans('BadStatusOfObject'), null, 'errors');
			$action = '';
		}

		if (!$error) {
			$db->commit();
			header('Location: list.php?restore_lastsearch_values=1');
			exit;
		} else {
			$db->rollback();
		}
	}

	// Action validate (+ send email for approval)
	if ($action == 'confirm_send') {
		$object->fetch($id);

		// If draft and owner of leave
		if ($object->statut == Holiday::STATUS_DRAFT && $cancreate && (in_array($object->fk_type, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)) || in_array(-1, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)))) {
			$object->oldcopy = dol_clone($object);

			if(!empty($object->listApprover1) || $conf->global->HOLIDAY_FDT_APPROVER) {
				$object->statut = Holiday::STATUS_VALIDATED;
			}
			else {
				$object->statut = Holiday::STATUS_APPROVED1;
			}

			$verif = $object->validate($user);

			// If no SQL error, we redirect to the request form
			if ($verif > 0) {
				// To
				// $destinataire = new User($db);
				// $destinataire->fetch($object->fk_validator);
				// $emailTo = $destinataire->email;

				$emailTo = '';
				if(!$conf->global->HOLIDAY_FDT_APPROVER) {
					if(!empty($object->listApprover1)) {
						$list_validation = $object->listApprover1;
					}
					else {
						$list_validation = $object->listApprover2;
					}
					foreach($list_validation[2] as $userid => $user_static){
						if(!empty($user_static->email)){
							$emailTo .= $user_static->email.', ';
						}
					}
				}
				elseif($conf->global->HOLIDAY_FDT_APPROVER) {
					$user_static = new User($db);
					$user_static->fetch($object->fk_user);
					$list_validation = explode(',', $user_static->array_options['options_approbateurfdt']);
					foreach($list_validation as $id_validation){
						$user_static->fetch($id_validation);
						if(!empty($user_static->email)){
							$emailTo .= $user_static->email.', ';
						}
					}
				}
				$emailTo = rtrim($emailTo, ", ");

				// if (!$emailTo) {
				// 	dol_syslog("Expected validator has no email, so we redirect directly to finished page without sending email");
				// 	header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
				// 	exit;
				// }

				// From
				$expediteur = new User($db);
				$expediteur->fetch($object->fk_user);
				//$emailFrom = $expediteur->email;		Email of user can be an email into another company. Sending will fails, we must use the generic email.
				$emailFrom = $conf->global->MAIN_MAIL_EMAIL_FROM;

				// Subject
				$societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
				if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
					$societeName = $conf->global->MAIN_APPLICATION_TITLE;
				}

				$subject = $societeName." - ".$langs->transnoentitiesnoconv("HolidaysToValidate");

				// Content
				//$message = "<p>".$langs->transnoentitiesnoconv("Hello")." ".$destinataire->firstname.",</p>\n";
				$message = "<p>".$langs->transnoentitiesnoconv("Hello").",</p>\n";

				$message .= "<p>".$langs->transnoentities("HolidaysToValidateBody")."</p>\n";


				// option to warn the validator in case of too short delay
				if (empty($conf->global->HOLIDAY_HIDE_APPROVER_ABOUT_TOO_LOW_DELAY)) {
					$delayForRequest = 0;		// TODO Set delay depending of holiday leave type
					if ($delayForRequest) {
						$nowplusdelay = dol_time_plus_duree($now, $delayForRequest, 'd');

						if ($object->date_debut < $nowplusdelay) {
							$message = "<p>".$langs->transnoentities("HolidaysToValidateDelay", $delayForRequest)."</p>\n";
						}
					}
				}

				// option to notify the validator if the balance is less than the request
				if (empty($conf->global->HOLIDAY_HIDE_APPROVER_ABOUT_NEGATIVE_BALANCE)) {
					$nbopenedday = num_open_day($object->date_debut_gmt, $object->date_fin_gmt, 0, 1, $object->halfday);

					$ACP = $object->getTypesCP(1, 'ACP');
					if ($object->fk_type == $ACP['rowid'] && $nbopenedday > $object->getCPforUser($object->fk_user, $object->fk_type)) {
						$message .= "<p>".$langs->transnoentities("HolidaysToValidateAlertSolde")."</p>\n";
					}
				}

				$type = $object->getTypeWithID($object->fk_type);
				$link = dol_buildpath("/custom/holidaycustom/card.php", 3) . '?id='.$object->id;

				$message .= "<ul>";
				$message .= "<li>".$langs->transnoentitiesnoconv("Name")." : ".dolGetFirstLastname($expediteur->firstname, $expediteur->lastname)."</li>\n";
				$message .= "<li>".$langs->transnoentitiesnoconv("Period")." : ".dol_print_date($object->date_debut, 'day')." ".$langs->transnoentitiesnoconv("To")." ".dol_print_date($object->date_fin, 'day')."</li>\n";
				$message .= "<li>".$langs->transnoentitiesnoconv("Type")." : ".$type['label']."</li>\n";
				$message .= "<li>".$langs->transnoentitiesnoconv("Link").' : <a href="'.$link.'" target="_blank">'.$link."</a></li>\n";
				$message .= "</ul>\n";

				$date_moins_2mois = dol_time_plus_duree($object->date_debut, -2, 'm')." ";
				$now = dol_now();
				$duree = $object->date_fin - $object->date_debut; 			// -86400
				if($object->halfday == -1 || $object->halfday == 0){
					$duree += 86400;
				}
				if($date_moins_2mois <= $now && $duree >= 86400*14){
					$message .= '<p style="color: red;">'.$langs->transnoentities("AttentionHoliday2semaines").'<p>';
				}

				$trackid = 'leav'.$object->id;

				$mail = new CMailFile($subject, $emailTo, $emailFrom, $message, array(), array(), array(), '', '', 0, 1, '', '', $trackid);

				// Sending the email
				if($emailTo) {
					$result = $mail->sendfile();
				}

				if (!$result) {
					setEventMessages($mail->error, $mail->errors, 'warnings');
					$action = '';
				} else {
					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
					exit;
				}
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
				$action = '';
			}
		}
		if ($object->statut == Holiday::STATUS_DRAFT && $cancreate && !in_array($object->fk_type, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE))) {
			$object->oldcopy = dol_clone($object);

			$verif = $object->approve2($user);

			// If no SQL error, we redirect to the request form
			if ($verif > 0) {
				header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
				exit;
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
				$action = '';
			}
		}
	}

	if ($action == 'update_extras') {
		$object->oldcopy = dol_clone($object);

		// Fill array 'array_options' with data from update form
		$ret = $extrafields->setOptionalsFromPost(null, $object, GETPOST('attribute', 'restricthtml'));
	
		if($object->array_options['options_client_informe'] != 1){
			$object->array_options['options_nom_client'] = "";
		}
		if ($ret < 0) {
			$error++;
		}

	
		if(GETPOST('attribute', 'restricthtml') == 'nom_client' && $object->array_options['options_client_informe'] == 1 && empty($object->array_options['options_nom_client'])){
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit_extras&attribute=nom_client&error=NomClient');
			exit;
		}

		if (!$error) {
			// Actions on extra fields
			$result = $object->insertExtraFields('HOLIDAY_MODIFY');
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}

		if ($error) {
			$action = 'edit_extras';
		}
	}

	// if($action == 'confirm_transferer' && $user->id == $object->fk_validator && $object->statut == Holiday::STATUS_VALIDATED){
	// 	$object->fetch($id);
	// 	$user_transferer = new User($db);
	// 	$user_transferer->fetch(GETPOST('user_transferer'));

	// 	$object->array_options['options_fk_validator2'] = $object->fk_validator;
	// 	$object->fk_validator = $user_transferer->id;

	// 	// Update
	// 	$verif = $object->update($user);

	// 	if($verif > 0){
	// 		$verif = $object->insertExtraFields('HOLIDAY_MODIFY');
	// 	}

	// 	if ($verif > 0) {
	// 		// To
	// 		$destinataire = new User($db);
	// 		$destinataire->fetch($object->fk_validator);
	// 		$emailTo = $destinataire->email;

	// 		if (!$emailTo) {
	// 			dol_syslog("Expected validator has no email, so we redirect directly to finished page without sending email");
	// 			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
	// 			exit;
	// 		}

	// 		// From
	// 		$expediteur = new User($db);
	// 		$expediteur->fetch($object->fk_user);
	// 		//$emailFrom = $expediteur->email;		Email of user can be an email into another company. Sending will fails, we must use the generic email.
	// 		$emailFrom = $conf->global->MAIN_MAIL_EMAIL_FROM;

	// 		// Subject
	// 		$societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
	// 		if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
	// 			$societeName = $conf->global->MAIN_APPLICATION_TITLE;
	// 		}

	// 		$subject = $societeName." - ".$langs->transnoentitiesnoconv("HolidaysToValidate");

	// 		// Content
	// 		$message = "<p>".$langs->transnoentitiesnoconv("Hello")." ".$destinataire->firstname.",</p>\n";

	// 		$message .= "<p>".$langs->transnoentities("HolidaysToValidateBody")."</p>\n";


	// 		// option to warn the validator in case of too short delay
	// 		if (empty($conf->global->HOLIDAY_HIDE_APPROVER_ABOUT_TOO_LOW_DELAY)) {
	// 			$delayForRequest = 0;		// TODO Set delay depending of holiday leave type
	// 			if ($delayForRequest) {
	// 				$nowplusdelay = dol_time_plus_duree($now, $delayForRequest, 'd');

	// 				if ($object->date_debut < $nowplusdelay) {
	// 					$message = "<p>".$langs->transnoentities("HolidaysToValidateDelay", $delayForRequest)."</p>\n";
	// 				}
	// 			}
	// 		}

	// 		// option to notify the validator if the balance is less than the request
	// 		if (empty($conf->global->HOLIDAY_HIDE_APPROVER_ABOUT_NEGATIVE_BALANCE)) {
	// 			$nbopenedday = num_open_day($object->date_debut_gmt, $object->date_fin_gmt, 0, 1, $object->halfday);

	// 			$ACP = $object->getTypesCP(1, 'ACP');
	// 			if ($object->fk_type == $ACP['rowid'] && $nbopenedday > $object->getCPforUser($object->fk_user, $object->fk_type)) {
	// 				$message .= "<p>".$langs->transnoentities("HolidaysToValidateAlertSolde")."</p>\n";
	// 			}
	// 		}

	// 		$type = $object->getTypeWithID($object->fk_type);
	// 		$link = dol_buildpath("/custom/holidaycustom/card.php", 3) . '?id='.$object->id;

	// 		$message .= "<ul>";
	// 		$message .= "<li>".$langs->transnoentitiesnoconv("Name")." : ".dolGetFirstLastname($expediteur->firstname, $expediteur->lastname)."</li>\n";
	// 		$message .= "<li>".$langs->transnoentitiesnoconv("Period")." : ".dol_print_date($object->date_debut, 'day')." ".$langs->transnoentitiesnoconv("To")." ".dol_print_date($object->date_fin, 'day')."</li>\n";
	// 		$message .= "<li>".$langs->transnoentitiesnoconv("Type")." : ".$type['label']."</li>\n";
	// 		$message .= "<li>".$langs->transnoentitiesnoconv("Link").' : <a href="'.$link.'" target="_blank">'.$link."</a></li>\n";
	// 		$message .= "</ul>\n";

	// 		$trackid = 'leav'.$object->id;

	// 		$mail = new CMailFile($subject, $emailTo, $emailFrom, $message, array(), array(), array(), '', '', 0, 1, '', '', $trackid);

	// 		// Sending the email
	// 		$result = $mail->sendfile();

	// 		if (!$result) {
	// 			setEventMessages($mail->error, $mail->errors, 'warnings'); // Show error, but do no make rollback, so $error is not set to 1
	// 			$action = '';
	// 		}
	// 	}

	// 	if ($verif > 0) {
	// 		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
	// 		exit;
	// 	} else {
	// 		setEventMessages("Impossible de transferer la demande de congés", null, 'errors');
	// 	}
	// }

	// Approve leave request (1ere validation)
	if ($action == 'confirm_valid1' && $permissiontovalidate1) {
		$object->fetch($id);

		$user_static = new User($db);
		$user_static->fetch($object->fk_user);

		// If status is waiting approval and approver is resp task and project
		if ($object->statut == Holiday::STATUS_VALIDATED && !$conf->global->HOLIDAY_FDT_APPROVER) {
			$object->oldcopy = dol_clone($object);

			$object->date_valid = dol_now();
			$object->fk_user_valid = $user->id;
			// if(!empty($object->array_options['options_fk_validator2'])){
			// 	$object->statut = Holiday::STATUS_APPROVED1;
			// }
			// else {
			// 	header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_valid2');
			// 	exit;
			// }

			$db->begin();
			$res = $result = $object->updateApprobation($user->id, 0, 1, 1);
			if($res <= 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}

			$list_valideur = $object->listApprover1;
			$list_valideur2 = $object->listApprover2;
			if(!in_array(0, $list_valideur[1]) && (sizeof($list_valideur2[0]) > 1 || (!empty($list_valideur2[0]) && !in_array($user->id, $list_valideur2[0])))){
				$verif = $object->approve1($user);
				if ($verif <= 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				}

				if (!$error) {
					$emailTo = '';
					$list_validation = $object->listApprover2;
					foreach($list_validation[2] as $userid => $user_static){
						if(!empty($user_static->email)){
							$emailTo .= $user_static->email.', ';
						}
					}
					$emailTo = rtrim($emailTo, ", ");

					if (!$emailTo) {
						$error++;
						dol_syslog("Expected validator has no email, so we redirect directly to finished page without sending email");
						header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
						exit;
					}

					// From
					$expediteur = new User($db);
					$expediteur->fetch($object->fk_user);
					//$emailFrom = $expediteur->email;		Email of user can be an email into another company. Sending will fails, we must use the generic email.
					$emailFrom = $conf->global->MAIN_MAIL_EMAIL_FROM;

					// Subject
					$societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
					if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
						$societeName = $conf->global->MAIN_APPLICATION_TITLE;
					}

					$subject = $societeName." - ".$langs->transnoentitiesnoconv("HolidaysToValidate");

					// Content
					$message = "<p>".$langs->transnoentitiesnoconv("Hello").",</p>\n";

					$message .= "<p>".$langs->transnoentities("HolidaysToValidateBody")."</p>\n";


					// option to warn the validator in case of too short delay
					if (empty($conf->global->HOLIDAY_HIDE_APPROVER_ABOUT_TOO_LOW_DELAY)) {
						$delayForRequest = 0;		// TODO Set delay depending of holiday leave type
						if ($delayForRequest) {
							$nowplusdelay = dol_time_plus_duree($now, $delayForRequest, 'd');

							if ($object->date_debut < $nowplusdelay) {
								$message = "<p>".$langs->transnoentities("HolidaysToValidateDelay", $delayForRequest)."</p>\n";
							}
						}
					}

					// option to notify the validator if the balance is less than the request
					if (empty($conf->global->HOLIDAY_HIDE_APPROVER_ABOUT_NEGATIVE_BALANCE)) {
						$nbopenedday = num_open_day($object->date_debut_gmt, $object->date_fin_gmt, 0, 1, $object->halfday);

						$ACP = $object->getTypesCP(1, 'ACP');
						if ($object->fk_type == $ACP['rowid'] && $nbopenedday > $object->getCPforUser($object->fk_user, $object->fk_type)) {
							$message .= "<p>".$langs->transnoentities("HolidaysToValidateAlertSolde")."</p>\n";
						}
					}

					$type = $object->getTypeWithID($object->fk_type);
					$link = dol_buildpath("/custom/holidaycustom/card.php", 3) . '?id='.$object->id;

					$message .= "<ul>";
					$message .= "<li>".$langs->transnoentitiesnoconv("Name")." : ".dolGetFirstLastname($expediteur->firstname, $expediteur->lastname)."</li>\n";
					$message .= "<li>".$langs->transnoentitiesnoconv("Period")." : ".dol_print_date($object->date_debut, 'day')." ".$langs->transnoentitiesnoconv("To")." ".dol_print_date($object->date_fin, 'day')."</li>\n";
					$message .= "<li>".$langs->transnoentitiesnoconv("Type")." : ".$type['label']."</li>\n";
					$message .= "<li>".$langs->transnoentitiesnoconv("Link").' : <a href="'.$link.'" target="_blank">'.$link."</a></li>\n";
					$message .= "</ul>\n";

					$trackid = 'leav'.$object->id;

					$mail = new CMailFile($subject, $emailTo, $emailFrom, $message, array(), array(), array(), '', '', 0, 1, '', '', $trackid);

					// Sending the email
					$result = $mail->sendfile();

					if (!$result) {
						setEventMessages($mail->error, $mail->errors, 'warnings'); // Show error, but do no make rollback, so $error is not set to 1
						$action = '';
					}
				}
			}
			elseif(!in_array(0, $list_valideur[1]) && (empty($list_valideur2[0]) || (sizeof($list_valideur2[0]) == 1 && in_array($user->id, $list_valideur2[0])))){
				if(sizeof($list_valideur2[0]) == 1 && in_array($user->id, $list_valideur2[0])) {
					$res = $object->updateApprobation($user->id, 1, 1, 2);
					if($res <= 0) {
						setEventMessages($object->error, $object->errors, 'errors');
						$error++;
					}
				}
				
				$verif = $object->approve2($user);
				if ($verif <= 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				}

				if (!$error) {
					// To
					$destinataire = new User($db);
					$destinataire->fetch($object->fk_user);
					$emailTo = $destinataire->email;
	
					if (!$emailTo) {
						dol_syslog("User that request leave has no email, so we redirect directly to finished page without sending email");
					} else {
						// From
						$expediteur = new User($db);
						$expediteur->fetch($object->fk_validator);
						//$emailFrom = $expediteur->email;		Email of user can be an email into another company. Sending will fails, we must use the generic email.
						$emailFrom = $conf->global->MAIN_MAIL_EMAIL_FROM;
	
						// Subject
						$societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
						if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
							$societeName = $conf->global->MAIN_APPLICATION_TITLE;
						}
	
						$subject = $societeName." - ".$langs->transnoentitiesnoconv("HolidaysValidated");
	
						// Content
						$message = "<p>".$langs->transnoentitiesnoconv("Hello")." ".$destinataire->firstname.",</p>\n";
	
						$message .= "<p>".$langs->transnoentities("HolidaysValidatedBody", dol_print_date($object->date_debut, 'day'), dol_print_date($object->date_fin, 'day'))."</p>\n";
	
						$link = dol_buildpath('/custom/holidaycustom/card.php', 3).'?id='.$object->id;
	
						$message .= "<ul>\n";
						$message .= "<li>".$langs->transnoentitiesnoconv("ValidatedBy")." : ".dolGetFirstLastname($expediteur->firstname, $expediteur->lastname)."</li>\n";
						$message .= "<li>".$langs->transnoentitiesnoconv("Link").' : <a href="'.$link.'" target="_blank">'.$link."</a></li>\n";
						$message .= "</ul>\n";
	
						$trackid = 'leav'.$object->id;
	
						$mail = new CMailFile($subject, $emailTo, $emailFrom, $message, array(), array(), array(), '', '', 0, 1, '', '', $trackid);
	
						// Sending email
						$result = $mail->sendfile();
	
						if (!$result) {
							setEventMessages($mail->error, $mail->errors, 'warnings'); // Show error, but do no make rollback, so $error is not set to 1
							$action = '';
						}
					}
				}
			}

			if (!$error) {
				$db->commit();
				header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
				exit;
			} else {
				$db->rollback();
				$action = '';
			}
		}
		// If status is waiting approval and approver is resp task and project
		elseif ($object->statut == Holiday::STATUS_VALIDATED && $conf->global->HOLIDAY_FDT_APPROVER) {
			$object->oldcopy = dol_clone($object);

			$object->date_valid = dol_now();
			$object->fk_user_valid = $user->id;

			$db->begin();

			$verif = $object->approve2($user);
			if ($verif <= 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}

			if (!$error) {
				// To
				$destinataire = new User($db);
				$destinataire->fetch($object->fk_user);
				$emailTo = $destinataire->email;

				if (!$emailTo) {
					dol_syslog("User that request leave has no email, so we redirect directly to finished page without sending email");
				} else {
					// From
					$expediteur = new User($db);
					$expediteur->fetch($object->fk_validator);
					//$emailFrom = $expediteur->email;		Email of user can be an email into another company. Sending will fails, we must use the generic email.
					$emailFrom = $conf->global->MAIN_MAIL_EMAIL_FROM;

					// Subject
					$societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
					if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
						$societeName = $conf->global->MAIN_APPLICATION_TITLE;
					}

					$subject = $societeName." - ".$langs->transnoentitiesnoconv("HolidaysValidated");

					// Content
					$message = "<p>".$langs->transnoentitiesnoconv("Hello")." ".$destinataire->firstname.",</p>\n";

					$message .= "<p>".$langs->transnoentities("HolidaysValidatedBody", dol_print_date($object->date_debut, 'day'), dol_print_date($object->date_fin, 'day'))."</p>\n";

					$link = dol_buildpath('/custom/holidaycustom/card.php', 3).'?id='.$object->id;

					$message .= "<ul>\n";
					$message .= "<li>".$langs->transnoentitiesnoconv("ValidatedBy")." : ".dolGetFirstLastname($expediteur->firstname, $expediteur->lastname)."</li>\n";
					$message .= "<li>".$langs->transnoentitiesnoconv("Link").' : <a href="'.$link.'" target="_blank">'.$link."</a></li>\n";
					$message .= "</ul>\n";

					$trackid = 'leav'.$object->id;

					$mail = new CMailFile($subject, $emailTo, $emailFrom, $message, array(), array(), array(), '', '', 0, 1, '', '', $trackid);

					// Sending email
					$result = $mail->sendfile();

					if (!$result) {
						setEventMessages($mail->error, $mail->errors, 'warnings'); // Show error, but do no make rollback, so $error is not set to 1
						$action = '';
					}
				}
			}

			if (!$error) {
				$db->commit();
				header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
				exit;
			} else {
				$db->rollback();
				$action = '';
			}
		}
	}

	// Approve leave request (2eme validation)
	if ($action == 'confirm_valid2') {
		$object->fetch($id);

		// If status is waiting approval and approver is resp task and project
		if ($object->statut == Holiday::STATUS_APPROVED1 && $permissiontovalidate2) {
			$object->oldcopy = dol_clone($object);

			$object->date_valid = dol_now();
			$object->fk_user_valid = $user->id;

			$db->begin();
			$res = $result = $object->updateApprobation($user->id, 0, 1, 2);
			if($res <= 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}

			$list_valideur = $object->listApprover2;
			if(!in_array(0, $list_valideur[1])) {
				$verif = $object->approve2($user);
				if ($verif <= 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				}
				// If no SQL error, we redirect to the request form
				/*if (!$error) {
					// Calculcate number of days consummed
					$nbopenedday = num_open_day($object->date_debut_gmt, $object->date_fin_gmt, 0, 1, $object->halfday);
					$soldeActuel = $object->getCpforUser($object->fk_user, $object->fk_type);
					$newSolde = ($soldeActuel - $nbopenedday);
					$label = $langs->transnoentitiesnoconv("Holidays").' - '.$object->ref;

					// The modification is added to the LOG
					$result = $object->addLogCP($user->id, $object->fk_user, $label, $newSolde, $object->fk_type);
					if ($result < 0) {
						$error++;
						setEventMessages(null, $object->errors, 'errors');
					}

					// Update balance
					$result = $object->updateSoldeCP($object->fk_user, $newSolde, $object->fk_type);
					if ($result < 0) {
						$error++;
						setEventMessages(null, $object->errors, 'errors');
					}

					$typeleaves_ACP = $object->getTypesCP(1, 'ACP');
					if($object->fk_type == $typeleaves_ACP['rowid']){
						$nbopenedday_restant = $nbopenedday;

						$typeleaves_CP_FRAC_ACQUIS = $object->getTypesCP(1, 'CP_FRAC_ACQUIS');
						$typeleaves_CP_FRAC_PRIS = $object->getTypesCP(1, 'CP_FRAC_PRIS');
						$nb_FRAC_ACQUIS = $object->getCPforUser($object->fk_user, $typeleaves_CP_FRAC_ACQUIS['rowid']);
						$nb_FRAC_ACQUIS = ($nb_FRAC_ACQUIS ? price2num($nb_FRAC_ACQUIS) : 0);
						$nb_FRAC_PRIS = $object->getCPforUser($object->fk_user, $typeleaves_CP_FRAC_PRIS['rowid']);
						$nb_FRAC_PRIS = ($nb_FRAC_PRIS ? price2num($nb_FRAC_PRIS) : 0);
						$nb_FRAC_SOLDE = $nb_FRAC_ACQUIS-$nb_FRAC_PRIS;
						if($nb_FRAC_SOLDE > 0){
							if($nb_FRAC_SOLDE >= $nbopenedday_restant){
								$newSolde = $nb_FRAC_PRIS + $nbopenedday_restant;
								$nbopenedday_restant = 0;

								// The modification is added to the LOG
								$result = $object->addLogCP($user->id, $object->fk_user, $label, $newSolde, $typeleaves_CP_FRAC_PRIS['rowid']);
								if ($result < 0) {
									$error++;
									setEventMessages(null, $object->errors, 'errors');
								}

								// Update balance
								$result = $object->updateSoldeCP($object->fk_user, $newSolde, $typeleaves_CP_FRAC_PRIS['rowid']);
								if ($result < 0) {
									$error++;
									setEventMessages(null, $object->errors, 'errors');
								}
							}
							else {
								$newSolde = $nb_FRAC_ACQUIS;
								$nbopenedday_restant -= $nb_FRAC_SOLDE;

								// The modification is added to the LOG
								$result = $object->addLogCP($user->id, $object->fk_user, $label, $newSolde, $typeleaves_CP_FRAC_PRIS['rowid']);
								if ($result < 0) {
									$error++;
									setEventMessages(null, $object->errors, 'errors');
								}

								// Update balance
								$result = $object->updateSoldeCP($object->fk_user, $newSolde, $typeleaves_CP_FRAC_PRIS['rowid']);
								if ($result < 0) {
									$error++;
									setEventMessages(null, $object->errors, 'errors');
								}
							}
						}

						if($nbopenedday_restant > 0){
							$typeleaves_CP_ANC_ACQUIS = $object->getTypesCP(1, 'CP_ANC_ACQUIS');
							$typeleaves_CP_ANC_PRIS = $object->getTypesCP(1, 'CP_ANC_PRIS');
							$nb_ANC_ACQUIS = $object->getCPforUser($object->fk_user, $typeleaves_CP_ANC_ACQUIS['rowid']);
							$nb_ANC_ACQUIS = ($nb_ANC_ACQUIS ? price2num($nb_ANC_ACQUIS) : 0);
							$nb_ANC_PRIS = $object->getCPforUser($object->fk_user, $typeleaves_CP_ANC_PRIS['rowid']);
							$nb_ANC_PRIS = ($nb_ANC_PRIS ? price2num($nb_ANC_PRIS) : 0);
							$nb_ANC_SOLDE = $nb_ANC_ACQUIS-$nb_ANC_PRIS;
							if($nb_ANC_SOLDE > 0){
								if($nb_ANC_SOLDE >= $nbopenedday_restant){
									$newSolde = $nb_ANC_PRIS + $nbopenedday_restant;
									$nbopenedday_restant = 0;

									// The modification is added to the LOG
									$result = $object->addLogCP($user->id, $object->fk_user, $label, $newSolde, $typeleaves_CP_ANC_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $object->errors, 'errors');
									}

									// Update balance
									$result = $object->updateSoldeCP($object->fk_user, $newSolde, $typeleaves_CP_ANC_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $object->errors, 'errors');
									}
								}
								else {
									$newSolde = $nb_ANC_ACQUIS;
									$nbopenedday_restant -= $nb_ANC_SOLDE;

									// The modification is added to the LOG
									$result = $object->addLogCP($user->id, $object->fk_user, $label, $newSolde, $typeleaves_CP_ANC_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $object->errors, 'errors');
									}

									// Update balance
									$result = $object->updateSoldeCP($object->fk_user, $newSolde, $typeleaves_CP_ANC_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $object->errors, 'errors');
									}
								}
							}
						}

						if($nbopenedday_restant > 0){
							$typeleaves_CP_N1_ACQUIS = $object->getTypesCP(1, 'CP_N-1_ACQUIS');
							$typeleaves_CP_N1_PRIS = $object->getTypesCP(1, 'CP_N-1_PRIS');
							$nb_N1_ACQUIS = $object->getCPforUser($object->fk_user, $typeleaves_CP_N1_ACQUIS['rowid']);
							$nb_N1_ACQUIS = ($nb_N1_ACQUIS ? price2num($nb_N1_ACQUIS) : 0);
							$nb_N1_PRIS = $object->getCPforUser($object->fk_user, $typeleaves_CP_N1_PRIS['rowid']);
							$nb_N1_PRIS = ($nb_N1_PRIS ? price2num($nb_N1_PRIS) : 0);
							$nb_N1_SOLDE = $nb_N1_ACQUIS-$nb_N1_PRIS;
							if($nb_N1_SOLDE > 0){
								if($nb_N1_SOLDE >= $nbopenedday_restant){
									$newSolde = $nb_N1_PRIS + $nbopenedday_restant;
									$nbopenedday_restant = 0;

									// The modification is added to the LOG
									$result = $object->addLogCP($user->id, $object->fk_user, $label, $newSolde, $typeleaves_CP_N1_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $object->errors, 'errors');
									}

									// Update balance
									$result = $object->updateSoldeCP($object->fk_user, $newSolde, $typeleaves_CP_N1_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $object->errors, 'errors');
									}
								}
								else {
									$newSolde = $nb_N1_ACQUIS;
									$nbopenedday_restant -= $nb_N1_SOLDE;

									// The modification is added to the LOG
									$result = $object->addLogCP($user->id, $object->fk_user, $label, $newSolde, $typeleaves_CP_N1_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $object->errors, 'errors');
									}

									// Update balance
									$result = $object->updateSoldeCP($object->fk_user, $newSolde, $typeleaves_CP_N1_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $object->errors, 'errors');
									}
								}
							}
						}

						if($nbopenedday_restant > 0){
							$typeleaves_CP_N_ACQUIS = $object->getTypesCP(1, 'CP_N_ACQUIS');
							$typeleaves_CP_N_PRIS = $object->getTypesCP(1, 'CP_N_PRIS');
							$nb_N_ACQUIS = $object->getCPforUser($object->fk_user, $typeleaves_CP_N_ACQUIS['rowid']);
							$nb_N_ACQUIS = ($nb_N_ACQUIS ? price2num($nb_N_ACQUIS) : 0);
							$nb_N_PRIS = $object->getCPforUser($object->fk_user, $typeleaves_CP_N_PRIS['rowid']);
							$nb_N_PRIS = ($nb_N_PRIS ? price2num($nb_N_PRIS) : 0);
							$nb_N_SOLDE = $nb_N_ACQUIS-$nb_N_PRIS;
							if($nb_N_SOLDE >= $nbopenedday_restant){
								$newSolde = $nb_N_PRIS + $nbopenedday_restant;
								$nbopenedday_restant = 0;

								// The modification is added to the LOG
								$result = $object->addLogCP($user->id, $object->fk_user, $label, $newSolde, $typeleaves_CP_N_PRIS['rowid']);
								if ($result < 0) {
									$error++;
									setEventMessages(null, $object->errors, 'errors');
								}

								// Update balance
								$result = $object->updateSoldeCP($object->fk_user, $newSolde, $typeleaves_CP_N_PRIS['rowid']);
								if ($result < 0) {
									$error++;
									setEventMessages(null, $object->errors, 'errors');
								}
							}
							else {
								$newSolde = $nb_N_PRIS + $nbopenedday_restant;
								$nbopenedday_restant = 0;

								// The modification is added to the LOG
								$result = $object->addLogCP($user->id, $object->fk_user, $label, $newSolde, $typeleaves_CP_N_PRIS['rowid']);
								if ($result < 0) {
									$error++;
									setEventMessages(null, $object->errors, 'errors');
								}

								// Update balance
								$result = $object->updateSoldeCP($object->fk_user, $newSolde, $typeleaves_CP_N_PRIS['rowid']);
								if ($result < 0) {
									$error++;
									setEventMessages(null, $object->errors, 'errors');
								}
							}
						}
					}
				}*/

				if (!$error) {
					// To
					$destinataire = new User($db);
					$destinataire->fetch($object->fk_user);
					$emailTo = $destinataire->email;

					if (!$emailTo) {
						dol_syslog("User that request leave has no email, so we redirect directly to finished page without sending email");
					} else {
						// From
						$expediteur = new User($db);
						$expediteur->fetch($object->fk_validator);
						//$emailFrom = $expediteur->email;		Email of user can be an email into another company. Sending will fails, we must use the generic email.
						$emailFrom = $conf->global->MAIN_MAIL_EMAIL_FROM;

						// Subject
						$societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
						if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
							$societeName = $conf->global->MAIN_APPLICATION_TITLE;
						}

						$subject = $societeName." - ".$langs->transnoentitiesnoconv("HolidaysValidated");

						// Content
						$message = "<p>".$langs->transnoentitiesnoconv("Hello")." ".$destinataire->firstname.",</p>\n";

						$message .= "<p>".$langs->transnoentities("HolidaysValidatedBody", dol_print_date($object->date_debut, 'day'), dol_print_date($object->date_fin, 'day'))."</p>\n";

						$link = dol_buildpath('/custom/holidaycustom/card.php', 3).'?id='.$object->id;

						$message .= "<ul>\n";
						$message .= "<li>".$langs->transnoentitiesnoconv("ValidatedBy")." : ".dolGetFirstLastname($expediteur->firstname, $expediteur->lastname)."</li>\n";
						$message .= "<li>".$langs->transnoentitiesnoconv("Link").' : <a href="'.$link.'" target="_blank">'.$link."</a></li>\n";
						$message .= "</ul>\n";

						$trackid = 'leav'.$object->id;

						$mail = new CMailFile($subject, $emailTo, $emailFrom, $message, array(), array(), array(), '', '', 0, 1, '', '', $trackid);

						// Sending email
						$result = $mail->sendfile();

						if (!$result) {
							setEventMessages($mail->error, $mail->errors, 'warnings'); // Show error, but do no make rollback, so $error is not set to 1
							$action = '';
						}
					}
				}
			}

			if (!$error) {
				$db->commit();

				header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
				exit;
			} else {
				$db->rollback();
				$action = '';
			}
		}
	}

	if ($action == 'confirm_refuse' && GETPOST('confirm', 'alpha') == 'yes') {
		if (!empty(GETPOST('detail_refuse', 'alphanohtml'))) {
			$object->fetch($id);

			// If status pending validation and validator = user
			if (($object->statut == Holiday::STATUS_VALIDATED && $permissiontovalidate1) || ($object->statut == Holiday::STATUS_APPROVED1 && $permissiontovalidate2)) {
				$object->date_refuse = dol_print_date('dayhour', dol_now());
				$object->fk_user_refuse = $user->id;
				$object->statut = Holiday::STATUS_REFUSED;
				$object->detail_refuse = GETPOST('detail_refuse', 'alphanohtml');

				if($conf->global->FDT_STATUT_HOLIDAY && ($object->array_options['options_statutfdt'] == 1 || $object->array_options['options_statutfdt'] == 2)) {
					$options_statutfdt = $object->array_options['options_statutfdt'];
					$object->array_options['options_statutfdt'] = 4;
				}
				
				$db->begin();

				$verif = $object->update($user);
				if ($verif <= 0) {
					$error++;
					setEventMessages($object->error, $object->errors, 'errors');
				}

				// If no SQL error, we redirect to the request form
				if (!$error) {
					// To
					$destinataire = new User($db);
					$destinataire->fetch($object->fk_user);
					$emailTo = $destinataire->email;

					if (!$emailTo) {
						dol_syslog("User that request leave has no email, so we redirect directly to finished page without sending email");
					} else {
						// From
						$expediteur = new User($db);
						$expediteur->fetch($user->id);
						
						//$emailFrom = $expediteur->email;		Email of user can be an email into another company. Sending will fails, we must use the generic email.
						$emailFrom = $conf->global->MAIN_MAIL_EMAIL_FROM;

						// Subject
						$societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
						if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
							$societeName = $conf->global->MAIN_APPLICATION_TITLE;
						}

						$subject = $societeName." - ".$langs->transnoentitiesnoconv("HolidaysRefused");

						// Content
						$message = "<p>".$langs->transnoentitiesnoconv("Hello")." ".$destinataire->firstname.",</p>\n";

						$message .= "<p>".$langs->transnoentities("HolidaysRefusedBody", dol_print_date($object->date_debut, 'day'), dol_print_date($object->date_fin, 'day'))."<p>\n";
						$message .= "<p>".GETPOST('detail_refuse', 'alphanohtml')."</p>";

						$link = dol_buildpath('/custom/holidaycustom/card.php', 3).'?id='.$object->id;

						$message .= "<ul>\n";
						$message .= "<li>".$langs->transnoentitiesnoconv("ModifiedBy")." : ".dolGetFirstLastname($expediteur->firstname, $expediteur->lastname)."</li>\n";
						$message .= "<li>".$langs->transnoentitiesnoconv("Link").' : <a href="'.$link.'" target="_blank">'.$link."</a></li>\n";
						$message .= "</ul>";

						$trackid = 'leav'.$object->id;

						$mail = new CMailFile($subject, $emailTo, $emailFrom, $message, array(), array(), array(), '', '', 0, 1, '', '', $trackid);

						// sending email
						$result = $mail->sendfile();

						if (!$result) {
							setEventMessages($mail->error, $mail->errors, 'warnings'); // Show error, but do no make rollback, so $error is not set to 1
							$action = '';
						}
					}
				} else {
					$action = '';
				}

				if(!$error && $conf->feuilledetemps->enabled && $verif && $conf->global->FDT_STATUT_HOLIDAY && ($options_statutfdt == 2 || $object->array_options['options_statutfdt'] == 3)) {
					global $dolibarr_main_url_root;
					$subject = '[OPTIM Industries] Notification automatique Congés à réguler';
					$from = 'erp@optim-industries.fr';
					$to = $conf->global->HOLIDAY_MAIL_TO;

					$user_static = new User($db);
					$user_static->fetch($object->fk_user);

					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/holidaycustom/card.php?id='.$object->id.'">'.$object->ref.'</a>';
					$msg = $langs->transnoentitiesnoconv(($options_statutfdt == 2 ? "EMailTextCongesRegulerRefuse2" : "EMailTextCongesRegulerRefuse3"), $user_static->firstname.' '.$user_static->lastname, dol_print_date($object->date_debut, '%d/%m/%Y'), dol_print_date($object->date_fin, '%d/%m/%Y'), $link);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)) {
						$res = $mail->sendfile();
					}
				}

				if (!$error) {
					$db->commit();

					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
					exit;
				} else {
					$db->rollback();
					$action = '';
				}
			}
		} else {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("DetailRefusCP")), null, 'errors');
			$action = 'refuse';
		}
	}

	// If the request is validated
	// if ($action == 'confirm_draft' && GETPOST('confirm') == 'yes') {
	// 	$error = 0;

	// 	$object->fetch($id);

	// 	if($object->array_options['options_statutfdt'] == 4 && ($object->statut == Holiday::STATUS_CANCELED || $object->statut == Holiday::STATUS_REFUSED) && $object->fk_type != 4) {
	// 		$object->array_options['options_statutfdt'] = 1;
	// 	}

	// 	$oldstatus = $object->statut;
	// 	$object->statut = Holiday::STATUS_DRAFT;

	// 	$result = $object->update($user);
	// 	if ($result < 0) {
	// 		$error++;
	// 		setEventMessages($langs->trans('ErrorBackToDraft').' '.$object->error, $object->errors, 'errors');
	// 	}

	// 	if(!$error) {
	// 		$list_validation1 = $object->listApprover1;
	// 		foreach($list_validation1[2] as $userid => $user_static){
	// 			$result = $object->updateApprobation($userid, 1, 0, 1);
	// 		}

	// 		$list_validation2 = $object->listApprover2;
	// 		foreach($list_validation2[2] as $userid => $user_static){
	// 			$result = $object->updateApprobation($userid, 1, 0, 2);
	// 		}
	// 	}

	// 	if (!$error) {
	// 		$db->commit();

	// 		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
	// 		exit;
	// 	} else {
	// 		$db->rollback();
	// 	}
	// }

	// If confirmation of cancellation
	if ($action == 'confirm_cancel' && GETPOST('confirm') == 'yes') {
		$error = 0;

		$object->fetch($id);

		// If status pending validation and validator = validator or user, or rights to do for others
		if (($object->statut == Holiday::STATUS_VALIDATED || $object->statut == Holiday::STATUS_APPROVED1 || $object->statut == Holiday::STATUS_APPROVED2) &&
			(!empty($user->admin) || $permissiontovalidate1 || $permissiontovalidate2 || $cancreate || $cancreateall)) {
			
			if(!empty(GETPOST('detail_annulation', 'alphanohtml'))) {
				$db->begin();

				$oldstatus = $object->statut;
				$object->date_cancel = dol_now();
				$object->fk_user_cancel = $user->id;
				$object->statut = Holiday::STATUS_CANCELED;
				$object->array_options['options_detail_annulation'] = GETPOST('detail_annulation', 'alphanohtml');

				if($conf->global->FDT_STATUT_HOLIDAY && ($object->array_options['options_statutfdt'] == 1 || $object->array_options['options_statutfdt'] == 2)) {
					$options_statutfdt = $object->array_options['options_statutfdt'];
					$object->array_options['options_statutfdt'] = 4;
				}
				
				$result = $object->update($user);

				if ($result >= 0 && $oldstatus == Holiday::STATUS_APPROVED2) {	// holiday was already validated, status 3, so we must increase back the balance
					// Call trigger
					$result = $object->call_trigger('HOLIDAY_CANCEL', $user);
					if ($result < 0) {
						$error++;
					}

					// Calculcate number of days consummed
					/*$nbopenedday = num_open_day($object->date_debut_gmt, $object->date_fin_gmt, 0, 1, $object->halfday);

					$soldeActuel = $object->getCpforUser($object->fk_user, $object->fk_type);
					$newSolde = ($soldeActuel + $nbopenedday);

					// The modification is added to the LOG
					$result1 = $object->addLogCP($user->id, $object->fk_user, $langs->transnoentitiesnoconv("HolidaysCancelation"), $newSolde, $object->fk_type);

					// Update of the balance
					$result2 = $object->updateSoldeCP($object->fk_user, $newSolde, $object->fk_type);

					$label = $langs->transnoentitiesnoconv("HolidaysCancelation");
					$typeleaves_ACP = $object->getTypesCP(1, 'ACP');
					if($object->fk_type == $typeleaves_ACP['rowid']){
						$nbopenedday_restant = $nbopenedday;

						$typeleaves_CP_N_PRIS = $object->getTypesCP(1, 'CP_N_PRIS');
						$nb_N_PRIS = $object->getCPforUser($object->fk_user, $typeleaves_CP_N_PRIS['rowid']);
						$nb_N_PRIS = ($nb_N_PRIS ? price2num($nb_N_PRIS) : 0);
						if($nb_N_PRIS > 0){
							if($nb_N_PRIS >= $nbopenedday_restant){
								$newSolde = $nb_N_PRIS - $nbopenedday_restant;
								$nbopenedday_restant = 0;

								// The modification is added to the LOG
								$result = $object->addLogCP($user->id, $object->fk_user, $label, $newSolde, $typeleaves_CP_N_PRIS['rowid']);
								if ($result < 0) {
									$error++;
									setEventMessages(null, $object->errors, 'errors');
								}

								// Update balance
								$result = $object->updateSoldeCP($object->fk_user, $newSolde, $typeleaves_CP_N_PRIS['rowid']);
								if ($result < 0) {
									$error++;
									setEventMessages(null, $object->errors, 'errors');
								}
							}
							else {
								$newSolde = 0;
								$nbopenedday_restant -= $nb_N_PRIS;

								// The modification is added to the LOG
								$result = $object->addLogCP($user->id, $object->fk_user, $label, $newSolde, $typeleaves_CP_N_PRIS['rowid']);
								if ($result < 0) {
									$error++;
									setEventMessages(null, $object->errors, 'errors');
								}

								// Update balance
								$result = $object->updateSoldeCP($object->fk_user, $newSolde, $typeleaves_CP_N_PRIS['rowid']);
								if ($result < 0) {
									$error++;
									setEventMessages(null, $object->errors, 'errors');
								}
							}
						}

						if($nbopenedday_restant > 0){
							$typeleaves_CP_N1_PRIS = $object->getTypesCP(1, 'CP_N-1_PRIS');
							$nb_N1_PRIS = $object->getCPforUser($object->fk_user, $typeleaves_CP_N1_PRIS['rowid']);
							$nb_N1_PRIS = ($nb_N1_PRIS ? price2num($nb_N1_PRIS) : 0);
							if($nb_N1_PRIS > 0){
								if($nb_N1_PRIS >= $nbopenedday_restant){
									$newSolde = $nb_N1_PRIS - $nbopenedday_restant;
									$nbopenedday_restant = 0;

									// The modification is added to the LOG
									$result = $object->addLogCP($user->id, $object->fk_user, $label, $newSolde, $typeleaves_CP_N1_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $object->errors, 'errors');
									}

									// Update balance
									$result = $object->updateSoldeCP($object->fk_user, $newSolde, $typeleaves_CP_N1_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $object->errors, 'errors');
									}
								}
								else {
									$newSolde = 0;
									$nbopenedday_restant -= $nb_N1_PRIS;

									// The modification is added to the LOG
									$result = $object->addLogCP($user->id, $object->fk_user, $label, $newSolde, $typeleaves_CP_N1_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $object->errors, 'errors');
									}

									// Update balance
									$result = $object->updateSoldeCP($object->fk_user, $newSolde, $typeleaves_CP_N1_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $object->errors, 'errors');
									}
								}
							}
						}

						if($nbopenedday_restant > 0){
							$typeleaves_CP_ANC_PRIS = $object->getTypesCP(1, 'CP_ANC_PRIS');
							$nb_ANC_PRIS = $object->getCPforUser($object->fk_user, $typeleaves_CP_ANC_PRIS['rowid']);
							$nb_ANC_PRIS = ($nb_ANC_PRIS ? price2num($nb_ANC_PRIS) : 0);
							if($nb_ANC_PRIS > 0){
								if($nb_ANC_PRIS >= $nbopenedday_restant){
									$newSolde = $nb_ANC_PRIS - $nbopenedday_restant;
									$nbopenedday_restant = 0;

									// The modification is added to the LOG
									$result = $object->addLogCP($user->id, $object->fk_user, $label, $newSolde, $typeleaves_CP_ANC_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $object->errors, 'errors');
									}

									// Update balance
									$result = $object->updateSoldeCP($object->fk_user, $newSolde, $typeleaves_CP_ANC_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $object->errors, 'errors');
									}
								}
								else {
									$newSolde = 0;
									$nbopenedday_restant -= $nb_ANC_PRIS;

									// The modification is added to the LOG
									$result = $object->addLogCP($user->id, $object->fk_user, $label, $newSolde, $typeleaves_CP_ANC_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $object->errors, 'errors');
									}

									// Update balance
									$result = $object->updateSoldeCP($object->fk_user, $newSolde, $typeleaves_CP_ANC_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $object->errors, 'errors');
									}
								}
							}
						}

						if($nbopenedday_restant > 0){
							$typeleaves_CP_FRAC_PRIS = $object->getTypesCP(1, 'CP_FRAC_PRIS');
							$nb_FRAC_PRIS = $object->getCPforUser($object->fk_user, $typeleaves_CP_FRAC_PRIS['rowid']);
							$nb_FRAC_PRIS = ($nb_FRAC_PRIS ? price2num($nb_FRAC_PRIS) : 0);
							if($nb_FRAC_PRIS > 0){
								if($nb_FRAC_PRIS >= $nbopenedday_restant){
									$newSolde = $nb_FRAC_PRIS - $nbopenedday_restant;
									$nbopenedday_restant = 0;

									// The modification is added to the LOG
									$result = $object->addLogCP($user->id, $object->fk_user, $label, $newSolde, $typeleaves_CP_FRAC_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $object->errors, 'errors');
									}

									// Update balance
									$result = $object->updateSoldeCP($object->fk_user, $newSolde, $typeleaves_CP_FRAC_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $object->errors, 'errors');
									}
								}
								else {
									$newSolde = $nb_FRAC_PRIS - $nbopenedday_restant;
									$nbopenedday_restant = 0;

									// The modification is added to the LOG
									$result = $object->addLogCP($user->id, $object->fk_user, $label, $newSolde, $typeleaves_CP_FRAC_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $object->errors, 'errors');
									}

									// Update balance
									$result = $object->updateSoldeCP($object->fk_user, $newSolde, $typeleaves_CP_FRAC_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $object->errors, 'errors');
									}
								}
							}
						}
					}

					if ($result1 < 0 || $result2 < 0) {
						$error++;
						setEventMessages($langs->trans('ErrorCantDeleteCP').' '.$object->error, $object->errors, 'errors');
					}*/
				}

				if(!$error && $conf->feuilledetemps->enabled && $result && $conf->global->FDT_STATUT_HOLIDAY && ($options_statutfdt == 2 || $object->array_options['options_statutfdt'] == 3)) {
					global $dolibarr_main_url_root;
					$subject = '[OPTIM Industries] Notification automatique Congés à réguler';
					$from = 'erp@optim-industries.fr';
					$to = $conf->global->HOLIDAY_MAIL_TO;

					$user_static = new User($db);
					$user_static->fetch($object->fk_user);

					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/holidaycustom/card.php?id='.$object->id.'">'.$object->ref.'</a>';
					$msg = $langs->transnoentitiesnoconv(($options_statutfdt == 2 ? "EMailTextCongesRegulerCancel2" : "EMailTextCongesRegulerCancel3"), $user_static->firstname.' '.$user_static->lastname, dol_print_date($object->date_debut, '%d/%m/%Y'), dol_print_date($object->date_debut, '%d/%m/%Y'), $link);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)) {
						$res = $mail->sendfile();
					}
				}

				if (!$error) {
					$db->commit();
				} else {
					$db->rollback();
				}

				// If no SQL error, we redirect to the request form
				if (!$error && $result > 0) {
					// To
					$emailTo = '';
					$destinataire = new User($db);
					$destinataire->fetch($object->fk_user);
					if(!empty($destinataire->email)) {
						$emailTo .= $destinataire->email.', ';
					}

					if(!$conf->global->HOLIDAY_FDT_APPROVER) {
						$list_validation = $object->listApprover1;
						foreach($list_validation[2] as $userid => $user_static){
							if(!empty($user_static->email)){
								$emailTo .= $user_static->email.', ';
							}
						}
						$list_validation = $object->listApprover2;
						foreach($list_validation[2] as $userid => $user_static){
							if(!empty($user_static->email)){
								$emailTo .= $user_static->email.', ';
							}
						}
					}
					elseif($conf->global->HOLIDAY_FDT_APPROVER) {
						$user_static = new User($db);
						$list_validation = explode(',', $destinataire->array_options['options_approbateurfdt']);
						foreach($list_validation as $id_validation){
							$user_static->fetch($id_validation);
							if(!empty($user_static->email)){
								$emailTo .= $user_static->email.', ';
							}
						}
					}
					$emailTo = rtrim($emailTo, ", ");

					if (!$emailTo) {
						header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
						exit;
					}

					// From
					$expediteur = new User($db);
					$expediteur->fetch($object->fk_user_cancel);
					//$emailFrom = $expediteur->email;		Email of user can be an email into another company. Sending will fails, we must use the generic email.
					$emailFrom = $conf->global->MAIN_MAIL_EMAIL_FROM;

					// Subject
					$societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
					if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
						$societeName = $conf->global->MAIN_APPLICATION_TITLE;
					}

					$subject = $societeName." - ".$langs->transnoentitiesnoconv("HolidaysCanceled");

					// Content
					$message = "<p>".$langs->transnoentitiesnoconv("Hello")." ".$destinataire->firstname.",</p>\n";

					$message .= "<p>".$langs->transnoentities("HolidaysCanceledBody", dol_print_date($object->date_debut, 'day'), dol_print_date($object->date_fin, 'day'))."</p>\n";
					$message .= "<p>".GETPOST('detail_annulation', 'alphanohtml')."</p>";

					$link = dol_buildpath('/custom/holidaycustom/card.php', 3).'?id='.$object->id;

					$message .= "<ul>\n";
					$message .= "<li>".$langs->transnoentitiesnoconv("ModifiedBy")." : ".dolGetFirstLastname($expediteur->firstname, $expediteur->lastname)."</li>\n";
					$message .= "<li>".$langs->transnoentitiesnoconv("Link").' : <a href="'.$link.'" target="_blank">'.$link."</a></li>\n";
					$message .= "</ul>\n";

					$trackid = 'leav'.$object->id;

					$mail = new CMailFile($subject, $emailTo, $emailFrom, $message, array(), array(), array(), '', '', 0, 1, '', '', $trackid);

					// sending email
					$result = $mail->sendfile();

					if (!$result) {
						setEventMessages($mail->error, $mail->errors, 'warnings');
						$action = '';
					} else {
						header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
						exit;
					}
				}
			}
			else {
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("DetailAnnulationCP")), null, 'errors');
				$action = 'cancel';
			}
		}
	}

	/*
	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions to send emails
	$triggersendname = 'HOLIDAY_SENTBYMAIL';
	$autocopy='MAIN_MAIL_AUTOCOPY_HOLIDAY_TO';
	$trackid='leav'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

	// Actions to build doc
	$upload_dir = $conf->holiday->dir_output;
	$permissiontoadd = $user->rights->holidaycustom->creer;
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
	*/
}



/*
 * View
 */

$form = new Form($db);

$listhalfday = array('morning'=>$langs->trans("Morning"), "afternoon"=>$langs->trans("Afternoon"));
$needHour = $object->holidayTypeNeedHour((int)$object->fk_type);

$title = $langs->trans('Leave');
$help_url = 'EN:Module_Holiday';

llxHeader('', $title, $help_url, "", "", "", array('/custom/holidaycustom/core/js/holiday.js', '/custom/holidaycustom/core/js/parameters.php'));

if ((empty($id) && empty($ref)) || $action == 'create' || $action == 'add') {
	// If user has no permission to create a leave
	if ((!in_array($fuserid, $childids) && empty($user->rights->holidaycustom->writeall) && (!in_array($user->id, explode(',', $user_static->array_options['options_approbateurfdt'])) || !$conf->global->HOLIDAY_FDT_APPROVER)) || (in_array($fuserid, $childids) && empty($user->rights->holidaycustom->write) && (!in_array($user->id, explode(',', $user_static->array_options['options_approbateurfdt'])) || !$conf->global->HOLIDAY_FDT_APPROVER))) {
		$errors[] = $langs->trans('CantCreateCP');
	} else {
		// Form to add a leave request
		print load_fiche_titre($langs->trans('MenuAddCP'), '', $object->picto);

		// Error management
		if (GETPOST('error')) {
			switch (GETPOST('error')) {
				case 'datefin':
					$errors[] = $langs->trans('ErrorEndDateCP');
					break;
				case 'SQL_Create':
					$errors[] = $langs->trans('ErrorSQLCreateCP').' <b>'.htmlentities($_GET['msg']).'</b>';
					break;
				case 'CantCreate':
					$errors[] = $langs->trans('CantCreateCP');
					break;
				case 'Valideur':
					$errors[] = $langs->trans('InvalidValidatorCP');
					break;
				case 'nodatedebut':
					$errors[] = $langs->trans('NoDateDebut');
					break;
				case 'nodatefin':
					$errors[] = $langs->trans('NoDateFin');
					break;
				case 'DureeHoliday':
					$errors[] = $langs->trans('ErrorDureeCP');
					break;
				case 'alreadyCP':
					$errors[] = $langs->trans('alreadyCPexist');
					break;
				case 'NomClient':
					$errors[] = $langs->trans('ErrorNomClient');
					break;
				case 'Approbateur':
					$errors[] = $langs->trans('ErrorApprobateur');
					break;
				case 'ErrorWeekHoliday':
					$errors[] = $langs->trans('ErrorWeekHoliday');
					break;
				case 'ErrorHalfdayHoliday':
					$errors[] = $langs->trans('ErrorHalfdayHoliday');
					break;
				case 'ErrorNbHourHoliday':
					$errors[] = $langs->trans('ErrorNbHourHoliday');
					break;
				case 'ErrorNbMinHoliday':
					$errors[] = $langs->trans('ErrorNbMinHoliday');
					break;
				case 'ErrorMaxHourHoliday':
					$errors[] = $langs->trans('ErrorMaxHourHoliday', $conf->global->HOLIDAY_INHOUR_MAX_HOUR);
					break;
				case 'Hour':
					$errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Hour"));
					break;
			}

			setEventMessages($errors, null, 'errors');
		}


		print '<script type="text/javascript">
		$( document ).ready(function() {
			$("input.button-save").click("submit", function(e) {
				console.log("Call valider()");
	    	    if (document.demandeCP.date_debut_.value != "")
	    	    {
		           	if(document.demandeCP.date_fin_.value != "")
		           	{
		               if(document.demandeCP.valideur.value != "-1") {
		                 return true;
		               }
		               else {
		                 alert("'.dol_escape_js($langs->transnoentities('InvalidValidatorCP')).'");
		                 return false;
		               }
		            }
		            else
		            {
		              alert("'.dol_escape_js($langs->transnoentities('NoDateFin')).'");
		              return false;
		            }
		        }
		        else
		        {
		           alert("'.dol_escape_js($langs->transnoentities('NoDateDebut')).'");
		           return false;
		        }
	       	});
		});
       </script>'."\n";


		// Formulaire de demande
		print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" name="demandeCP">'."\n";
		print '<input type="hidden" name="token" value="'.newToken().'" />'."\n";
		print '<input type="hidden" name="action" value="add" />'."\n";

		if (empty($conf->global->HOLIDAY_HIDE_BALANCE)) {
			print dol_get_fiche_head('', '', '', -1);

			/*$out = '';
			$typeleaves = $object->getTypesNoCP(1, 1);
			foreach ($typeleaves as $key => $val) {
				$nb_type = $object->getCPforUser($user->id, $val['rowid']);
				$nb_holiday += $nb_type;

				$out .= ' - '.($langs->trans($val['code']) != $val['code'] ? $langs->trans($val['code']) : $val['label']).': <strong>'.($nb_type ? price2num($nb_type) : 0).'</strong><br>';
				//$out .= ' - '.$val['label'].': <strong>'.($nb_type ?price2num($nb_type) : 0).'</strong><br>';
			}
			print $langs->trans('SoldeCPUser', round($nb_holiday, 5)).'<br>';
			print $out;*/

			print $object->getArrayHoliday($user->id, 1);
			
			$holyday_franc_anc_datestart = $conf->global->HOLIDAY_FRAC_ANC_DATESTART.'/'.dol_print_date(dol_now(), '%Y');
			$holyday_franc_anc_dateend  = $conf->global->HOLIDAY_FRAC_ANC_DATEEND.'/'.dol_print_date(dol_now(), '%Y');
			$holyday_franc_anc_datestart = dol_mktime(0, 0, 0, substr($holyday_franc_anc_datestart, 3, 2), substr($holyday_franc_anc_datestart, 0, 2), substr($holyday_franc_anc_datestart, 6, 4));
			$holyday_franc_anc_dateend = dol_mktime(0, 0, 0, substr($holyday_franc_anc_dateend, 3, 2), substr($holyday_franc_anc_dateend, 0, 2), substr($holyday_franc_anc_dateend, 6, 4));

			if((dol_now() > $holyday_franc_anc_datestart || dol_now() < $holyday_franc_anc_dateend) && ($nb_ANC_SOLDE + $nb_FRAC_SOLDE) >= 1) {
				print '<div class="center" style="color: #c40000; font-size: larger;"><strong>';
				print '⚠ Vous avez des congés payés fractionné / d\'ancienneté, veuillez les consommer en priorité';
				print '</strong></div><br>';
			}

			print '<span class="hideonsmartphone opacitymedium">';
			print $conf->global->ORGANISATION_CONGES;
			print '</span>';

			print dol_get_fiche_end();
		} elseif (!is_numeric($conf->global->HOLIDAY_HIDE_BALANCE)) {
			print $langs->trans($conf->global->HOLIDAY_HIDE_BALANCE).'<br>';
		}

		print dol_get_fiche_head();

		//print '<span>'.$langs->trans('DelayToRequestCP',$object->getConfCP('delayForRequest')).'</span><br><br>';

		print '<table class="border centpercent">';
		print '<tbody>';

		// User for leave request
		print '<tr>';
		print '<td class="titlefield fieldrequired">'.$langs->trans("User").'</td>';
		print '<td>';
		if ($cancreate && !$cancreateall && !$conf->global->HOLIDAY_FDT_APPROVER) {
			print img_picto('', 'user').$form->select_dolusers(($fuserid ? $fuserid : $user->id), 'fuserid', 0, '', 0, 'hierarchyme', '', '0,'.$conf->entity, 0, 0, $morefilter, 0, '', 'minwidth200 maxwidth500');
			//print '<input type="hidden" name="fuserid" value="'.($fuserid?$fuserid:$user->id).'">';
		} elseif ($cancreate && !$cancreateall && $conf->global->HOLIDAY_FDT_APPROVER) {
			$feuilledetemps = new FeuilleDeTemps($db);
			$include = $feuilledetemps->getUserImApprover();
			if (!in_array($user->id, $include)) {
				$include[] = $user->id;
			}
			print img_picto('', 'user').$form->select_dolusers(($fuserid ? $fuserid : $user->id), 'fuserid', 0, '', 0, $include, '', '0,'.$conf->entity, 0, 0, $morefilter, 0, '', 'minwidth200 maxwidth500');
		} else {
			print img_picto('', 'user').$form->select_dolusers($fuserid ? $fuserid : $user->id, 'fuserid', 0, '', 0, '', '', '0,'.$conf->entity, 0, 0, $morefilter, 0, '', 'minwidth200 maxwidth500');
		}
		print '</td>';
		print '</tr>';

		// Type
		print '<tr>';
		print '<td class="fieldrequired">'.$langs->trans("Type").'</td>';
		print '<td>';
		$typeleaves = $object->getTypesNoCP(1, -1);
		$arraytypeleaves = array();
		foreach ($typeleaves as $key => $val) {
			$labeltoshow = ($langs->trans($val['code']) != $val['code'] ? $langs->trans($val['code']) : $val['label']);
			$labeltoshow .= ($val['delay'] > 0 ? ' ('.$langs->trans("NoticePeriod").': '.$val['delay'].' '.$langs->trans("days").')' : '');
			$arraytypeleaves[$val['rowid']] = $labeltoshow;
		}
		print $form->selectarray('type', $arraytypeleaves, (GETPOST('type', 'alpha') ?GETPOST('type', 'alpha') : ''), 1, 0, 0, '', 0, 0, 0, 'ASC', '', true);
		if ($user->admin) {
			print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		}
		print '</td>';
		print '</tr>';

		// Date start
		print '<tr>';
		print '<td class="fieldrequired">';
		print $form->textwithpicto($langs->trans("DateDebCP"), $langs->trans("FirstDayOfHoliday"));
		print '</td>';
		print '<td>';
		// Si la demande ne vient pas de l'agenda
		if (!GETPOST('date_debut_')) {
			print $form->selectDate(-1, 'date_debut_', 0, 0, 0, '', 1, 1);
		} else {
			$tmpdate = dol_mktime(0, 0, 0, GETPOST('date_debut_month', 'int'), GETPOST('date_debut_day', 'int'), GETPOST('date_debut_year', 'int'));
			print $form->selectDate($tmpdate, 'date_debut_', 0, 0, 0, '', 1, 1);
		}
		print ' &nbsp; &nbsp; ';
		print $form->selectarray('starthalfday', $listhalfday, (GETPOST('starthalfday', 'alpha') ?GETPOST('starthalfday', 'alpha') : 'morning'));
		print '</td>';
		print '</tr>';

		// Date end
		print '<tr>';
		print '<td class="fieldrequired">';
		print $form->textwithpicto($langs->trans("DateFinCP"), $langs->trans("LastDayOfHoliday"));
		print '</td>';
		print '<td>';
		if (!GETPOST('date_fin_')) {
			print $form->selectDate(-1, 'date_fin_', 0, 0, 0, '', 1, 1);
		} else {
			$tmpdate = dol_mktime(0, 0, 0, GETPOST('date_fin_month', 'int'), GETPOST('date_fin_day', 'int'), GETPOST('date_fin_year', 'int'));
			print $form->selectDate($tmpdate, 'date_fin_', 0, 0, 0, '', 1, 1);
		}
		print ' &nbsp; &nbsp; ';
		print $form->selectarray('endhalfday', $listhalfday, (GETPOST('endhalfday', 'alpha') ?GETPOST('endhalfday', 'alpha') : 'afternoon'));
		print '</td>';
		print '</tr>';

		// Approver
		/*print '<tr>';
		print '<td class="fieldrequired">'.$langs->trans("ReviewedByCP").'</td>';
		print '<td>';

		$object = new Holiday($db);
		$include_users = $object->fetch_users_approver_holiday();
		if (empty($include_users)) {
			print img_warning().' '.$langs->trans("NobodyHasPermissionToValidateHolidays");
		} else {
			// Defined default approver (the forced approved of user or the supervisor if no forced value defined)
			// Note: This use will be set only if the deinfed approvr has permission to approve so is inside include_users
			$defaultselectuser = (empty($user->fk_user_holiday_validator) ? $user->fk_user : $user->fk_user_holiday_validator);
			if (!empty($conf->global->HOLIDAY_DEFAULT_VALIDATOR)) {
				$defaultselectuser = $conf->global->HOLIDAY_DEFAULT_VALIDATOR; // Can force default approver
			}
			if (GETPOST('valideur', 'int') > 0) {
				$defaultselectuser = GETPOST('valideur', 'int');
			}
			$s = $form->select_dolusers($defaultselectuser, "valideur", 1, '', 0, $include_users, '', '0,'.$conf->entity, 0, 0, '', 0, '', 'minwidth200 maxwidth500');
			print img_picto('', 'user').$form->textwithpicto($s, $langs->trans("AnyOtherInThisListCanValidate"));
		}

		//print $form->select_dolusers((GETPOST('valideur','int')>0?GETPOST('valideur','int'):$user->fk_user), "valideur", 1, ($user->admin ? '' : array($user->id)), 0, '', 0, 0, 0, 0, '', 0, '', '', 1);	// By default, hierarchical parent
		print '</td>';
		print '</tr>';*/

		// Description
		print '<tr>';
		print '<td>'.$langs->trans("Com").'</td>';
		print '<td class="tdtop">';
		$doleditor = new DolEditor('description', GETPOST('description', 'restricthtml'), '', 80, 'dolibarr_notes', 'In', 0, false, empty($conf->fckeditor->enabled) ? false : $conf->fckeditor->enabled, ROWS_3, '90%');
		print $doleditor->Create(1);
		print '</td></tr>';

		// Other attributes
		unset($extrafields->attributes[$object->table_element]['label']['nom_client']);
		unset($extrafields->attributes[$object->table_element]['label']['fk_validator2']);
		// if(!$needHour) {
		// 	unset($extrafields->attributes[$object->table_element]['label']['hour']);
		// }
		include DOL_DOCUMENT_ROOT.'/custom/holidaycustom/core/tpl/extrafields_add.tpl.php';

		print '</tbody>';
		print '</table>';

		print dol_get_fiche_end();

		print $form->buttonsSaveCancel("SendRequestCP");

		print '</from>'."\n";
	}
} else {
	if ($error) {
		print '<div class="tabBar">';
		print $error;
		print '<br><br><input type="button" value="'.$langs->trans("ReturnCP").'" class="button" onclick="history.go(-1)" />';
		print '</div>';
	} else {
		// Affichage de la fiche d'une demande de congés payés
		if (($id > 0) || $ref) {
			$result = $object->fetch($id, $ref);

			// $approverexpected = new User($db);
			// $approverexpected->fetch($object->fk_validator);

			$userRequest = new ExtendedUser2($db);
			$userRequest->fetch($object->fk_user);

			//print load_fiche_titre($langs->trans('TitreRequestCP'));

			// Si il y a une erreur
			if (GETPOST('error')) {
				switch (GETPOST('error')) {
					case 'datefin':
						$errors[] = $langs->transnoentitiesnoconv('ErrorEndDateCP');
						break;
					case 'SQL_Create':
						$errors[] = $langs->transnoentitiesnoconv('ErrorSQLCreateCP').' '.$_GET['msg'];
						break;
					case 'CantCreate':
						$errors[] = $langs->transnoentitiesnoconv('CantCreateCP');
						break;
					case 'Valideur':
						$errors[] = $langs->transnoentitiesnoconv('InvalidValidatorCP');
						break;
					case 'nodatedebut':
						$errors[] = $langs->transnoentitiesnoconv('NoDateDebut');
						break;
					case 'nodatefin':
						$errors[] = $langs->transnoentitiesnoconv('NoDateFin');
						break;
					case 'DureeHoliday':
						$errors[] = $langs->transnoentitiesnoconv('ErrorDureeCP');
						break;
					case 'alreadyCP':
						$errors[] = $langs->trans('alreadyCPexist');
						break;
					case 'NoMotifRefuse':
						$errors[] = $langs->transnoentitiesnoconv('NoMotifRefuseCP');
						break;
					case 'mail':
						$errors[] = $langs->transnoentitiesnoconv('ErrorMailNotSend')."\n".$_GET['error_content'];
						break;
					case 'NomClient':
						$errors[] = $langs->transnoentitiesnoconv('ErrorNomClient');
						break;
					case 'Approbateur':
						$errors[] = $langs->trans('ErrorApprobateur');
						break;
					case 'ErrorWeekHoliday':
						$errors[] = $langs->trans('ErrorWeekHoliday');
						break;
					case 'ErrorNbHourHoliday':
						$errors[] = $langs->trans('ErrorNbHourHoliday');
						break;
					case 'ErrorNbMinHoliday':
						$errors[] = $langs->trans('ErrorNbMinHoliday');
						break;
					case 'ErrorMaxHourHoliday':
						$errors[] = $langs->trans('ErrorMaxHourHoliday', $conf->global->HOLIDAY_INHOUR_MAX_HOUR);
						break;
					case 'Hour':
						$errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Hour"));
						break;
				}

				setEventMessages($errors, null, 'errors');
			}

			// check if the user has the right to read this request
			if ($canread) {
				$head = holiday_prepare_head($object);

				if (($action == 'edit' && ($object->statut == Holiday::STATUS_DRAFT || ($object->array_options['options_statutfdt'] == 1 && $object->statut == Holiday::STATUS_APPROVED2 && !in_array($object->fk_type, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)) && !in_array(-1, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE))))) || ($action == 'editvalidator1') || ($action == 'editvalidator2')) {
					if ($action == 'edit' && ($object->statut == Holiday::STATUS_DRAFT || ($object->array_options['options_statutfdt'] == 1 && $object->statut == Holiday::STATUS_APPROVED2 && !in_array($object->fk_type, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)) && !in_array(-1, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE))))) {
						$edit = true;
					}

					print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">'."\n";
					print '<input type="hidden" name="token" value="'.newToken().'" />'."\n";
					print '<input type="hidden" name="action" value="update"/>'."\n";
					print '<input type="hidden" name="id" value="'.$object->id.'" />'."\n";
					print '<input type="hidden" name="fuserid" value="'.$object->fk_user.'" />'."\n";
				}

				print dol_get_fiche_head($head, 'card', $langs->trans("CPTitreMenu"), -1, $object->picto);

				$linkback = '<a href="'.DOL_URL_ROOT.'/custom/holidaycustom/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

				dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref');

				print '<div class="fichecenter">';
				print '<div class="fichehalfleft">';
				print '<div class="underbanner clearboth"></div>';

				print '<table class="border tableforfield centpercent">';
				print '<tbody>';

				// User
				print '<tr>';
				print '<td class="titlefield">'.$langs->trans("User").'</td>';
				print '<td>';
				print $userRequest->getNomUrlCustom(-1, 'leave');
				if (empty($conf->global->HOLIDAY_HIDE_BALANCE)) $conges_texte = $object->getArrayHoliday($object->fk_user, 1, 1);
				print $form->textwithpicto('', $conges_texte);
				print '</td></tr>';

				// Type
				print '<tr>';
				print '<td>'.$langs->trans("Type").'</td>';
				print '<td>';
				$typeleaves = $object->getTypesNoCP(-1, -1);
				$labeltoshow = (($typeleaves[$object->fk_type]['code'] && $langs->trans($typeleaves[$object->fk_type]['code']) != $typeleaves[$object->fk_type]['code']) ? $langs->trans($typeleaves[$object->fk_type]['code']) : $typeleaves[$object->fk_type]['label']);
				print empty($labeltoshow) ? $langs->trans("TypeWasDisabledOrRemoved", $object->fk_type) : $labeltoshow;
				print '</td>';
				print '</tr>';

				$starthalfday = ($object->halfday == -1 || $object->halfday == 2) ? 'afternoon' : 'morning';
				$endhalfday = ($object->halfday == 1 || $object->halfday == 2) ? 'morning' : 'afternoon';

				if (!$edit) {
					print '<tr>';
					print '<td class="nowrap">';
					print $form->textwithpicto($langs->trans('DateDebCP'), $langs->trans("FirstDayOfHoliday"));
					print '</td>';
					print '<td>'.dol_print_date($object->date_debut, 'day');
					print ' &nbsp; &nbsp; ';
					print '<span class="opacitymedium">'.$langs->trans($listhalfday[$starthalfday]).'</span>';
					print '</td>';
					print '</tr>';
				} else {
					print '<tr>';
					print '<td class="nowrap">';
					print $form->textwithpicto($langs->trans('DateDebCP'), $langs->trans("FirstDayOfHoliday"));
					print '</td>';
					print '<td>';
					print $form->selectDate($object->date_debut, 'date_debut_');
					print ' &nbsp; &nbsp; ';
					print $form->selectarray('starthalfday', $listhalfday, (GETPOST('starthalfday') ?GETPOST('starthalfday') : $starthalfday));
					print '</td>';
					print '</tr>';
				}

				if (!$edit) {
					print '<tr>';
					print '<td class="nowrap">';
					print $form->textwithpicto($langs->trans('DateFinCP'), $langs->trans("LastDayOfHoliday"));
					print '</td>';
					print '<td>'.dol_print_date($object->date_fin, 'day');
					print ' &nbsp; &nbsp; ';
					print '<span class="opacitymedium">'.$langs->trans($listhalfday[$endhalfday]).'</span>';
					print '</td>';
					print '</tr>';
				} else {
					print '<tr>';
					print '<td class="nowrap">';
					print $form->textwithpicto($langs->trans('DateFinCP'), $langs->trans("LastDayOfHoliday"));
					print '</td>';
					print '<td>';
					print $form->selectDate($object->date_fin, 'date_fin_');
					print ' &nbsp; &nbsp; ';
					print $form->selectarray('endhalfday', $listhalfday, (GETPOST('endhalfday') ?GETPOST('endhalfday') : $endhalfday));
					print '</td>';
					print '</tr>';
				}

				// Nb of days
				print '<tr>';
				print '<td>';
				$htmlhelp = $langs->trans('NbUseDaysCPHelp');
				$includesaturday = (isset($conf->global->MAIN_NON_WORKING_DAYS_INCLUDE_SATURDAY) ? $conf->global->MAIN_NON_WORKING_DAYS_INCLUDE_SATURDAY : 1);
				$includesunday   = (isset($conf->global->MAIN_NON_WORKING_DAYS_INCLUDE_SUNDAY) ? $conf->global->MAIN_NON_WORKING_DAYS_INCLUDE_SUNDAY : 1);
				if ($includesaturday) {
					$htmlhelp .= '<br>'.$langs->trans("DayIsANonWorkingDay", $langs->trans("Saturday"));
				}
				if ($includesunday) {
					$htmlhelp .= '<br>'.$langs->trans("DayIsANonWorkingDay", $langs->trans("Sunday"));
				}
				print $form->textwithpicto($langs->trans('NbUseDaysCP'), $htmlhelp);
				print '</td>';
				print '<td>';
				print num_open_day($object->date_debut_gmt, $object->date_fin_gmt, 0, 1, $object->halfday);
				print '</td>';
				print '</tr>';

				if ($object->statut == Holiday::STATUS_REFUSED) {
					print '<tr>';
					print '<td>'.$langs->trans('DetailRefusCP').'</td>';
					print '<td>'.$object->detail_refuse.'</td>';
					print '</tr>';
				}

				// Description
				if (!$edit) {
					print '<tr>';
					print '<td>'.$langs->trans('Com').'</td>';
					print '<td>'.nl2br($object->description).'</td>';
					print '</tr>';
				} else {
					print '<tr>';
					print '<td>'.$langs->trans('Com').'</td>';
					print '<td class="tdtop">';
					$doleditor = new DolEditor('description', $object->description, '', 80, 'dolibarr_notes', 'In', 0, false, empty($conf->fckeditor->enabled) ? false : $conf->fckeditor->enabled, ROWS_3, '90%');
					print $doleditor->Create(1);
					print '</td></tr>';
				}

				// Other attributes
				if($action != 'edit'){
					$tmp_fields = $extrafields->attributes[$object->table_element]['label'];
					unset($extrafields->attributes[$object->table_element]['label']['fk_validator2']);
					if($object->array_options['options_client_informe'] != 1){
						unset($extrafields->attributes[$object->table_element]['label']['nom_client']);
					}
					if(!$needHour) {
						unset($extrafields->attributes[$object->table_element]['label']['hour']);
					}
					include DOL_DOCUMENT_ROOT.'/custom/holidaycustom/core/tpl/extrafields_view.tpl.php';
					$extrafields->attributes[$object->table_element]['label'] = $tmp_fields;
				}
				else {
					$parameters = array('colspan' => ' colspan="2"');
					//include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';		// We do not use common tpl here because we need a special test on $caneditfield
					$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
					print $hookmanager->resPrint;
					if (empty($reshook)) {
						unset($extrafields->attributes[$object->table_element]['label']['fk_validator2']);
						if(!$needHour) {
							unset($extrafields->attributes[$object->table_element]['label']['hour']);
						}
						print $object->showOptionals_custom($extrafields, 'edit', null, '', '', 0, 'card');
					}
				}

				print '</tbody>';
				print '</table>'."\n";

				print '</div>';
				print '<div class="fichehalfright">';

				print '<div class="underbanner clearboth"></div>';

				// Info workflow
				print '<table class="border tableforfield centpercent">'."\n";
				print '<tbody>';

				if (!empty($object->fk_user_create)) {
					$userCreate = new User($db);
					$userCreate->fetch($object->fk_user_create);
					print '<tr>';
					print '<td class="titlefield">'.$langs->trans('RequestByCP').'</td>';
					print '<td>'.$userCreate->getNomUrl(-1).'</td>';
					print '</tr>';
				}

				// Approver
				if(!$conf->global->HOLIDAY_FDT_APPROVER && (in_array($object->fk_type, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)) || in_array(-1, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)))) {
					print '<tr>';
					print '<td class="titlefield">';
					print '<table class="nobordernopadding centpercent">';
					print '<tr><td class="">';
					print '1ère Approbation par';
					print '</td>';
					print "<td class='right'>";
					if(!empty($user->rights->holidaycustom->changeappro) && $action != 'editvalidator1' && ($object->statut == Holiday::STATUS_DRAFT || $object->statut == Holiday::STATUS_VALIDATED)) {
						print '<a class="editfielda paddingleft" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editvalidator1&token='.newToken().'">'.img_edit($langs->trans("Edit")).'</a>';
					}
					print '</td>';
					print '</tr></table></td>';
					print '<td>';
					if (!$edit && $action == 'editvalidator1' && !empty($user->rights->holidaycustom->changeappro)) {
						$value = array();
						$list_validation1 = $object->listApprover1;
						foreach($list_validation1[2] as $id => $user_static){
							$value = array_merge($value, array($id));
						}
						$key = 'fk_user_approbation1';
						$object->fields[$key] = array('type'=>'chkbxlst:user:firstname|lastname:rowid', 'label'=>'UserApprobation1', 'enabled'=>'1', 'position'=>50, 'notnull'=>1, 'visible'=>1);
						print $object->showInputField($object->fields[$key], $key, $value, '', '', '', 0);
						unset($object->fields[$key]);

						if ($action == 'editvalidator1') {
							print '<input type="submit" class="button button-save" name="savevalidator1" value="'.$langs->trans("Save").'">';
							print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
						}
					}
					else {
						$list_validation1 = $object->listApprover1;
						foreach($list_validation1[2] as $id => $user_static){
							print $user_static->getNomUrl(1).($list_validation1[1][$id] == 1 ? ' <i class="fas fa-check" style="color: #00a300;"></i>' : ' <i class="fas fa-times" style="color: red"></i>').'<br>';
						}
					}
					print '</td>';
					print '</tr>';
				}
				elseif($conf->global->HOLIDAY_FDT_APPROVER && (in_array($object->fk_type, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)) || in_array(-1, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)))) {
					$extrafieldsuser = new extrafields($db);
					$extrafieldsuser->fetch_name_optionals_label($user_static->table_element);
					$user_static->fetch($object->fk_user);

					print '<tr>';
					print '<td class="titlefield">';
					print '<table class="nobordernopadding centpercent">';
					print '<tr><td class="">';
					print '1ère Approbation par';
					print '</td>';
					print '</tr></table></td>';
					print '<td>';
					print $extrafieldsuser->showOutputField('approbateurfdt', $user_static->array_options['options_approbateurfdt'], '', $user_static->table_element);
					print '</td>';
					print '</tr>';
				}

				// Approver 2
				if(!$conf->global->HOLIDAY_FDT_APPROVER && (in_array($object->fk_type, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)) || in_array(-1, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)))) {
					print '<tr>';
					print '<td class="titlefield">';
					print '<table class="nobordernopadding centpercent">';
					print '<tr><td class="">';
					print '2ème Approbation par';
					print '</td>';
					print "<td class='right'>";
					if(!empty($user->rights->holidaycustom->changeappro) && $action != 'editvalidator2' && ($object->statut == Holiday::STATUS_DRAFT || $object->statut == Holiday::STATUS_VALIDATED || $object->statut == Holiday::STATUS_APPROVED1)) {
						print '<a class="editfielda paddingleft" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editvalidator2&token='.newToken().'">'.img_edit($langs->trans("Edit")).'</a>';
					}
					print '</td>';
					print '</tr></table></td>';
					print '<td>';
					if (!$edit && $action == 'editvalidator2' && !empty($user->rights->holidaycustom->changeappro)) {
						$value = array();
						$list_validation2 = $object->listApprover2;
						foreach($list_validation2[2] as $id => $user_static){
							$value = array_merge($value, array($id));
						}
						$key = 'fk_user_approbation2';
						$object->fields[$key] = array('type'=>'chkbxlst:user:firstname|lastname:rowid', 'label'=>'UserApprobation2', 'enabled'=>'1', 'position'=>50, 'notnull'=>1, 'visible'=>1);
						print $object->showInputField($object->fields[$key], $key, $value, '', '', '', 0);
						unset($object->fields[$key]);

						if ($action == 'editvalidator2') {
							print '<input type="submit" class="button button-save" name="savevalidator2" value="'.$langs->trans("Save").'">';
							print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
						}
					}
					else {
						$list_validation2 = $object->listApprover2;
						foreach($list_validation2[2] as $id => $user_static){
							print $user_static->getNomUrl(1).($list_validation2[1][$id] == 1 ? ' <i class="fas fa-check" style="color: #00a300;"></i>' : ' <i class="fas fa-times" style="color: red"></i>').'<br>';
						}
					}

					print '</td>';
					print '</tr>';
				}


				// Other attributes
				// if($action != 'edit'){
				// 	$tmp_fields = $extrafields->attributes[$object->table_element]['label'];
				// 	unset($extrafields->attributes[$object->table_element]['label']['fk_validator2']);
				// 	unset($extrafields->attributes[$object->table_element]['label']['hour']);
				// 	unset($extrafields->attributes[$object->table_element]['label']['remplacement']);
				// 	unset($extrafields->attributes[$object->table_element]['label']['client_informe']);
				// 	unset($extrafields->attributes[$object->table_element]['label']['nom_client']);
				// 	unset($extrafields->attributes[$object->table_element]['label']['detail_annulation']);
				// 	unset($extrafields->attributes[$object->table_element]['label']['statutfdt']);
				// 	include DOL_DOCUMENT_ROOT.'/custom/holidaycustom/core/tpl/extrafields_view.tpl.php';
				// 	$extrafields->attributes[$object->table_element]['label'] = $tmp_fields;
				// }
				// else {
				// 	$parameters = array('colspan' => ' colspan="2"');
				// 	//include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';		// We do not use common tpl here because we need a special test on $caneditfield
				// 	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
				// 	print $hookmanager->resPrint;
				// 	if (empty($reshook) && !empty($user->rights->holidaycustom->approve)) {
				// 		print $object->showOptionals($extrafields, 'edit', null, '', '', 0, 'card');
				// 	}
				// }

				print '<tr>';
				print '<td>'.$langs->trans('DateCreation').'</td>';
				print '<td>'.dol_print_date($object->date_create, 'dayhour', 'tzuser').'</td>';
				print '</tr>';
				if ($object->statut == Holiday::STATUS_APPROVED2 || $object->statut == Holiday::STATUS_CANCELED) {
					print '<tr>';
					print '<td>'.$langs->trans('DateValidCP').'</td>';
					print '<td>'.dol_print_date($object->date_valid, 'dayhour', 'tzuser').'</td>'; // warning: date_valid is approval date on holiday module
					print '</tr>';
				}
				if ($object->statut == Holiday::STATUS_CANCELED) {
					print '<tr>';
					print '<td>'.$langs->trans('DateCancelCP').'</td>';
					print '<td>'.dol_print_date($object->date_cancel, 'dayhour', 'tzuser').'</td>';
					print '</tr>';
				}
				if ($object->statut == Holiday::STATUS_REFUSED) {
					print '<tr>';
					print '<td>'.$langs->trans('DateRefusCP').'</td>';
					print '<td>'.dol_print_date($object->date_refuse, 'dayhour', 'tzuser').'</td>';
					print '</tr>';
				}

				if($conf->feuilledetemps->enabled && $conf->global->HOLIDAY_FDT_LINK) {
					$feuilledetemps = new FeuilleDeTemps($db);
					$fdt_month = $feuilledetemps->fetchWithUserAndDate($object->fk_user, $object->date_debut);
					if ($fdt_month) {
						$feuilledetemps->fetch($fdt_month->rowid);
						print '<tr>';
						print '<td>'.$langs->trans('FDTMonth').'</td>';
						print '<td>'.$feuilledetemps->getNomUrl().'</td>';
						print '</tr>';
					}
				}

				print '</tbody>';
				print '</table>';

				print '</div>';
				print '</div>';

				print '<div class="clearboth"></div>';

				print dol_get_fiche_end();


				// Confirmation messages
				if ($action == 'delete') {
					if ($candelete) {
						print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("TitleDeleteCP"), $langs->trans("ConfirmDeleteCP"), "confirm_delete", '', 0, 1);
					}
				}

				// Si envoi en validation
				if ($action == 'sendToValidate' && $object->statut == Holiday::STATUS_DRAFT) {
					print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("TitleToValidCP"), $langs->trans("ConfirmToValidCP"), "confirm_send", '', 1, 1);
				}

				// Si demande de transfert
				// if ($action == 'transferer') {
				// 	$include_users = $object->fetch_users_approver_holiday();
				// 	$arrayofvalidatorstoexclude = array($user->id);
				// 	$s = $form->select_dolusers('', "user_transferer", 0, $arrayofvalidatorstoexclude, 0, $include_users);
				// 	$formactions = array(array('label' => 'Transférer à', 'type' => 'other', 'value' => $s, 'name' => 'user_transferer'));
				// 	print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("Transferer"), '', "confirm_transferer", $formactions, 0, 2);
				// }

				// Si validation de la demande (1ere validation)
				if ($action == 'valid1') {
					print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("TitleValidCP"), $langs->trans("ConfirmValidCP"), "confirm_valid1", '', 1, 1);
				}

				// Si validation de la demande (2eme validation)
				if ($action == 'valid2') {
					print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("TitleValidCP"), $langs->trans("ConfirmValidCP"), "confirm_valid2", '', 1, 1);
				}


				// Si refus de la demande
				if ($action == 'refuse') {
					$array_input = array(array('type'=>"text", 'label'=> $langs->trans('DetailRefusCP'), 'name'=>"detail_refuse", 'size'=>"50", 'value'=>""));
					print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id."&action=confirm_refuse", $langs->trans("TitleRefuseCP"), $langs->trans('ConfirmRefuseCP'), "confirm_refuse", $array_input, 0, 2);
				}

				// Si annulation de la demande
				if ($action == 'cancel') {
					$array_input = array(array('type'=>"text", 'label'=> $langs->trans('DetailAnnulationCP'), 'name'=>"detail_annulation", 'size'=>"50", 'value'=>""));
					print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("TitleCancelCP"), $langs->trans("ConfirmCancelCP"), "confirm_cancel", $array_input, 0, 2);
				}

				// Si back to draft
				// if ($action == 'backtodraft') {
				// 	print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("TitleSetToDraft"), $langs->trans("ConfirmSetToDraft"), "confirm_draft", '', 1, 1);
				// }

				if (($action == 'edit' && ($object->statut == Holiday::STATUS_DRAFT || ($object->array_options['options_statutfdt'] == 1 && $object->statut == Holiday::STATUS_APPROVED2 && !in_array($object->fk_type, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)) && !in_array(-1, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE))))) || ($action == 'editvalidator1') || ($action == 'editvalidator2')) {
					if ($action == 'edit' && $cancreate && ($object->statut == Holiday::STATUS_DRAFT || ($object->array_options['options_statutfdt'] == 1 && $object->statut == Holiday::STATUS_APPROVED2 && !in_array($object->fk_type, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)) && !in_array(-1, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE))))) {
						print $form->buttonsSaveCancel();
					}

					print '</form>';
				}

				if (!$edit) {
					// Buttons for actions

					print '<div class="tabsAction">';

					if ($cancreate && ($object->statut == Holiday::STATUS_DRAFT || ($object->array_options['options_statutfdt'] == 1 && $object->statut == Holiday::STATUS_APPROVED2 && !in_array($object->fk_type, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)) && !in_array(-1, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE))))) {
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken().'" class="butAction">'.$langs->trans("EditCP").'</a>';
					}

					if ($cancreate && $object->statut == Holiday::STATUS_DRAFT) {		// If draft
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=sendToValidate&token='.newToken().'" class="butAction">'.$langs->trans("Validate").'</a>';
					}

					if ($object->statut == Holiday::STATUS_VALIDATED) {	// If validated
						// Button Approve / Refuse
						if ($permissiontovalidate1) {
							print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=valid1" class="butAction">'.$langs->trans("Approve").'</a>';
							print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=refuse" class="butAction">'.$langs->trans("ActionRefuseCP").'</a>';
							// print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=transferer" class="butAction">'.$langs->trans("Transferer").'</a>';
						} else {
							print '<a href="#" class="butActionRefused classfortooltip" title="'.$langs->trans("NotTheAssignedApprover").'">'.$langs->trans("Approve").'</a>';
							print '<a href="#" class="butActionRefused classfortooltip" title="'.$langs->trans("NotTheAssignedApprover").'">'.$langs->trans("ActionRefuseCP").'</a>';

							// Button Cancel (because we can't approve)
							if ($cancreate || $cancreateall) {
								if (($object->date_debut > dol_now()) || !empty($user->admin)) {
									print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=cancel&token='.newToken().'" class="butAction">'.$langs->trans("ActionCancelCP").'</a>';
								} else {
									print '<a href="#" class="butActionRefused classfortooltip" title="'.$langs->trans("HolidayStarted").'-'.$langs->trans("NotAllowed").'">'.$langs->trans("ActionCancelCP").'</a>';
								}
							}
						}
					}

					if ($object->statut == Holiday::STATUS_APPROVED1) {	// If validated
						// Button Approve / Refuse
						if ($permissiontovalidate2) {
							print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=valid2" class="butAction">'.$langs->trans("Approve").'</a>';
							print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=refuse" class="butAction">'.$langs->trans("ActionRefuseCP").'</a>';
						} else {
							print '<a href="#" class="butActionRefused classfortooltip" title="'.$langs->trans("NotTheAssignedApprover").'">'.$langs->trans("Approve").'</a>';
							print '<a href="#" class="butActionRefused classfortooltip" title="'.$langs->trans("NotTheAssignedApprover").'">'.$langs->trans("ActionRefuseCP").'</a>';

							// Button Cancel (because we can't approve)
							if ($cancreate || $cancreateall) {
								if (($object->date_debut > dol_now()) || !empty($user->admin)) {
									print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=cancel&token='.newToken().'" class="butAction">'.$langs->trans("ActionCancelCP").'</a>';
								} else {
									print '<a href="#" class="butActionRefused classfortooltip" title="'.$langs->trans("HolidayStarted").'-'.$langs->trans("NotAllowed").'">'.$langs->trans("ActionCancelCP").'</a>';
								}
							}
						}
					}

					if ($object->statut == Holiday::STATUS_APPROVED2) { // If validated and approved
						if (/*$user->id == $object->fk_validator*/ (in_array($user->id, $object->listApprover1[0]) || in_array($user->id, $object->listApprover2[0])) || $cancreate || $cancreateall) {
							if (($object->date_debut > dol_now()) || !empty($user->admin)) {
								print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=cancel&token='.newToken().'" class="butAction">'.$langs->trans("ActionCancelCP").'</a>';
							} else {
								print '<a href="#" class="butActionRefused classfortooltip" title="'.$langs->trans("HolidayStarted").'-'.$langs->trans("NotAllowed").'">'.$langs->trans("ActionCancelCP").'</a>';
							}
							
						} else { // I have no rights on the user of the holiday.
							if (!empty($user->admin)) {	// If current approver can't cancel an approved leave, we allow admin user
								print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=cancel&token='.newToken().'" class="butAction">'.$langs->trans("ActionCancelCP").'</a>';
							} else {
								print '<a href="#" class="butActionRefused classfortooltip" title="'.$langs->trans("NotAllowed").'">'.$langs->trans("ActionCancelCP").'</a>';
							}
						}
					}

					// if (($cancreate || $cancreateall) && $object->statut == Holiday::STATUS_CANCELED || (($cancreate || $cancreateall) && $object->statut > Holiday::STATUS_DRAFT)) {
					// 	print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=backtodraft" class="butAction">'.$langs->trans("SetToDraft").'</a>';
					// }
					if ($candelete && ($object->statut == Holiday::STATUS_DRAFT || ($object->array_options['options_statutfdt'] == 1 && $object->statut == Holiday::STATUS_APPROVED2 && !in_array($object->fk_type, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE)) && !in_array(-1, explode(",", $conf->global->HOLIDAY_VALIDATE_TYPE))))) {	// If draft or canceled or refused
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken().'" class="butActionDelete">'.$langs->trans("DeleteCP").'</a>';
					}
					
				

					print '</div>';
				}
			} else {
				print '<div class="tabBar">';
				print $langs->trans('ErrorUserViewCP');
				print '<br><br><input type="button" value="'.$langs->trans("ReturnCP").'" class="button" onclick="history.go(-1)" />';
				print '</div>';
			}
		} else {
			print '<div class="tabBar">';
			print $langs->trans('ErrorIDFicheCP');
			print '<br><br><input type="button" value="'.$langs->trans("ReturnCP").'" class="button" onclick="history.go(-1)" />';
			print '</div>';
		}


		// Select mail models is same action as presend
		if (GETPOST('modelselected')) {
			$action = 'presend';
		}

		if ($action != 'presend' && $action != 'edit') {
			print '<div class="fichecenter"><div class="fichehalfleft">';
			print '<a name="builddoc"></a>'; // ancre

			$includedocgeneration = 0;

			// Documents
			if ($includedocgeneration) {
				$objref = dol_sanitizeFileName($object->ref);
				$relativepath = $objref.'/'.$objref.'.pdf';
				$filedir = $conf->holiday->dir_output.'/'.$object->element.'/'.$objref;
				$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
				$genallowed = ($user->rights->holidaycustom->read && $object->fk_user == $user->id) || !empty($user->rights->holidaycustom->readall); // If you can read, you can build the PDF to read content
				$delallowed = ($user->rights->holidaycustom->write && $object->fk_user == $user->id) || !empty($user->rights->holidaycustom->writeall); // If you can create/edit, you can remove a file on card
				print $formfile->showdocuments('holiday:Holiday', $object->element.'/'.$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
			}

			// Show links to link elements
			//$linktoelem = $form->showLinkToObjectBlock($object, null, array('myobject'));
			//$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


			print '</div><div class="fichehalfright">';

			$MAXEVENT = 10;

			// List of actions on element
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
			$formactions = new FormActions($db);
			$somethingshown = $formactions->showactions($object, $object->element, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlright);

			print '</div></div>';
		}
	}
}

$nom = "'".$object->array_options['options_nom_client']."'";

if($nom == '\'\'' && !empty(GETPOST('options_nom_client'))){
	$nom = "'".GETPOST('options_nom_client')."'";
}
print '<script>afficherNomClient('.$nom.')</script>';

// End of page
llxFooter();

if (is_object($db)) {
	$db->close();
}
