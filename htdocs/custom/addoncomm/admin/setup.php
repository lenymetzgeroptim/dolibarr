<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 FADEL Soufiane <s.fadel@optim-industries.fr>
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
 * \file    addoncomm/admin/setup.php
 * \ingroup addoncomm
 * \brief   AddOnComm setup page.
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
require_once '../lib/addoncomm.lib.php';
//require_once "../class/myclass.class.php";

// Translations
$langs->loadLangs(array("admin", "addoncomm@addoncomm"));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('addoncommsetup', 'globalsetup'));

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

$arrayofparameters = array(
	'ADDONCOMM_MYPARAM1'=>array('type'=>'string', 'css'=>'minwidth500' ,'enabled'=>1),
	'ADDONCOMM_MYPARAM2'=>array('type'=>'textarea','enabled'=>1),
	//'ADDONCOMM_MYPARAM3'=>array('type'=>'category:'.Categorie::TYPE_CUSTOMER, 'enabled'=>1),
	//'ADDONCOMM_MYPARAM4'=>array('type'=>'emailtemplate:thirdparty', 'enabled'=>1),
	//'ADDONCOMM_MYPARAM5'=>array('type'=>'yesno', 'enabled'=>1),
	//'ADDONCOMM_MYPARAM5'=>array('type'=>'thirdparty_type', 'enabled'=>1),
	//'ADDONCOMM_MYPARAM6'=>array('type'=>'securekey', 'enabled'=>1),
	//'ADDONCOMM_MYPARAM7'=>array('type'=>'product', 'enabled'=>1),
);

$error = 0;
$setupnotempty = 0;

// Set this to 1 to use the factory to manage constants. Warning, the generated module will be compatible with version v15+ only
$useFormSetup = 0;
// Convert arrayofparameter into a formSetup object
if ($useFormSetup && (float) DOL_VERSION >= 15) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
	$formSetup = new FormSetup($db);

	// you can use the param convertor
	$formSetup->addItemsFromParamsArray($arrayofparameters);

	// or use the new system see exemple as follow (or use both because you can ;-) )

	/*
	// Hôte
	$item = $formSetup->newItem('NO_PARAM_JUST_TEXT');
	$item->fieldOverride = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'];
	$item->cssClass = 'minwidth500';

	// Setup conf ADDONCOMM_MYPARAM1 as a simple string input
	$item = $formSetup->newItem('ADDONCOMM_MYPARAM1');

	// Setup conf ADDONCOMM_MYPARAM1 as a simple textarea input but we replace the text of field title
	$item = $formSetup->newItem('ADDONCOMM_MYPARAM2');
	$item->nameText = $item->getNameText().' more html text ';

	// Setup conf ADDONCOMM_MYPARAM3
	$item = $formSetup->newItem('ADDONCOMM_MYPARAM3');
	$item->setAsThirdpartyType();

	// Setup conf ADDONCOMM_MYPARAM4 : exemple of quick define write style
	$formSetup->newItem('ADDONCOMM_MYPARAM4')->setAsYesNo();

	// Setup conf ADDONCOMM_MYPARAM5
	$formSetup->newItem('ADDONCOMM_MYPARAM5')->setAsEmailTemplate('thirdparty');

	// Setup conf ADDONCOMM_MYPARAM6
	$formSetup->newItem('ADDONCOMM_MYPARAM6')->setAsSecureKey()->enabled = 0; // disabled

	// Setup conf ADDONCOMM_MYPARAM7
	$formSetup->newItem('ADDONCOMM_MYPARAM7')->setAsProduct();
	*/

	$setupnotempty = count($formSetup->items);
}


$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask') {
	$maskconst = GETPOST('maskconst', 'alpha');
	$maskvalue = GETPOST('maskvalue', 'alpha');

	if ($maskconst) {
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
		$file = dol_buildpath($reldir."core/modules/addoncomm/doc/pdf_".$modele."_".strtolower($tmpobjectkey).".modules.php", 0);
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
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=addoncomm-".strtolower($tmpobjectkey)."&file=SPECIMEN.pdf");
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
		$constforval = 'ADDONCOMM_'.strtoupper($tmpobjectkey)."_ADDON";
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
			$constforval = 'ADDONCOMM_'.strtoupper($tmpobjectkey).'_ADDON_PDF';
			if ($conf->global->$constforval == "$value") {
				dolibarr_del_const($db, $constforval, $conf->entity);
			}
		}
	}
} elseif ($action == 'setdoc') {
	// Set or unset default model
	$tmpobjectkey = GETPOST('object');
	if (!empty($tmpobjectkey)) {
		$constforval = 'ADDONCOMM_'.strtoupper($tmpobjectkey).'_ADDON_PDF';
		if (dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity)) {
			// The constant that was read before the new set
			// We therefore requires a variable to have a coherent view
			$conf->global->$constforval = $value;
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
		$constforval = 'ADDONCOMM_'.strtoupper($tmpobjectkey).'_ADDON_PDF';
		dolibarr_del_const($db, $constforval, $conf->entity);
	}
}



/*
 * View
 */

$form = new Form($db);

$help_url = '';
$page_name = "Configuration des Indicateurs Commerciaux";

llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = addoncommAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($page_name), -1, "addoncomm@addoncomm");

// Setup page goes here
echo '<span class="opacitymedium">'.$langs->trans("Page de configuration des Indicateurs Commerciaux").'</span><br><br>';


if ($action == 'edit') {
	if ($useFormSetup && (float) DOL_VERSION >= 15) {
		print $formSetup->generateOutput(true);
	} else {
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="update">';

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

		foreach ($arrayofparameters as $constname => $val) {
			if ($val['enabled']==1) {
				$setupnotempty++;
				print '<tr class="oddeven"><td>';
				$tooltiphelp = (($langs->trans($constname . 'Tooltip') != $constname . 'Tooltip') ? $langs->trans($constname . 'Tooltip') : '');
				print '<span id="helplink'.$constname.'" class="spanforparamtooltip">'.$form->textwithpicto($langs->trans($constname), $tooltiphelp, 1, 'info', '', 0, 3, 'tootips'.$constname).'</span>';
				print '</td><td>';

				if ($val['type'] == 'textarea') {
					print '<textarea class="flat" name="'.$constname.'" id="'.$constname.'" cols="50" rows="5" wrap="soft">' . "\n";
					print $conf->global->{$constname};
					print "</textarea>\n";
				} elseif ($val['type']== 'html') {
					require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
					$doleditor = new DolEditor($constname, $conf->global->{$constname}, '', 160, 'dolibarr_notes', '', false, false, $conf->fckeditor->enabled, ROWS_5, '90%');
					$doleditor->Create();
				} elseif ($val['type'] == 'yesno') {
					print $form->selectyesno($constname, $conf->global->{$constname}, 1);
				} elseif (preg_match('/emailtemplate:/', $val['type'])) {
					include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
					$formmail = new FormMail($db);

					$tmp = explode(':', $val['type']);
					$nboftemplates = $formmail->fetchAllEMailTemplate($tmp[1], $user, null, 1); // We set lang=null to get in priority record with no lang
					//$arraydefaultmessage = $formmail->getEMailTemplate($db, $tmp[1], $user, null, 0, 1, '');
					$arrayofmessagename = array();
					if (is_array($formmail->lines_model)) {
						foreach ($formmail->lines_model as $modelmail) {
							//var_dump($modelmail);
							$moreonlabel = '';
							if (!empty($arrayofmessagename[$modelmail->label])) {
								$moreonlabel = ' <span class="opacitymedium">(' . $langs->trans("SeveralLangugeVariatFound") . ')</span>';
							}
							// The 'label' is the key that is unique if we exclude the language
							$arrayofmessagename[$modelmail->id] = $langs->trans(preg_replace('/\(|\)/', '', $modelmail->label)) . $moreonlabel;
						}
					}
					print $form->selectarray($constname, $arrayofmessagename, $conf->global->{$constname}, 'None', 0, 0, '', 0, 0, 0, '', '', 1);
				} elseif (preg_match('/category:/', $val['type'])) {
					require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
					require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
					$formother = new FormOther($db);

					$tmp = explode(':', $val['type']);
					print img_picto('', 'category', 'class="pictofixedwidth"');
					print $formother->select_categories($tmp[1],  $conf->global->{$constname}, $constname, 0, $langs->trans('CustomersProspectsCategoriesShort'));
				} elseif (preg_match('/thirdparty_type/', $val['type'])) {
					require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
					$formcompany = new FormCompany($db);
					print $formcompany->selectProspectCustomerType($conf->global->{$constname}, $constname);
				} elseif ($val['type'] == 'securekey') {
					print '<input required="required" type="text" class="flat" id="'.$constname.'" name="'.$constname.'" value="'.(GETPOST($constname, 'alpha') ?GETPOST($constname, 'alpha') : $conf->global->{$constname}).'" size="40">';
					if (!empty($conf->use_javascript_ajax)) {
						print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_token'.$constname.'" class="linkobject"');
					}
					if (!empty($conf->use_javascript_ajax)) {
						print "\n".'<script type="text/javascript">';
						print '$(document).ready(function () {
                        $("#generate_token'.$constname.'").click(function() {
                	        $.get( "'.DOL_URL_ROOT.'/core/ajax/security.php", {
                		      action: \'getrandompassword\',
                		      generic: true
    				        },
    				        function(token) {
    					       $("#'.$constname.'").val(token);
            				});
                         });
                    });';
						print '</script>';
					}
				} elseif ($val['type'] == 'product') {
					if (!empty($conf->product->enabled) || !empty($conf->service->enabled)) {
						$selected = (empty($conf->global->$constname) ? '' : $conf->global->$constname);
						$form->select_produits($selected, $constname, '', 0);
					}
				} else {
					print '<input name="'.$constname.'"  class="flat '.(empty($val['css']) ? 'minwidth200' : $val['css']).'" value="'.$conf->global->{$constname}.'">';
				}
				print '</td></tr>';
			}
		}
		print '</table>';

		print '<br><div class="center">';
		print '<input class="button button-save" type="submit" value="'.$langs->trans("Save").'">';
		print '</div>';

		print '</form>';
	}

	print '<br>';
} else {
	if ($useFormSetup && (float) DOL_VERSION >= 15) {
		if (!empty($formSetup->items)) {
			print $formSetup->generateOutput();
		}
	} else {
		if (!empty($arrayofparameters)) {
			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

			foreach ($arrayofparameters as $constname => $val) {
				if ($val['enabled']==1) {
					$setupnotempty++;
					print '<tr class="oddeven"><td>';
					$tooltiphelp = (($langs->trans($constname . 'Tooltip') != $constname . 'Tooltip') ? $langs->trans($constname . 'Tooltip') : '');
					print $form->textwithpicto($langs->trans($constname), $tooltiphelp);
					print '</td><td>';

					if ($val['type'] == 'textarea') {
						print dol_nl2br($conf->global->{$constname});
					} elseif ($val['type']== 'html') {
						print  $conf->global->{$constname};
					} elseif ($val['type'] == 'yesno') {
						print ajax_constantonoff($constname);
					} elseif (preg_match('/emailtemplate:/', $val['type'])) {
						include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
						$formmail = new FormMail($db);

						$tmp = explode(':', $val['type']);

						$template = $formmail->getEMailTemplate($db, $tmp[1], $user, $langs, $conf->global->{$constname});
						if ($template<0) {
							setEventMessages(null, $formmail->errors, 'errors');
						}
						print $langs->trans($template->label);
					} elseif (preg_match('/category:/', $val['type'])) {
						$c = new Categorie($db);
						$result = $c->fetch($conf->global->{$constname});
						if ($result < 0) {
							setEventMessages(null, $c->errors, 'errors');
						} elseif ($result > 0 ) {
							$ways = $c->print_all_ways(' &gt;&gt; ', 'none', 0, 1); // $ways[0] = "ccc2 >> ccc2a >> ccc2a1" with html formated text
							$toprint = array();
							foreach ($ways as $way) {
								$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories"' . ($c->color ? ' style="background: #' . $c->color . ';"' : ' style="background: #bbb"') . '>' . $way . '</li>';
							}
							print '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">' . implode(' ', $toprint) . '</ul></div>';
						}
					} elseif (preg_match('/thirdparty_type/', $val['type'])) {
						if ($conf->global->{$constname}==2) {
							print $langs->trans("Prospect");
						} elseif ($conf->global->{$constname}==3) {
							print $langs->trans("ProspectCustomer");
						} elseif ($conf->global->{$constname}==1) {
							print $langs->trans("Customer");
						} elseif ($conf->global->{$constname}==0) {
							print $langs->trans("NorProspectNorCustomer");
						}
					} elseif ($val['type'] == 'product') {
						$product = new Product($db);
						$resprod = $product->fetch($conf->global->{$constname});
						if ($resprod > 0) {
							print $product->ref;
						} elseif ($resprod < 0) {
							setEventMessages(null, $object->errors, "errors");
						}
					} else {
						print $conf->global->{$constname};
					}
					print '</td></tr>';
				}
			}

			print '</table>';
		}
	}

	if ($setupnotempty) {
		print '<div class="tabsAction">';
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>';
		print '</div>';
	} else {
		print '<br>'.$langs->trans("NothingToSetup");
	}
}


$moduledir = 'addoncomm';
$myTmpObjects = array();
$myTmpObjects['MyObject'] = array('includerefgeneration'=>0, 'includedocgeneration'=>0);


foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
	if ($myTmpObjectKey == 'MyObject') {
		continue;
	}
	if ($myTmpObjectArray['includerefgeneration']) {
		/*
		 * Orders Numbering model
		 */
		$setupnotempty++;

		print load_fiche_titre($langs->trans("NumberingModules", $myTmpObjectKey), '', '');

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
							if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) {
								continue;
							}
							if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) {
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
								$constforvar = 'ADDONCOMM_'.strtoupper($myTmpObjectKey).'_ADDON';
								if ($conf->global->$constforvar == $file) {
									print img_picto($langs->trans("Activated"), 'switch_on');
								} else {
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&token='.newToken().'&object='.strtolower($myTmpObjectKey).'&value='.urlencode($file).'">';
									print img_picto($langs->trans("Disabled"), 'switch_off');
									print '</a>';
								}
								print '</td>';

								$mytmpinstance = new $myTmpObjectKey($db);
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
									if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) {
										$modulequalified = 0;
									}
									if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) {
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
										$constforvar = 'ADDONCOMM_'.strtoupper($myTmpObjectKey).'_ADDON';
										if ($conf->global->$constforvar == $name) {
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


// Custom for color domain to setup
$action     = GETPOST('action', 'aZ09');
$domainColors = GETPOST('domain_colors');
print '
<style>
    .container {
        font-family: "Arial", sans-serif;
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        // max-width: 1500px;
        margin: 20px auto;
		
    }

    h1 {
        font-size: 16px;
        color: #3c3f41;
        text-align: center;
        margin-bottom: 20px;
        font-weight: bold;
    }

    .agency-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 20px;
    }


    .agency-section {
        background-color: #fff;
        border: 1px solid #dfe4ea;
        border-radius: 6px;
        padding: 15px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .agency-section h2 {
        font-size: 12px;
        color: #2c3e50;
        margin-bottom: 15px;
        font-weight: normal;
    }

   
    .domain-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        align-items: center;
    }

    .domain-item label {
        font-size: 14px;
        color: #34495e;
        font-weight: bold;
        flex: 1;
    }

    .domain-item input[type="color"], .domain-item input[type="text"] {
        width: 28%;
        height: 35px;
        font-size: 14px;
        border: 1px solid #ccc;
        border-radius: 4px;
        padding: 5px;
        background-color: #fff;
        box-sizing: border-box;
    }

    /* Champ de texte hexadécimal avec des bordures douces */
    .domain-item input[type="text"] {
        text-transform: uppercase;
    }

    /* Bouton de soumission stylisé */
    .save-button {
        // width: 100%;
		float: right;
        padding: 12px;
        background-color: rgb(40, 80, 139);
        border: none;
        color: #fff;
        font-size: 16px;
        text-transform: uppercase;
        border-radius: 5px;
        cursor: pointer;
        // margin-top: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: background-color 0.3s ease;
		margin-top: 50px;
		margin-bottom: 50px;
    }

    .save-button:hover {
        background-color: #177ca1;
    }

    .save-button:active {
        background-color: #166fa0;
    }

    /* Styles pour une meilleure présentation sur petits écrans */
    @media screen and (max-width: 768px) {
        .agency-grid {
            grid-template-columns: 1fr;
        }

        .domain-item input[type="color"], .domain-item input[type="text"] {
            width: 100%;
            margin-bottom: 10px;
			font-size: 12px;
        }


        .save-button {
            width: auto;
            margin: 10px auto;
			margin-top: 30px;
        }
    }
</style>
';

/*
 * Actions
 */

if($action == 'color_domain') {
	// var_dump($domainColors);
	// Parcourir les domaines et mettre à jour les couleurs
	foreach ($domainColors as $agencyData => $domains) {
		foreach ($domains as $domain => $colorData) {
			// Validation des données
			$color = isset($colorData['color']) ? $db->escape($colorData['color']) : '';
			$text = isset($colorData['text']) ? $db->escape($colorData['text']) : '';
	
			$agencyDataParts = explode('_', $agencyData);
	
			$agenceId = $db->escape($agencyDataParts[1]);
			$agency = $db->escape($agencyDataParts[0]);
	
			// Préparation de la requête SQL
			$update_sql = "UPDATE " . MAIN_DB_PREFIX . "projet_extrafields 
							SET color_domain = '$color'
							WHERE agenceconcerne = '$agenceId'
							AND domaine = '" . $db->escape($domain) . "'";
	
			// Debug SQL
			dol_syslog("Requête exécutée : $update_sql");
	
			// Exécution de la requête
			$resql_update = $db->query($update_sql);
	
			// Vérification du résultat
			if (!$resql_update) {
				dol_print_error($db, "Erreur lors de la mise à jour du champ color_domain pour l'agence '$agency' et le domaine '$domain'");
			}
		}
	}
}

$sql = "SELECT se.code as agence, pe.agenceconcerne as agence_id ,pe.domaine, pe.color_domain as color";
$sql .= "  FROM ".MAIN_DB_PREFIX."projet as p";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe on p.rowid = pe.fk_object";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid = pe.agenceconcerne";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields as se on s.rowid = se.fk_object";
// $sql .= " WHERE pe.agenceconcerne NOT LIKE '%,%' AND pe.agenceconcerne REGEXP '^[0-9]+$'";
$sql .= " GROUP BY agence, pe.agenceconcerne, pe.domaine";

$resql = $db->query($sql);

if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	while ($i < $num) {
		$obj = $db->fetch_object($resql);
		if ($obj) {
			$agences[] = $obj;
			}
		
		$i++;
	}
	$db->free($resql);
} else {
	dol_print_error($db);
}

$domain_colors = []; 

foreach ($agences as $item) {
    

    // Découper les clés d'agences en cas de valeurs multiples
    $agencyKeys = explode(',', $item->agence_id);

    // Si plusieurs agences, il faut parcourir chaque clé et assigner une couleur
    // foreach ($agencyKeys as $key) {
        // Créer un nom unique pour chaque agence dans le tableau des couleurs
        $agenceName = (sizeof($agencyKeys) > 1) ? 'Agence multiple' : $item->agence;
	// }
        // Assignation de la couleur (ou couleur par défaut si non spécifiée)
        $domain_colors[$agenceName.'_'.implode(',', $agencyKeys)][$item->domaine] = !empty($item->color) ? $item->color : '#000000';
    
}

krsort($domain_colors);
/*
 * View
 */

 print '<div class="container" id="color_config">';
 print '<span class="fas fas fa-palette" style="margin-right: 8px; font-size: 14px; color: #333;padding-bottom:30px;"></span>';
 print '<span style="font-size: 18px; color: #333;">' . $langs->trans("Personnaliser les Couleurs par Domaine et Agence - En cours") . '</span>';

    print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'#color_config">'."\n";
	// print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="color_domain">';

// Grille des agences : afficher trois colonnes
print '<div class="agency-grid">';

foreach ($domain_colors as $agencyData => $domains) {
	$agencyDataParts = explode('_', $agencyData);
	$agenceId = $db->escape($agencyDataParts[1]);
	$agency = $db->escape($agencyDataParts[0]);

    print '
        <div class="agency-section">
            <h1>' . htmlspecialchars($agency) . '</h1>';

    // Afficher les domaines pour chaque agence
	foreach ($domains as $domain => $color) {
        print '
            <div class="domain-item">
                <label for="color_' . htmlspecialchars($agencyData) . '_' . htmlspecialchars($domain) . '">' . htmlspecialchars($domain) . '</label>
                <input type="color" id="color_' . htmlspecialchars($agencyData) . '_' . htmlspecialchars($domain) . '" name="domain_colors[' . htmlspecialchars($agencyData) . '][' . htmlspecialchars($domain) . '][color]" class="color-picker" value="' . htmlspecialchars($color) . '">
                <input type="text" id="text_' . htmlspecialchars($agencyData) . '_' . htmlspecialchars($domain) . '" name="domain_colors[' . htmlspecialchars($agencyData) . '][' . htmlspecialchars($domain) . '][text]" class="color-picker" value="' . htmlspecialchars($color) . '" placeholder="#FFFFFF">
            </div>';
    }


    print '</div>'; // Fin de la section de l'agence
}

print '</div>'; // Fin de la grille des agences

print '
    <button type="submit" class="save-button">Enregistrer les couleurs</button>
</form>
</div>';
// Script JavaScript pour la synchronisation couleur/texte
print '<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Sélectionner tous les champs de couleur et de texte
        const colorInputs = document.querySelectorAll("input[type=\'color\']");
        const textInputs = document.querySelectorAll("input[type=\'text\']");
        
        // Synchroniser les valeurs entre couleur et texte
        colorInputs.forEach(input => {
            input.addEventListener("input", function() {
                const textInput = document.getElementById("text_" + this.id.split("_").slice(1).join("_"));
                if (textInput) {
                    textInput.value = this.value;
                }
            });
        });

        textInputs.forEach(input => {
            input.addEventListener("input", function() {
                const colorInput = document.getElementById("color_" + this.id.split("_").slice(1).join("_"));
                if (colorInput) {
                    colorInput.value = this.value;
                }
            });
        });
    });

</script>';
print '<br>';

if (empty($setupnotempty)) {
	print '<br>'.$langs->trans("NothingToSetup");
}

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
