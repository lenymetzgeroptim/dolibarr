<?php
/* Copyright (C) 2015-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2021 Nicolas ZABOURI	<info@inovea-conseil.com>
 * Copyright (C) 2018 	   Juanjo Menent  <jmenent@2byte.es>
 * Copyright (C) 2019 	   Ferran Marcet  <fmarcet@2byte.es>
 * Copyright (C) 2019-2021 Frédéric France <frederic.france@netlogic.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *	\file			htdocs/core/actions_massactions.inc.php
 *  \brief			Code for actions done with massaction button (send by email, merge pdf, delete, ...)
 */


// $massaction must be defined
// $objectclass and $objectlabel must be defined
// $parameters, $object, $action must be defined for the hook.

// $permissiontoread, $permissiontoadd, $permissiontodelete, $permissiontoclose may be defined
// $uploaddir may be defined (example to $conf->project->dir_output."/";)
// $toselect may be defined
// $diroutputmassaction may be defined


// Protection
if (empty($objectclass) || empty($uploaddir)) {
	dol_print_error(null, 'include of actions_massactions.inc.php is done but var $objectclass or $uploaddir was not defined');
	exit;
}
if (empty($massaction)) {
	$massaction = '';
}
$error = 0;

// For backward compatibility
if (!empty($permtoread) && empty($permissiontoread)) {
	$permissiontoread = $permtoread;
}
if (!empty($permtocreate) && empty($permissiontoadd)) {
	$permissiontoadd = $permtocreate;
}
if (!empty($permtodelete) && empty($permissiontodelete)) {
	$permissiontodelete = $permtodelete;
}

// Mass actions. Controls on number of lines checked.
$maxformassaction = (!getDolGlobalString('MAIN_LIMIT_FOR_MASS_ACTIONS') ? 1000 : $conf->global->MAIN_LIMIT_FOR_MASS_ACTIONS);
if ($massaction && is_array($toselect) && count($toselect) < 1) {
	$error++;
	setEventMessages($langs->trans("NoRecordSelected"), null, "warnings");
}
if (!$error && isset($toselect) && is_array($toselect) && count($toselect) > $maxformassaction) {
	setEventMessages($langs->trans('TooManyRecordForMassAction', $maxformassaction), null, 'errors');
	$error++;
}

// Delete record from mass action (massaction = 'delete' for direct delete, action/confirm='deletelines'/'yes' with a confirmation step before)
if (!$error && ($massaction == 'delete' || ($action == 'deletelines' && $confirm == 'yes')) && $permissiontoaddline) {
	$db->begin();

	$objecttmp = new $objectclass($db);
	$nbok = 0;
	$TMsg = array();

	//$toselect could contain duplicate entries, cf https://github.com/Dolibarr/dolibarr/issues/26244
	$unique_arr = array_unique($toselect);
	foreach ($unique_arr as $toselectid) {
		$result = $objecttmp->fetch($toselectid);
		if ($result > 0) {
			// Refuse deletion for some objects/status
			if ($objectclass == 'Facture' && !getDolGlobalString('INVOICE_CAN_ALWAYS_BE_REMOVED') && $objecttmp->status != Facture::STATUS_DRAFT) {
				$langs->load("errors");
				$nbignored++;
				$TMsg[] = '<div class="error">'.$langs->trans('ErrorOnlyDraftStatusCanBeDeletedInMassAction', $objecttmp->ref).'</div><br>';
				continue;
			}

			if (method_exists($objecttmp, 'is_erasable') && $objecttmp->is_erasable() <= 0) {
				$langs->load("errors");
				$nbignored++;
				$TMsg[] = '<div class="error">'.$langs->trans('ErrorRecordHasChildren').' '.$objecttmp->ref.'</div><br>';
				continue;
			}

			if ($objectclass == 'Holiday' && ! in_array($objecttmp->statut, array(Holiday::STATUS_DRAFT, Holiday::STATUS_CANCELED, Holiday::STATUS_REFUSED))) {
				$langs->load("errors");
				$nbignored++;
				$TMsg[] = '<div class="error">'.$langs->trans('ErrorLeaveRequestMustBeDraftCanceledOrRefusedToBeDeleted', $objecttmp->ref).'</div><br>';
				continue;
			}

			if ($objectclass == "Task" && $objecttmp->hasChildren() > 0) {
				$sql = "UPDATE ".MAIN_DB_PREFIX."projet_task SET fk_task_parent = 0 WHERE fk_task_parent = ".((int) $objecttmp->id);
				$res = $db->query($sql);

				if (!$res) {
					setEventMessage('ErrorRecordParentingNotModified', 'errors');
					$error++;
				}
			}

			if (in_array($objecttmp->element, array('societe', 'member'))) {
				$result = $objecttmp->delete($objecttmp->id, $user, 1);
			} elseif (in_array($objecttmp->element, array('action'))) {
				$result = $objecttmp->delete();	// TODO Add User $user as first param
			} else {
				$result = $objecttmp->delete($user);
			}

			if (empty($result)) { // if delete returns 0, there is at least one object linked
				$TMsg = array_merge($objecttmp->errors, $TMsg);
			} elseif ($result < 0) { // if delete returns is < 0, there is an error, we break and rollback later
				setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
				$error++;
				break;
			} else {
				$nbok++;
			}
		} else {
			setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
			$error++;
			break;
		}
	}

	if (empty($error)) {
		// Message for elements well deleted
		if ($nbok > 1) {
			setEventMessages($langs->trans("RecordsDeleted", $nbok), null, 'mesgs');
		} elseif ($nbok > 0) {
			setEventMessages($langs->trans("RecordDeleted", $nbok), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("NoRecordDeleted"), null, 'mesgs');
		}

		// Message for elements which can't be deleted
		if (!empty($TMsg)) {
			sort($TMsg);
			setEventMessages('', array_unique($TMsg), 'warnings');
		}

		$db->commit();
	} else {
		$db->rollback();
	}

	//var_dump($listofobjectthirdparties);exit;
}

// Validate record from mass action
if (!$error && ($massaction == 'validate' || ($action == 'validatelines' && $confirm == 'yes')) && $permissiontoaddline) {
	$db->begin();

	$objecttmp = new $objectclass($db);
	$nbok = 0;
	$TMsg = array();

	//$toselect could contain duplicate entries, cf https://github.com/Dolibarr/dolibarr/issues/26244
	$unique_arr = array_unique($toselect);
	foreach ($unique_arr as $toselectid) {
		$result = $objecttmp->fetch($toselectid);
		if ($result > 0) {
			$result = $objecttmp->validate($user);

			if (empty($result)) { // if validate returns 0, there is at least one object linked
				$TMsg = array_merge($objecttmp->errors, $TMsg);
			} elseif ($result < 0) { // if validate returns is < 0, there is an error, we break and rollback later
				setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
				$error++;
				break;
			} else {
				$nbok++;
			}
		} else {
			setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
			$error++;
			break;
		}
	}

	if (empty($error)) {
		// Message for elements well validated
		if ($nbok > 1) {
			setEventMessages($langs->trans("RecordsValidated", $nbok), null, 'mesgs');
		} elseif ($nbok > 0) {
			setEventMessages($langs->trans("RecordValidated", $nbok), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("NoRecordValidated"), null, 'mesgs');
		}

		// Message for elements which can't be validated
		if (!empty($TMsg)) {
			sort($TMsg);
			setEventMessages('', array_unique($TMsg), 'warnings');
		}

		$db->commit();
	} else {
		$db->rollback();
	}
}