<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2014		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2015		Jean-François Ferry		<jfefe@aternatik.fr>
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
 *      \file       admin/adminrgpd_extrafields.php
 *		\ingroup    rgpd
 *		\brief      Page to setup extra fields of adminrgpd
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


require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once '../lib/rgpd.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('rgpd@rgpd', 'admin', 'users'));

//TEST FUTUR CODE 

require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta//bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/rgpd/class/adminrgpd.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/rgpd/class/rgpd_element.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
$adminUser = new Adherent($db);
$adminAccount = new Account($db);
$rgpd = new AdminRgpd($db);
$deadLine = new Rgpd_element($db);
// var_dump($deadLine);


//END TEST

// List of supported format
$extrafields = new ExtraFields($db);
$form = new Form($db);

// List of supported format
$tmptype2label = ExtraFields::$type2label;
$type2label = array('');
foreach ($tmptype2label as $key => $val) {
	$type2label[$key] = $langs->transnoentitiesnoconv($val);
}

$action = GETPOST('action', 'aZ09');
$attrname = GETPOST('attrname', 'alpha');
$elementtype = 'rgpd_adminrgpd'; //Must be the $table_element of the class that manage extrafield

if (!$user->admin) {
	accessforbidden();
}


/*
 * Actions
 */

require DOL_DOCUMENT_ROOT.'/core/actions_extrafields.inc.php';


/*
 * View
 */

$textobject = $langs->transnoentitiesnoconv("AdminRgpd");
$text = $langs->transnoentitiesnoconv("Users");

$help_url = '';
$page_name = "RgpdSetup";

llxHeader('', $langs->trans("RgpdSetup"), $help_url);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

$head = rgpdAdminPrepareHead();

print dol_get_fiche_head($head, 'adminrgpd_extrafields', $langs->trans($page_name), -1, 'rgpd@rgpd');

require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_view.tpl.php';


//CSS du tableau des données du User
print '<div class="div-table-responsive">';
print '<table summary="listofattributes" class="noborder centpercent small">';

print '<tr class="liste_titre">';
print '<td class="left">'.$langs->trans("Position");
print '<span class="nowrap">';
print img_picto('A-Z', '1downarrow.png');
print '</span>';
print '</td>';
print '<td>'.$langs->trans("LabelOrTranslationKey").'</td>';
print '<td>'.$langs->trans("TranslationString").'</td>';
print '<td>'.$langs->trans("AttributeCode").'</td>';
print '<td>'.$langs->trans("Type").'</td>';
print '<td>'.$langs->trans("Enabled").'</td>';
print '<td class="right">'.$langs->trans("Size").'</td>';
print '<td>'.$langs->trans("ComputedFormula").'</td>';
print '<td class="center">'.$langs->trans("Unique").'</td>';
print '<td class="center">'.$langs->trans("Mandatory").'</td>';
print '<td class="center">'.$form->textwithpicto($langs->trans("AlwaysEditable"), $langs->trans("EditableWhenDraftOnly")).'</td>';
print '<td class="center">'.$form->textwithpicto($langs->trans("Visibility"), $langs->trans("VisibleDesc")).'</td>';
print '<td class="center">'.$form->textwithpicto($langs->trans("DisplayOnPdf"), $langs->trans("DisplayOnPdfDesc")).'</td>';
print '<td class="center">'.$form->textwithpicto($langs->trans("Totalizable"), $langs->trans("TotalizableDesc")).'</td>';
print '<td class="center">'.$form->textwithpicto($langs->trans("CssOnEdit"), $langs->trans("HelpCssOnEditDesc")).'</td>';
print '<td class="center">'.$form->textwithpicto($langs->trans("CssOnView"), $langs->trans("HelpCssOnViewDesc")).'</td>';
print '<td class="center">'.$form->textwithpicto($langs->trans("CssOnList"), $langs->trans("HelpCssOnListDesc")).'</td>';
print '<td class="center">'.$form->textwithpicto($langs->trans("DeadLine"), $langs->trans("Définie une date de fin de conservation de la donnée")).'</td>';
if (isModEnabled('multicompany')) {
	print '<td class="center">'.$langs->trans("Entity").'</td>';
}
print '<td width="80">&nbsp;</td>';
print "</tr>\n";
// fin du CSS

//foreach des données ADHERENT
$excludeFields = ['canvas', 'public', 'morphy', 'ref', 'ref_text', 'fk_adherent_type', 'fk_user_author', 'fk_user_mod', 'fk_user_valid'];
foreach([$adminUser , $adminAccount] as $object) {
	foreach($object->fields as $code => $values) {
			if (!in_array($code, $excludeFields)){
	// 			var_dump($adminAccount);
				// var_dump('code '.$deadLine);
		print '<tr class="oddeven">';
		// Position
		print "<td>".dol_escape_htmltag($values['position'])."</td>\n";
		// Label
		print '<td title="'.dol_escape_htmltagC.'" class="tdoverflowmax150">'.dol_escape_htmltag($values['label'])."</td>\n"; // We don't translate here, we want admin to know what is the key not translated value
		// Label translated
		print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv($values['label'])).'">'.dol_escape_htmltag($langs->transnoentitiesnoconv($values['label']))."</td>\n";
		// Key
		print '<td title="'.dol_escape_htmltag($code).'" class="tdoverflowmax100">'.dol_escape_htmltag($code)."</td>\n";
		// Type
		$typetoshow = $values['type'];
		print '<td title="'.dol_escape_htmltag($typetoshow).'" class="tdoverflowmax100">';
		print dol_escape_htmltag($typetoshow);
		print "</td>\n";
		//Enabled
		print '<td class="right">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['enabled'][$key])."</td>\n";
		// Size
		print '<td class="right">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['size'][$key])."</td>\n";
		// Computed field
		print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($extrafields->attributes[$elementtype]['computed'][$key]).'">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['computed'][$key])."</td>\n";
		// Is unique ?
		print '<td class="center">'.yn($extrafields->attributes[$elementtype]['unique'][$key])."</td>\n";
		// Is mandatory ?
		print '<td class="center">'.yn($extrafields->attributes[$elementtype]['required'][$key])."</td>\n";
		// Can always be editable ?
		print '<td class="center">'.yn($extrafields->attributes[$elementtype]['alwayseditable'][$key])."</td>\n";
		// Visible
		print '<td class="center tdoverflowmax100" title="'.dol_escape_htmltag($extrafields->attributes[$elementtype]['list'][$key]).'">'.dol_escape_htmltag($values['visible'])."</td>\n";
		// Print on PDF
		print '<td class="center tdoverflowmax100" title="'.dol_escape_htmltag($extrafields->attributes[$elementtype]['printable'][$key]).'">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['printable'][$key])."</td>\n";
		// Summable
		print '<td class="center">'.yn($extrafields->attributes[$elementtype]['totalizable'][$key])."</td>\n";
		// CSS
		print '<td class="center tdoverflowmax100" title="'.dol_escape_htmltag($extrafields->attributes[$elementtype]['css'][$key]).'">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['css'][$key])."</td>\n";
		// // CSS view
		print '<td class="center tdoverflowmax100" title="'.dol_escape_htmltag($extrafields->attributes[$elementtype]['cssview'][$key]).'">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['cssview'][$key])."</td>\n";
		// // CSS list
		print '<td class="center tdoverflowmax100" title="'.dol_escape_htmltag($extrafields->attributes[$elementtype]['csslist'][$key]).'">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['csslist'][$key])."</td>\n";
		//DeadLine
		print '<td class="center tdoverflowmax100" title="'.dol_escape_htmltag(($values['deadline'])).'">'.dol_escape_htmltag(($values['deadline']))."</td>\n";
		
		
		// print '<td class="right nowraponall">
		// <a class="editfielda" href='.$_SERVER["PHP_SELF"].'?action=editUser&amp;&token='.newToken().';codeAttr='.$code.';deadline=deadline#formeditextrafield">
		// <span class="fas fa-pencil-alt" style=" color: #444;" title="Modifier"></span>
		// </a>&nbsp; 
		// <a class="paddingleft" href='.$_SERVER["PHP_SELF"].'?action=delete&amp;token='.newToken().';codeAttr='.$code.'>
		// <span class="fas fa-trash pictodelete" style="" title="Supprimer"></span>
		// </a>
		// </td>';
	
		// print '<td>';
		// print $form->selectDate($dateendvalidity, 'dateendvalidity', 0, 0, 1, 'formdateendvalidity', 1, 0);
		// print '</td>';
		// print "</tr>\n";
		}
	}	
}
	print "</table>";
	print '</div>';
	
	print dol_get_fiche_end();
	
	// Buttons
	if ((float) DOL_VERSION < 17) {	// On v17+, the "New Attribute" button is included into tpl.
		if ($action != 'create' && $action != 'edit') {
			print '<div class="tabsAction">';
			print '<a class="butAction reposition" href="'.$_SERVER["PHP_SELF"].'?action=create">'.$langs->trans("NewAttribute").'</a>';
			print "</div>";
	}
}


/*
 * Creation of an optional field
 */
if ($action == 'create') {
	print '<br><div id="newattrib"></div>';
	print load_fiche_titre($langs->trans('NewAttribute'));

	require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_add.tpl.php';
}

/*
 * Edition of an optional field
 */
if ($action == 'edit' && !empty($attrname)) {
	print "<br>";
	print load_fiche_titre($langs->trans("FieldEdition", $attrname));

	require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_edit.tpl.php';
}

// End of page
llxFooter();
$db->close();
