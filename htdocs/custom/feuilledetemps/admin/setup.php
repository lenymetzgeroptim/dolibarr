<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * \file    htdocs/modulebuilder/template/admin/setup.php
 * \ingroup feuilledetemps
 * \brief   feuilledetemps setup page.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../lib/feuilledetemps.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/extendedHoliday.class.php';

// Translations
$langs->loadLangs(array("feuilledetemps@feuilledetemps", "admin"));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('feuilledetempssetup', 'globalsetup'));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'myobject';


$error = 0;
$setupnotempty = 0;

// Set this to 1 to use the factory to manage constants. Warning, the generated module will be compatible with version v15+ only
$useFormSetup = 1;

if (!class_exists('FormSetup')) {
	// For retrocompatibility Dolibarr < 16.0
	if (floatval(DOL_VERSION) < 16.0 && !class_exists('FormSetup')) {
		require_once __DIR__.'/../backport/v16/core/class/html.formsetup.class.php';
	} else {
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
	}
}

$formSetup = new FormSetup($db);

// HEURE_MAX_JOUR
$item = $formSetup->newItem('HEURE_MAX_JOUR')->setAsString();

// HEURE_MAX_SEMAINE
$item = $formSetup->newItem('HEURE_MAX_SEMAINE')->setAsString();

// HEURE_JOUR
$item = $formSetup->newItem('HEURE_JOUR')->setAsString();

// HEURE_DEMIJOUR_NORTT
$item = $formSetup->newItem('HEURE_DEMIJOUR_NORTT')->setAsString();

// HEURE_SEMAINE
$item = $formSetup->newItem('HEURE_SEMAINE')->setAsString();

// HEURE_SEMAINE_NO_RTT
$item = $formSetup->newItem('HEURE_SEMAINE_NO_RTT')->setAsString();

// HEURE_SUP1
$item = $formSetup->newItem('HEURE_SUP1')->setAsString();

// HEURE_SUP_SUPERIOR_HEURE_MAX_SEMAINE
$item = $formSetup->newItem('HEURE_SUP_SUPERIOR_HEURE_MAX_SEMAINE')->setAsYesNo();


// Titre Congés
$formSetup->newItem('Congés')->setAsTitle();

// HOLIDAYTYPE_EXLUDED_EXPORT
$item = $formSetup->newItem('HOLIDAYTYPE_EXLUDED_EXPORT');
$holiday = new extendedHoliday($db);
$typeleaves = $holiday->getTypesNoCP(-1, -1);
$arraytypeleaves = array();
foreach ($typeleaves as $key => $val) {
	$labeltoshow = $val['label'];
	$arraytypeleaves[$val['rowid']] = $labeltoshow;
}	
$item->setAsMultiSelect($arraytypeleaves);


// Titre Couleur
$formSetup->newItem('Couleur')->setAsTitle();

// Setup conf HOLIDAY_DRAFT_COLOR
$item = $formSetup->newItem('HOLIDAY_DRAFT_COLOR');
$item->setAsColor();
$item->defaultFieldValue = '#F9E79F';

// Setup conf HOLIDAY_APPROBATION1_COLOR
$item = $formSetup->newItem('HOLIDAY_APPROBATION1_COLOR');
$item->setAsColor();
$item->defaultFieldValue = '#aed6f1';

// Setup conf HOLIDAY_APPROBATION2_COLOR
$item = $formSetup->newItem('HOLIDAY_APPROBATION2_COLOR');
$item->setAsColor();
$item->defaultFieldValue = '#aed6f1';

// Setup conf HOLIDAY_VALIDATED_COLOR
$item = $formSetup->newItem('HOLIDAY_VALIDATED_COLOR');
$item->setAsColor();
$item->defaultFieldValue = '#abebc6';

// Setup conf FDT_WEEKEND_COLOR
$item = $formSetup->newItem('FDT_WEEKEND_COLOR');
$item->setAsColor();
$item->defaultFieldValue = '#eeeeee';

// Setup conf FDT_ANTICIPE_COLOR
$item = $formSetup->newItem('FDT_ANTICIPE_COLOR');
$item->setAsColor();
$item->defaultFieldValue = '#ebdef0';

// Setup conf FDT_ANTICIPE_WEEKEND_COLOR
$item = $formSetup->newItem('FDT_ANTICIPE_WEEKEND_COLOR');
$item->setAsColor();
$item->defaultFieldValue = '#dacce1';

// Setup conf FDT_FERIE_COLOR
$item = $formSetup->newItem('FDT_FERIE_COLOR');
$item->setAsColor();
$item->defaultFieldValue = '#f4eede';


// Titre FDT
$formSetup->newItem('AutreParamFDT')->setAsTitle();

// JOUR_ANTICIPES
$item = $formSetup->newItem('JOUR_ANTICIPES')->setAsString();

// FDT_DISPLAY_COLUMN
$item = $formSetup->newItem('FDT_DISPLAY_COLUMN')->setAsYesNo();

// FDT_DECIMAL_HOUR_FORMAT
$item = $formSetup->newItem('FDT_DECIMAL_HOUR_FORMAT')->setAsYesNo();

// FDT_USE_HS_CASE
$item = $formSetup->newItem('FDT_USE_HS_CASE')->setAsYesNo();

// FDT_DISPLAY_FULL_WEEK
$item = $formSetup->newItem('FDT_DISPLAY_FULL_WEEK')->setAsYesNo();

// FDT_COLUMN_MAX_TASK_DAY
$item = $formSetup->newItem('FDT_COLUMN_MAX_TASK_DAY')->setAsString();

// FDT_USER_APPROVER
$item = $formSetup->newItem('FDT_USER_APPROVER')->setAsYesNo();

// FDT_ANTICIPE_BLOCKED
$item = $formSetup->newItem('FDT_ANTICIPE_BLOCKED')->setAsYesNo();

// FDT_DAY_FOR_NEXT_FDT
$item = $formSetup->newItem('FDT_DAY_FOR_NEXT_FDT')->setAsString();

// FDT_MANAGE_EMPLOYER
$item = $formSetup->newItem('FDT_MANAGE_EMPLOYER')->setAsYesNo();

// FDT_STATUT_HOLIDAY
$item = $formSetup->newItem('FDT_STATUT_HOLIDAY')->setAsYesNo();

// FDT_STATUT_HOLIDAY_VALIDATE_VERIF
$item = $formSetup->newItem('FDT_STATUT_HOLIDAY_VALIDATE_VERIF')->setAsYesNo();

// FDT_SHORTCUT_HOLIDAY
// $item = $formSetup->newItem('FDT_SHORTCUT_HOLIDAY')->setAsYesNo();

// FDT_GENERATE_TASK_PROJECTCREATION
$item = $formSetup->newItem('FDT_GENERATE_TASK_PROJECTCREATION')->setAsYesNo();

// FDT_SCREEN_VERIFICATION
$item = $formSetup->newItem('FDT_SCREEN_VERIFICATION')->setAsYesNo();

// FDT_SENDMAIL_FROM
$item = $formSetup->newItem('FDT_SENDMAIL_FROM')->setAsString();

// FDT_VERIF_MODIFWHENHOLIDAY
$item = $formSetup->newItem('FDT_VERIF_MODIFWHENHOLIDAY')->setAsYesNo();

// FDT_SHOW_USERADRESS
$item = $formSetup->newItem('FDT_SHOW_USERADRESS')->setAsYesNo();

// FDT_ORDER_MATRICULE
$item = $formSetup->newItem('FDT_ORDER_MATRICULE')->setAsYesNo();


// Titre FDT_STANDARD_WEEK
$formSetup->newItem('FDT_STANDARD_WEEK')->setAsTitle();

// FDT_STANDARD_WEEK_FOR_HOLIDAY
$item = $formSetup->newItem('FDT_STANDARD_WEEK_FOR_HOLIDAY')->setAsYesNo();

// Titre FDT_STANDARD_WEEK_WITH_RTT
$formSetup->newItem('FDT_STANDARD_WEEK_WITH_RTT')->setAsTitle();

// FDT_STANDARD_WEEK_MONDAY_WITH_RTT
$item = $formSetup->newItem('FDT_STANDARD_WEEK_MONDAY_WITH_RTT')->setAsString();

// FDT_STANDARD_WEEK_TUESDAY_WITH_RTT
$item = $formSetup->newItem('FDT_STANDARD_WEEK_TUESDAY_WITH_RTT')->setAsString();

// FDT_STANDARD_WEEK_WEDNESDAY_WITH_RTT
$item = $formSetup->newItem('FDT_STANDARD_WEEK_WEDNESDAY_WITH_RTT')->setAsString();

// FDT_STANDARD_WEEK_THURSDAY_WITH_RTT
$item = $formSetup->newItem('FDT_STANDARD_WEEK_THURSDAY_WITH_RTT')->setAsString();

// FDT_STANDARD_WEEK_FRIDAY_WITH_RTT
$item = $formSetup->newItem('FDT_STANDARD_WEEK_FRIDAY_WITH_RTT')->setAsString();

// FDT_STANDARD_WEEK_SATURDAY_WITH_RTT
$item = $formSetup->newItem('FDT_STANDARD_WEEK_SATURDAY_WITH_RTT')->setAsString();

// FDT_STANDARD_WEEK_SUNDAY_WITH_RTT
$item = $formSetup->newItem('FDT_STANDARD_WEEK_SUNDAY_WITH_RTT')->setAsString();

// Titre FDT_STANDARD_WEEK_NO_RTT
$formSetup->newItem('FDT_STANDARD_WEEK_NO_RTT')->setAsTitle();

// FDT_STANDARD_WEEK_MONDAY_NO_RTT
$item = $formSetup->newItem('FDT_STANDARD_WEEK_MONDAY_NO_RTT')->setAsString();

// FDT_STANDARD_WEEK_TUESDAY_NO_RTT
$item = $formSetup->newItem('FDT_STANDARD_WEEK_TUESDAY_NO_RTT')->setAsString();

// FDT_STANDARD_WEEK_WEDNESDAY_NO_RTT
$item = $formSetup->newItem('FDT_STANDARD_WEEK_WEDNESDAY_NO_RTT')->setAsString();

// FDT_STANDARD_WEEK_THURSDAY_NO_RTT
$item = $formSetup->newItem('FDT_STANDARD_WEEK_THURSDAY_NO_RTT')->setAsString();

// FDT_STANDARD_WEEK_FRIDAY_NO_RTT
$item = $formSetup->newItem('FDT_STANDARD_WEEK_FRIDAY_NO_RTT')->setAsString();

// FDT_STANDARD_WEEK_SATURDAY_NO_RTT
$item = $formSetup->newItem('FDT_STANDARD_WEEK_SATURDAY_NO_RTT')->setAsString();

// FDT_STANDARD_WEEK_SUNDAY_NO_RTT
$item = $formSetup->newItem('FDT_STANDARD_WEEK_SUNDAY_NO_RTT')->setAsString();


$setupnotempty += count($formSetup->items);


$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);


/*
 * Actions
 */

// For retrocompatibility Dolibarr < 15.0
if ( versioncompare(explode('.', DOL_VERSION), array(15)) < 0 && $action == 'update' && !empty($user->admin)) {
	$formSetup->saveConfFromPost();
}

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask') {
	$maskconst = GETPOST('maskconst', 'aZ09');
	$maskvalue = GETPOST('maskvalue', 'alpha');

	if ($maskconst && preg_match('/_MASK$/', $maskconst)) {
		$res = dolibarr_set_const($db, $maskconst, $maskvalue, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'specimen') {
	$modele = GETPOST('module', 'alpha');
	$tmpobjectkey = GETPOST('object');

	$tmpobject = new $tmpobjectkey($db);
	$tmpobject->initAsSpecimen();

	// Search template files
	$file = ''; $classname = ''; $filefound = 0;
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir."core/modules/feuilledetemps/doc/pdf_".$modele."_".strtolower($tmpobjectkey).".modules.php", 0);
		if (file_exists($file)) {
			$filefound = 1;
			$classname = "pdf_".$modele."_".strtolower($tmpobjectkey);
			break;
		}
	}

	if ($filefound) {
		require_once $file;

		$module = new $classname($db);

		if ($module->write_file($tmpobject, $langs) > 0) {
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=feuilledetemps-".strtolower($tmpobjectkey)."&file=SPECIMEN.pdf");
			return;
		} else {
			setEventMessages($module->error, null, 'errors');
			dol_syslog($module->error, LOG_ERR);
		}
	} else {
		setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
} elseif ($action == 'setmod') {
	// TODO Check if numbering module chosen can be activated by calling method canBeActivated
	$tmpobjectkey = GETPOST('object');
	if (!empty($tmpobjectkey)) {
		$constforval = 'feuilledetemps_'.strtoupper($tmpobjectkey)."_ADDON";
		dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);
	}
} elseif ($action == 'set') {
	// Activate a model
	$ret = addDocumentModel($value, $type, $label, $scandir);
} elseif ($action == 'del') {
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$tmpobjectkey = GETPOST('object');
		if (!empty($tmpobjectkey)) {
			$constforval = 'feuilledetemps_'.strtoupper($tmpobjectkey).'_ADDON_PDF';
			if (getDolGlobalString($constforval) == "$value") {
				dolibarr_del_const($db, $constforval, $conf->entity);
			}
		}
	}
} elseif ($action == 'setdoc') {
	// Set or unset default model
	$tmpobjectkey = GETPOST('object');
	if (!empty($tmpobjectkey)) {
		$constforval = 'feuilledetemps_'.strtoupper($tmpobjectkey).'_ADDON_PDF';
		if (dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity)) {
			// The constant that was read before the new set
			// We therefore requires a variable to have a coherent view
			$conf->global->{$constforval} = $value;
		}

		// We disable/enable the document template (into llx_document_model table)
		$ret = delDocumentModel($value, $type);
		if ($ret > 0) {
			$ret = addDocumentModel($value, $type, $label, $scandir);
		}
	}
} elseif ($action == 'unsetdoc') {
	$tmpobjectkey = GETPOST('object');
	if (!empty($tmpobjectkey)) {
		$constforval = 'feuilledetemps_'.strtoupper($tmpobjectkey).'_ADDON_PDF';
		dolibarr_del_const($db, $constforval, $conf->entity);
	}
}



/*
 * View
 */

$form = new Form($db);

$help_url = '';
$page_name = "feuilledetempsSetup";

llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = feuilledetempsAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($page_name), -1, "feuilledetemps@feuilledetemps");

// Setup page goes here
// echo '<span class="opacitymedium">'.$langs->trans("feuilledetempsSetupPage").'</span><br><br>';


if ($action == 'edit') {
	print $formSetup->generateOutput(true);
	print '<br>';
} elseif (!empty($formSetup->items)) {
	print $formSetup->generateOutput();
	print '<div class="tabsAction">';
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>';
	print '</div>';
} else {
	print '<br>'.$langs->trans("NothingToSetup");
}


$moduledir = 'feuilledetemps';
$myTmpObjects = array();
// TODO Scan list of objects
$myTmpObjects['myobject'] = array('label'=>'MyObject', 'includerefgeneration'=>0, 'includedocgeneration'=>0, 'class'=>'MyObject');


foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
	if ($myTmpObjectArray['includerefgeneration']) {
		/*
		 * Orders Numbering model
		 */
		$setupnotempty++;

		print load_fiche_titre($langs->trans("NumberingModules", $myTmpObjectArray['label']), '', '');

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Name").'</td>';
		print '<td>'.$langs->trans("Description").'</td>';
		print '<td class="nowrap">'.$langs->trans("Example").'</td>';
		print '<td class="center" width="60">'.$langs->trans("Status").'</td>';
		print '<td class="center" width="16">'.$langs->trans("ShortInfo").'</td>';
		print '</tr>'."\n";

		clearstatcache();

		foreach ($dirmodels as $reldir) {
			$dir = dol_buildpath($reldir."core/modules/".$moduledir);

			if (is_dir($dir)) {
				$handle = opendir($dir);
				if (is_resource($handle)) {
					while (($file = readdir($handle)) !== false) {
						if (strpos($file, 'mod_'.strtolower($myTmpObjectKey).'_') === 0 && substr($file, dol_strlen($file) - 3, 3) == 'php') {
							$file = substr($file, 0, dol_strlen($file) - 4);

							require_once $dir.'/'.$file.'.php';

							$module = new $file($db);

							// Show modules according to features level
							if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
								continue;
							}
							if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
								continue;
							}

							if ($module->isEnabled()) {
								dol_include_once('/'.$moduledir.'/class/'.strtolower($myTmpObjectKey).'.class.php');

								print '<tr class="oddeven"><td>'.$module->name."</td><td>\n";
								print $module->info();
								print '</td>';

								// Show example of numbering model
								print '<td class="nowrap">';
								$tmp = $module->getExample();
								if (preg_match('/^Error/', $tmp)) {
									$langs->load("errors");
									print '<div class="error">'.$langs->trans($tmp).'</div>';
								} elseif ($tmp == 'NotConfigured') {
									print $langs->trans($tmp);
								} else {
									print $tmp;
								}
								print '</td>'."\n";

								print '<td class="center">';
								$constforvar = 'feuilledetemps_'.strtoupper($myTmpObjectKey).'_ADDON';
								if (getDolGlobalString($constforvar) == $file) {
									print img_picto($langs->trans("Activated"), 'switch_on');
								} else {
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&token='.newToken().'&object='.strtolower($myTmpObjectKey).'&value='.urlencode($file).'">';
									print img_picto($langs->trans("Disabled"), 'switch_off');
									print '</a>';
								}
								print '</td>';

								$nameofclass = $myTmpObjectArray['class'];
								$mytmpinstance = new $nameofclass($db);
								$mytmpinstance->initAsSpecimen();

								// Info
								$htmltooltip = '';
								$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';

								$nextval = $module->getNextValue($mytmpinstance);
								if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
									$htmltooltip .= ''.$langs->trans("NextValue").': ';
									if ($nextval) {
										if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured') {
											$nextval = $langs->trans($nextval);
										}
										$htmltooltip .= $nextval.'<br>';
									} else {
										$htmltooltip .= $langs->trans($module->error).'<br>';
									}
								}

								print '<td class="center">';
								print $form->textwithpicto('', $htmltooltip, 1, 0);
								print '</td>';

								print "</tr>\n";
							}
						}
					}
					closedir($handle);
				}
			}
		}
		print "</table><br>\n";
	}

	if ($myTmpObjectArray['includedocgeneration']) {
		/*
		 * Document templates generators
		 */
		$setupnotempty++;
		$type = strtolower($myTmpObjectKey);

		print load_fiche_titre($langs->trans("DocumentModules", $myTmpObjectKey), '', '');

		// Load array def with activated templates
		$def = array();
		$sql = "SELECT nom";
		$sql .= " FROM ".MAIN_DB_PREFIX."document_model";
		$sql .= " WHERE type = '".$db->escape($type)."'";
		$sql .= " AND entity = ".$conf->entity;
		$resql = $db->query($sql);
		if ($resql) {
			$i = 0;
			$num_rows = $db->num_rows($resql);
			while ($i < $num_rows) {
				$array = $db->fetch_array($resql);
				array_push($def, $array[0]);
				$i++;
			}
		} else {
			dol_print_error($db);
		}

		print "<table class=\"noborder\" width=\"100%\">\n";
		print "<tr class=\"liste_titre\">\n";
		print '<td>'.$langs->trans("Name").'</td>';
		print '<td>'.$langs->trans("Description").'</td>';
		print '<td class="center" width="60">'.$langs->trans("Status")."</td>\n";
		print '<td class="center" width="60">'.$langs->trans("Default")."</td>\n";
		print '<td class="center" width="38">'.$langs->trans("ShortInfo").'</td>';
		print '<td class="center" width="38">'.$langs->trans("Preview").'</td>';
		print "</tr>\n";

		clearstatcache();

		foreach ($dirmodels as $reldir) {
			foreach (array('', '/doc') as $valdir) {
				$realpath = $reldir."core/modules/".$moduledir.$valdir;
				$dir = dol_buildpath($realpath);

				if (is_dir($dir)) {
					$handle = opendir($dir);
					if (is_resource($handle)) {
						while (($file = readdir($handle)) !== false) {
							$filelist[] = $file;
						}
						closedir($handle);
						arsort($filelist);

						foreach ($filelist as $file) {
							if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file)) {
								if (file_exists($dir.'/'.$file)) {
									$name = substr($file, 4, dol_strlen($file) - 16);
									$classname = substr($file, 0, dol_strlen($file) - 12);

									require_once $dir.'/'.$file;
									$module = new $classname($db);

									$modulequalified = 1;
									if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
										$modulequalified = 0;
									}
									if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
										$modulequalified = 0;
									}

									if ($modulequalified) {
										print '<tr class="oddeven"><td width="100">';
										print (empty($module->name) ? $name : $module->name);
										print "</td><td>\n";
										if (method_exists($module, 'info')) {
											print $module->info($langs);
										} else {
											print $module->description;
										}
										print '</td>';

										// Active
										if (in_array($name, $def)) {
											print '<td class="center">'."\n";
											print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&token='.newToken().'&value='.urlencode($name).'">';
											print img_picto($langs->trans("Enabled"), 'switch_on');
											print '</a>';
											print '</td>';
										} else {
											print '<td class="center">'."\n";
											print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
											print "</td>";
										}

										// Default
										print '<td class="center">';
										$constforvar = 'feuilledetemps_'.strtoupper($myTmpObjectKey).'_ADDON_PDF';
										if (getDolGlobalString($constforvar) == $name) {
											//print img_picto($langs->trans("Default"), 'on');
											// Even if choice is the default value, we allow to disable it. Replace this with previous line if you need to disable unset
											print '<a href="'.$_SERVER["PHP_SELF"].'?action=unsetdoc&token='.newToken().'&object='.urlencode(strtolower($myTmpObjectKey)).'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'&amp;type='.urlencode($type).'" alt="'.$langs->trans("Disable").'">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
										} else {
											print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&token='.newToken().'&object='.urlencode(strtolower($myTmpObjectKey)).'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
										}
										print '</td>';

										// Info
										$htmltooltip = ''.$langs->trans("Name").': '.$module->name;
										$htmltooltip .= '<br>'.$langs->trans("Type").': '.($module->type ? $module->type : $langs->trans("Unknown"));
										if ($module->type == 'pdf') {
											$htmltooltip .= '<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
										}
										$htmltooltip .= '<br>'.$langs->trans("Path").': '.preg_replace('/^\//', '', $realpath).'/'.$file;

										$htmltooltip .= '<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
										$htmltooltip .= '<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
										$htmltooltip .= '<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang, 1, 1);

										print '<td class="center">';
										print $form->textwithpicto('', $htmltooltip, 1, 0);
										print '</td>';

										// Preview
										print '<td class="center">';
										if ($module->type == 'pdf') {
											$newname = preg_replace('/_'.preg_quote(strtolower($myTmpObjectKey), '/').'/', '', $name);
											print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.urlencode($newname).'&object='.urlencode($myTmpObjectKey).'">'.img_object($langs->trans("Preview"), 'pdf').'</a>';
										} else {
											print img_object($langs->trans("PreviewNotAvailable"), 'generic');
										}
										print '</td>';

										print "</tr>\n";
									}
								}
							}
						}
					}
				}
			}
		}

		print '</table>';
	}
}

if (empty($setupnotempty)) {
	print '<br>'.$langs->trans("NothingToSetup");
}

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
