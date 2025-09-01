<?php
/* Copyright (C) 2012      Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015-2016 Charlie BENKE 	<charlie@patas-monkey.com>
 * Copyright (C) 2021      Frédéric France     <frederic.france@netlogic.fr>
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
 *
 * This template needs:
 * $object
 * $withproject (if we are on task contact)
 *
 * $preselectedtypeofcontact may be defined or not
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit;
}

// Si ce n'est pas un projet, on retourne 0 pour utiliser le template par défaut
if ($object->element != 'project') {
    return 0;
}

// Traitement de l'ajout de fonction
if ($action == 'addcontact') {
    $fonction_id = GETPOST('fonction_internal', 'int');
    if (empty($fonction_id)) {
        $fonction_id = GETPOST('fonction_external', 'int');
    }

    if (!empty($fonction_id)) {
        // Récupération de l'ID du contact
        $contact_id = GETPOST('contactid', 'int');
        if (empty($contact_id)) {
            $contact_id = GETPOST('userid', 'int');
        }

        if (!empty($contact_id)) {
            // Suppression de l'ancienne fonction si elle existe
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."element_contact_fonction 
                    WHERE element_id = ".((int) $object->id)." 
                    AND contact_id = ".((int) $contact_id);
            $db->query($sql);

            // Ajout de la nouvelle fonction
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."element_contact_fonction 
                    (element_id, contact_id, function_id) 
                    VALUES (".((int) $object->id).", ".((int) $contact_id).", ".((int) $fonction_id).")";
            $db->query($sql);
        }
    }

    // Ajouter un script pour afficher la popup pour créer un OT après l'ajout d'un utilisateur
    print '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var popup = document.createElement("div");
            popup.style.position = "fixed";
            popup.style.top = "50%";
            popup.style.left = "50%";
            popup.style.transform = "translate(-50%, -50%)";
            popup.style.backgroundColor = "#fff";
            popup.style.border = "1px solid #ccc";
            popup.style.padding = "30px";
            popup.style.zIndex = "1000";
            popup.style.boxShadow = "0 4px 8px rgba(0,0,0,0.1)";
            popup.style.borderRadius = "5px";
            popup.style.textAlign = "center";
            popup.style.minWidth = "400px";
            popup.innerHTML = `
                <h3 style="margin-bottom: 20px; color: #333;">'.$langs->trans('Créer un OT').'</h3>
                <p style="margin-bottom: 30px; color: #666; line-height: 1.5;">'.$langs->trans('Voulez-vous créer un OT pour ce projet ?').'</p>
                <div style="display: flex; justify-content: center; gap: 15px;">
                    <button type="button" id="confirmCreateOT" style="
                        background-color: rgb(40, 80, 139);
                        color: white;
                        border: 1px solid rgb(40, 80, 139);
                        padding: 8px 16px;
                        border-radius: 3px;
                        cursor: pointer;
                        font-size: 13px;
                        font-weight: bold;
                        text-transform: uppercase;
                        min-width: 80px;
                    ">'.$langs->trans('Confirmer').'</button>
                    <button type="button" id="cancelCreateOT" style="
                        background-color: rgb(40, 80, 139);
                        color: white;
                        border: 1px solid rgb(40, 80, 139);
                        padding: 8px 16px;
                        border-radius: 3px;
                        cursor: pointer;
                        font-size: 13px;
                        font-weight: bold;
                        text-transform: uppercase;
                        min-width: 80px;
                    ">'.$langs->trans('Annuler').'</button>
                </div>
            `;
            
            // Ajouter un overlay pour assombrir le fond
            var overlay = document.createElement("div");
            overlay.style.position = "fixed";
            overlay.style.top = "0";
            overlay.style.left = "0";
            overlay.style.width = "100%";
            overlay.style.height = "100%";
            overlay.style.backgroundColor = "rgba(0,0,0,0.5)";
            overlay.style.zIndex = "999";
            
            document.body.appendChild(overlay);
            document.body.appendChild(popup);

            document.getElementById("confirmCreateOT").addEventListener("click", function() {
                document.body.removeChild(popup);
                document.body.removeChild(overlay);
                window.location.href = "'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=create_ot_from_button&token='.newToken().'";
            });

            document.getElementById("cancelCreateOT").addEventListener("click", function() {
                document.body.removeChild(popup);
                document.body.removeChild(overlay);
            });
        });
    </script>';
}


if (empty($preselectedtypeofcontact)) {
	$preselectedtypeofcontact = 0;
}

require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

$module = $object->element;

// Special cases
if (isset($permissiontoadd) && ! isset($permission)) {
	$permission = $permissiontoadd;
}
// TODO Remove this section. We already got $permissiontoadd.
if ($module == 'propal') {
	$permission = $user->hasRight('propal', 'creer');
} elseif ($module == 'fichinter') {
	$permission = $user->hasRight('ficheinter', 'creer');
} elseif ($module == 'order_supplier') {
	if (!getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD')) {
		$permission = $user->hasRight('fournisseur', 'commande', 'creer');
	} else {
		$permission = $user->hasRight('supplier_order', 'creer');
	}
} elseif ($module == 'invoice_supplier' && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD')) {
	if (!getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD')) {
		$permission = $user->hasRight('fournisseur', 'facture', 'creer');
	} else {
		$permission = $user->hasRight('supplier_invoice', 'creer');
	}
} elseif ($module == 'project') {
	$permission = $user->hasRight('projet', 'creer');
} elseif ($module == 'action') {
	$permission = $user->hasRight('agenda', 'myactions', 'create');
} elseif ($module == 'shipping') {
	$permission = $user->hasRight('expedition', 'creer');
} elseif ($module == 'reception') {
	$permission = $user->hasRight('reception', 'creer');
} elseif ($module == 'project_task') {
	$permission = $user->hasRight('projet', 'creer');
} elseif (!isset($permission) && $user->hasRight($module, 'creer')) {
	$permission = $user->hasRight($module, 'creer');
} elseif (!isset($permission) && $user->hasRight($module, 'write')) {
	$permission = $user->hasRight($module, 'write');
}

$formcompany = new FormCompany($db);
$companystatic = new Societe($db);
$contactstatic = new Contact($db);
$userstatic = new User($db);

?>

<!-- BEGIN PHP TEMPLATE CONTACTS -->
<?php
if ($permission) {
	print '<div class="underbanner clearboth"></div>'."\n";

	print '<div class="div-table-responsive-no-min">'."\n";
	print '<div class="tagtable tableforcontact centpercent noborder nobordertop allwidth">'."\n"; ?>
	<form class="tagtr liste_titre">
		<div class="tagtd liste_titre"><?php echo img_object('', 'company', 'class="optiongrey paddingright"').$langs->trans("ThirdParty"); ?></div>
		<div class="tagtd liste_titre"><?php echo img_picto($langs->trans("Users"), 'user', 'class="optiongrey paddingright"').$langs->trans("Users").' | '.img_picto($langs->trans("Contacts"), 'contact', 'class="optiongrey paddingright"').$langs->trans("Contacts"); ?></div>
		<div class="tagtd liste_titre"><?php echo $langs->trans("ContactType"); ?></div>
		<div class="tagtd liste_titre"><?php echo $langs->trans("Function"); ?></div>
		<div class="tagtd liste_titre">&nbsp;</div>
	</form>

	<?php

	if (empty($hideaddcontactforuser)) {
		?>
	<form class="tagtr impair nohover" action="<?php echo $_SERVER["PHP_SELF"].'?id='.$object->id; ?>" method="POST">
		<input type="hidden" name="token" value="<?php echo newToken(); ?>" />
		<input type="hidden" name="id" value="<?php echo $object->id; ?>" />
		<input type="hidden" name="action" value="addcontact" />
		<input type="hidden" name="source" value="internal" />
		<?php if (!empty($withproject)) {
			print '<input type="hidden" name="withproject" value="'.$withproject.'">';
		} ?>

		<div class="tagtd"><?php echo $conf->global->MAIN_INFO_SOCIETE_NOM; ?></div>
		<div class="tagtd maxwidthonsmartphone" style="width: 200px;"><?php echo img_object('', 'user', 'class="pictofixedwidth"').$form->select_dolusers($user->id, 'userid', 0, (!empty($userAlreadySelected) ? $userAlreadySelected : null), 0, null, null, 0, 56, 0, '', 0, '', 'minwidth100imp widthcentpercentminusxx maxwidth200'); ?></div>
		<div class="tagtd maxwidthonsmartphone" style="width: 150px;">
		<?php
		$tmpobject = $object;
		if (($object->element == 'shipping' || $object->element == 'reception') && is_object($objectsrc)) {
			$tmpobject = $objectsrc;
		}
		$formcompany->selectTypeContact($tmpobject, '', 'type', 'internal', 'position', 0, 'minwidth125imp widthcentpercentminusx maxwidth150'); ?></div>
		<div class="tagtd" style="width: 150px;">
			<?php
				// Utilisation de Form::selectarray pour générer le dropdown des fonctions avec le style Dolibarr
				$sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."contact_fonction WHERE active = 1 ORDER BY label ASC";
				$resql = $db->query($sql);
				$functionOptions = array();
				if ($resql) {
					while ($obj = $db->fetch_object($resql)) {
						$functionOptions[$obj->rowid] = $obj->label;
					}
				}
				print $form->selectarray('fonction_internal', $functionOptions, '', 1, 0, 0, '', 0, 0, 0, '', 'minwidth100imp widthcentpercentminusxx maxwidth200');
			?>
		</div>
		<div class="tagtd center" style="width: 150px; text-align: center; padding-bottom: 15px;">
			<input type="submit" class="button small" value="<?php echo $langs->trans("Add"); ?>" />
		</div>
	</form>

		<?php
	}

	if (empty($hideaddcontactforthirdparty)) {
		?>

	<form class="tagtr pair nohover" action="<?php echo $_SERVER["PHP_SELF"].'?id='.$object->id; ?>" method="POST">
		<input type="hidden" name="token" value="<?php echo newToken(); ?>" />
		<input type="hidden" name="id" value="<?php echo $object->id; ?>" />
		<input type="hidden" name="action" value="addcontact" />
		<input type="hidden" name="source" value="external" />
		<input type="hidden" name="page_y" value="" />
		<?php if (!empty($withproject)) {
			print '<input type="hidden" name="withproject" value="'.$withproject.'">';
		} ?>

		<div class="tagtd nowrap noborderbottom" style="width: 200px;">
			<?php
			$selectedCompany = GETPOSTISSET("newcompany") ? GETPOST("newcompany", 'int') : (empty($object->socid) ? 0 : $object->socid);
			$selectedCompany = $formcompany->selectCompaniesForNewContact($object, 'id', $selectedCompany, 'newcompany', '', 0, '', 'minwidth200imp');	// This also print the select component?>
		</div>
		<div class="tagtd noborderbottom" style="width: 200px;">
			<?php
			print img_object('', 'contact', 'class="pictofixedwidth"').$form->selectcontacts(($selectedCompany > 0 ? $selectedCompany : -1), '', 'contactid', 3, '', '', 1, 'minwidth100imp widthcentpercentminusxx maxwidth200');
			$nbofcontacts = $form->num;

			$newcardbutton = '';
			if (!empty($object->socid) && $object->socid > 1 && $user->hasRight('societe', 'creer')) {
				$newcardbutton .= '<a href="'.DOL_URL_ROOT.'/contact/card.php?socid='.$selectedCompany.'&action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?id='.$object->id).'" title="'.$langs->trans('NewContact').'"><span class="fa fa-plus-circle valignmiddle paddingleft"></span></a>';
			}
			print $newcardbutton; ?>
		</div>
		<div class="tagtd noborderbottom" style="width: 150px;">
			<?php
			$tmpobject = $object;
			if (($object->element == 'shipping' || $object->element == 'reception') && is_object($objectsrc)) {
				$tmpobject = $objectsrc;
			}
			$formcompany->selectTypeContact($tmpobject, $preselectedtypeofcontact, 'typecontact', 'external', 'position', 0, 'minwidth125imp widthcentpercentminusx maxwidth150'); ?>
		</div>
		<div class="tagtd noborderbottom" style="width: 150px;">
			<?php
			// Ajout d'un espace blanc pour aligner le bouton "Ajouter" des contacts externes
			print '<div style="min-width: 150px;"></div>';
			?>
		</div>
		<div class="tagtd center noborderbottom">
			<input type="submit" id="add-customer-contact" class="button small" value="<?php echo $langs->trans("Add"); ?>"<?php if (!$nbofcontacts) {
				echo ' disabled';
																					   } ?>>
		</div>
	</form>

		<?php
	}

	print "</div>";
	print "</div>";

	print '<br>';
}


// Prepare list

// TODO: replace this with direct SQL string to use $db->sort($sortfield, $sortorder)
$list = array();
foreach (array('internal', 'external') as $source) {
	if (($object->element == 'shipping' || $object->element == 'reception') && is_object($objectsrc)) {
		$contactlist = $objectsrc->liste_contact(-1, $source);
	} else {
		$contactlist = $object->liste_contact(-1, $source);
	}

	foreach ($contactlist as $contact) {
		$entry = new stdClass();
		$entry->id   = $contact['rowid'];
		$entry->type_id = $contact['fk_c_type_contact'];
		$entry->type = $contact['libelle'];
		$entry->nature = "";
		$entry->nature_html = "";
		$entry->thirdparty_id = 0;
		$entry->thirdparty_html = "";
		$entry->thirdparty_name = "";
		$entry->contact_html = "";
		$entry->contact_name = "";
		$entry->status = 0;
		$entry->status_html = "";

		if ($contact['source'] == 'internal') {
			$entry->nature = 'user';
			$entry->nature_html = $langs->trans("User");
		} elseif ($contact['source'] == 'external') {
			$entry->nature = 'thirdparty';
			$entry->nature_html = $langs->trans("ThirdPartyContact");
		}

		if ($contact['socid'] > 0) {
			$companystatic->fetch($contact['socid']);
			$entry->thirdparty_id   = $companystatic->id;
			$entry->thirdparty_html = $companystatic->getNomUrl(1);
			$entry->thirdparty_name = strtolower($companystatic->getFullName($langs));
		} elseif ($contact['socid'] < 0) {
			$entry->thirdparty_html = $conf->global->MAIN_INFO_SOCIETE_NOM;
			$entry->thirdparty_name = strtolower($conf->global->MAIN_INFO_SOCIETE_NOM);
		}

		if ($contact['source'] == 'internal') {
			$userstatic->fetch($contact['id']);
			$entry->contact_id   = $userstatic->id;
			$entry->contact_html = $userstatic->getNomUrl(-1, '', 0, 0, 0, 0, '', 'valignmiddle');
			$entry->contact_name = strtolower($userstatic->getFullName($langs));
		} elseif ($contact['source'] == 'external') {
			$contactstatic->fetch($contact['id']);
			$entry->contact_id   = $contactstatic->id;
			$entry->contact_html = $contactstatic->getNomUrl(1, '', 0, '', 0, 0);
			$entry->contact_name = strtolower($contactstatic->getFullName($langs));
		}

		if ($contact['source'] == 'internal') {
			$entry->status = $contact['statuscontact'];
			$entry->status_html = $userstatic->LibStatut($contact['statuscontact'], 3);
		} elseif ($contact['source'] == 'external') {
			$entry->status = $contact['statuscontact'];
			$entry->status_html = $contactstatic->LibStatut($contact['statuscontact'], 3);
		}

		$list[] = $entry;
	}
}


$sortfield = GETPOST("sortfield", "aZ09comma");
$sortorder = GETPOST("sortorder", 'aZ09comma');

if (!$sortfield) {
	$sortfield = "nature";
}
if (!$sortorder) {
	$sortorder = "asc";
}

// Re-sort list
$list = dol_sort_array($list, $sortfield, $sortorder, 1, 0, 1);

$arrayfields = array(
	'rowid' 		=> array('label'=>$langs->trans("Id"), 'checked'=>1),
	'nature' 		=> array('label'=>$langs->trans("NatureOfContact"), 'checked'=>1),
	'thirdparty' 	=> array('label'=>$langs->trans("ThirdParty"), 'checked'=>1),
	'contact' 		=> array('label'=>$langs->trans("Users").' | '.$langs->trans("Contacts"), 'checked'=>1),
	'type' 			=> array('label'=>$langs->trans("ContactType"), 'checked'=>1),
	'fonction' 		=> array('label'=>$langs->trans("Function"), 'checked'=>1),
	'status' 		=> array('label'=>$langs->trans("Status"), 'checked'=>1),
	'link' 			=> array('label'=>$langs->trans("Link"), 'checked'=>1),
);

$param = 'id='.$object->id.'&mainmenu=home';

/**
 * Show list
 */

// Ajout du bouton pour créer une OT
if ($permission) {
    require_once DOL_DOCUMENT_ROOT.'/custom/ot/class/actions_ot.class.php';
    $actionsOT = new ActionsOT($db);
    $actionsOT->formContactTpl(array(), $object, $action, $hookmanager);
}



print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

print '<div class="div-table-responsive-no-min">'."\n";
print '<table class="tagtable nobottomiftotal liste">';

print '<tr class="liste_titre">';
print_liste_field_titre($arrayfields['thirdparty']['label'], $_SERVER["PHP_SELF"], "thirdparty_name", "", $param, "", $sortfield, $sortorder);
print_liste_field_titre($arrayfields['contact']['label'], $_SERVER["PHP_SELF"], "contact_name", "", $param, "", $sortfield, $sortorder);
print_liste_field_titre($arrayfields['nature']['label'], $_SERVER["PHP_SELF"], "nature", "", $param, "", $sortfield, $sortorder);
print_liste_field_titre($arrayfields['type']['label'], $_SERVER["PHP_SELF"], "type", "", $param, "", $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Function"), $_SERVER["PHP_SELF"], "fonction", "", $param, "", $sortfield, $sortorder);
if ($permission) {
	print_liste_field_titre('', $_SERVER["PHP_SELF"], "", "", "", "", $sortfield, $sortorder, 'center maxwidthsearch ');
}
print "</tr>";

foreach ($list as $entry) {
	print '<tr class="oddeven" data-rowid="' . $entry->id . '">';

	print '<td class="tdoverflowmax200" data-thirdparty_id="' . ((int) $entry->thirdparty_id) . '" data-thirdparty_name="' . dol_escape_htmltag($entry->thirdparty_name) . '">'.$entry->thirdparty_html.'</td>';
	print '<td class="tdoverflowmax200" data-contact_id="' . ((int) $entry->contact_id) . '">'.$entry->contact_html.'</td>';
	print '<td class="nowrap" data-nature="' . dol_escape_htmltag($entry->nature) . '"><span class="opacitymedium">'.dol_escape_htmltag($entry->nature_html).'</span></td>';
	print '<td class="tdoverflowmax200" data-type_id="' . ((int) $entry->type_id) . '" data-type="' . dol_escape_htmltag($entry->type) . '">'.dol_escape_htmltag($entry->type).'</td>';
	
	// Affichage de la fonction
	print '<td class="tdoverflowmax200">';
	$sql = "SELECT cf.label 
			FROM ".MAIN_DB_PREFIX."contact_fonction as cf 
			INNER JOIN ".MAIN_DB_PREFIX."element_contact_fonction as ecf ON ecf.function_id = cf.rowid 
			WHERE ecf.element_id = ".((int) $object->id)." 
			AND ecf.contact_id = ".((int) $entry->contact_id)." 
			AND cf.active = 1";
	$resql = $db->query($sql);
	if ($resql && ($obj = $db->fetch_object($resql))) {
		print $obj->label;
	}
	print '</td>';

	if ($permission) {
		$href = $_SERVER["PHP_SELF"];
		$href .= '?id='.((int) $object->id);
		$href .= '&action=deletecontact&token='.newToken();
		$href .= '&lineid='.((int) $entry->id);

		print '<td class="center">';
		print '<a href="'.$href.'">';
		print img_picto($langs->trans("Unlink"), "unlink");
		print "</a>";
		print "</td>";
	}

	print "</tr>";
}
if (empty($list)) {
	$colspan = 5 + ($permission ? 1 : 0);
	print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">';
	if (is_object($object) && !empty($object->thirdparty)) {
		print $form->textwithpicto($langs->trans("NoSpecificContactAddress"), $langs->trans("NoSpecificContactAddressBis"));
	} else {
		print $langs->trans("NoSpecificContactAddress");
	}
	print '</span>';
	print '</td></tr>';
}
print "</table>";
print '</div>';

print "</form>";


print "<!-- END PHP TEMPLATE CONTACTS -->\n";
