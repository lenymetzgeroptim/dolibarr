<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2018	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Benoit Mortier			<benoit.mortier@opensides.be>
 * Copyright (C) 2005-2017	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2016	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2011-2021	Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2011		Remy Younes				<ryounes@gmail.com>
 * Copyright (C) 2012-2015	Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2012		Christophe Battarel		<christophe.battarel@ltairis.fr>
 * Copyright (C) 2011-2021	Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2015		Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2016		Raphaël Doursenaud		<rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2019-2020  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2020		Open-Dsi				<support@open-dsi.fr>
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
 *	    \file       htdocs/admin/dict.php
 *		\ingroup    setup
 *		\brief      Page to administer data tables
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';


// Load translation files required by the page
$langs->loadLangs(array("gpec@gpec", "errors"));

$action = GETPOST('action', 'alpha') ?GETPOST('action', 'alpha') : 'view';
$confirm = GETPOST('confirm', 'alpha');
$rowid = GETPOST('rowid', 'alpha');

$allowed = $user->rights->gpec->gpec->modify_competence;
if (!$allowed) {
	accessforbidden();
}

// Security check
if (empty($conf->gpec->enabled)) {
	accessforbidden('Module non activé');
}

$acts = array(); $actl = array();
$acts[0] = "activate";
$acts[1] = "disable";
$actl[0] = img_picto($langs->trans("Disabled"), 'switch_off', 'class="size15x"');
$actl[1] = img_picto($langs->trans("Activated"), 'switch_on', 'class="size15x"');

$listoffset = GETPOST('listoffset');
$listlimit = GETPOST('listlimit') > 0 ?GETPOST('listlimit') : 1000; // To avoid too long dictionaries
$active = 1;

$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $listlimit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;


$tabname = MAIN_DB_PREFIX."gpec_competencesElementaires";
$tablib = "Compétences élémentaires";
$tabsql = 'SELECT f.rowid as rowid, f.domaine, f.competence, f.active FROM '.MAIN_DB_PREFIX.'gpec_competencesElementaires as f';
$tabsqlsort = "domaine ASC";
$tabfield = "domaine,competence";
$tabfieldvalue = "domaine,competence";
$tabfieldinsert = "domaine,competence";
$tabrowid = "rowid";
$tabcond = $conf->gpec->enabled;
$tabhelp = ""; 


/*
 * Actions
 */

if (GETPOST('button_removefilter', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter_x', 'alpha')) {
	$search_country_id = '';
	$search_code = '';
}

// Actions add or modify an entry into a dictionary
if (GETPOST('actionadd') || GETPOST('actionmodify')) {
	$listfield = explode(',', str_replace(' ', '', $tabfield));
	$listfieldinsert = explode(',', $tabfieldinsert);
	$listfieldmodify = explode(',', $tabfieldinsert);
	$listfieldvalue = explode(',', $tabfieldvalue);

	// Check that all mandatory fields are filled
	$ok = 1;

	// If check ok and action add, add the line
	if ($ok && GETPOST('actionadd')) {
		if ($tabrowid) {
			// Get free id for insert
			$newid = 0;
			$sql = "SELECT max(".$tabrowid.") newid from ".$tabname;
			$result = $db->query($sql);
			if ($result) {
				$obj = $db->fetch_object($result);
				$newid = ($obj->newid + 1);
			} else {
				dol_print_error($db);
			}
		}

		// Add new entry
		$sql = "INSERT INTO ".$tabname." (";
		// List of fields
		if ($tabrowid && !in_array($tabrowid, $listfieldinsert)) {
			$sql .= $tabrowid.",";
		}
		$sql .= $tabfieldinsert;
		$sql .= ",active)";
		$sql .= " VALUES(";

		// List of values
		if ($tabrowid && !in_array($tabrowid, $listfieldinsert)) {
			$sql .= $newid.",";
		}
		$i = 0;
		foreach ($listfieldinsert as $f => $value) {
			$keycode = $listfieldvalue[$i];
			if (empty($keycode)) {
				$keycode = $value;
			}

			if ($value == 'price' || preg_match('/^amount/i', $value)) {
				$_POST[$keycode] = price2num(GETPOST($keycode), 'MU');
			} elseif ($value == 'taux' || $value == 'localtax1') {
				$_POST[$keycode] = price2num(GETPOST($keycode), 8);	// Note that localtax2 can be a list of rates separated by coma like X:Y:Z
			} elseif ($value == 'entity') {
				$_POST[$keycode] = getEntity($tabname);
			}

			if ($i) {
				$sql .= ",";
			}

			if ($keycode == 'sortorder') {		// For column name 'sortorder', we use the field name 'position'
				$sql .= (int) GETPOST('position', 'int');
			} elseif ($_POST[$keycode] == '' && !($keycode == 'code' && $id == 10)) {
				$sql .= "null"; // For vat, we want/accept code = ''
			} elseif ($keycode == 'content') {
				$sql .= "'".$db->escape(GETPOST($keycode, 'restricthtml'))."'";
			} elseif (in_array($keycode, array('joinfile', 'private', 'pos', 'position', 'scale', 'use_default'))) {
				$sql .= (int) GETPOST($keycode, 'int');
			} else {
				$sql .= "'".$db->escape(GETPOST($keycode, 'nohtml'))."'";
			}

			$i++;
		}
		$sql .= ",1)";

		dol_syslog("actionadd", LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql) {	// Add is ok
			setEventMessages($langs->transnoentities("RecordCreatedSuccessfully"), null, 'mesgs');

			// Clean $_POST array
			$_POST = array();
		} else {
			if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				setEventMessages($langs->transnoentities("ErrorRecordAlreadyExists"), null, 'errors');
			} else {
				dol_print_error($db);
			}
		}
	}

	// If verif ok and action modify, modify the line
	if ($ok && GETPOST('actionmodify')) {
		if ($tabrowid) {
			$rowidcol = $tabrowid;
		} else {
			$rowidcol = "rowid";
		}

		// Modify entry
		$sql = "UPDATE ".$tabname." SET ";
		// Modifie valeur des champs
		if ($tabrowid && !in_array($tabrowid, $listfieldmodify)) {
			$sql .= $tabrowid."=";
			$sql .= "'".$db->escape($rowid)."', ";
		}
		$i = 0;
		foreach ($listfieldmodify as $field) {
			$keycode = $listfieldvalue[$i];
			if (empty($keycode)) {
				$keycode = $field;
			}

			if ($field == 'price' || preg_match('/^amount/i', $field)) {
				$_POST[$keycode] = price2num(GETPOST($keycode), 'MU');
			} elseif ($field == 'taux' || $field == 'localtax1') {
				$_POST[$keycode] = price2num(GETPOST($keycode), 8);	// Note that localtax2 can be a list of rates separated by coma like X:Y:Z
			} elseif ($field == 'entity') {
				$_POST[$keycode] = getEntity($tabname);
			}

			if ($i) {
				$sql .= ",";
			}
			$sql .= $field."=";
			if ($listfieldvalue[$i] == 'sortorder') {		// For column name 'sortorder', we use the field name 'position'
				$sql .= (int) GETPOST('position', 'int');
			} elseif ($_POST[$keycode] == '' && !($keycode == 'code' && $id == 10)) {
				$sql .= "null"; // For vat, we want/accept code = ''
			} elseif ($keycode == 'content') {
				$sql .= "'".$db->escape(GETPOST($keycode, 'restricthtml'))."'";
			} elseif (in_array($keycode, array('joinfile', 'private', 'pos', 'position', 'scale', 'use_default'))) {
				$sql .= (int) GETPOST($keycode, 'int');
			} else {
				$sql .= "'".$db->escape(GETPOST($keycode, 'nohtml'))."'";
			}

			$i++;
		}
		if (in_array($rowidcol, array('code', 'code_iso'))) {
			$sql .= " WHERE ".$rowidcol." = '".$db->escape($rowid)."'";
		} else {
			$sql .= " WHERE ".$rowidcol." = ".((int) $rowid);
		}
		if (in_array('entity', $listfieldmodify)) {
			$sql .= " AND entity = ".((int) getEntity($tabname, 0));
		}

		dol_syslog("actionmodify", LOG_DEBUG);
		//print $sql;
		$resql = $db->query($sql);
		if (!$resql) {
			setEventMessages($db->error(), null, 'errors');
		}
	}
}

if (GETPOST('actioncancel')) {
	//$_GET["id"]=GETPOST('id', 'int');       // Force affichage dictionnaire en cours d'edition
}

if ($action == 'confirm_delete' && $confirm == 'yes') {       // delete
	if ($tabrowid) {
		$rowidcol = $tabrowid;
	} else {
		$rowidcol = "rowid";
	}

	$sql = "DELETE FROM ".$tabname." WHERE ".$rowidcol."='".$db->escape($rowid)."'";

	dol_syslog("delete", LOG_DEBUG);
	$result = $db->query($sql);
	if (!$result) {
		if ($db->errno() == 'DB_ERROR_CHILD_EXISTS') {
			setEventMessages($langs->transnoentities("ErrorRecordIsUsedByChild"), null, 'errors');
		} else {
			dol_print_error($db);
		}
	}
}

// activate
if ($action == $acts[0]) {
	if ($tabrowid) {
		$rowidcol = $tabrowid;
	} else {
		$rowidcol = "rowid";
	}

	if ($rowid) {
		$sql = "UPDATE ".$tabname." SET active = 1 WHERE ".$rowidcol."='".$db->escape($rowid)."'";
	} elseif ($code) {
		$sql = "UPDATE ".$tabname." SET active = 1 WHERE code='".dol_escape_htmltag($code)."'";
	}

	$result = $db->query($sql);
	if (!$result) {
		dol_print_error($db);
	}
}

// disable
if ($action == $acts[1]) {
	if ($tabrowid) {
		$rowidcol = $tabrowid;
	} else {
		$rowidcol = "rowid";
	}

	if ($rowid) {
		$sql = "UPDATE ".$tabname." SET active = 0 WHERE ".$rowidcol."='".$db->escape($rowid)."'";
	} elseif ($code) {
		$sql = "UPDATE ".$tabname." SET active = 0 WHERE code='".dol_escape_htmltag($code)."'";
	}

	$result = $db->query($sql);
	if (!$result) {
		dol_print_error($db);
	}
}


/*
 * View
 */

$form = new Form($db);
$formadmin = new FormAdmin($db);

$title = $langs->trans("Compétences Elémentaires");

llxHeader('', $title);

$linkback = '';
$titlepicto = 'object_gpec_32@gpec';

print load_fiche_titre($title, $linkback, $titlepicto);

$param = '';
$paramwithsearch = $param;
if ($sortorder) {
	$paramwithsearch .= '&sortorder='.urlencode($sortorder);
}
if ($sortfield) {
	$paramwithsearch .= '&sortfield='.urlencode($sortfield);
}
if (GETPOST('from')) {
	$paramwithsearch .= '&from='.urlencode(GETPOST('from', 'alpha'));
}


// Confirmation of the deletion of the line
if ($action == 'delete') {
	print $form->formconfirm($_SERVER["PHP_SELF"].'?'.($page ? 'page='.$page.'&' : '').'rowid='.urlencode($rowid).'&code='.urlencode($code).$paramwithsearch, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_delete', '', 0, 1);
}



// Complete search values request with sort criteria
$sql = 'SELECT f.rowid as rowid, f.domaine, f.competence, f.active, nom FROM '.MAIN_DB_PREFIX.'gpec_competencesElementaires as f';
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."gpec_domaines as d ON d.rowid = f.domaine";

if (!preg_match('/ WHERE /', $sql)) {
	$sql .= " WHERE 1 = 1";
}

if ($sortfield) {
	// If sort order is "country", we use country_code instead
	if ($sortfield == 'country') {
		$sortfield = 'country_code';
	}
	$sql .= $db->order($sortfield, $sortorder);
	$sql .= ", ";
	// Clear the required sort criteria for the tabsqlsort to be able to force it with selected value
	$tabsqlsort = preg_replace('/([a-z]+\.)?'.$sortfield.' '.$sortorder.',/i', '', $tabsqlsort);
	$tabsqlsort = preg_replace('/([a-z]+\.)?'.$sortfield.',/i', '', $tabsqlsort);
} else {
	$sql .= " ORDER BY ";
}
$sql .= $tabsqlsort;
$sql .= $db->plimit($listlimit + 1, $offset);
//print $sql;

if (empty($tabfield)) {
	dol_print_error($db, 'The table with id '.$id.' has no array tabfield defined');
	exit;
}
$fieldlist = explode(',', $tabfield);

print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="from" value="'.dol_escape_htmltag(GETPOST('from', 'alpha')).'">';

// Form to add a new line
if ($tabname) {
	$withentity = null;

	$fieldlist = explode(',', $tabfield);

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	// Line for title
	$tdsoffields = '<tr class="liste_titre">';
	foreach ($fieldlist as $field => $value) {
		// Define field friendly name from its technical name
		$valuetoshow = ucfirst($value); // Par defaut
		$valuetoshow = $langs->trans($valuetoshow); // try to translate
		$class = '';

		/*if ($value == 'pos') {
			$valuetoshow = $langs->trans("Position"); $class = 'right';
		}
		if ($value == 'source') {
			$valuetoshow = $langs->trans("Contact");
		}
		if ($value == 'price') {
			$valuetoshow = $langs->trans("PriceUHT");
		}
		if ($value == 'taux') {
			if ($tabname != MAIN_DB_PREFIX."c_revenuestamp") {
				$valuetoshow = $langs->trans("Rate");
			} else {
				$valuetoshow = $langs->trans("Amount");
			}
			$class = 'center';
		}
		if ($value == 'localtax1_type') {
			$valuetoshow = $langs->trans("UseLocalTax")." 2"; $class = "center"; $sortable = 0;
		}
		if ($value == 'localtax1') {
			$valuetoshow = $langs->trans("RateOfTaxN", '2'); $class = "center";
		}
		if ($value == 'localtax2_type') {
			$valuetoshow = $langs->trans("UseLocalTax")." 3"; $class = "center"; $sortable = 0;
		}
		if ($value == 'localtax2') {
			$valuetoshow = $langs->trans("RateOfTaxN", '3'); $class = "center";
		}
		if ($value == 'organization') {
			$valuetoshow = $langs->trans("Organization");
		}
		if ($value == 'lang') {
			$valuetoshow = $langs->trans("Language");
		}
		if ($value == 'type') {
			if ($tabname == MAIN_DB_PREFIX."c_paiement") {
				$valuetoshow = $form->textwithtooltip($langs->trans("Type"), $langs->trans("TypePaymentDesc"), 2, 1, img_help(1, ''));
			} else {
				$valuetoshow = $langs->trans("Type");
			}
		}
		if ($value == 'code') {
			$valuetoshow = $langs->trans("Code"); $class = 'maxwidth100';
		}
		if ($value == 'libelle' || $value == 'label') {
			$valuetoshow = $form->textwithtooltip($langs->trans("Label"), $langs->trans("LabelUsedByDefault"), 2, 1, img_help(1, ''));
		}
		if ($value == 'libelle_facture') {
			$valuetoshow = $form->textwithtooltip($langs->trans("LabelOnDocuments"), $langs->trans("LabelUsedByDefault"), 2, 1, img_help(1, ''));
		}
		if ($value == 'country') {
			if (in_array('region_id', $fieldlist)) {
				print '<td>&nbsp;</td>'; continue;
			}		// For region page, we do not show the country input
			$valuetoshow = $langs->trans("Country");
		}
		if ($value == 'recuperableonly') {
			$valuetoshow = $langs->trans("NPR"); $class = "center";
		}
		if ($value == 'nbjour') {
			$valuetoshow = $langs->trans("NbOfDays");
		}
		if ($value == 'type_cdr') {
			$valuetoshow = $langs->trans("AtEndOfMonth"); $class = "center";
		}
		if ($value == 'decalage') {
			$valuetoshow = $langs->trans("Offset");
		}
		if ($value == 'width' || $value == 'nx') {
			$valuetoshow = $langs->trans("Width");
		}
		if ($value == 'height' || $value == 'ny') {
			$valuetoshow = $langs->trans("Height");
		}
		if ($value == 'unit' || $value == 'metric') {
			$valuetoshow = $langs->trans("MeasuringUnit");
		}
		if ($value == 'region_id' || $value == 'country_id') {
			$valuetoshow = '';
		}
		if ($value == 'accountancy_code') {
			$valuetoshow = $langs->trans("AccountancyCode");
		}
		if ($value == 'accountancy_code_sell') {
			$valuetoshow = $langs->trans("AccountancyCodeSell");
		}
		if ($value == 'accountancy_code_buy') {
			$valuetoshow = $langs->trans("AccountancyCodeBuy");
		}
		if ($value == 'pcg_version' || $value == 'fk_pcg_version') {
			$valuetoshow = $langs->trans("Pcg_version");
		}
		if ($value == 'account_parent') {
			$valuetoshow = $langs->trans("Accountparent");
		}
		if ($value == 'pcg_type') {
			$valuetoshow = $langs->trans("Pcg_type");
		}
		if ($value == 'pcg_subtype') {
			$valuetoshow = $langs->trans("Pcg_subtype");
		}
		if ($value == 'sortorder') {
			$valuetoshow = $langs->trans("SortOrder");
		}
		if ($value == 'short_label') {
			$valuetoshow = $langs->trans("ShortLabel");
		}
		if ($value == 'fk_parent') {
			$valuetoshow = $langs->trans("ParentID"); $class = 'center';
		}
		if ($value == 'range_account') {
			$valuetoshow = $langs->trans("Range");
		}
		if ($value == 'sens') {
			$valuetoshow = $langs->trans("Sens");
		}
		if ($value == 'category_type') {
			$valuetoshow = $langs->trans("Calculated");
		}
		if ($value == 'formula') {
			$valuetoshow = $langs->trans("Formula");
		}
		if ($value == 'paper_size') {
			$valuetoshow = $langs->trans("PaperSize");
		}
		if ($value == 'orientation') {
			$valuetoshow = $langs->trans("Orientation");
		}
		if ($value == 'leftmargin') {
			$valuetoshow = $langs->trans("LeftMargin");
		}
		if ($value == 'topmargin') {
			$valuetoshow = $langs->trans("TopMargin");
		}
		if ($value == 'spacex') {
			$valuetoshow = $langs->trans("SpaceX");
		}
		if ($value == 'spacey') {
			$valuetoshow = $langs->trans("SpaceY");
		}
		if ($value == 'font_size') {
			$valuetoshow = $langs->trans("FontSize");
		}
		if ($value == 'custom_x') {
			$valuetoshow = $langs->trans("CustomX");
		}
		if ($value == 'custom_y') {
			$valuetoshow = $langs->trans("CustomY");
		}
		if ($value == 'percent') {
			$valuetoshow = $langs->trans("Percentage");
		}
		if ($value == 'affect') {
			$valuetoshow = $langs->trans("WithCounter");
		}
		if ($value == 'delay') {
			$valuetoshow = $langs->trans("NoticePeriod");
		}
		if ($value == 'newbymonth') {
			$valuetoshow = $langs->trans("NewByMonth");
		}
		if ($value == 'fk_tva') {
			$valuetoshow = $langs->trans("VAT");
		}
		if ($value == 'range_ik') {
			$valuetoshow = $langs->trans("RangeIk");
		}
		if ($value == 'fk_c_exp_tax_cat') {
			$valuetoshow = $langs->trans("CarCategory");
		}
		if ($value == 'revenuestamp_type') {
			$valuetoshow = $langs->trans('TypeOfRevenueStamp');
		}
		if ($value == 'use_default') {
			$valuetoshow = $langs->trans('Default'); $class = 'center';
		}
		if ($value == 'unit_type') {
			$valuetoshow = $langs->trans('TypeOfUnit');
		}
		if ($value == 'public' && $tablib[$id] == 'TicketDictCategory') {
			$valuetoshow = $langs->trans('TicketGroupIsPublic'); $class = 'center';
		}*/

		if ($valuetoshow != '') {
			$tdsoffields .= '<td'.($class ? ' class="'.$class.'"' : '').'>';
			if (!empty($tabhelp[$value]) && preg_match('/^http(s*):/i', $tabhelp[$value])) {
				$tdsoffields .= '<a href="'.$tabhelp[$value].'" target="_blank">'.$valuetoshow.' '.img_help(1, $valuetoshow).'</a>';
			} elseif (!empty($tabhelp[$value])) {
				$tdsoffields .= $form->textwithpicto($valuetoshow, $tabhelp[$value]);
			} else {
				$tdsoffields .= $valuetoshow;
			}
			$tdsoffields .= '</td>';
		}
	}

	$tdsoffields .= '<td>';
	if (!is_null($withentity)) {
		$tdsoffields .= '<input type="hidden" name="entity" value="'.$withentity.'">';
	}
	$tdsoffields .= '</td>';
	$tdsoffields .= '<td style="min-width: 26px;"></td>';
	$tdsoffields .= '<td style="min-width: 26px;"></td>';
	$tdsoffields .= '</tr>';

	print $tdsoffields;


	// Line to enter new values
	print '<!-- line to add new entry -->';
	print '<tr class="oddeven nodrag nodrop nohover">';

	$obj = new stdClass();
	// If data was already input, we define them in obj to populate input fields.
	if (GETPOST('actionadd')) {
		foreach ($fieldlist as $key => $val) {
			if (GETPOST($val) != '') {
				$obj->$val = GETPOST($val);
			}
		}
	}

	$tmpaction = 'create';
	$parameters = array('fieldlist'=>$fieldlist, 'tabname'=>$tabname);
	$reshook = $hookmanager->executeHooks('createDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks
	$error = $hookmanager->error; $errors = $hookmanager->errors;

	if (empty($reshook)) {
		fieldList($fieldlist, $obj, $tabname, 'add');
	}

	print '<td colspan="3" class="center">';
	if ($action != 'edit') {
		print '<input type="submit" class="button button-add" name="actionadd" value="'.$langs->trans("Add").'">';
	}
	print '</td>';

	print "</tr>";

	print '</table>';
	print '</div>';
}

print '</form>';
print '<br>';


print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="from" value="'.dol_escape_htmltag(GETPOST('from', 'alpha')).'">';

// List of available record in database
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	// There is several pages
	if ($num > $listlimit || $page) {
		print_fleche_navigation($page, $_SERVER["PHP_SELF"], $paramwithsearch, ($num > $listlimit), '<li class="pagination"><span>'.$langs->trans("Page").' '.($page + 1).'</span></li>');
		print '<div class="clearboth"></div>';
	}

	print '<div class="div-table-responsive">';
	print '<table class="noborder centpercent">';

	// Title line with search input fields
	print '<tr class="liste_titre_filter">';
	$filterfound = 0;
	foreach ($fieldlist as $field => $value) {
		if ($value == 'entity') {
			continue;
		}

		$showfield = 1; // By default

		if ($value == 'region_id' || $value == 'country_id') {
			$showfield = 0;
		}

		if ($showfield) {
			if ($value == 'country') {
				print '<td class="liste_titre">';
				print $form->select_country($search_country_id, 'search_country_id', '', 28, 'maxwidth150 maxwidthonsmartphone');
				print '</td>';
				$filterfound++;
			} elseif ($value == 'code') {
				print '<td class="liste_titre">';
				print '<input type="text" class="maxwidth100" name="search_code" value="'.dol_escape_htmltag($search_code).'">';
				print '</td>';
				$filterfound++;
			} else {
				print '<td class="liste_titre">';
				print '</td>';
			}
		}
	}
	print '<td class="liste_titre"></td>';
	print '<td class="liste_titre right" colspan="2">';
	if ($filterfound) {
		$searchpicto = $form->showFilterAndCheckAddButtons(0);
		print $searchpicto;
	}
	print '</td>';
	print '</tr>';

	// Title of lines
	print '<tr class="liste_titre">';
	foreach ($fieldlist as $field => $value) {
		if ($value == 'entity') {
			continue;
		}

		if (in_array($value, array('label', 'libelle', 'libelle_facture')) && empty($tabhelp[$value])) {
			$tabhelp[$value] = $langs->trans('LabelUsedByDefault');
		}

		// Determines the name of the field in relation to the possible names
		// in data dictionaries
		$showfield = 1; // By defaut
		$cssprefix = '';
		$sortable = 1;
		$valuetoshow = ucfirst($value); // By defaut
		$valuetoshow = $langs->trans($valuetoshow); // try to translate
		$moreattrib = '';

		// Special cases
		/*if ($value == 'source') {
			$valuetoshow = $langs->trans("Contact");
		}
		if ($value == 'price') {
			$valuetoshow = $langs->trans("PriceUHT");
		}
		if ($value == 'taux') {
			if ($tabname != MAIN_DB_PREFIX."c_revenuestamp") {
				$valuetoshow = $langs->trans("Rate");
			} else {
				$valuetoshow = $langs->trans("Amount");
			}
			$cssprefix = 'center ';
		}

		if ($value == 'localtax1_type') {
			$valuetoshow = $langs->trans("UseLocalTax")." 2"; $cssprefix = "center "; $sortable = 0;
		}
		if ($value == 'localtax1') {
			$valuetoshow = $langs->trans("RateOfTaxN", '2'); $cssprefix = "center "; $sortable = 0;
		}
		if ($value == 'localtax2_type') {
			$valuetoshow = $langs->trans("UseLocalTax")." 3"; $cssprefix = "center "; $sortable = 0;
		}
		if ($value == 'localtax2') {
			$valuetoshow = $langs->trans("RateOfTaxN", '3'); $cssprefix = "center "; $sortable = 0;
		}
		if ($value == 'organization') {
			$valuetoshow = $langs->trans("Organization");
		}
		if ($value == 'lang') {
			$valuetoshow = $langs->trans("Language");
		}
		if ($value == 'type') {
			$valuetoshow = $langs->trans("Type");
		}
		if ($value == 'code') {
			$valuetoshow = $langs->trans("Code");
		}
		if (in_array($value, array('pos', 'position'))) {
			$valuetoshow = $langs->trans("Position"); $cssprefix = 'right ';
		}
		if ($value == 'libelle' || $value == 'label') {
			$valuetoshow = $langs->trans("Label");
		}
		if ($value == 'libelle_facture') {
			$valuetoshow = $langs->trans("LabelOnDocuments");
		}
		if ($value == 'country') {
			$valuetoshow = $langs->trans("Country");
		}
		if ($value == 'recuperableonly') {
			$valuetoshow = $langs->trans("NPR"); $cssprefix = "center ";
		}
		if ($value == 'nbjour') {
			$valuetoshow = $langs->trans("NbOfDays");
		}
		if ($value == 'type_cdr') {
			$valuetoshow = $langs->trans("AtEndOfMonth"); $cssprefix = "center ";
		}
		if ($value == 'decalage') {
			$valuetoshow = $langs->trans("Offset");
		}
		if ($value == 'width' || $value == 'nx') {
			$valuetoshow = $langs->trans("Width");
		}
		if ($value == 'height' || $value == 'ny') {
			$valuetoshow = $langs->trans("Height");
		}
		if ($value == 'unit' || $value == 'metric') {
			$valuetoshow = $langs->trans("MeasuringUnit");
		}
		if ($value == 'accountancy_code') {
			$valuetoshow = $langs->trans("AccountancyCode");
		}
		if ($value == 'accountancy_code_sell') {
			$valuetoshow = $langs->trans("AccountancyCodeSell"); $sortable = 0;
		}
		if ($value == 'accountancy_code_buy') {
			$valuetoshow = $langs->trans("AccountancyCodeBuy"); $sortable = 0;
		}
		if ($value == 'fk_pcg_version') {
			$valuetoshow = $langs->trans("Pcg_version");
		}
		if ($value == 'account_parent') {
			$valuetoshow = $langs->trans("Accountsparent");
		}
		if ($value == 'pcg_type') {
			$valuetoshow = $langs->trans("Pcg_type");
		}
		if ($value == 'pcg_subtype') {
			$valuetoshow = $langs->trans("Pcg_subtype");
		}
		if ($value == 'sortorder') {
			$valuetoshow = $langs->trans("SortOrder");
		}
		if ($value == 'short_label') {
			$valuetoshow = $langs->trans("ShortLabel");
		}
		if ($value == 'fk_parent') {
			$valuetoshow = $langs->trans("ParentID"); $cssprefix = 'center ';
		}
		if ($value == 'range_account') {
			$valuetoshow = $langs->trans("Range");
		}
		if ($value == 'sens') {
			$valuetoshow = $langs->trans("Sens");
		}
		if ($value == 'category_type') {
			$valuetoshow = $langs->trans("Calculated");
		}
		if ($value == 'formula') {
			$valuetoshow = $langs->trans("Formula");
		}
		if ($value == 'paper_size') {
			$valuetoshow = $langs->trans("PaperSize");
		}
		if ($value == 'orientation') {
			$valuetoshow = $langs->trans("Orientation");
		}
		if ($value == 'leftmargin') {
			$valuetoshow = $langs->trans("LeftMargin");
		}
		if ($value == 'topmargin') {
			$valuetoshow = $langs->trans("TopMargin");
		}
		if ($value == 'spacex') {
			$valuetoshow = $langs->trans("SpaceX");
		}
		if ($value == 'spacey') {
			$valuetoshow = $langs->trans("SpaceY");
		}
		if ($value == 'font_size') {
			$valuetoshow = $langs->trans("FontSize");
		}
		if ($value == 'custom_x') {
			$valuetoshow = $langs->trans("CustomX");
		}
		if ($value == 'custom_y') {
			$valuetoshow = $langs->trans("CustomY");
		}
		if ($value == 'percent') {
			$valuetoshow = $langs->trans("Percentage");
		}
		if ($value == 'affect') {
			$valuetoshow = $langs->trans("WithCounter");
		}
		if ($value == 'delay') {
			$valuetoshow = $langs->trans("NoticePeriod");
		}
		if ($value == 'newbymonth') {
			$valuetoshow = $langs->trans("NewByMonth");
		}
		if ($value == 'fk_tva') {
			$valuetoshow = $langs->trans("VAT");
		}
		if ($value == 'range_ik') {
			$valuetoshow = $langs->trans("RangeIk");
		}
		if ($value == 'fk_c_exp_tax_cat') {
			$valuetoshow = $langs->trans("CarCategory");
		}
		if ($value == 'revenuestamp_type') {
			$valuetoshow = $langs->trans('TypeOfRevenueStamp');
		}
		if ($value == 'use_default') {
			$valuetoshow = $langs->trans('Default'); $cssprefix = 'center ';
		}
		if ($value == 'unit_type') {
			$valuetoshow = $langs->trans('TypeOfUnit');
		}
		if ($value == 'public' && $tablib[$id] == 'TicketDictCategory') {
			$valuetoshow = $langs->trans('TicketGroupIsPublic'); $cssprefix = 'center ';
		}

		if ($value == 'region_id' || $value == 'country_id') {
			$showfield = 0;
		}*/
		if($value == "nom"){
			$moreattrib = 'style = "min-width: 7%"';
		}
		elseif($value == "activite"){
			$moreattrib = 'style = "min-width: 40%"';
		}

		// Show field title
		if ($showfield) {
			if (!empty($tabhelp[$value]) && preg_match('/^http(s*):/i', $tabhelp[$value])) {
				$newvaluetoshow = '<a href="'.$tabhelp[$value].'" target="_blank">'.$valuetoshow.' '.img_help(1, $valuetoshow).'</a>';
			} elseif (!empty($tabhelp[$value])) {
				$newvaluetoshow = $form->textwithpicto($valuetoshow, $tabhelp[$value]);
			} else {
				$newvaluetoshow = $valuetoshow;
			}

			print getTitleFieldOfList($newvaluetoshow, 0, $_SERVER["PHP_SELF"], ($sortable ? $value : ''), ($page ? 'page='.$page.'&' : ''), $param, $moreattrib, $sortfield, $sortorder, $cssprefix);
		}
	}

	print getTitleFieldOfList($langs->trans("Status"), 0, $_SERVER["PHP_SELF"], "active", ($page ? 'page='.$page.'&' : ''), $param, 'align="center"', $sortfield, $sortorder);
	print getTitleFieldOfList('');
	print getTitleFieldOfList('');
	print '</tr>';

	if ($num) {
		// Lines with values
		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			//print_r($obj);
			print '<tr class="oddeven" id="rowid-'.(empty($obj->rowid) ? '' : $obj->rowid).'">';
			if ($action == 'edit' && ($rowid == (!empty($obj->rowid) ? $obj->rowid : $obj->code))) {
				$tmpaction = 'edit';
				$parameters = array('fieldlist'=>$fieldlist, 'tabname'=>$tabname);
				$reshook = $hookmanager->executeHooks('editDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks
				$error = $hookmanager->error; $errors = $hookmanager->errors;

				// Show fields
				if (empty($reshook)) {
					$withentity = fieldList($fieldlist, $obj, $tabname, 'edit');
				}

				print '<td colspan="3" class="center">';
				print '<div name="'.(!empty($obj->rowid) ? $obj->rowid : $obj->code).'"></div>';
				print '<input type="hidden" name="page" value="'.dol_escape_htmltag($page).'">';
				print '<input type="hidden" name="rowid" value="'.dol_escape_htmltag($rowid).'">';
				if (!is_null($withentity)) {
					print '<input type="hidden" name="entity" value="'.$withentity.'">';
				}
				print '<input type="submit" class="button button-edit" name="actionmodify" value="'.$langs->trans("Modify").'">';
				print '<input type="submit" class="button button-cancel" name="actioncancel" value="'.$langs->trans("Cancel").'">';
				print '</td>';
			} else {
				$tmpaction = 'view';
				$parameters = array('fieldlist'=>$fieldlist, 'tabname'=>$tabname);
				$reshook = $hookmanager->executeHooks('viewDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks

				$error = $hookmanager->error; $errors = $hookmanager->errors;

				if (empty($reshook)) {
					$withentity = null;

					foreach ($fieldlist as $field => $value) {
						//var_dump($fieldlist);
						$class = '';
						$showfield = 1;
						$valuetoshow = empty($obj->{$value}) ? '' : $obj->{$value};
						$titletoshow = '';
						$valuetoshow = dol_htmlentitiesbr($valuetoshow);

						if ($value == 'domaine') {
							$valuetoshow = $obj->nom;
						}

						// Show value for field
						if ($showfield) {
							print '<!-- '. $value .' --><td class="'.$class.'"'.($titletoshow ? ' title="'.dol_escape_htmltag($titletoshow).'"' : '').'>'.$valuetoshow.'</td>';
						}
					}
				}

				// Can an entry be erased or disabled ?
				// all true by default
				$iserasable = 1;
				$canbedisabled = 1;
				$canbemodified = 1;
				
				// Build Url. The table is id=, the id of line is rowid=
				$rowidcol = $tabrowid;

				$url = $_SERVER["PHP_SELF"].'?'.($page ? 'page='.$page.'&' : '').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.(isset($obj->{$rowidcol}) ? $obj->{$rowidcol} : (!empty($obj->code) ? urlencode($obj->code) : '')).'&code='.(!empty($obj->code) ?urlencode($obj->code) : '');
				if (!empty($param)) {
					$url .= '&'.$param;
				}
				if (!is_null($withentity)) {
					$url .= '&entity='.$withentity;
				}
				$url .= '&';

				// Active
				print '<td class="nowrap center">';
				if ($canbedisabled) {
					print '<a class="reposition" href="'.$url.'action='.$acts[$obj->active].'&token='.newToken().'">'.$actl[$obj->active].'</a>';
				} else {
					if (in_array($obj->code, array('AC_OTH', 'AC_OTH_AUTO'))) {
						print $langs->trans("AlwaysActive");
					} elseif (isset($obj->type) && in_array($obj->type, array('systemauto')) && empty($obj->active)) {
						print $langs->trans("Deprecated");
					} elseif (isset($obj->type) && in_array($obj->type, array('system')) && !empty($obj->active) && $obj->code != 'AC_OTH') {
						print $langs->trans("UsedOnlyWithTypeOption");
					} else {
						print $langs->trans("AlwaysActive");
					}
				}
				print "</td>";

				// Modify link
				if ($canbemodified) {
					print '<td align="center"><a class="reposition editfielda" href="'.$url.'action=edit&token='.newToken().'">'.img_edit().'</a></td>';
				} else {
					print '<td>&nbsp;</td>';
				}

				// Delete link
				if ($iserasable) {
					print '<td class="center">';
					if ($user->admin) {
						print '<a class="reposition" href="'.$url.'action=delete&token='.newToken().'">'.img_delete().'</a>';
					}
					//else print '<a href="#">'.img_delete().'</a>';    // Some dictionary can be edited by other profile than admin
					print '</td>';
				} else {
					print '<td>&nbsp;</td>';
				}

				print "</tr>\n";
			}
			$i++;
		}
	}

	print '</table>';
	print '</div>';
} else {
	dol_print_error($db);
}

print '</form>';


print '<br>';

// End of page
llxFooter();
$db->close();


/**
 *	Show fields in insert/edit mode
 *
 * 	@param		array		$fieldlist		Array of fields
 * 	@param		Object		$obj			If we show a particular record, obj is filled with record fields
 *  @param		string		$tabname		Name of SQL table
 *  @param		string		$context		'add'=Output field for the "add form", 'edit'=Output field for the "edit form", 'hide'=Output field for the "add form" but we dont want it to be rendered
 *	@return		string						'' or value of entity into table
 */
function fieldList($fieldlist, $obj = '', $tabname = '', $context = '')
{
	global $conf, $langs, $db, $mysoc;
	global $form;
	global $region_id;
	global $elementList, $sourceList, $localtax_typeList;

	$formadmin = new FormAdmin($db);
	$formcompany = new FormCompany($db);
	$formaccounting = new FormAccounting($db);

	$withentity = '';

	foreach ($fieldlist as $field => $value) {
		if ($value == 'entity') {
			$withentity = $obj->{$value};
			continue;
		}

		$fieldValue = isset($obj->{$value}) ? $obj->{$value}: '';

		if($value == "domaine"){
			$out = '';
			$out .= '<td class="'.$classtd.'">';
			$out .= '<select class="flat '.$morecss.' maxwidthonsmartphone" name="'.$fieldlist[$field].'" id="'.$fieldlist[$field].'" '.($moreparam ? $moreparam : '').'>';
			
			$sqlwhere = '';
			$sql = "SELECT rowid, nom";
			$sql .= " FROM ".MAIN_DB_PREFIX."gpec_domaines";
			$sqlwhere .= ' WHERE 1=1';
			$sqlwhere .= " AND active = 1";
			$sql .= $sqlwhere;
			$sql .= ' ORDER BY nom';

			$resql = $db->query($sql);
			if ($resql) {
				$out .= '<option value="0">&nbsp;</option>';
				$num = $db->num_rows($resql);
				$i = 0;
				while ($i < $num) {
					$labeltoshow = '';
					$obj2 = $db->fetch_object($resql);
					
					$out .= '<option value="'.$obj2->rowid.'"';
					$out .= ($fieldValue == $obj2->rowid ? ' selected' : '');
					$out .= '>'.$obj2->nom.'</option>';

					$i++;
				}
				$db->free($resql);
			} else {
				print 'Error in request '.$sql.' '.$db->lasterror().'. Check setup of extra parameters.<br>';
			}
			$out .= '</select></td>';
			print $out;
		}
		else {
			if ($value == 'sortorder') {
				$fieldlist[$field] = 'position';
			}

			$classtd = ''; $class = '';
			if ($fieldlist[$field] == 'code') {
				$class = 'maxwidth100';
			}
			if (in_array($fieldlist[$field], array('pos', 'position'))) {
				$classtd = 'right'; $class = 'maxwidth50 right';
			}
			if (in_array($fieldlist[$field], array('dayrule', 'day', 'month', 'year', 'use_default', 'affect', 'delay', 'public', 'sortorder', 'sens', 'category_type', 'fk_parent'))) {
				$class = 'maxwidth50 center';
			}
			if (in_array($fieldlist[$field], array('use_default', 'public', 'fk_parent'))) {
				$classtd = 'center';
			}
			if (in_array($fieldlist[$field], array('libelle', 'label', 'tracking'))) {
				$class = 'quatrevingtpercent';
			}
			// Fields that must be suggested as '0' instead of ''
			if ($fieldlist[$field] == 'fk_parent') {
				if (empty($fieldValue)) {
					$fieldValue = '0';
				}
			}
			print '<td class="'.$classtd.'">';
			$transfound = 0;
			$transkey = '';
			if (in_array($fieldlist[$field], array('label', 'libelle'))) {		// For label
				// Special case for labels
				if ($tabname == MAIN_DB_PREFIX.'c_civility' && !empty($obj->code)) {
					$transkey = "Civility".strtoupper($obj->code);
				}
				if ($tabname == MAIN_DB_PREFIX.'c_payment_term' && !empty($obj->code)) {
					$langs->load("bills");
					$transkey = "PaymentConditionShort".strtoupper($obj->code);
				}
				if ($transkey && $langs->trans($transkey) != $transkey) {
					$transfound = 1;
					print $form->textwithpicto($langs->trans($transkey), $langs->trans("GoIntoTranslationMenuToChangeThis"));
				}
			}
			if (!$transfound) {
				print '<input type="text" class="flat'.($class ? ' '.$class : '').'" value="'.dol_escape_htmltag($fieldValue).'" name="'.$fieldlist[$field].'">';
			} else {
				print '<input type="hidden" name="'.$fieldlist[$field].'" value="'.$transkey.'">';
			}
			print '</td>';
		}
	}

	return $withentity;
}
