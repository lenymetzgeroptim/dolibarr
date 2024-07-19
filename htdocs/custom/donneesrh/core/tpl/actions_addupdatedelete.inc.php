<?php
/* Copyright (C) 2017-2019 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file			htdocs/core/actions_addupdatedelete.inc.php
 *  \brief			Code for common actions cancel / add / update / update_extras / delete / deleteline / validate / cancel / reopen / clone
 */


// $action or $cancel must be defined
// $object must be defined
// $permissiontoadd must be defined
// $permissiontodelete must be defined
// $backurlforlist must be defined
// $backtopage may be defined
// $noback may be defined
// $triggermodname may be defined

$hidedetails = isset($hidedetails) ? $hidedetails : '';
$hidedesc = isset($hidedesc) ? $hidedesc : '';
$hideref = isset($hideref) ? $hideref : '';


if (!empty($permissionedit) && empty($permissiontoadd)) {
	$permissiontoadd = $permissionedit; // For backward compatibility
}

if ($cancel) {
	/*var_dump($cancel);var_dump($backtopage);var_dump($backtopageforcancel);exit;*/
	if (!empty($backtopageforcancel)) {
		header("Location: ".$backtopageforcancel);
		exit;
	} elseif (!empty($backtopage)) {
		header("Location: ".$backtopage);
		exit;
	}
	$action = '';
}


// Action to add record
if ($action == 'add' && !empty($permissiontoadd)) {
	foreach ($object->fields as $key => $val) {
		if ($object->fields[$key]['type'] == 'duration') {
			if (GETPOST($key.'hour') == '' && GETPOST($key.'min') == '') {
				continue; // The field was not submited to be saved
			}
		} else {
			if (!GETPOSTISSET($key) && !preg_match('/^chkbxlst:/', $object->fields[$key]['type'])) {
				continue; // The field was not submited to be saved
			}
		}
		// Ignore special fields
		if (in_array($key, array('rowid', 'entity', 'import_key'))) {
			continue;
		}
		if (in_array($key, array('date_creation', 'tms', 'fk_user_creat', 'fk_user_modif'))) {
			if (!in_array(abs($val['visible']), array(1, 3))) {
				continue; // Only 1 and 3 that are case to create
			}
		}

		// Set value to insert
		if (in_array($object->fields[$key]['type'], array('text', 'html'))) {
			$value = GETPOST($key, 'restricthtml');
		} elseif ($object->fields[$key]['type'] == 'date') {
			$value = dol_mktime(12, 0, 0, GETPOST($key.'month', 'int'), GETPOST($key.'day', 'int'), GETPOST($key.'year', 'int')); // for date without hour, we use gmt
		} elseif ($object->fields[$key]['type'] == 'datetime') {
			$value = dol_mktime(GETPOST($key.'hour', 'int'), GETPOST($key.'min', 'int'), GETPOST($key.'sec', 'int'), GETPOST($key.'month', 'int'), GETPOST($key.'day', 'int'), GETPOST($key.'year', 'int'), 'tzuserrel');
		} elseif ($object->fields[$key]['type'] == 'duration') {
			$value = 60 * 60 * GETPOST($key.'hour', 'int') + 60 * GETPOST($key.'min', 'int');
		} elseif (preg_match('/^(integer|price|real|double)/', $object->fields[$key]['type'])) {
			$value = price2num(GETPOST($key, 'alphanohtml')); // To fix decimal separator according to lang setup
		} elseif ($object->fields[$key]['type'] == 'boolean') {
			$value = ((GETPOST($key) == '1' || GETPOST($key) == 'on') ? 1 : 0);
		} elseif ($object->fields[$key]['type'] == 'reference') {
			$tmparraykey = array_keys($object->param_list);
			$value = $tmparraykey[GETPOST($key)].','.GETPOST($key.'2');
		} elseif (preg_match('/^chkbxlst:(.*)/', $object->fields[$key]['type'])) {
			$value = '';
			$values_arr = GETPOST($key, 'array');
			if (!empty($values_arr)) {
				$value = implode(',', $values_arr);
			}
		} else {
			if ($key == 'lang') {
				$value = GETPOST($key, 'aZ09') ?GETPOST($key, 'aZ09') : "";
			} else {
				$value = GETPOST($key, 'alphanohtml');
			}
		}
		if (preg_match('/^integer:/i', $object->fields[$key]['type']) && $value == '-1') {
			$value = ''; // This is an implicit foreign key field
		}
		if (!empty($object->fields[$key]['foreignkey']) && $value == '-1') {
			$value = ''; // This is an explicit foreign key field
		}

		//var_dump($key.' '.$value.' '.$object->fields[$key]['type']);
		$object->$key = $value;
		if (!empty($val['notnull']) && $val['notnull'] > 0 && $object->$key == '' && isset($val['default']) && $val['default'] == '(PROV)') {
			$object->$key = '(PROV)';
		}
		if (!empty($val['notnull']) && $val['notnull'] > 0 && $object->$key == '' && !isset($val['default'])) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}

		if($key == 'label' && !$object->verifLabel($object->label)) {
			$error++;
			setEventMessages($langs->trans("ErrorLabelExisting", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}

		// Validation of fields values
		if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2 || !empty($conf->global->MAIN_ACTIVATE_VALIDATION_RESULT)) {
			if (!$error && !empty($val['validate']) && is_callable(array($object, 'validateField'))) {
				if (!$object->validateField($object->fields, $key, $value)) {
					$error++;
				}
			}
		}
	}

	// Fill array 'array_options' with data from add form
	if (!$error) {
		$ret = $extrafields->setOptionalsFromPost(null, $object, '', 1);
		if ($ret < 0) {
			$error++;
		}
	}

	if (!$error) {
		$db->begin();

		$result = $object->create($user);

		if ($result > 0) {
			// Creation OK
			if (isModEnabled('categorie') && method_exists($object, 'setCategories')) {
				$categories = GETPOST('categories', 'array:int');
				$object->setCategories($categories);
			}

			$urltogo = $backtopage ? str_replace('__ID__', $result, $backtopage) : $backurlforlist;
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $object->id, $urltogo); // New method to autoselect project after a New on another form object creation

			$db->commit();

			if (empty($noback)) {
				header("Location: " . $urltogo);
				exit;
			}
		} else {
			$db->rollback();

			$error++;
			// Creation KO
			if (!empty($object->errors)) {
				setEventMessages(null, $object->errors, 'errors');
			} else {
				setEventMessages($object->error, null, 'errors');
			}
			$action = 'create';
		}
	} else {
		$action = 'create';
	}
}