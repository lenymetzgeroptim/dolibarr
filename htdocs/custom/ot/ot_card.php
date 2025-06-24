<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024 Faure Louis <l.faure@optim-industries.fr>
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
 *   	\file       ot_card.php
 *		\ingroup    ot
 *		\brief      Page to create/edit/view ot
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');				// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');		// Do not check injection attack on POST parameters
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');				// Do not check style html tag into posted data
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');					// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined("MAIN_SECURITY_FORCECSP"))   define('MAIN_SECURITY_FORCECSP', 'none');	// Disable all Content Security Policies
//if (! defined('CSRFCHECK_WITH_TOKEN'))     define('CSRFCHECK_WITH_TOKEN', '1');		// Force use of CSRF protection with tokens even for GET
//if (! defined('NOBROWSERNOTIF'))     		 define('NOBROWSERNOTIF', '1');				// Disable browser notification
//if (! defined('NOSESSION'))     		     define('NOSESSION', '1');				    // Disable session


// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; 
$tmp2 = realpath(__FILE__); 
$i = strlen($tmp) - 1; 
$j = strlen($tmp2) - 1;
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
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}


ini_set('display_errors',0);
ini_set('display_startup_errors', 0);
error_reporting(0);




// Check if it's a POST request (for saving data)




// Required files and initializations
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';


dol_include_once('/ot/class/ot.class.php');
dol_include_once('/ot/lib/ot_ot.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("ot@ot", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$lineid = GETPOST('lineid', 'int');

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)).basename(__FILE__, '.php'));
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$backtopagejsfields = GETPOST('backtopagejsfields', 'alpha');
$dol_openinpopup = GETPOST('dol_openinpopup', 'aZ09');

if (!empty($backtopagejsfields)) {
	$tmpbacktopagejsfields = explode(':', $backtopagejsfields);
	$dol_openinpopup = $tmpbacktopagejsfields[0];
}

// Initialize technical objects
$object = new Ot($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->ot->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('otcard', 'globalcard'));

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = GETPOST("search_all", 'alpha');
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha')) {
		$search[$key] = GETPOST('search_'.$key, 'alpha');
	}
}

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';

// Permission checks
$enablepermissioncheck = 0;
if ($enablepermissioncheck) {
	$permissiontoread = $user->hasRight('ot', 'ot', 'read');
	$permissiontoadd = $user->hasRight('ot', 'ot', 'write');
	$permissiontodelete = $user->hasRight('ot', 'ot', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
	$permissionnote = $user->hasRight('ot', 'ot', 'write');
	$permissiondellink = $user->hasRight('ot', 'ot', 'write');
} else {
	$permissiontoread = 1;
	$permissiontoadd = 1;
	$permissiontodelete = 1;
	$permissionnote = 1;
	$permissiondellink = 1;
}

$upload_dir = $conf->ot->multidir_output[isset($object->entity) ? $object->entity : 1].'/ot';

if (!isModEnabled("ot")) {
	accessforbidden();
}
if (!$permissiontoread) {
	accessforbidden();
}



$data = json_decode(file_get_contents('php://input'), true);

// Vérifier le statut de l'OT
$sql = "SELECT status FROM " . MAIN_DB_PREFIX . "ot_ot WHERE rowid = " . intval($data['otid']);
$resql = $db->query($sql);
if ($resql && $db->num_rows($resql) > 0) {
    $obj = $db->fetch_object($resql);
    if ($obj->status == 1 || $obj->status == 2) {
        // Si le statut est actif (1) ou terminé (2), on bloque la sauvegarde
        header('Content-Type: application/json');
        echo json_encode(array('status' => 'error', 'message' => 'Les modifications ne sont pas autorisées car l\'OT est active ou terminée.'));
        exit;
    }
}

// Commencer une transaction
$db->begin();

try {
    $insertedCellIds = [];
    $receivedCellIds = [];
    $receivedSubcontractors = []; // Stocker les sous-traitants reçus

    $otId = isset($data['otid']) ? intval($data['otid']) : 0;

    // Récupérer toutes les cellules existantes pour cet OT
    $existingCellIds = [];
    $sql = "SELECT id_cellule, rowid FROM " . MAIN_DB_PREFIX . "ot_ot_cellule WHERE ot_id = $otId";
    $resql = $db->query($sql);
    while ($row = $db->fetch_object($resql)) {
        $existingCellIds[$row->id_cellule] = $row->rowid;
    }

    // Traiter chaque élément de `cardsData`
    if (isset($data['cardsData']) && is_array($data['cardsData'])) {
        foreach ($data['cardsData'] as $item) {
            $title = isset($item['title']) ? $db->escape($item['title']) : '';
            $type = isset($item['type']) ? $db->escape($item['type']) : 'card';
            $x = isset($item['x']) ? intval($item['x']) : 0;
            $y = isset($item['y']) ? intval($item['y']) : 0;
            $cellId = isset($item['id']) ? $db->escape($item['id']) : '';
            $role = isset($item['role']) ? $db->escape($item['role']) : '';
            $userId = isset($item['userId']) ? intval($item['userId']) : null;

            $receivedCellIds[] = $cellId; // Stocker les ID reçus

            if ($type !== 'listesoustraitant') {
                if (isset($existingCellIds[$cellId])) {
                    $rowid = $existingCellIds[$cellId];

                    // Mise à jour de la cellule existante
                    $sql = "UPDATE " . MAIN_DB_PREFIX . "ot_ot_cellule 
                            SET x = $x, y = $y, type = '$type', title = '$title' 
                            WHERE rowid = $rowid AND ot_id = $otId";
                    if (!$db->query($sql)) {
                        throw new Exception("Erreur lors de la mise à jour de la cellule : " . $db->lasterror());
                    }
                } else {
                    // Insérer une nouvelle cellule
                    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_cellule (ot_id, x, y, type, title, id_cellule)
                            VALUES ($otId, $x, $y, '$type', '$title', '$cellId')";
                    if (!$db->query($sql)) {
                        throw new Exception("Erreur lors de l'insertion dans ot_ot_cellule : " . $db->lasterror());
                    }

                    $rowid = $db->last_insert_id(MAIN_DB_PREFIX . 'ot_ot_cellule');
                    $insertedCellIds[] = $rowid;
                    $existingCellIds[$cellId] = $rowid;
                }
            }

            // Gestion des rôles principaux (RA, Q3, PCR)
            if ($type === 'cardprincipale' && in_array($role, ['RA', 'Q3', 'PCR'])) {
                // Vérifier si une cellule existe déjà pour ce rôle
                $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "ot_ot_cellule WHERE ot_id = $otId AND type = '$type' AND title = '$role'";
                $resql = $db->query($sql);
                $row = $resql ? $db->fetch_object($resql) : null;
            
                if (!$row) {
                    // Insérer une nouvelle cellule
                    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_cellule (ot_id, type, title) 
                            VALUES ($otId, '$type', '$role')";
                    if (!$db->query($sql)) {
                        throw new Exception("Erreur lors de l'insertion dans ot_ot_cellule : " . $db->lasterror());
                    }
                    $otCelluleId = $db->last_insert_id(MAIN_DB_PREFIX . 'ot_ot_cellule');
            
                    // Insérer dans ot_ot_cellule_donne même si l'utilisateur est null
                    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_cellule_donne (ot_cellule_id, fk_user, role) 
                            VALUES ($otCelluleId, " . ($userId !== null ? $userId : "NULL") . ", '$role')";
                    if (!$db->query($sql)) {
                        throw new Exception("Erreur lors de l'insertion dans ot_ot_cellule_donne : " . $db->lasterror());
                    }
                } else {
                    // Si la cellule existe déjà, mettre à jour l'utilisateur associé
                    $otCelluleId = $row->rowid;
            
                    // Vérifier si une entrée existe déjà dans ot_ot_cellule_donne
                    $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "ot_ot_cellule_donne WHERE ot_cellule_id = $otCelluleId AND role = '$role'";
                    $resql = $db->query($sql);
                    $donneRow = $resql ? $db->fetch_object($resql) : null;
            
                    if ($donneRow) {
                        // Mettre à jour l'utilisateur si une entrée existe déjà
                        $sql = "UPDATE " . MAIN_DB_PREFIX . "ot_ot_cellule_donne 
                                SET fk_user = " . ($userId !== null ? $userId : "NULL") . " 
                                WHERE rowid = " . $donneRow->rowid;
                        if (!$db->query($sql)) {
                            throw new Exception("Erreur lors de la mise à jour de l'utilisateur dans ot_ot_cellule_donne : " . $db->lasterror());
                        }
                    } else {
                        // Insérer une nouvelle entrée dans ot_ot_cellule_donne si elle n'existe pas
                        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_cellule_donne (ot_cellule_id, fk_user, role) 
                                VALUES ($otCelluleId, " . ($userId !== null ? $userId : "NULL") . ", '$role')";
                        if (!$db->query($sql)) {
                            throw new Exception("Erreur lors de l'insertion dans ot_ot_cellule_donne : " . $db->lasterror());
                        }
                    }
                }
            }

            // Gestion des userId pour le type 'card' (ajout ou mise à jour)
            if ($type === 'card' && isset($item['userId'])) {
                $userid = intval($item['userId']);
                if (isset($existingCellIds[$cellId])) {
                    $otCelluleId = $existingCellIds[$cellId];

                    // Vérifier si un userid est déjà enregistré
                    $sql = "SELECT fk_user FROM " . MAIN_DB_PREFIX . "ot_ot_cellule_donne WHERE ot_cellule_id = $otCelluleId";
                    $resql = $db->query($sql);
                    $existingUser = $resql ? $db->fetch_object($resql) : null;

                    if ($existingUser) {
                        // Mettre à jour si le userId est différent
                        if ($existingUser->fk_user != $userid) {
                            $sql = "UPDATE " . MAIN_DB_PREFIX . "ot_ot_cellule_donne SET fk_user = $userid WHERE ot_cellule_id = $otCelluleId";
                            if (!$db->query($sql)) {
                                throw new Exception("Erreur lors de la mise à jour du userId dans ot_ot_cellule_donne : " . $db->lasterror());
                            }
                        }
                    } else {
                        // Insérer un nouveau userId
                        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_cellule_donne (ot_cellule_id, fk_user) VALUES ($otCelluleId, $userid)";
                        if (!$db->query($sql)) {
                            throw new Exception("Erreur lors de l'insertion du userId dans ot_ot_cellule_donne : " . $db->lasterror());
                        }
                    }
                }
            }

            // GESTION DES userIds POUR LES LISTES ET LISTEUNIQUE
            if (($type === 'list' || $type === 'listeunique') && isset($item['userIds']) && is_array($item['userIds'])) {
                $userIds = $item['userIds']; // Récupère les userIds depuis le JSON
                if (isset($existingCellIds[$cellId])) {
                    $otCelluleId = $existingCellIds[$cellId];

                    // Récupérer les userIds existants dans la base de données
                    $sql = "SELECT fk_user FROM " . MAIN_DB_PREFIX . "ot_ot_cellule_donne WHERE ot_cellule_id = $otCelluleId";
                    $resql = $db->query($sql);
                    $existingUserIds = [];
                    while ($row = $db->fetch_object($resql)) {
                        $existingUserIds[] = $row->fk_user;
                    }

                    // Trouver les userIds à supprimer (ceux qui ne sont plus dans le JSON)
                    $userIdsToDelete = array_diff($existingUserIds, $userIds);
                    if (!empty($userIdsToDelete)) {
                        $userIdsToDeleteString = implode(',', $userIdsToDelete);
                        // Supprimer les userIds non présents dans le JSON
                        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "ot_ot_cellule_donne WHERE ot_cellule_id = $otCelluleId AND fk_user IN ($userIdsToDeleteString)";
                        if (!$db->query($sql)) {
                            throw new Exception("Erreur lors de la suppression des userIds obsolètes dans ot_ot_cellule_donne : " . $db->lasterror());
                        }
                    }

                    // Ajouter les nouveaux userIds qui ne sont pas encore enregistrés
                    foreach ($userIds as $userid) {
                        if (!in_array($userid, $existingUserIds)) {
                            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_cellule_donne (ot_cellule_id, fk_user) VALUES ($otCelluleId, $userid)";
                            if (!$db->query($sql)) {
                                throw new Exception("Erreur lors de l'insertion du userId dans ot_ot_cellule_donne : " . $db->lasterror());
                            }
                        }
                    }
                }
            }

            // Gestion des sous-traitants pour les listes de type 'listesoustraitant'
            if ($type === 'listesoustraitant' && isset($item['soustraitants']) && is_array($item['soustraitants'])) {
                foreach ($item['soustraitants'] as $soustraitant) {
                    $fk_socpeople = intval($soustraitant['soc_people']);
                    $fk_societe = $db->escape($soustraitant['supplier_id']);
                    $fonction = $db->escape($soustraitant['fonction']);
                    $contrat = $db->escape($soustraitant['contrat']);
                    $habilitation = $db->escape($soustraitant['habilitation']);

                    $receivedSubcontractors[] = $fk_socpeople; // Stocker les sous-traitants reçus

                    // Vérifier si le sous-traitant est déjà enregistré
                    $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "ot_ot_sous_traitants 
                            WHERE ot_id = $otId AND fk_socpeople = $fk_socpeople";
                    $resql = $db->query($sql);

                    if ($resql && $db->num_rows($resql) > 0) {
                        $row = $db->fetch_object($resql);
                        $rowid = $row->rowid;

                        // Mise à jour des informations du sous-traitant
                        $sql = "UPDATE " . MAIN_DB_PREFIX . "ot_ot_sous_traitants 
                                SET fonction = '$fonction', contrat = '$contrat', habilitation = '$habilitation', fk_societe = '$fk_societe'
                                WHERE rowid = $rowid AND ot_id = $otId";
                        if (!$db->query($sql)) {
                            throw new Exception("Erreur lors de la mise à jour du sous-traitant : " . $db->lasterror());
                        }
                    } else {
                        // Insérer un nouveau sous-traitant
                        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_sous_traitants (ot_id, fk_socpeople, fonction, contrat, habilitation, fk_societe) 
                                VALUES ($otId, $fk_socpeople, '$fonction', '$contrat', '$habilitation', '$fk_societe')";
                        if (!$db->query($sql)) {
                            throw new Exception("Erreur lors de l'insertion du sous-traitant : " . $db->lasterror());
                        }
                    }
                }

                // Supprimer les sous-traitants non reçus, mais uniquement ceux qui ne proviennent pas du projet
                    $sql = "SELECT fk_socpeople FROM " . MAIN_DB_PREFIX . "ot_ot_sous_traitants WHERE ot_id = $otId";
                    $resql = $db->query($sql);
                    $existingSubcontractors = [];

                    while ($row = $db->fetch_object($resql)) {
                        $existingSubcontractors[] = $row->fk_socpeople;
                    }

                    // Ne supprimer que les sous-traitants qui ne sont pas dans les données reçues ET qui ne proviennent pas du projet
                    $subcontractorsToDelete = array_diff($existingSubcontractors, $receivedSubcontractors);
                    if (!empty($subcontractorsToDelete)) {
                        $subcontractorsToDeleteString = implode(',', $subcontractorsToDelete);

                        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "ot_ot_sous_traitants WHERE ot_id = $otId AND fk_socpeople IN ($subcontractorsToDeleteString)";
                        if (!$db->query($sql)) {
                            throw new Exception("Erreur lors de la suppression des sous-traitants obsolètes : " . $db->lasterror());
                        }
                    }
            }


            
            
        }
    }

    // Supprimer les cellules non reçues
    $cellsToDelete = array_diff(array_keys($existingCellIds), $receivedCellIds);
    if (!empty($cellsToDelete)) {
        $cellsToDeleteString = implode("','", $cellsToDelete);

        // Récupérer les ID des cellules à supprimer
        $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "ot_ot_cellule WHERE ot_id = $otId AND id_cellule IN ('$cellsToDeleteString')";
        $resql = $db->query($sql);
        $cellIdsToDelete = [];

        while ($row = $db->fetch_object($resql)) {
            $cellIdsToDelete[] = $row->rowid;
        }

        if (!empty($cellIdsToDelete)) {
            $cellIdsToDeleteString = implode(',', $cellIdsToDelete);

            // Supprimer les entrées dans ot_ot_cellule_donne
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "ot_ot_cellule_donne WHERE ot_cellule_id IN ($cellIdsToDeleteString)";
            if (!$db->query($sql)) {
                throw new Exception("Erreur lors de la suppression des userId liés aux cellules supprimées : " . $db->lasterror());
            }

            // Supprimer les cellules
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "ot_ot_cellule WHERE rowid IN ($cellIdsToDeleteString)";
            if (!$db->query($sql)) {
                throw new Exception("Erreur lors de la suppression des cellules : " . $db->lasterror());
            }
        }
    }

    // Valider la transaction
    $db->commit();

} catch (Exception $e) {
    // En cas d'erreur, annuler la transaction
    $db->rollback();
    error_log($e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}



/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/ot/ot_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/ot/ot_card.php', 1).'?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'OT_MYOBJECT_MODIFY'; // Name of trigger action code to execute when we modify record

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Action to move up and down lines of object
	//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';

	// Action to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	if ($action == 'set_thirdparty' && $permissiontoadd) {
		$object->setValueFrom('fk_soc', GETPOST('fk_soc', 'int'), '', '', 'date', '', $user, $triggermodname);
	}
	if ($action == 'classin' && $permissiontoadd) {
		$object->setProject(GETPOST('projectid', 'int'));
	}

	// Actions to send emails
	$triggersendname = 'OT_MYOBJECT_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_MYOBJECT_TO';
	$trackid = 'ot'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

	if ($action == 'confirm_archive' && $confirm == 'yes' && $permissiontoadd) {
		$result = $object->setArchive($user);
		if ($result > 0) {
			// Define output language
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$model = $object->model_pdf;
				$ret = $object->generateDocument($model, $outputlangs, 0, 0, 0);
				if ($ret < 0) {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
		header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
		exit;
	}

	if ($action == 'confirm_validate' && $confirm == 'yes' && $permissiontoadd) {
		// ... existing code ...
	}
}




/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("Ot");
$help_url = '';
$array_js = array('/ot/js/ot.js.php');
llxHeader("", $title, $help_url, '', '', '', $array_js);


// Example : Adding jquery code
// print '<script type="text/javascript">
// jQuery(document).ready(function() {
// 	function init_myfunc()
// 	{
// 		jQuery("#myid").removeAttr(\'disabled\');
// 		jQuery("#myid").attr(\'disabled\',\'disabled\');
// 	}
// 	init_myfunc();
// 	jQuery("#mybutton").click(function() {
// 		init_myfunc();
// 	});
// });
// </script>';








// Part to create
if ($action == 'create') {
	if (empty($permissiontoadd)) {
		accessforbidden('NotEnoughPermissions', 0, 1);
	}

	print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("Ot")), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}
	if ($backtopagejsfields) {
		print '<input type="hidden" name="backtopagejsfields" value="'.$backtopagejsfields.'">';
	}
	if ($dol_openinpopup) {
		print '<input type="hidden" name="dol_openinpopup" value="'.$dol_openinpopup.'">';
	}

	print dol_get_fiche_head(array(), '');

	// Set some default values
	//if (! GETPOSTISSET('fieldname')) $_POST['fieldname'] = 'myvalue';

	print '<table class="border centpercent tableforfieldcreate">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Create");

	print '</form>';

	//dol_set_focus('input[name="ref"]');
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("Ot"), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';
}



// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$head = otPrepareHead($object);

	print dol_get_fiche_head($head, 'card', $langs->trans("Ot"), -1, $object->picto, 0, '', '', 0, '', 1);
	

	$formconfirm = '';
	
	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteOt'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}

	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

    if($action == 'confirm_genererDocConstat' && $confirm == 'yes') {
        if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
            if (method_exists($object, 'generateDocument') && !$error) {
                $outputlangs = $langs;
                $newlang = '';
                if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
                    $newlang = GETPOST('lang_id', 'aZ09');
                }
                if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
                    $newlang = $object->thirdparty->default_lang;
                }
                if (!empty($newlang)) {
                    $outputlangs = new Translate("", $conf);
                    $outputlangs->setDefaultLang($newlang);
                }
				
                $model = 'standard_ot';
 
                $retgen = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
                if ($retgen < 0) {
                    setEventMessages($object->error, $object->errors, 'warnings');
                }
            }
        }
    }


	/*	if ($action == 'remplir') {
		print '
		<!-- Définition de la modale -->
		<div class="modal fade" id="orgChartModal" tabindex="-1" role="dialog" aria-labelledby="orgChartModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="orgChartModalLabel">'.$langs->trans('Prévisualisation de l\'Organigramme de Travail').'</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">
						<!-- Conteneur de l\'organigramme -->
						<div id="orgChartContainer">
							<!-- Les lignes et cases de l\'organigramme apparaîtront ici -->
						</div>
						<button type="button" class="btn btn-secondary" id="addLineButton">'.$langs->trans('Ajouter une ligne').'</button>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-dismiss="modal">'.$langs->trans('Fermer').'</button>
						<button type="button" class="btn btn-primary" id="saveChangesButton">'.$langs->trans('Enregistrer les modifications').'</button>
					</div>
				</div>
			</div>
		</div>
		';
	}

	print '
	<script>
	$(document).ready(function() {
		// Vérifier l\'action et afficher la modale si nécessaire
		var action = "'.htmlentities($action).'";
		if(action === "remplir") {
			$("#orgChartModal").modal("show");
		}
	});
	</script>
	';
	*/
	// Confirmation of action xxxx (You can use it for xxx = 'close', xxx = 'reopen', ...)
	if ($action == 'xxx') {
		$text = $langs->trans('ConfirmActionOt', $object->ref);
		/*if (isModEnabled('notification'))
		{
			require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
			$notify = new Notify($db);
			$text .= '<br>';
			$text .= $notify->confirmMessage('MYOBJECT_CLOSE', $object->socid, $object);
		}*/




		$formquestion = array();
	
		/*
		$forcecombo=0;
		if ($conf->browser->name == 'ie') $forcecombo = 1;	// There is a bug in IE10 that make combo inside popup crazy
		$formquestion = array(
			// 'text' => $langs->trans("ConfirmClone"),
			// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
			// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
			// array('type' => 'other',    'name' => 'idwarehouse',   'label' => $langs->trans("SelectWarehouseForStockDecrease"), 'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse')?GETPOST('idwarehouse'):'ifone', 'idwarehouse', '', 1, 0, 0, '', 0, $forcecombo))
		);
		*/
		
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('XXX'), $text, 'confirm_xxx', $formquestion, 0, 1, 220);
	
		
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/ot/ot_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	/*
		// Ref customer
		$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, $usercancreate, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, $usercancreate, 'string'.(getDolGlobalInt('THIRDPARTY_REF_INPUT_SIZE') ? ':'.getDolGlobalInt('THIRDPARTY_REF_INPUT_SIZE') : ''), '', null, null, '', 1);
		// Thirdparty
		$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1, 'customer');
		if (!getDolGlobalInt('MAIN_DISABLE_OTHER_LINK') && $object->thirdparty->id > 0) {
			$morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/commande/list.php?socid='.$object->thirdparty->id.'&search_societe='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherOrders").'</a>)';
		}
		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			$morehtmlref .= '<br>';
			if ($permissiontoadd) {
				$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
				if ($action != 'classify') {
					$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
				}
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
			} else {
				if (!empty($object->fk_project)) {
					$proj = new Project($db);
					$proj->fetch($object->fk_project);
					$morehtmlref .= $proj->getNomUrl(1);
					if ($proj->title) {
						$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
					}
				}
			}
		}
	*/
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	//------------------------------------------------------------------------------------------------
	// L'ORGANIGRAMME :
	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	//$keyforbreak='fieldkeytoswitchonsecondcolumn';	// We change column just before this field
	//unset($object->fields['fk_project']);				// Hide field already shown in banner
	//unset($object->fields['fk_soc']);					// Hide field already shown in banner
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();


	/*
	 * Lines
	 */

	if (!empty($object->table_element_line)) {
		// Show object lines
		$result = $object->getLinesArray();

		print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '' : '#line_'.GETPOST('lineid', 'int')).'" method="POST">
		<input type="hidden" name="token" value="' . newToken().'">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="page_y" value="">
		<input type="hidden" name="id" value="' . $object->id.'">
		';

		if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
		}

		print '<div class="div-table-responsive-no-min">';
		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '<table id="tablelines" class="noborder noshadow" width="100%">';
		}

		if (!empty($object->lines)) {
			$object->printObjectLines($action, $mysoc, null, GETPOST('lineid', 'int'), 1);
		}

		// Form to add new line
		if ($object->status == 0 && $permissiontoadd && $action != 'selectlines') {
			if ($action != 'editline') {
				// Add products/services form

				$parameters = array();
				$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
				if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				if (empty($reshook))
					$object->formAddObjectLine(1, $mysoc, $soc);
			}
		}

		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '</table>';
		}
		print '</div>';

		print "</form>\n";
	}

	//------------------------------------------------------------------------------------------------------------------------------------------------
	// Buttons for actions

	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			// Send button - commented out
			/*
			if (empty($user->socid)) {
				print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&token='.newToken().'&mode=init#formmailbeforetitle');
			}
			*/

			// Back to draft
			if ($object->status == $object::STATUS_VALIDATED) {
				print dolGetButtonAction('', $langs->trans('SetToDraft'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_setdraft&confirm=yes&token='.newToken(), '', $permissiontoadd);
			}

			print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissiontoadd);

			// Validate
			if ($object->status == $object::STATUS_DRAFT) {
				if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
					print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&confirm=yes&token='.newToken(), '', $permissiontoadd);
				} else {
					$langs->load("errors");
					print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
				}
			}

			// Archive
			if ($object->status == $object::STATUS_VALIDATED) {
				print dolGetButtonAction('', $langs->trans('Archiver'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_archive&confirm=yes&token='.newToken(), '', $permissiontoadd);
			}

			//généré pdf constat
			
            print dolGetButtonAction('', $langs->trans('généré PDF'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_genererDocConstat&confirm=yes&token='.newToken(), '', $permissiontoadd);
                
                /*//passé au  Status Cancel
                if ($user->rights->constat->constat->ResponsableQ3SE) {
                    if ($object->status == $object::STATUS_EN_COURS) {
                        print dolGetButtonAction('', $langs->trans('passé au status cancel'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=setCancel&confirm=yes&token='.newToken(), '', $permissiontoadd);
                        
                    }
                }*/
			

			// Clone
			/*if ($permissiontoadd) {
				print dolGetButtonAction('', $langs->trans('ToClone'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.(!empty($object->socid)?'&socid='.$object->socid:'').'&action=clone&token='.newToken(), '', $permissiontoadd);
			}*/

			/*
			if ($permissiontoadd) {
				if ($object->status == $object::STATUS_ENABLED) {
					print dolGetButtonAction('', $langs->trans('Disable'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=disable&token='.newToken(), '', $permissiontoadd);
				} else {
					print dolGetButtonAction('', $langs->trans('Enable'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=enable&token='.newToken(), '', $permissiontoadd);
				}
			}
			if ($permissiontoadd) {
				if ($object->status == $object::STATUS_VALIDATED) {
					print dolGetButtonAction('', $langs->trans('Cancel'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=close&token='.newToken(), '', $permissiontoadd);
				} else {
					print dolGetButtonAction('', $langs->trans('Re-Open'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=reopen&token='.newToken(), '', $permissiontoadd);
				}
			}
			*/

			// Delete
			$params = array();
			// print dolGetButtonAction('', $langs->trans("Delete"), 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken(), 'delete', $permissiontodelete, $params);
		}
		print '</div>'."\n";
	}


	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

//------------------------------------------------------------------------------------------------------------------------------------------------------------	


$sql = "SELECT u.lastname, u.firstname, u.rowid, 
        GROUP_CONCAT(DISTINCT fh.ref) as habilitations,
        cct.type AS contrat
        FROM ".MAIN_DB_PREFIX."user as u
        LEFT JOIN ".MAIN_DB_PREFIX."formationhabilitation_userhabilitation as fuh 
            ON u.rowid = fuh.fk_user
        LEFT JOIN ".MAIN_DB_PREFIX."formationhabilitation_habilitation as fh 
            ON fuh.fk_habilitation = fh.rowid
        LEFT JOIN ".MAIN_DB_PREFIX."ot_ot_cellule_donne AS ocd 
            ON ocd.fk_user = u.rowid
        LEFT JOIN ".MAIN_DB_PREFIX."donneesrh_positionetcoefficient_extrafields AS drh 
            ON drh.fk_object = u.rowid
        LEFT JOIN ".MAIN_DB_PREFIX."c_contrattravail AS cct 
            ON drh.contratdetravail = cct.rowid
        WHERE u.statut = 1
        GROUP BY u.rowid, u.lastname, u.firstname, cct.type";

$resql = $db->query($sql);

if ($resql) {
    $arrayresult = [];
    while ($obj = $db->fetch_object($resql)) {
        $arrayresult[] = $obj; 
		
    }
} else {
    $arrayresult = array('error' => 'SQL query error: '.$db->lasterror());
}

// Convertir les résultats en JSON
$userjson = json_encode($arrayresult);


// Fonction pour récupérer les fonctions d'un utilisateur sur un projet
function getFonctions($userId, $projectId, $db) {
    $fonctions = array();
    
    // Récupération des fonctions depuis la table element_contact_fonction
    $sql = "SELECT cf.label, cf.rowid 
            FROM ".MAIN_DB_PREFIX."element_contact_fonction as ecf 
            INNER JOIN ".MAIN_DB_PREFIX."contact_fonction as cf ON cf.rowid = ecf.function_id 
            WHERE ecf.element_id = ".((int) $projectId)." 
            AND ecf.contact_id = ".((int) $userId)." 
            AND cf.status = 1";
    
    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            // Conversion des fonctions en anagrammes
            $anagramme = '';
            switch (strtoupper($obj->label)) {
                case 'RESPONSABLE D\'AFFAIRE':
                    $anagramme = 'RA';
                    break;
                case 'RESPONSABLE DE SITE':
                    $anagramme = 'RS';
                    break;
                case 'RESPONSABLE DE SUIVI D\'INTERVENTION':
                    $anagramme = 'RSI';
                    break;
                case 'RESPONSABLE D\'ÉQUIPES':
                    $anagramme = 'RE';
                    break;
                case 'RESPONSABLE D\'INTERVENTION':
                    $anagramme = 'RI';
                    break;
                case 'CHARGÉ DE TRAVAUX':
                    $anagramme = 'CdT';
                    break;
                case 'CONTRÔLEUR TECHNIQUE':
                    $anagramme = 'CT';
                    break;
                case 'VÉRIFICATEUR':
                    $anagramme = 'V';
                    break;
                case 'INTERVENANT':
                    $anagramme = 'INT';
                    break;
                case 'RESPONSABLE DU SUIVI RADIOLOGIQUE / PERSONNE TECHNiquement COMPÉTENTE':
                    $anagramme = 'RSR/PTC';
                    break;
                case 'PRIMO-INTERVENANT':
                    $anagramme = 'PI';
                    break;
                case 'TUTEUR PRIMO-INTERVENANT':
                    $anagramme = 'TPI';
                    break;
                case 'PERSONNEL EN COMPAGNONNAGE':
                    $anagramme = 'CO';
                    break;
                case 'SAUVETEUR SECOURISTE DU TRAVAIL':
                    $anagramme = 'SST';
                    break;
                default:
                    $anagramme = $obj->label;
            }
            
            $fonctions[] = $anagramme;
        }
    }
    
    return !empty($fonctions) ? implode('-', $fonctions) : null;
}

// Fonction pour récupérer les habilitations d'un utilisateur
function getHabilitations($userId, $db) {
    $habilitationRefs = [];

    $sql = "SELECT fh.ref 
            FROM ".MAIN_DB_PREFIX."formationhabilitation_userhabilitation as fuh 
            JOIN ".MAIN_DB_PREFIX."formationhabilitation_habilitation as fh 
                ON fuh.fk_habilitation = fh.rowid 
            WHERE fuh.fk_user = ".intval($userId);

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $habilitationRefs[] = $obj->ref;
        }
    }

    // Retourner une chaîne de refs séparées par des "-"
    return !empty($habilitationRefs) ? implode("-", $habilitationRefs) : null;
}

$sql = "
    (SELECT  
        u.firstname,
        u.lastname,
        u.office_phone AS phone,
        ctc.libelle, 
        sp.fk_c_type_contact, 
        sp.fk_socpeople,
        cct.type AS contrat,
        ctc.source AS source,
        NULL AS fonction,      
        NULL AS habilitation,
        NULL AS fk_societe,
        NULL AS societe_nom  
    FROM ".MAIN_DB_PREFIX."element_contact AS sp 
    JOIN ".MAIN_DB_PREFIX."user AS u 
        ON sp.fk_socpeople = u.rowid 
    JOIN ".MAIN_DB_PREFIX."c_type_contact AS ctc 
        ON sp.fk_c_type_contact = ctc.rowid 
    LEFT JOIN ".MAIN_DB_PREFIX."donneesrh_positionetcoefficient_extrafields AS drh 
        ON drh.fk_object = u.rowid  
    LEFT JOIN ".MAIN_DB_PREFIX."c_contrattravail AS cct 
        ON drh.contratdetravail = cct.rowid  
    WHERE sp.element_id = $object->fk_project
    AND sp.statut = 4
    AND ctc.element = 'project'
    AND ctc.source = 'internal')

    UNION

    (SELECT  
        spc.firstname,
        spc.lastname,
        spc.phone_mobile AS phone,
        ctc.libelle, 
        sp.fk_c_type_contact, 
        sp.fk_socpeople,
        ots.contrat AS contrat,
        ctc.source AS source,
        ots.fonction,  
        ots.habilitation,
        spc.fk_soc as fk_societe,
        s.nom AS societe_nom
    FROM ".MAIN_DB_PREFIX."element_contact AS sp 
    JOIN ".MAIN_DB_PREFIX."socpeople AS spc 
        ON sp.fk_socpeople = spc.rowid 
    JOIN ".MAIN_DB_PREFIX."c_type_contact AS ctc 
        ON sp.fk_c_type_contact = ctc.rowid 
    LEFT JOIN ".MAIN_DB_PREFIX."ot_ot_sous_traitants AS ots 
        ON sp.fk_socpeople = ots.fk_socpeople 
        AND ots.ot_id = $object->id
    LEFT JOIN ".MAIN_DB_PREFIX."societe AS s 
        ON spc.fk_soc = s.rowid
    WHERE sp.element_id = $object->fk_project
    AND sp.statut = 4
    AND ctc.element = 'project'
    AND ctc.source = 'external')";

$resql = $db->query($sql);

if ($resql) {
    $arrayresult = [];

    while ($obj = $db->fetch_object($resql)) {
        // Pour les utilisateurs internes, on récupère les fonctions et habilitations avec les fonctions externes
        if ($obj->source == 'internal') {
            // Récupérer les fonctions sous forme de chaîne (ex: "RA-CT")
            $obj->fonction = getFonctions($obj->fk_socpeople, $object->fk_project, $db);
            
            // Récupérer les habilitations sous forme de chaîne (ex: "B0-HN1-HN2")
            $obj->habilitation = getHabilitations($obj->fk_socpeople, $db);
        }

        // Ajouter l'utilisateur au résultat final
        $arrayresult[] = $obj; 
    }
} else {
    $arrayresult = array('error' => 'SQL query error: '.$db->lasterror());
}

// Convertir les résultats en JSON
$data = json_encode($arrayresult);



$otId = $object->id;

$usercard = new User($db);

// Définir le filtre pour récupérer seulement les utilisateurs actifs
$filter = array('statut' => 1);


//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------

$otId = $object->id;  // L'ID de l'OT actuel

// Récupérer toutes les cellules liées à l'OT, triées par tms (le plus récent en premier)
$sql = "SELECT oc.rowid, oc.x, oc.y, oc.type, oc.title, oc.tms
        FROM " . MAIN_DB_PREFIX . "ot_ot_cellule as oc
        WHERE oc.ot_id = " . intval($otId) . "
        ORDER BY oc.x ASC, oc.y ASC, oc.tms DESC";

$resql = $db->query($sql);

$cellData = [];
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $cellData[] = $obj;  // On stocke chaque cellule
    }
}

// Ajouter les utilisateurs aux cellules existantes
foreach ($cellData as $cell) {
    // Vérifier si la cellule est valide
    if (!is_object($cell) || !isset($cell->rowid)) {
        continue;
    }

    // Ajouter les informations utilisateur uniquement pour les cards principales
    if ($cell->type === 'cardprincipale') {
        $sql = "SELECT 
                    u.rowid AS userId,
                    u.firstname,
                    u.lastname,
                    u.office_phone AS phone
                FROM " . MAIN_DB_PREFIX . "ot_ot_cellule_donne AS ocd
                JOIN " . MAIN_DB_PREFIX . "user AS u ON ocd.fk_user = u.rowid
                WHERE ocd.ot_cellule_id = " . intval($cell->rowid);

        $resql = $db->query($sql);

        if ($resql) {
            $user = $db->fetch_object($resql);
            if ($user) {
                // Ajouter les informations utilisateur à la cellule
                $cell->userId = $user->userId;
                $cell->firstname = $user->firstname;
                $cell->lastname = $user->lastname;
                $cell->phone = $user->phone ?? '';
            } else {
                // Si aucun utilisateur n'est trouvé
                $cell->userId = null;
                $cell->firstname = '';
                $cell->lastname = '';
                $cell->phone = '';
            }
        } else {
            echo "Erreur SQL : " . $db->lasterror();
        }
    }
}

// Récupérer les sous-traitants liés à l'OT
$sql = "SELECT ots.rowid, ots.fk_socpeople, ots.fk_societe, ots.fonction, ots.contrat, ots.habilitation,
               sp.firstname, sp.lastname, s.nom AS societe_nom
        FROM " . MAIN_DB_PREFIX . "ot_ot_sous_traitants AS ots
        LEFT JOIN " . MAIN_DB_PREFIX . "socpeople AS sp ON ots.fk_socpeople = sp.rowid
        LEFT JOIN " . MAIN_DB_PREFIX . "societe AS s ON ots.fk_societe = s.rowid
        WHERE ots.ot_id = " . intval($otId);

$resql = $db->query($sql);

$subcontractorsFromDB = [];
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $subcontractorsFromDB[] = [
            'rowid' => $obj->rowid,
            'fk_socpeople' => $obj->fk_socpeople,
            'fk_societe' => $obj->fk_societe,
            'fonction' => $obj->fonction,
            'contrat' => $obj->contrat,
            'habilitation' => $obj->habilitation,
            'firstname' => $obj->firstname,
            'lastname' => $obj->lastname,
            'societe_nom' => $obj->societe_nom
        ];
    }
}

// Ajouter les sous-traitants provenant du projet avec ceux de la base de données
$allSubcontractors = $subcontractorsFromDB;

// Ajouter les sous-traitants provenant du projet qui ne sont pas encore dans la base de données
foreach ($jsdatasoustraitants as $sub) {
    $existsInDB = array_filter($subcontractorsFromDB, function ($dbSub) use ($sub) {
        return $dbSub['fk_socpeople'] == $sub['fk_socpeople'];
    });

    if (empty($existsInDB)) {
        $allSubcontractors[] = [
            'rowid' => null,
            'fk_socpeople' => $sub['fk_socpeople'],
            'fk_societe' => $sub['fk_societe'],
            'fonction' => $sub['fonction'] ?? '',
            'contrat' => $sub['contrat'] ?? '',
            'habilitation' => $sub['habilitation'] ?? '',
            'firstname' => $sub['firstname'],
            'lastname' => $sub['lastname'],
            'societe_nom' => $sub['societe_nom']
        ];
    }
}

// Ajouter les sous-traitants au tableau cellData
$cellData[] = [
    'type' => 'soustraitantlist',
    'subcontractors' => $allSubcontractors
];

// Ajouter les utilisateurs aux cellules existantes
foreach ($cellData as $cell) {
    // Requête pour récupérer les utilisateurs liés à chaque cellule
    $sql = "SELECT fk_user
            FROM " . MAIN_DB_PREFIX . "ot_ot_cellule_donne
            WHERE ot_cellule_id = " . intval($cell->rowid);

    $resql = $db->query($sql);
    $users = [];

    if ($resql) {
        while ($user = $db->fetch_object($resql)) {
            $users[] = $user->fk_user;
        }
    }

    // Ajouter les utilisateurs à chaque cellule en fonction du type de cellule
    if ($cell->type == 'card') {
        // Si la cellule est de type "card", on met les utilisateurs dans "userId"
        if (count($users) > 0) {
            $cell->userId = $users[0];  // On prend seulement le premier utilisateur (si existant)
        } else {
            $cell->userId = null;  // Si aucun utilisateur, on met null
        }
    } else {
        // Pour les autres types, on garde les utilisateurs dans "userIds"
        $cell->userIds = $users;
    }
}

foreach ($cellData as $cell) {
    if (!is_object($cell) || !isset($cell->rowid)) {
        continue;
    }

    if ($cell->type === 'listeunique') {
        $sql = "SELECT  
            u.rowid AS userId,
            u.firstname,
            u.lastname,
            u.office_phone AS phone,        
            cct.type AS contrat
            
        FROM " . MAIN_DB_PREFIX . "ot_ot_cellule_donne AS ocd
        JOIN " . MAIN_DB_PREFIX . "user AS u 
            ON ocd.fk_user = u.rowid
        LEFT JOIN " . MAIN_DB_PREFIX . "donneesrh_positionetcoefficient_extrafields AS drh 
            ON drh.fk_object = u.rowid
        LEFT JOIN " . MAIN_DB_PREFIX . "c_contrattravail AS cct 
            ON drh.contratdetravail = cct.rowid
        WHERE ocd.ot_cellule_id = " . intval($cell->rowid);
        
        $resql = $db->query($sql);
        $userDetails = [];
        $seenUserIds = [];

        if ($resql) {
            while ($user = $db->fetch_object($resql)) {
                if (in_array($user->userId, $seenUserIds)) {
                    continue; // déjà traité
                }
                $seenUserIds[] = $user->userId;

                // Vérifier si l'utilisateur n'est pas Q3SE ou PCR
                $sql_check = "SELECT ctc.libelle 
                             FROM ".MAIN_DB_PREFIX."element_contact AS sp 
                             JOIN ".MAIN_DB_PREFIX."c_type_contact AS ctc 
                                ON sp.fk_c_type_contact = ctc.rowid 
                             WHERE sp.fk_socpeople = ".((int) $user->userId)." 
                             AND sp.element_id = ".((int) $object->fk_project)." 
                             AND sp.statut = 4 
                             AND ctc.element = 'project'";
                $resql_check = $db->query($sql_check);
                $skip_user = false;
                if ($resql_check) {
                    while ($obj = $db->fetch_object($resql_check)) {
                        if (in_array($obj->libelle, array('ResponsableQ3SE', 'PCRRéférent'))) {
                            $skip_user = true;
                            break;
                        }
                    }
                }
                
                if (!$skip_user) {
                    $userDetails[] = [
                        'userId' => $user->userId,
                        'firstname' => $user->firstname,
                        'lastname' => $user->lastname,
                        'phone' => $user->phone,
                        'contrat' => $user->contrat ?? 'Non défini',
                        'fonction' => getFonctions($user->userId, $object->fk_project, $db) ?? 'Non définie',
                        'habilitation' => getHabilitations($user->userId, $db) ?? 'Aucune habilitation'
                    ];
                }
            }
        } else {
            echo "Erreur SQL : " . $db->lasterror();
        }

        $cell->userDetails = $userDetails;
    }
}


// Ajouter la liste des sous-traitants au tableau cellData
$cellData[] = [
    'type' => 'soustraitantlist',
    'subcontractors' => $subcontractors
];

// Convertir les données en JSON pour les envoyer au JavaScript
$cellDataJson = json_encode($cellData);


//---------------------------------------------------------------------------------------------------------------------------------------------------------------------------




print '

    <style>
        /* Styles globaux */
        body {
            font-family: Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .card {
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease-in-out;
            margin-bottom: 20px;
            width: 100%;  /* surer que les cartes utilisent toute la largeur disponible */
            height: auto; /* Laisse la hauteur se calculer selon le contenu */
            position: relative;
            display: block; /* Forcer le bloc pour que la taille de la carte prenne toute la largeur */
        }

        .card-header {
            background-color: #2F508B;
            color: #fff;
            padding: 10px;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            font-size: 1.2em;
            font-weight: bold;
            text-align: center;
        }

        .card-body {
            padding: 15px;
            text-align: center;
        }

        .btn {
            display: inline-block;
            padding: 8px 12px;
            font-size: 14px;
            color: #fff;
            text-align: center;
            font-weight: bold;
            background-color: #28508b;
            border-radius: 4px;
            margin-top: 10px;
            cursor: pointer;
            transition: background-color 0.3s;
            border: 1px solid transparent;
        }

        .btn-info {
            background-color: #3c9613;
        }

        .btn-secondary {
            background-color: #28508b;
        }

        .btn-danger {
            background-color: #dc3545;
        }

        .btn:hover {
            background-color: #218838;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); 
        }

        .btn-info:hover {
            background-color: #3c9613; 
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); 
        }

        .btn-secondary:hover {
            background-color: #28508b;
            transform: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); 
        }

        .btn-danger:hover {
            background-color: #c82333;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); 
        }

        .card-container {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 5px;
            width: 100%;
        }

        .card-column {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            max-width: 350px;
            flex-grow: 1;
        }

        .card-column .card {
            margin: 10px 0; /* Ajouté pour espacer les cartes verticalement */
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #fff;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
            z-index: 1;
            left: 50%;
            transform: translateX(-50%);
            top: 100%; 
            margin-top: 1px; 
        }

        .dropdown-content button {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            width: 100%;
            text-align: left;
            border: none;
            background: #fff;
            cursor: pointer;
            font-weight: bold;
            margin: 0;
        }

        .dropdown-content button:hover {
            background-color: #ddd;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        .delete-button {
            background-color: #dc3545;
            border: none;
            color: #fff;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin: 10px auto;
            display: block;
        }

 .form-label {
        display: block;
        font-weight: bold;
        margin-top: 10px;
    }

    select {
        width: 100%;
        padding: 8px;
        margin-top: 5px;
        border-radius: 4px;
        border: 1px solid #ddd;
    }

    .contact-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        margin-top: 10px;
        font-size: 14px;
        background: #f1f1f1;
        padding: 8px;
        border-radius: 4px;
    }

    .contact-info input {
        width: 100px;
        padding: 5px;
        font-size: 12px;
        border-radius: 4px;
        border: 1px solid #ccc;
    }

        /* Conteneur principal pour centrer la carte */
        .supplier-section {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 20px;
        }

        /* Carte principale */
        .cardsoustraitant {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            padding: 20px;
            text-align: center;
        }

        /* Titre de la carte */
         .card-header-soustraitant {       
            transition: transform 0.2s ease-in-out;
            margin-bottom: 20px;
            width: 100%;  /* surer que les cartes utilisent toute la largeur disponible */
            height: auto; /* Laisse la hauteur se calculer selon le contenu */
            position: relative;
            display: block; /* Forcer le bloc pour que la taille de la carte prenne toute la largeur */
        }
            

        /* Labels et Sélecteurs */
        .cardsoustraitant label {
            display: block;
            font-weight: bold;
            margin: 10px 0 5px;
            text-align: left;
        }

        .cardsoustraitant select {
            width: 100%;
            padding: 8px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background: #f9f9f9;
        }

        /* Section contact cachée par défaut */
        .contact-container {
            display: none;
            margin-top: 15px;
        }



        /* Conteneur des détails du contact sous forme de tableau */
        .cardsoustraitant .card-body {
            display: table;
            width: 100%;
            background: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #ccc;
            padding: 10px;
        }



        /* Données des contacts */
        .cardsoustraitant .data-row {
            display: table-row;
            text-align: center;
            padding: 5px 0;
        }

        input:disabled, select:disabled, textarea:disabled {
            background-color: white !important; 
            color: #333 !important;
            border: none; 
            cursor: default; 
            box-shadow: none; 
        }

    .list-title-input {
        font-size: 16px; 
        font-weight: bold; 
        text-align: center; 
        color: #333; 
        margin-top: 8px; 
    }
    .card .title-input {
        font-size: 16px; 
        font-weight: bold; 
        text-align: center; 
        color: #333;

    }

    
 .cardsoustraitant .legend-row {
    display: flex; 
    justify-content: space-between; 
    text-align: center; 
    padding: 5px 0;
    font-weight: bold;
    width: 100%;
    box-sizing: border-box; 
}

.cardsoustraitant .form-input {
    font-size: 15px; 
    padding: 5px; 
    border-radius: 4px; 
    
    box-sizing: border-box; 
}

.cardsoustraitant .data-row > div {
    flex: 1; 
    padding: 0 10px; 
    text-align: center; 
    box-sizing: border-box;
}

    </style>
';

print '
<div class="container-fluid">
        <div class="row justify-content-center">
            <div>
                <div>
                    <div class="card-header">
                        <h4>Organigramme de Travail</h4>
                    </div>
                    <div class="card-body">
                        <div class="card-container">
                            <!-- Colonne 1 -->
                            <div class="card-column">
                                <div class="card" data-role="ResponsableAffaire" id="card-1">
                                    <div class="card-body">
                                        <p><strong>ResponsableAffaire</strong></p>
                                        <p class="name">Nom et Prénom</p>
                                        <p class="telephone">num téléphone</p>
                                        <p>Coordonnées : X = 1, Y = 1</p>
                                    </div>
                                    
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-secondary dropdown-toggle">Ajouter</button>
                                    <div class="dropdown-content">
                                        <button class="add-card-button" data-column="1" data-type="card">Ajouter une carte</button>
                                        <button class="add-card-button" data-column="1" data-type="list">Ajouter une liste</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Colonne 2 -->
                            <div class="card-column">
                                <div class="card" data-role="ResponsableQ3SE" id="card-2">
                                    <div class="card-body">
                                        <p><strong>Responsable-Q3SE</strong></p>
                                        <p class="name">Nom et Prénom</p>
                                         <p class="telephone">num téléphone</p>
                                        <p>Coordonnées : X = 2, Y = 1</p>
                                    </div>
                                    
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-secondary dropdown-toggle">Ajouter</button>
                                    <div class="dropdown-content">
                                        <button class="add-card-button" data-column="2" data-type="card">Ajouter une carte</button>
										
                                        <button class="add-card-button" data-column="2" data-type="list">Ajouter une liste</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Colonne 3 -->
                            <div class="card-column">
                                <div class="card" data-role="PCRReferent" id="card-3">
                                    <div class="card-body">
                                        <p><strong>PCRReferent</strong></p>
                                        <p class="name">Nom et Prénom</p>
                                        <p class="telephone">num téléphone</p>
                                        <p>Coordonnées : X = 3, Y = 1</p>
                                    </div>
                                    
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-secondary dropdown-toggle">Ajouter</button>
                                    <div class="dropdown-content">
                                        <button id="save-data-button-add-card" class="add-card-button" data-column="3" data-type="card">Ajouter une carte</button>
                                        <button id="save-data-button-add-liste" class="add-card-button" data-column="3" data-type="list">Ajouter une liste</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="card-columns" class="card-columns"></div>	
                    </div>
                    <div style="text-align: center; margin-top: 10px;">
                    </div>
                    <div class="supplier-section">
                    </div>
                </div>
            </div>
        </div>
    </div>

';

print '<script>

document.addEventListener("DOMContentLoaded", function() {
    let cellData = ' . $cellDataJson . ';
    let otId = ' . json_encode($otId) . ';
    let userdata = ' . json_encode($userdata) . ';
    let userjson = ' . $userjson . ';
    let status = ' . json_encode($object->status) . ';
    let isDataSaved = false;
    let isUniqueListCreated = false;
    console.log(cellData);
     console.log(userjson);
    let jsdata = '.$data.'; 
    let users = typeof jsdata === "string" ? JSON.parse(jsdata) : jsdata;
    let selectedContacts = [];
    let jsdatasoustraitants = users.filter(user => user.source === "external" && user.fk_c_type_contact === "1031141");
    let jsdataFiltered = users.filter(user => user.source !== "external"); 

    jsdata = jsdataFiltered;
    console.log("status", );
    console.log("les autres", jsdataFiltered);
    console.log("soustraitants", jsdatasoustraitants);



let columnsContainer = document.querySelector(".card-columns") ||
    document.querySelector("#card-columns") ||
    document.querySelector(".main-columns-container");

if (!columnsContainer) {
    console.warn("Le conteneur parent des colonnes a pas été trouvé, création un nouveau conteneur.");
    columnsContainer = document.createElement("div");
    columnsContainer.id = "card-columns";
    columnsContainer.className = "card-columns";
    document.body.appendChild(columnsContainer);
}


const uniqueJsData = jsdata.filter((value, index, self) => 
    index === self.findIndex((t) => (
        t.fk_socpeople === value.fk_socpeople
    ))
);



// Désactiver les champs si le statut est égal à 1
    if (status === 1 || status === 2) {
    
    setTimeout(() => {
        // Masquer les boutons "Ajouter une carte" et "Ajouter une liste"
        document.querySelectorAll(".dropdown").forEach(function (dropdown) {
            dropdown.style.display = "none";
        });

        // Masquer les croix pour les lignes des listes
        document.querySelectorAll(".remove-user").forEach(function (removeButton) {
            removeButton.style.display = "none";
        });

        // Masquer les boutons "Supprimer" des cartes et des listes
        document.querySelectorAll(".delete-button, .delete-list-button").forEach(function (deleteButton) {
            deleteButton.style.display = "none";
        });

        // Désactiver la sélection utilisateur dans les cartes
        document.querySelectorAll(".name-dropdown").forEach(function (dropdown) {
            const selectedUserId = dropdown.value;
            const selectedUser = dropdown.options[dropdown.selectedIndex]?.text || "Utilisateur non défini";

            // Remplacer le menu déroulant par un texte affichant utilisateur sélectionné
            const userDisplay = document.createElement("p");
            userDisplay.textContent = selectedUser;
            userDisplay.style.textAlign = "center";
            userDisplay.style.color = "#333";

            dropdown.replaceWith(userDisplay);
        });

        // Désactiver les champs des sous-traitants
        document.querySelectorAll(".cardsoustraitant .form-input").forEach(function (input) {
            input.disabled = true;
        });

        // Désactiver les champs des cartes et listes
        document.querySelectorAll(".form-input, .list-title-input, .title-input").forEach(function (input) {
            input.disabled = true;
        });
    }, 500); 
}


function displayUserList() {
    const existingUniqueList = document.querySelector(".user-list.unique-list");

    // Supprimer la liste par défaut si elle existe, pour éviter des doublons
    if (existingUniqueList) {
        existingUniqueList.remove();
    }

    // Vérifier si des données de la BDD existent dans `cellData`
    if (typeof cellData !== "undefined" && cellData.length > 0) {
        const hasUniqueList = cellData.some(cell => cell.type === "listeunique");

        if (hasUniqueList) {
            console.log("Affichage de la liste unique depuis la BDD.");
            cellData.forEach(cell => {
                if (cell.type === "listeunique") {
                    const listVersion = parseInt(cell.version || 1); // Version de la liste dans la BDD
                    const domVersion = existingUniqueList ? parseInt(existingUniqueList.dataset.version || 0) : 0;

                    // Comparer les versions
                    if (listVersion > domVersion) {
                        console.log(`Mise à jour de la liste unique (version ${listVersion}).`);
                        const list = createUniqueUserList();

                        // Remplir le titre de la liste
                        const titleInput = list.querySelector(".list-title-input");
                        titleInput.value = cell.title;

                        // Vérifier si `userDetails` est défini et est un tableau
                        if (Array.isArray(cell.userDetails)) {
                            // Remplir les utilisateurs de la liste depuis `cellData`
                            const ulElement = list.querySelector("ul");
                            ulElement.innerHTML = ""; // Vider la liste avant de la remplir
                            cell.userDetails.forEach(user => {
                                console.log("User data:", user); // Pour voir la structure des données
                                // Vérifier si lutilisateur nest pas Q3SE ou PCR
                                if (user.type !== "ResponsableQ3SE" && user.type !== "PCRRéférent") {
                                    const li = document.createElement("li");
                                    li.setAttribute("data-user-id", user.userId);
                                    li.style = "display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #ddd; text-align: center;";

                                    li.innerHTML = `
                                        <div style="flex: 1; text-align: center; padding-right: 10px;">${user.firstname} ${user.lastname}</div>
                                        <div style="flex: 1; text-align: center; padding-right: 10px;">${user.fonction || "Non définie"}</div>
                                        <div style="flex: 1; text-align: center; padding-right: 10px;">${user.contrat || "Non défini"}</div>
                                        <div style="flex: 1; text-align: center; padding-right: 10px;">${user.habilitation || "Aucune habilitation"}</div>
                                        <div style="flex: 1; text-align: center;">${user.phone || "Non défini"}</div>
                                    `;
                                    ulElement.appendChild(li);
                                }
                            });
                        } else {
                            console.warn(`userDetails est manquant ou nest pas un tableau pour la cellule avec le titre : ${cell.title}`);
                        }

                        // Ajouter la version au DOM
                        list.dataset.version = listVersion;

                        attachUserRemoveListeners(list);

                        // Ajouter la liste au conteneur
                        columnsContainer.appendChild(list);
                    } else {
                        console.log("La version de la liste unique est déjà à jour.");
                    }
                }
            });
        } else {
            console.log("Aucune liste unique trouvée dans la BDD, création dune nouvelle liste.");
            const uniqueList = createUniqueUserList();
            uniqueList.style.marginTop = "20px"; // Ajouter un espace de 20px en haut
            uniqueList.dataset.version = 1; // Initialiser la version à 1
            columnsContainer.appendChild(uniqueList);

            // Sauvegarder la nouvelle liste dans la BDD
            saveData();
        }
    } else {
        console.log("Aucune donnée dans `cellData`, création dune nouvelle liste par défaut.");
        const uniqueList = createUniqueUserList();
        uniqueList.style.marginTop = "20px"; // Ajouter un espace de 20px en haut
        uniqueList.dataset.version = 1; // Initialiser la version à 1
        columnsContainer.appendChild(uniqueList);

        // Sauvegarder la nouvelle liste dans la BDD
        saveData();
    }
}


// Appeler la fonction pour afficher la liste lors du chargement de la page
displayUserList();

    // Trier les utilisateurs par ordre croissant de nom (lastname)
    userjson.sort(function(a, b) {
        if (a.lastname < b.lastname) return -1;
        if (a.lastname > b.lastname) return 1;
        return 0;
    });

    // Générer les options du dropdown après tri des utilisateurs
    let alluser = `<option value="" disabled selected>Sélectionner un utilisateur</option>`;
    userjson.forEach(function(user) {
        alluser += `<option value="${user.rowid}">${user.lastname} ${user.firstname}</option>`;
    });

    // Générer les options de  uniqueJsData
    let userOptions = `<option value="" disabled selected>Sélectionner un utilisateur</option>`;
    uniqueJsData.forEach(function(user) {
        userOptions += `<option value="${user.fk_socpeople}">${user.firstname} ${user.lastname}</option>`;
    });

if (typeof cellData !== "undefined" && cellData.length > 0) {
    const addedCardTitles = new Set();
    const addedListTitles = new Set();

    cellData.forEach(function(cell) {
        let column = cell.x;

    if (cell.type === "card") {                                          
    console.log(`Chargement de la carte ${cell.title} avec userId:`, cell.userId);

    if (!addedCardTitles.has(cell.title)) {
        const card = createEmptyCard(column);
        const titleInput = card.querySelector(".title-input");
        titleInput.value = cell.title;

        const nameDropdown = card.querySelector(".name-dropdown");
        nameDropdown.innerHTML = userOptions; // Use filtered project users

        if (cell.userId) {  
            const userId = cell.userId;  
            console.log("User ID détecté :", userId);  // ✅ Vérifier si userId est bien récupéré

            const user = userjson.find(u => u.rowid == userId);
            if (user) {
                nameDropdown.value = userId;
                
                // Afficher les habilitations et le contrat
                const habilitationInfo = card.querySelector(".habilitation-info");
                const contratInfo = card.querySelector(".contrat-info");
                
                if (habilitationInfo && contratInfo) {
                    habilitationInfo.textContent = `Habilitations: ${cell.habilitations || user.habilitations || "Non spécifié"}`;
                    contratInfo.textContent = `Contrat: ${cell.contrat || user.contrat || "Non spécifié"}`;
                    
                    // Sauvegarder dans le dataset
                    card.dataset.habilitations = cell.habilitations || user.habilitations || "";
                    card.dataset.contrat = cell.contrat || user.contrat || "";
                }
            } else {
                console.warn("⚠️ User introuvable avec ID :", userId);
            }
        } else {
            console.warn("⚠️ Aucun userId trouvé pour cette carte !");
        }

        const columnElement = document.querySelector(`.card-column:nth-child(${column})`);
        if (columnElement) {
            columnElement.appendChild(card);
        }

        addedCardTitles.add(cell.title);
    }
}

else if (cell.type === "list") {
    if (!addedListTitles.has(cell.title)) {
        const list = createUserList(column);
        const titleInput = list.querySelector(".list-title-input");
        titleInput.value = cell.title;

        const ulElement = list.querySelector("ul");
        ulElement.innerHTML = "";

        cell.userIds.forEach(function (userId) {
            const user = uniqueJsData.find(u => u.fk_socpeople === userId);

            if (user) {
                const li = document.createElement("li");
                li.setAttribute("data-user-id", userId);
                li.style = "display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #ddd; text-align: center;";

                // Utiliser le même affichage que dans createUserList
                li.innerHTML = `
                    <div style="flex: 1; text-align: center; white-space: normal;" title="${user.lastname} ${user.firstname}">
                        ${user.lastname}<br>${user.firstname}
                    </div>
                    <div style="flex: 1; text-align: center; white-space: normal;" title="${user.fonction || "Non définie"}">
                        ${user.fonction || "Non définie"}
                    </div>
                    <div style="flex: 1; text-align: center; white-space: normal;" title="${user.contrat || "Non défini"}">
                        ${user.contrat || "Non défini"}
                    </div>
                    <div style="flex: 1; text-align: center; white-space: normal;" title="${user.habilitation || "Aucune habilitation"}">
                        ${user.habilitation || "Aucune habilitation"}
                    </div>
                    <div style="flex: 1; text-align: center; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${user.phone || "Non défini"}">
                        ${user.phone || "Non défini"}
                    </div>
                `;

                // Ajouter une fonction pour gérer le style en fonction du statut
                function updateListItemsStyle() {
                    const listStatus = parseInt(list.getAttribute("data-status"));
                    const items = list.querySelectorAll("li > div");
                    
                    items.forEach((item, index) => {
                        // Ne pas modifier le style du nom et du téléphone
                        if (index !== 0 && index !== 4) {
                            if (listStatus === 1 || listStatus === 2) {
                                item.style.textAlign = "center";
                                item.style.whiteSpace = "normal";
                            }
                        }
                    });
                }

                // Observer les changements dattribut data-status sur la liste
                const observer = new MutationObserver(updateListItemsStyle);
                observer.observe(list, { attributes: true, attributeFilter: ["data-status"] });

                // Appliquer le style initial
                updateListItemsStyle();

                // Ajouter le bouton de suppression
                const removeSpan = document.createElement("span");
                removeSpan.textContent = "×";
                removeSpan.style = "color:red; cursor:pointer;";
                removeSpan.className = "remove-user";
                li.appendChild(removeSpan);

                ulElement.appendChild(li);

                // Ajouter une ligne vide entre les utilisateurs
                const emptyRow = document.createElement("li");
                emptyRow.style = "height: 10px;"; // Hauteur de la ligne vide
                ulElement.appendChild(emptyRow);
            } else {
                console.warn(`Utilisateur avec ID ${userId} introuvable dans uniqueJsData.`);
            }
        });

        attachUserRemoveListeners(list);
        const columnElement = document.querySelector(`.card-column:nth-child(${column})`);
        if (columnElement) {
            columnElement.appendChild(list);
        }

        addedListTitles.add(cell.title);
    }
}
 else if (cell.type === "listeunique") {
    // Vérifie si une liste unique avec ce titre existe déjà dans le DOM
    const existingUniqueList = document.querySelector(`.user-list.unique-list[data-list-id="${cell.title}"]`);

    if (isUniqueListCreated && !existingUniqueList) {
        console.log("La liste unique a été créée mais est plus dans le DOM. Mise à jour du contenu.");
        const list = createUniqueUserList(); // recréer la structure
        const titleInput = list.querySelector(".list-title-input");
        titleInput.value = cell.title;

        const ulElement = list.querySelector("ul");
        ulElement.innerHTML = ""; // Vider la liste

        // Remplir avec les utilisateurs de cellData
        cell.userIds.forEach(function(userId) {
            const user = uniqueJsData.find(u => u.fk_socpeople === userId);
            if (user) {
                const li = document.createElement("li");
                li.setAttribute("data-user-id", userId);
                li.innerHTML = `
                     <div style="flex: 1; text-align: center; padding-right: 10px;">${user.firstname} ${user.lastname}</div>
                    <div style="flex: 1; text-align: center; padding-right: 10px;">${user.fonction || "Non définie"}</div>
                    <div style="flex: 1; text-align: center; padding-right: 10px;">${user.contrat || "Non défini"}</div>
                    <div style="flex: 1; text-align: center; padding-right: 10px;">${user.habilitation || "Aucune habilitation"}</div>
                    <div style="flex: 1; text-align: center;">${user.phone || "Non défini"}</div>
                     <span class="remove-user" style="color:red; cursor:pointer;">&times;</span>`;
                ulElement.appendChild(li);
            } else {
                console.warn(`Utilisateur avec ID ${userId} introuvable dans uniqueJsData.`);
            }
        });

        attachUserRemoveListeners(list);

        if (columnsContainer) {
            columnsContainer.appendChild(list);
        } else {
            console.error("Le conteneur parent des colonnes a pas été trouvé.");
        }
        
    } 
}
    });
}



function createUniqueUserList() {
    const list = document.createElement("div");
    list.className = "user-list card unique-list";

    
    const lineBreak = document.createElement("br");
    list.appendChild(lineBreak);

    // Ajouter un ID unique
    const uniqueListId = `unique_${Date.now()}`;
    list.setAttribute("data-list-id", uniqueListId);

    // Créer un conteneur pour le titre avec le trait rouge
    const titleContainer = document.createElement("div");
    titleContainer.style = "text-align: center; padding-bottom: 10px; margin-bottom: 10px; color: #333; font-weight: bold;";

    const listTitleInput = document.createElement("input");
    listTitleInput.type = "text";
    listTitleInput.className = "list-title-input";
    listTitleInput.name = "listTitle";
    listTitleInput.placeholder = ""; // On enlève le placeholder par défaut
    listTitleInput.required = true;
    listTitleInput.style = "width: 80%; padding: 5px; text-align: center; color: #333;";
console.log("Status de la carte :", status);
    // Fonction pour gérer laffichage du placeholder et le style
    function updatePlaceholder() {
        const card = list.closest(".card");
        const cardStatus = card ? parseInt(card.getAttribute("data-status")) : 0;
        
        if (listTitleInput.value === "" && cardStatus === 0) {
            listTitleInput.placeholder = "Titre de la liste";
            listTitleInput.style.textAlign = "center";
        } else {
            listTitleInput.placeholder = "";
            if (cardStatus === 1 || cardStatus === 2) {
                listTitleInput.style.textAlign = "center";
            }
        }
    }

    // Ajouter les écouteurs dévénements
    listTitleInput.addEventListener("focus", updatePlaceholder);
    listTitleInput.addEventListener("blur", updatePlaceholder);
    listTitleInput.addEventListener("input", updatePlaceholder);

    // Observer les changements dattribut data-status sur la carte
    const observer = new MutationObserver(updatePlaceholder);
    const card = list.closest(".card");
    if (card) {
        observer.observe(card, { attributes: true, attributeFilter: ["data-status"] });
    }

    titleContainer.appendChild(listTitleInput); 

    // Créer une légende pour décrire les informations
    const legend = document.createElement("div");
    legend.className = "list-legend";
    legend.style = "display: flex; justify-content: space-between; padding: 10px; font-weight: bold; color: #333; margin-bottom: 10px; text-align: center;"; // Centrer la légende
    legend.innerHTML = `
        <div style="flex: 1; text-align: center;">Nom Prénom</div>
        <div style="flex: 1; text-align: center;">Fonction</div>
        <div style="flex: 1; text-align: center;">Contrat</div>
        <div style="flex: 1; text-align: center;">Habilitations</div>
        <div style="flex: 1; text-align: center;">Téléphone</div>
    `;

    const ulElement = document.createElement("ul");
    ulElement.style = "list-style: none; padding: 0; margin: 0;";

    // Afficher la structure des données
    console.log("uniqueJsData structure:", uniqueJsData);

    // Remplir les utilisateurs de la liste depuis uniqueJsData
    uniqueJsData.forEach(user => {
        console.log("User in uniqueJsData:", user); // Afficher chaque utilisateur
        // Vérifier si lutilisateur nest pas Q3SE ou PCR
        if (user.libelle !== "ResponsableQ3SE" && user.libelle !== "PCRRéférent") {
            const li = document.createElement("li");
            li.setAttribute("data-user-id", user.fk_socpeople);
            li.style = "display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #ddd; text-align: center;"; // Centrer les éléments de utilisateur

            // Créer une ligne avec les informations de utilisateur, réparties uniformément
            li.innerHTML = `
                <div style="flex: 1; text-align: center; padding-right: 10px;">${user.firstname} ${user.lastname}</div>
                <div style="flex: 1; text-align: center; padding-right: 10px;">${user.fonction || "Non définie"}</div>
                <div style="flex: 1; text-align: center; padding-right: 10px;">${user.contrat || "Non défini"}</div>
                <div style="flex: 1; text-align: center; padding-right: 10px;">${user.habilitation || "Aucune habilitation"}</div>
                <div style="flex: 1; text-align: center;">${user.phone || "Non défini"}</div>
            `;

            // Ajouter le bouton de suppression
            const removeSpan = document.createElement("span");
            removeSpan.textContent = "×";
            removeSpan.style = "color:red; cursor:pointer;";
            removeSpan.className = "remove-user";
            li.appendChild(removeSpan);

            ulElement.appendChild(li);
        }
    });

    const listBody = document.createElement("div");
    listBody.className = "list-body";
    listBody.style = "text-align: left; color: #333; padding-left: 20px; padding-right: 20px; margin-bottom: 20px;"; // Ajouter des espaces à gauche et à droite
    listBody.appendChild(titleContainer);  // Ajouter le titre avec le trait rouge
    listBody.appendChild(legend);  // Ajouter la légende en haut de la liste
    listBody.appendChild(ulElement);

    list.appendChild(listBody);



    // Attacher les écouteurs de suppression utilisateur
    attachUserRemoveListeners(list);

    return list;
}


function deleteUniqueList(uniqueListId, list) {
      list.remove();
}


function updateCards() {
    var cardHeaders = {
        "ResponsableAffaire": null,
        "ResponsableQ3SE": null,
        "PCRReferent": null
    };

    // Vérifier si les données sont dans `cellData`
    cellData.forEach(function(cell) {
        if (cell.type === "cardprincipale" && cell.title) {
            switch (cell.title) {
                case "RA":
                    cardHeaders["ResponsableAffaire"] = cell;
                    break;
                case "Q3":
                    cardHeaders["ResponsableQ3SE"] = cell;
                    break;
                case "PCR":
                    cardHeaders["PCRReferent"] = cell;
                    break;
            }
        }
    });

    // Si les données ne sont pas dans `cellData`, les récupérer depuis `jsdata`
    jsdata.forEach(function(contact) {
        if (!cardHeaders["ResponsableAffaire"] && contact.fk_c_type_contact === "160") {
            cardHeaders["ResponsableAffaire"] = {
                type: "cardprincipale",
                title: "RA",
                firstname: contact.firstname,
                lastname: contact.lastname,
                phone: contact.phone || "N/A",
                userId: contact.fk_socpeople
            };
            saveData(); // Enregistrer dans la BDD
        }
        if (!cardHeaders["ResponsableQ3SE"] && contact.fk_c_type_contact === "1031142") {
            cardHeaders["ResponsableQ3SE"] = {
                type: "cardprincipale",
                title: "Q3",
                firstname: contact.firstname,
                lastname: contact.lastname,
                phone: contact.phone || "N/A",
                userId: contact.fk_socpeople
            };
            saveData(); // Enregistrer dans la BDD
        }
        if (!cardHeaders["PCRReferent"] && contact.fk_c_type_contact === "1031143") {
            cardHeaders["PCRReferent"] = {
                type: "cardprincipale",
                title: "PCR",
                firstname: contact.firstname,
                lastname: contact.lastname,
                phone: contact.phone || "N/A",
                userId: contact.fk_socpeople
            };
            saveData(); // Enregistrer dans la BDD
        }
    });

    // Ajouter des cartes vides si certains rôles sont absents dans `jsdata`
    if (!cardHeaders["ResponsableAffaire"]) {
        cardHeaders["ResponsableAffaire"] = {
            type: "cardprincipale",
            title: "RA",
            firstname: null,
            lastname: null,
            phone: null,
            userId: null
        };
        saveData(); // Enregistrer la carte vide dans la BDD
    }
    if (!cardHeaders["ResponsableQ3SE"]) {
        cardHeaders["ResponsableQ3SE"] = {
            type: "cardprincipale",
            title: "Q3",
            firstname: null,
            lastname: null,
            phone: null,
            userId: null
        };
        saveData(); // Enregistrer la carte vide dans la BDD
    }
    if (!cardHeaders["PCRReferent"]) {
        cardHeaders["PCRReferent"] = {
            type: "cardprincipale",
            title: "PCR",
            firstname: null,
            lastname: null,
            phone: null,
            userId: null
        };
        saveData(); // Enregistrer la carte vide dans la BDD
    }

    // Mettre à jour les cartes dans le DOM
    for (var role in cardHeaders) {
        if (cardHeaders.hasOwnProperty(role)) {
            var contact = cardHeaders[role];
            var selector = `.card[data-role="${role}"]`;
            var card = document.querySelector(selector);

            if (card) {
                var cardBody = card.querySelector(".card-body");

                if (cardBody) {
                if (contact && contact.type === "cardprincipale") {
                    // Vérifier si la carte est vide (pas de firstname, lastname ou phone)
                    if (!contact.firstname && !contact.lastname && !contact.phone) {
                        let message = "";
                        switch (role) {
                            case "ResponsableAffaire":
                                message = "Pas de Responsable Affaire";
                                break;
                            case "ResponsableQ3SE":
                                message = "Pas de Responsable Q3SE";
                                break;
                            case "PCRReferent":
                                message = "Pas de PCR Référent";
                                break;
                            default:
                                message = "Aucune donnée disponible";
                        }
                        cardBody.innerHTML = `
                            <p><strong>${role}</strong></p>
                            <p>${message}</p>
                            <p class="phone"> </p>
                            <p style="visibility: hidden;">Ligne vide</p> <!-- Ligne vide pour uniformiser la hauteur -->
                        `;
                    } else {
                        // Afficher les informations si elles sont présentes
                        cardBody.innerHTML = `
                            <p><strong>${role}</strong></p>
                            <p>${contact.firstname || ""} ${contact.lastname || ""}</p>
                            <p class="phone">Téléphone : ${contact.phone || ""}</p>
                            
                        `;
                    }
                } else {
                    // Si aucune donnée n\'est disponible, vider la carte
                    cardBody.innerHTML = `
                        <p><strong>${role}</strong></p>
                        <p>Aucune donnée disponible</p>
                    `;
                }
            

                    // Désactiver les champs pour empêcher la modification
                    card.querySelectorAll("input, select, button").forEach(function(field) {
                        field.disabled = true;
                    });
                }
            }
        }
    }
}



 function attachDeleteListener(card) {
    var deleteButton = card.querySelector(".delete-button");
    if (deleteButton) {
        deleteButton.addEventListener("click", function () {
            
            deleteCard(card);
        });
    }
}


//---------------------------------------------------------------------------------------------------------------------------------------------------
/**
 * partie sous traitant
 * 
 */



    // Fonction pour récupérer les fournisseurs et contacts via Ajax
    function fetchSuppliersAndContacts() {
        $.ajax({
            url: "ajax/myobject.php",  
            type: "GET",
            data: { mode: "getSuppliersAndContacts" },
            dataType: "json",
            success: function(response) { 
                if (response.status === "success") {
                   
                    createSupplierDropdown(response.data);  
                } else {
                    console.error("Erreur dans la réponse:", response.message);  
                }
            },
            error: function(xhr, status, error) {
                console.error("Erreur Ajax :", error);  
            }
        });
    }

    // Appel de la fonction pour récupérer les données dès que la page est prête
    fetchSuppliersAndContacts();






function createSupplierDropdown() {
    const existingCard = document.querySelector(".cardsoustraitant");
    if (existingCard) {
        existingCard.remove();
    }
    const cardContainer = document.createElement("div");
    cardContainer.className = "cardsoustraitant";

    const cardTitle = document.createElement("h3");
   cardTitle.textContent = "Sous traitants";
    cardTitle.className = "card-header-soustraitant";
    cardContainer.appendChild(cardTitle);

    document.querySelector(".supplier-section").appendChild(cardContainer);

    // Conteneur pour regrouper la légende et les contacts
    const tableContainer = document.createElement("div");
    tableContainer.className = "table-container";
    cardContainer.appendChild(tableContainer);

    // Ajouter une légende pour le tableau
    const legendRow = document.createElement("div");
    legendRow.className = "legend-row";
    legendRow.style.cssText = "display: flex; text-align: center; padding: 5px 0; font-weight: bold;";

    const legendFields = ["Nom Prénom", "Entreprise", "Fonction", "Contrat", "Habilitations"];
    legendFields.forEach(field => {
        const fieldCell = document.createElement("div");
        fieldCell.style.flex = "1";
        fieldCell.textContent = field;
        legendRow.appendChild(fieldCell);
    });

    tableContainer.appendChild(legendRow);

    // Fonction pour mettre à jour le style des champs en fonction du status
    function updateFieldsStyle() {
        const status = parseInt(cardContainer.getAttribute("data-status") || "0");
        const inputs = tableContainer.querySelectorAll(".form-input");
        inputs.forEach(input => {
            if (status === 1 || status === 2) {
                input.style.textAlign = "center";
                input.style.whiteSpace = "normal";
                input.style.width = "100%";
                input.style.padding = "0 5px";
                input.style.boxSizing = "border-box";
                input.disabled = true; // Désactiver les champs quand le status est 1 ou 2
                input.style.backgroundColor = "#f5f5f5"; // Gris clair pour indiquer que le champ est désactivé
                input.style.cursor = "not-allowed"; // Curseur "non autorisé"
            } else {
                input.style.textAlign = "left";
                input.style.whiteSpace = "nowrap";
                input.style.overflow = "hidden";
                input.style.textOverflow = "ellipsis";
                input.style.width = "100%";
                input.style.padding = "0 5px";
                input.style.boxSizing = "border-box";
                input.disabled = false; // Réactiver les champs quand le status est 0
                input.style.backgroundColor = ""; // Retour à la couleur par défaut
                input.style.cursor = "text"; // Curseur normal
            }
        });
    }

    // Observer les changements dattribut data-status
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === "attributes" && mutation.attributeName === "data-status") {
                updateFieldsStyle();
            }
        });
    });
    observer.observe(cardContainer, { attributes: true, attributeFilter: ["data-status"] });

    // Vérifier si les données de `cellData` contiennent des sous-traitants
    const subcontractorData = cellData.find(cell => cell.type === "soustraitantlist");
   
    if ((!subcontractorData || !subcontractorData.subcontractors || subcontractorData.subcontractors.length === 0) 
    && jsdatasoustraitants && Array.isArray(jsdatasoustraitants) && jsdatasoustraitants.length > 0) {
        // Afficher les sous-traitants de `jsdatasoustraitants` une seule fois
        jsdatasoustraitants.forEach(contact => {
            const dataRow = document.createElement("div");
            dataRow.className = "data-row";
            dataRow.setAttribute("data-contact-id", contact.fk_socpeople);
            dataRow.style.cssText = "display: flex; text-align: center; padding: 5px 0;";

            const fields = [
                `${contact.firstname} ${contact.lastname}`,
                `${contact.societe_nom}`,
                `<input type="text" placeholder="Fonction" class="form-input" data-field="function" value="${contact.fonction || ""}">`,
                `<input type="text" placeholder="Contrat" class="form-input" data-field="contract" value="${contact.contrat || ""}">`,
                `<input type="text" placeholder="Habilitations" class="form-input" data-field="qualifications" value="${contact.habilitation || ""}">`
            ];

            fields.forEach(field => {
                const fieldCell = document.createElement("div");
                fieldCell.style.flex = "1";
                fieldCell.innerHTML = field;
                dataRow.appendChild(fieldCell);
            });

            tableContainer.appendChild(dataRow);

            // Ajouter le contact dans `selectedContacts` pour éviter les doublons
            selectedContacts.push({
                contact_id: contact.fk_socpeople,
                firstname: contact.firstname,
                lastname: contact.lastname,
                supplier_name: contact.societe_nom,
                supplier_id: contact.fk_societe,
                function: contact.fonction,
                contract: contact.contrat,
                qualifications: contact.habilitation
            });
        });

        // Sauvegarder les données après affichage
        saveData();
    } else if (subcontractorData && subcontractorData.subcontractors) {
        // Afficher uniquement les sous-traitants enregistrés dans `cellData`
        subcontractorData.subcontractors.forEach(contact => {
            const dataRow = document.createElement("div");
            dataRow.className = "data-row";
            dataRow.setAttribute("data-contact-id", contact.fk_socpeople);
            dataRow.style.cssText = "display: flex; text-align: center; padding: 5px 0;";

            const fields = [
                `${contact.firstname} ${contact.lastname}`,
                `${contact.societe_nom}`,
                `<input type="text" placeholder="Fonction" class="form-input" data-field="function" value="${contact.fonction || ""}">`,
                `<input type="text" placeholder="Contrat" class="form-input" data-field="contract" value="${contact.contrat || ""}">`,
                `<input type="text" placeholder="Habilitations" class="form-input" data-field="qualifications" value="${contact.habilitation || ""}">`
            ];

            fields.forEach(field => {
                const fieldCell = document.createElement("div");
                fieldCell.style.flex = "1";
                fieldCell.innerHTML = field;
                dataRow.appendChild(fieldCell);
            });

            tableContainer.appendChild(dataRow);

            // Ajouter le contact dans `selectedContacts` pour éviter les doublons
            selectedContacts.push({
                contact_id: contact.fk_socpeople,
                firstname: contact.firstname,
                lastname: contact.lastname,
                supplier_name: contact.societe_nom,
                supplier_id: contact.fk_societe,
                function: contact.fonction,
                contract: contact.contrat,
                qualifications: contact.habilitation
            });
        });
    }

    // Appliquer le style initial
    updateFieldsStyle();

    // Ajouter un écouteur pour sauvegarder les modifications
    document.querySelector(".table-container").addEventListener("blur", function (e) {
        if (e.target && e.target.classList.contains("form-input")) {
            const inputField = e.target;
            const dataRow = inputField.closest(".data-row");
            const contactId = dataRow.getAttribute("data-contact-id");

            // Trouver le contact correspondant dans `selectedContacts`
            const selectedContact = selectedContacts.find(c => c.contact_id == contactId);
            if (selectedContact) {
                const fieldName = inputField.getAttribute("data-field");
                selectedContact[fieldName] = inputField.value; // Mettre à jour la valeur
            }

            saveData(); // Sauvegarder les modifications
        }
    }, true);
}


// Appel de la fonction pour récupérer et afficher les fournisseurs
fetchSuppliersAndContacts();





//---------------------------------------------------------------------------------------------------------------------------------------------------



function deleteCard(card) {
    card.remove(); // Supprime la carte du DOM
}

// Fonction pour créer une nouvelle carte vide
function createEmptyCard(column) {
    const columnElement = document.querySelector(`.card-column:nth-child(${column})`);
    
    // Compter les cartes et listes existantes dans la colonne
    const itemsInColumn = columnElement.querySelectorAll(".card, .user-list").length;

    // Y commence à 2 si il y a déjà des éléments dans la colonne, sinon à 2
    const yPosition = itemsInColumn + 1;  // +1 car Y commence à 2
    const uniqueId = `${column}${yPosition}`;

    const card = document.createElement("div");
    card.className = "card";
    card.setAttribute("data-status", "0"); // Ajouter le statut initial

    card.innerHTML = `
        <div class="card-body" style="text-align: center; color: #333;">
            <form class="card-form" style="display: flex; flex-direction: column; align-items: center;">
                <input type="text" class="title-input" name="title" placeholder="" required
                    style="width: 80%; margin-bottom: 10px; padding: 5px; text-align: center; color: #333;">
                <select class="name-dropdown" name="name" required
                    style="width: 80%; margin-bottom: 10px; padding: 5px; text-align: center; color: #333;">
                    ${alluser}
                </select>
                <div class="user-details" style="margin-top: 10px; width: 100%; display: flex; flex-direction: column; align-items: center;">
                    <div class="habilitation-info" style="margin-bottom: 5px; word-wrap: break-word; white-space: normal; text-align: center; padding: 5px; font-size: 0.9em; line-height: 1.4; width: 90%;"></div>
                    <div class="contrat-info" style="margin-bottom: 5px; word-wrap: break-word; white-space: normal; text-align: center; padding: 5px; font-size: 0.9em; width: 90%;"></div>
                </div>
                <input type="hidden" class="card-id" value="${uniqueId}"> 
            </form>
            <button class="delete-button" style="margin-top: 10px;">Supprimer</button>
        </div>
    `;

    // Fonction pour gérer laffichage du placeholder
    function updatePlaceholder() {
        const titleInput = card.querySelector(".title-input");
        const cardStatus = parseInt(card.getAttribute("data-status"));
        
        if (titleInput.value === "" && cardStatus === 0) {
            titleInput.placeholder = "Titre de la carte";
        } else {
            titleInput.placeholder = "";
        }
    }

    // Ajouter les écouteurs dévénements
    const titleInput = card.querySelector(".title-input");
    titleInput.addEventListener("focus", updatePlaceholder);
    titleInput.addEventListener("blur", updatePlaceholder);
    titleInput.addEventListener("input", updatePlaceholder);

    // Observer les changements dattribut data-status sur la carte
    const observer = new MutationObserver(updatePlaceholder);
    observer.observe(card, { attributes: true, attributeFilter: ["data-status"] });

    // Ajouter lécouteur dévénement pour le changement utilisateur
    const nameDropdown = card.querySelector(".name-dropdown");
    nameDropdown.addEventListener("change", function() {
        const selectedUserId = this.value;
        const selectedUser = userjson.find(user => user.rowid === selectedUserId);
        
        if (selectedUser) {
            const habilitationInfo = card.querySelector(".habilitation-info");
            const contratInfo = card.querySelector(".contrat-info");
            
            // Formater les habilitations avec des retours à la ligne
            const habilitations = selectedUser.habilitations || "Non spécifié";
            const formattedHabilitations = habilitations.split(",").map(h => h.trim()).join(",\n");
            
            habilitationInfo.innerHTML = `<strong>Habilitations:</strong><br>${formattedHabilitations}`;
            contratInfo.innerHTML = `<strong>Contrat:</strong><br>${selectedUser.contrat || "Non spécifié"}`;
            
            // Sauvegarder les informations dans le dataset de la carte
            card.dataset.habilitations = selectedUser.habilitations || "";
            card.dataset.contrat = selectedUser.contrat || "";
        }
    });

    card.querySelector(".card-form").addEventListener("submit", function (event) {
        event.preventDefault();
        const selectedUserId = card.querySelector(".name-dropdown").value;
        const selectedUser = userjson.find(user => user.rowid === selectedUserId);
        const name = selectedUser ? `${selectedUser.firstname} ${selectedUser.lastname}` : "Non spécifié";

        // Formater les habilitations avec des retours à la ligne
        const habilitations = selectedUser ? (selectedUser.habilitations || "Non spécifié") : "Non spécifié";
        const formattedHabilitations = habilitations.split(",").map(h => h.trim()).join(",\n");

        card.innerHTML = `
            <div class="card-body" style="text-align: center; color: #333;">
                <input type="text" class="title-input" name="title" value="${card.querySelector(".title-input").value}" required
                    style="width: 80%; margin-bottom: 10px; padding: 5px; text-align: center; color: #333;">
                <p><strong>${name}</strong></p>
                <div class="user-details" style="margin-top: 10px; width: 100%; display: flex; flex-direction: column; align-items: center;">
                    <div class="habilitation-info" style="margin-bottom: 5px; word-wrap: break-word; white-space: normal; text-align: center; padding: 5px; font-size: 0.9em; line-height: 1.4; width: 90%;">
                        <strong>Habilitations:</strong><br>${formattedHabilitations}
                    </div>
                    <div class="contrat-info" style="margin-bottom: 5px; word-wrap: break-word; white-space: normal; text-align: center; padding: 5px; font-size: 0.9em; width: 90%;">
                        <strong>Contrat:</strong><br>${selectedUser ? (selectedUser.contrat || "Non spécifié") : "Non spécifié"}
                    </div>
                </div>
                <input type="hidden" class="card-id" value="${uniqueId}">
            </div>
            <button class="delete-button" style="margin-top: 10px;">Supprimer</button>
        `;

        attachDeleteListener(card);
    });

    attachDeleteListener(card);
    return card;
}


function createUserList(column) {
    const columnElement = document.querySelector(`.card-column:nth-child(${column})`);
    
    // Compter les cartes et listes existantes dans la colonne
    const itemsInColumn = columnElement.querySelectorAll(".card, .user-list").length;

    // Y commence à 2 si il y a déjà des éléments dans la colonne, sinon à 2
    const yPosition = itemsInColumn + 1;
    const listId = `${column}${yPosition}`;

    const list = document.createElement("div");
    list.className = "user-list card";
    list.setAttribute("data-status", "0"); // Ajouter le statut initial

    // Ajouter un saut de ligne avant le titre
    const lineBreak = document.createElement("br");
    list.appendChild(lineBreak);

    // Ajouter un ID unique
    list.setAttribute("data-list-id", listId);

    // Créer un conteneur pour le titre avec le trait rouge
    const titleContainer = document.createElement("div");
    titleContainer.style = "text-align: center; padding-bottom: 10px; margin-bottom: 10px; color: #333; font-weight: bold;";

    const listTitleInput = document.createElement("input");
    listTitleInput.type = "text";
    listTitleInput.className = "list-title-input";
    listTitleInput.name = "listTitle";
    listTitleInput.placeholder = ""; // On enlève le placeholder par défaut
    listTitleInput.required = true;
    listTitleInput.style = "width: 80%; padding: 5px; text-align: center; color: #333;";

    // Fonction pour gérer laffichage du placeholder
    function updatePlaceholder() {
        const listStatus = parseInt(list.getAttribute("data-status"));
        
        if (listTitleInput.value === "" && listStatus === 0) {
            listTitleInput.placeholder = "Titre de la liste";
            listTitleInput.style.textAlign = "center";
        } else {
            listTitleInput.placeholder = "";
            if (listStatus === 1 || listStatus === 2) {
                listTitleInput.style.textAlign = "center";
            }
        }
    }

    // Ajouter les écouteurs dévénements
    listTitleInput.addEventListener("focus", updatePlaceholder);
    listTitleInput.addEventListener("blur", updatePlaceholder);
    listTitleInput.addEventListener("input", updatePlaceholder);

    // Observer les changements dattribut data-status sur la liste
    const observer = new MutationObserver(updatePlaceholder);
    observer.observe(list, { attributes: true, attributeFilter: ["data-status"] });

    titleContainer.appendChild(listTitleInput);

    // Créer une légende pour décrire les informations
    const legend = document.createElement("div");
    legend.className = "list-legend";
    legend.style = "display: flex; justify-content: space-between; padding: 10px; font-weight: bold; color: #333; margin-bottom: 10px; text-align: center;";
    legend.innerHTML = `
        <div style="flex: 1; text-align: center;">Nom</div>
        <div style="flex: 1; text-align: center;">Fonction</div>
        <div style="flex: 1; text-align: center;">Contrat</div>
        <div style="flex: 1; text-align: center;">Habil</div>
        <div style="flex: 1; text-align: center;">Tél</div>
    `;

    const ulElement = document.createElement("ul");
    ulElement.style = "list-style: none; padding: 0; margin: 0;";

    // Remplir les utilisateurs de la liste depuis uniqueJsData
    uniqueJsData.forEach(user => {
        // Vérifier si lutilisateur nest pas Q3SE ou PCR
        if (user.libelle !== "ResponsableQ3SE" && user.libelle !== "PCRRéférent") {
            const li = document.createElement("li");
            li.setAttribute("data-user-id", user.fk_socpeople);
            li.style = "display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #ddd; text-align: center;";

            // Créer une ligne avec les informations de lutilisateur
            li.innerHTML = `
                <div style="flex: 1; text-align: center; white-space: normal;" title="${user.lastname} ${user.firstname}">
                    ${user.lastname}<br>${user.firstname}
                </div>
                <div style="flex: 1; text-align: center; white-space: normal;" title="${user.fonction || "Non définie"}">
                    ${user.fonction || "Non définie"}
                </div>
                <div style="flex: 1; text-align: center; white-space: normal;" title="${user.contrat || "Non défini"}">
                    ${user.contrat || "Non défini"}
                </div>
                <div style="flex: 1; text-align: center; white-space: normal;" title="${user.habilitation || "Aucune habilitation"}">
                    ${user.habilitation || "Aucune habilitation"}
                </div>
                <div style="flex: 1; text-align: center; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${user.phone || "Non défini"}">
                    ${user.phone || "Non défini"}
                </div>
            `;

            // Ajouter une fonction pour gérer le style en fonction du statut
            function updateListItemsStyle() {
                const listStatus = parseInt(list.getAttribute("data-status"));
                const items = list.querySelectorAll("li > div");
                
                items.forEach((item, index) => {
                    // Ne pas modifier le style du nom et du téléphone
                    if (index !== 0 && index !== 4) {
                        if (listStatus === 1 || listStatus === 2) {
                            item.style.textAlign = "center";
                            item.style.whiteSpace = "normal";
                        } else {
                            item.style.textAlign = "left";
                            item.style.whiteSpace = "nowrap";
                            item.style.overflow = "hidden";
                            item.style.textOverflow = "ellipsis";
                        }
                    }
                });
            }

            // Observer les changements dattribut data-status sur la liste
            const observer = new MutationObserver(updateListItemsStyle);
            observer.observe(list, { attributes: true, attributeFilter: ["data-status"] });

            // Appliquer le style initial
            updateListItemsStyle();

            // Ajouter le bouton de suppression
            const removeSpan = document.createElement("span");
            removeSpan.textContent = "×";
            removeSpan.style = "color:red; cursor:pointer;";
            removeSpan.className = "remove-user";
            li.appendChild(removeSpan);

            ulElement.appendChild(li);

            // Ajouter une ligne vide entre les utilisateurs
            const emptyRow = document.createElement("li");
            emptyRow.style = "height: 10px;"; // Hauteur de la ligne vide
            ulElement.appendChild(emptyRow);
        }
    });

    const listBody = document.createElement("div");
    listBody.className = "list-body";
    listBody.style = "text-align: left; color: #333; padding-left: 20px; padding-right: 20px; margin-bottom: 20px;"; // Ajouter des espaces à gauche et à droite
    listBody.appendChild(titleContainer); // Ajouter le titre avec le trait rouge
    listBody.appendChild(legend); // Ajouter la légende en haut de la liste
    listBody.appendChild(ulElement);

    list.appendChild(listBody);

    const lineBreakAfter = document.createElement("br");
    list.appendChild(lineBreakAfter);

    // Attacher les écouteurs de suppression utilisateur
    attachUserRemoveListeners(list);

    // Ajouter un bouton de suppression pour la liste
    const deleteButton = document.createElement("button");
    deleteButton.textContent = "Supprimer";
    deleteButton.className = "delete-list-button btn btn-danger";
    deleteButton.style = "margin: 10px auto; display: block;"; // Centrer le bouton horizontalement
    deleteButton.addEventListener("click", () => {
        list.remove();
        saveData(); // Sauvegarder les modifications après suppression
    });

    list.appendChild(deleteButton);

    return list;
}



// Fonction pour attacher les écouteurs de suppression aux utilisateurs
function attachUserRemoveListeners(list) {
    const removeButtons = list.querySelectorAll(".remove-user");

    removeButtons.forEach(removeButton => {
        removeButton.addEventListener("click", function() {
            const li = this.parentElement;
            li.remove(); // Retirer utilisateur de la liste
            saveData();  // Sauvegarder les données après suppression
        });
    });
}


function updateUserIdsForCard(cell, newUserId) {
    if (cell.userId && cell.userId.length > 0) {
        // Mettre à jour utilisateur avec le dernier ID
        cell.userId = [newUserId]; // On garde uniquement le dernier utilisateur ajouté
    } else {
        // Si aucune donnée dans userId, on linitialise avec ID du nouvel utilisateur
        cell.userId = [newUserId];
    }
    console.log(`User ID mis à jour pour la carte avec le nouvel utilisateur: ${newUserId}`);
}


function addItemToColumn(column, type) {
        var columnElement = document.querySelector(`.card-column:nth-child(${column})`);
        if (columnElement) {
            var newItem = null;

            if (type === "card") {
                newItem = createEmptyCard(column);  // Appel pour créer une carte
            } else if (type === "list") {
                newItem = createUserList(column);
            }

            // Vérifie que newItem est un nœud valide
            if (newItem && newItem instanceof Node) {
                // Ajoutez newItem à la fin de la colonne
                columnElement.appendChild(newItem);

                // Réattacher le listener de suppression si nécessaire
                attachDeleteListener(newItem);
            }
        }
    }


    document.querySelectorAll(".unique-list .list-title-input").forEach(input => {
        if (!input.dataset.listenerAttached) {
            input.addEventListener("blur", saveData);
            input.dataset.listenerAttached = true;
        }
    });

    document.querySelectorAll(".unique-list .list-title-input").forEach(input => {
        if (!input.dataset.listenerAttached) {
            input.addEventListener("blur", function () {
                saveData(); 
            });
            input.dataset.listenerAttached = true;
        }
    });
   

    document.querySelectorAll(".dropdown-content button").forEach(button => {
        button.addEventListener("click", function () {
            var column = this.getAttribute("data-column");
            var type = this.getAttribute("data-type");
            addItemToColumn(parseInt(column), type);
        });
    });

function attachEventListeners() {
    // Attacher un écouteur sur les boutons ajout de carte/liste
    document.querySelectorAll(".add-card-button").forEach(button => {
        if (!button.dataset.listenerAttached) {
            button.addEventListener("click", function() {
                saveData();
                attachEventListeners(); // Ré-attacher les événements après ajout
            });
            button.dataset.listenerAttached = true;
        }
    });

    // Attacher des écouteurs sur les changements des champs de titre
    document.querySelectorAll(".title-input, .list-title-input").forEach(input => {
        if (!input.dataset.listenerAttached) {
            input.addEventListener("blur", saveData);
            input.dataset.listenerAttached = true;
        }
    });

    
    // Attacher des écouteurs sur la suppression des cartes
    document.querySelectorAll(".card .delete-button").forEach(button => {
        if (!button.dataset.listenerAttached) {
            button.addEventListener("click", function() {
                const card = button.closest(".card");
                if (card) {
                    card.remove();
                    saveData();
                    attachEventListeners(); // Ré-attacher les événements après suppression
                }
            });
            button.dataset.listenerAttached = true;
        }
    });

    // Attacher des écouteurs sur la suppression des listes
    document.querySelectorAll(".delete-list-button").forEach(button => {
        if (!button.dataset.listenerAttached) {
            button.addEventListener("click", function() {
                const list = button.closest(".user-list");
                if (list) {
                    list.remove(); // Supprime la liste
                    saveData(); // Sauvegarder les modifications après suppression
                    attachEventListeners(); // Ré-attacher les événements après suppression
                }
            });
            button.dataset.listenerAttached = true;
        }
    });

    // Attacher des écouteurs sur la suppression des éléments des listes uniques
    document.querySelectorAll(".unique-list .remove-user").forEach(removeButton => {
        if (!removeButton.dataset.listenerAttached) {
            removeButton.addEventListener("click", function() {
                const li = this.closest("li");
                if (li) {
                    li.remove();
                    saveData();
                    attachEventListeners(); // Ré-attacher les événements après suppression
                }
            });
            removeButton.dataset.listenerAttached = true;
        }
    });

    // Attacher des écouteurs sur le changement utilisateur dans les cartes
    document.querySelectorAll(".card .name-dropdown").forEach(dropdown => {
        if (!dropdown.dataset.listenerAttached) {
       
            dropdown.addEventListener("change", function() {
                const selectedUser = this.value; // Nouveau utilisateur sélectionné
                const card = this.closest(".card");

                if (card) {
                    const userDisplay = card.querySelector(".selected-user-display");
                    if (userDisplay) {
                        userDisplay.textContent = selectedUser; // Afficher le nom de utilisateur
                    }
                    card.dataset.userId = selectedUser; // Mettre à jour ID utilisateur dans dataset
                    console.log("Utilisateur sélectionné pour la carte :", selectedUser);
                    console.log("Dataset après modification :", card.dataset.userId);
                    saveData(); // Sauvegarder les changements
                    console.log("Sauvegarde après sélection");
                }
            });
            dropdown.dataset.listenerAttached = true;
        }
    });
}


// Appeler `attachEventListeners()` immédiatement après la création ou la modification des cartes.


attachEventListeners();
function saveData() { 
    let cardsData = [];
    
    // Parcours de toutes les cartes pour récupérer les informations
    document.querySelectorAll(".card-column .card").forEach(function (card) {
        let titleInput = card.querySelector(".title-input");
        let nameDropdown = card.querySelector(".name-dropdown");

        // Récupération des coordonnées X et Y
        let x = Array.from(card.closest(".card-column").parentNode.children).indexOf(card.closest(".card-column")) + 1; // Récupérer X
        let y = Array.from(card.closest(".card-column").querySelectorAll(".card")).indexOf(card) + 1; // Récupérer Y

        if (titleInput && nameDropdown) {
            let title = titleInput.value;
            let userId = nameDropdown.value || card.dataset.userId || "undefined";
            let cardId = card.querySelector(".card-id").value; 

            // Récupérer les habilitations et le contrat depuis le dataset de la carte
            let habilitations = card.dataset.habilitations || "";
            let contrat = card.dataset.contrat || "";

            let cardCoordinates = {
                title: title,
                userId: userId, 
                type: card.classList.contains("user-list") ? "list" : "card",
                otid: otId, 
                id: cardId, 
                x: x || 0,
                y: y || 0,
                habilitations: habilitations,
                contrat: contrat
            };

            cardsData.push(cardCoordinates);
        }
    });


    const contactsData = selectedContacts.map(contact => {
        
        return {
            soc_people: contact.contact_id,
            firstname: contact.firstname,
            lastname: contact.lastname,
            supplier_name: contact.supplier_name,
            supplier_id: contact.supplier_id,
            fonction: contact.function,
            contrat: contact.contract,
            habilitation: contact.qualifications
        };
    });


    // Ajouter ou mettre à jour les contacts sélectionnés dans cardsData
    if (contactsData.length > 0) {
        let existingSubcontractorList = cardsData.find(item => item.type === "listesoustraitant");

        if (!existingSubcontractorList) {
            //console.log("Aucune liste de sous-traitants existante, création dune nouvelle liste.");
            existingSubcontractorList = {
                type: "listesoustraitant",
                soustraitants: []
            };
            cardsData.push(existingSubcontractorList);
        }

        //console.log("Sous-traitants existants avant mise à jour :", existingSubcontractorList.soustraitants);

        // Parcourir les sous-traitants récupérés de la base de données
        contactsData.forEach(contact => {
            const existingContact = existingSubcontractorList.soustraitants.find(
                c => c.soc_people == contact.soc_people
            );

            if (existingContact) {
                
                // Mettre à jour les informations du sous-traitant existant
            // Object.assign(existingContact, contact);
            } else {
                
                // Ajouter un nouveau sous-traitant
                existingSubcontractorList.soustraitants.push(contact);
            }
        });

        // Supprimer les doublons dans la liste des sous-traitants
        existingSubcontractorList.soustraitants = existingSubcontractorList.soustraitants.filter((contact, index, self) =>
            index === self.findIndex(c => c.soc_people === contact.soc_people)
        );

        //console.log("Sous-traitants après suppression des doublons :", existingSubcontractorList.soustraitants);
    }

    // Parcours de toutes les listes pour récupérer les informations
    document.querySelectorAll(".card-column .user-list").forEach(function (list) {
        let titleInput = list.querySelector(".list-title-input");
        let listId = list.getAttribute("data-list-id"); 

        // Récupération des coordonnées X et Y
        let x = Array.from(list.closest(".card-column").parentNode.children).indexOf(list.closest(".card-column")) + 1; // Récupérer X
        let y = Array.from(list.closest(".card-column").querySelectorAll(".card")).indexOf(list) + 1; // Récupérer Y

        if (titleInput) {
            let title = titleInput.value;

            // Remplacer la collecte des IDs utilisateurs pour ne pas ajouter de doublons
            let userIds = Array.from(list.querySelectorAll("li[data-user-id]")).map(function (li) {
                return li.getAttribute("data-user-id");
            }).filter((id, index, self) => self.indexOf(id) === index); // Supprimer les doublons si nécessaire

            let listCoordinates = {
                title: title,
                userIds: userIds, // Stocker des IDs uniques des utilisateurs
                type: "list",
                otid: otId, 
                id: listId, 
                x: x || 0,
                y: y || 0
            };

            cardsData.push(listCoordinates);
        }
    });

    document.querySelectorAll(".user-list.unique-list").forEach(function (uniqueList) {
        let titleInput = uniqueList.querySelector(".list-title-input");
        let uniqueListId = uniqueList.getAttribute("data-list-id");

        if (titleInput) {
            let title = titleInput.value;
            let userIds = Array.from(uniqueList.querySelectorAll("li[data-user-id]")).map(function (li) {
                return li.getAttribute("data-user-id");
            }).filter((id, index, self) => self.indexOf(id) === index); // Supprimer les doublons si nécessaire

            let uniqueListCoordinates = {
                title: title,
                userIds: userIds,
                type: "listeunique",
                otid: otId,
                id: 1
            };

            cardsData.push(uniqueListCoordinates); // Ajouter la liste unique à cardsData
        }
    });

// Ajouter les informations des rôles principaux (ResponsableAffaire, ResponsableQ3SE, PCRReferent)
    const roleMapping = {
        "160": "RA", // ResponsableAffaire
        "1031142": "Q3", // ResponsableQ3SE
        "1031143": "PCR" // PCRReferent
    };

    // Ajouter les cartes principales depuis jsdata
    jsdata.forEach(function(contact) {
        if (roleMapping[contact.fk_c_type_contact]) {
            cardsData.push({
                type: "cardprincipale",
                role: roleMapping[contact.fk_c_type_contact],
                userId: contact.fk_socpeople,
                userName: `${contact.firstname} ${contact.lastname}`
            });
        }
    });

    // Ajouter des cartes principales vides si elles ne sont pas présentes
    Object.keys(roleMapping).forEach(function(key) {
        const role = roleMapping[key];
        const existingCard = cardsData.find(card => card.type === "cardprincipale" && card.role === role);

        if (!existingCard) {
            cardsData.push({
                type: "cardprincipale",
                role: role,
                userId: null,
                userName: null
            });
        }
    });

    
    let payload = {
        otid: otId,
        cardsData: cardsData.length > 0 ? cardsData : null, // Mettre null si vide
        selectedContacts: contactsData.length > 0 ? contactsData : null // Inclure les contacts sélectionnés
    };
    
    console.log(cardsData);

    fetch("ot_card.php?action=save", {
        method: "GET",
    })
    .then(() => {
        return fetch("ot_card.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify(payload),
        });
    })
    .then(response => response.text()) // Assurez-vous que la réponse est en JSON ou texte
    .then(data => {
        console.log("Réponse du serveur :", cardsData);
    
        onSaveSuccess();
    })
    .catch(error => {
        console.error("Erreur de sauvegarde :", error);
    });

    // Callback après succès
    function onSaveSuccess() {
        console.log("Sauvegarde réussie !");
        // Par exemple : Afficher un message, masquer un loader, ou actualiser
    }
}
    updateCards(); 
});

</script>';
 

//------------------------------------------------------------------------------------------------------------------------------------------------------------
	if ($action != 'presend') {
		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre

		$includedocgeneration = 1;

		// Documents
	    if ($includedocgeneration) {
			$objref = dol_sanitizeFileName($object->ref);
			$relativepath = $objref.'/'.$objref.'.pdf';
			$filedir = $conf->ot->dir_output.'/'.$object->element.'/'.$objref;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
			$genallowed = $permissiontoread; // If you can read, you can build the PDF to read content
			$delallowed = $permissiontoadd; // If you can create/edit, you can remove a file on card
			print $formfile->showdocuments('ot:Ot', $object->element.'/'.$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
		}

		// Show links to link elements
		//$tableauContactProj = $form->showLinkedObjectBlock($object->showCard($object->fk_project), $linktoelem, 'contactProj');
		$linktoelem = $form->showLinkToObjectBlock($object, null, array('ot'));	
		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);
		


		print '</div><div class="fichehalfright">';

		$MAXEVENT = 10;

		$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/ot/ot_agenda.php', 1).'?id='.$object->id);

		// List of actions on element

		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);
		//$tableauContactProj = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);

		print '</div></div>';
	}

	
	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form
	$modelmail = 'ot';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->ot->dir_output;
	$trackid = 'ot'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';

	
}

// End of page
llxFooter();
$db->close();