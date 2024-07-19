<?php
/* Copyright (C) 2008-2021 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008-2021 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2020	   Ferran Marcet        <fmarcet@2byte.es>
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
 *  \file		htdocs/core/lib/security.lib.php
 *  \ingroup    core
 *  \brief		Set of function used for dolibarr security (common function included into filefunc.inc.php)
 *  			Warning, this file must not depends on other library files, except function.lib.php
 *  			because it is used at low code level.
 */


/**
 *	Check permissions of a user to show a page and an object. Check read permission.
 * 	If GETPOST('action','aZ09') defined, we also check write and delete permission.
 *  This method check permission on module then call checkUserAccessToObject() for permission on object (according to entity and socid of user).
 *
 *	@param	User	$user      	  	User to check
 *	@param  string	$features	    Features to check (it must be module $object->element. Can be a 'or' check with 'levela|levelb'.
 *									Examples: 'societe', 'contact', 'produit&service', 'produit|service', ...)
 *									This is used to check permission $user->rights->features->...
 *	@param  int		$objectid      	Object ID if we want to check a particular record (optional) is linked to a owned thirdparty (optional).
 *	@param  string	$tableandshare  'TableName&SharedElement' with Tablename is table where object is stored. SharedElement is an optional key to define where to check entity for multicompany module. Param not used if objectid is null (optional).
 *	@param  string	$feature2		Feature to check, second level of permission (optional). Can be a 'or' check with 'sublevela|sublevelb'.
 *									This is used to check permission $user->rights->features->feature2...
 *  @param  string	$dbt_keyfield   Field name for socid foreign key if not fk_soc. Not used if objectid is null (optional)
 *  @param  string	$dbt_select     Field name for select if not rowid. Not used if objectid is null (optional)
 *  @param	int		$isdraft		1=The object with id=$objectid is a draft
 *  @param	int		$mode			Mode (0=default, 1=return with not die)
 * 	@return	int						If mode = 0 (default): Always 1, die process if not allowed. If mode = 1: Return 0 if access not allowed.
 *  @see dol_check_secure_access_document(), checkUserAccessToObject()
 */
function restrictedArea_custom($user, $features, $objectid = 0, $tableandshare = '', $feature2 = '', $dbt_keyfield = 'fk_soc', $dbt_select = 'rowid', $isdraft = 0, $mode = 0)
{
	global $db, $conf;
	global $hookmanager;

	//dol_syslog("functions.lib:restrictedArea $feature, $objectid, $dbtablename, $feature2, $dbt_socfield, $dbt_select, $isdraft");
	//print "user_id=".$user->id.", features=".$features.", feature2=".$feature2.", objectid=".$objectid;
	//print ", dbtablename=".$dbtablename.", dbt_socfield=".$dbt_keyfield.", dbt_select=".$dbt_select;
	//print ", perm: ".$features."->".$feature2."=".($user->rights->$features->$feature2->lire)."<br>";

	$parentfortableentity = '';

	// Fix syntax of $features param
	$originalfeatures = $features;
	if ($features == 'facturerec') {
		$features = 'facture';
	}
	if ($features == 'mo') {
		$features = 'mrp';
	}
	if ($features == 'member') {
		$features = 'adherent';
	}
	if ($features == 'subscription') {
		$features = 'adherent';
		$feature2 = 'cotisation';
	};
	if ($features == 'websitepage') {
		$features = 'website';
		$tableandshare = 'website_page';
		$parentfortableentity = 'fk_website@website';
	}
	if ($features == 'project') {
		$features = 'projet';
	}
	if ($features == 'product') {
		$features = 'produit';
	}


	// Get more permissions checks from hooks
	$parameters = array('features'=>$features, 'originalfeatures'=>$originalfeatures, 'objectid'=>$objectid, 'dbt_select'=>$dbt_select, 'idtype'=>$dbt_select, 'isdraft'=>$isdraft);
	$reshook = $hookmanager->executeHooks('restrictedArea', $parameters);

	if (isset($hookmanager->resArray['result'])) {
		if ($hookmanager->resArray['result'] == 0) {
			if ($mode) {
				return 0;
			} else {
				accessforbidden(); // Module returns 0, so access forbidden
			}
		}
	}
	if ($reshook > 0) {		// No other test done.
		return 1;
	}

	if ($dbt_select != 'rowid' && $dbt_select != 'id') {
		$objectid = "'".$objectid."'";
	}

	// Features/modules to check
	$featuresarray = array($features);
	if (preg_match('/&/', $features)) {
		$featuresarray = explode("&", $features);
	} elseif (preg_match('/\|/', $features)) {
		$featuresarray = explode("|", $features);
	}

	// More subfeatures to check
	if (!empty($feature2)) {
		$feature2 = explode("|", $feature2);
	}

	$listofmodules = explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL);

	// Check read permission from module
	$readok = 1;
	$nbko = 0;
	foreach ($featuresarray as $feature) {	// first we check nb of test ko
		$featureforlistofmodule = $feature;
		if ($featureforlistofmodule == 'produit') {
			$featureforlistofmodule = 'product';
		}
		if (!empty($user->socid) && !empty($conf->global->MAIN_MODULES_FOR_EXTERNAL) && !in_array($featureforlistofmodule, $listofmodules)) {	// If limits on modules for external users, module must be into list of modules for external users
			$readok = 0;
			$nbko++;
			continue;
		}

		if ($feature == 'societe') {
			if (empty($user->rights->societe->lire) && empty($user->rights->fournisseur->lire)) {
				$readok = 0;
				$nbko++;
			}
		} elseif ($feature == 'contact') {
			if (empty($user->rights->societe->contact->lire)) {
				$readok = 0;
				$nbko++;
			}
		} elseif ($feature == 'produit|service') {
			if (!$user->rights->produit->lire && !$user->rights->service->lire) {
				$readok = 0;
				$nbko++;
			}
		} elseif ($feature == 'prelevement') {
			if (!$user->rights->prelevement->bons->lire) {
				$readok = 0;
				$nbko++;
			}
		} elseif ($feature == 'cheque') {
			if (empty($user->rights->banque->cheque)) {
				$readok = 0;
				$nbko++;
			}
		} elseif ($feature == 'projet') {
			if (!$user->rights->projet->lire && empty($user->rights->projet->all->lire)) {
				$readok = 0;
				$nbko++;
			}
		} elseif ($feature == 'payment') {
			if (!$user->rights->facture->lire) {
				$readok = 0;
				$nbko++;
			}
		} elseif ($feature == 'payment_supplier') {
			if (empty($user->rights->fournisseur->facture->lire)) {
				$readok = 0;
				$nbko++;
			}
		} elseif (!empty($feature2)) { 													// This is for permissions on 2 levels
			$tmpreadok = 1;
			foreach ($feature2 as $subfeature) {
				if ($subfeature == 'user' && $user->id == $objectid) {
					continue; // A user can always read its own card
				}
				if (!empty($subfeature) && empty($user->rights->$feature->$subfeature->lire) && empty($user->rights->$feature->$subfeature->read)) {
					$tmpreadok = 0;
				} elseif (empty($subfeature) && empty($user->rights->$feature->lire) && empty($user->rights->$feature->read)) {
					$tmpreadok = 0;
				} else {
					$tmpreadok = 1;
					break;
				} // Break is to bypass second test if the first is ok
			}
			if (!$tmpreadok) {	// We found a test on feature that is ko
				$readok = 0; // All tests are ko (we manage here the and, the or will be managed later using $nbko).
				$nbko++;
			}
		} elseif (!empty($feature) && ($feature != 'user' && $feature != 'usergroup')) {		// This is permissions on 1 level
			if (empty($user->rights->$feature->lire)
				&& empty($user->rights->$feature->read)
				&& empty($user->rights->$feature->run)) {
				$readok = 0;
				$nbko++;
			}
		}
	}

	// If a or and at least one ok
	if (preg_match('/\|/', $features) && $nbko < count($featuresarray)) {
		$readok = 1;
	}

	if (!$readok) {
		if ($mode) {
			return 0;
		} else {
			accessforbidden();
		}
	}
	//print "Read access is ok";

	// Check write permission from module (we need to know write permission to create but also to delete drafts record or to upload files)
	$createok = 1;
	$nbko = 0;
	$wemustcheckpermissionforcreate = (GETPOST('sendit', 'alpha') || GETPOST('linkit', 'alpha') || in_array(GETPOST('action', 'aZ09'), array('create', 'update', 'add_element_resource', 'confirm_delete_linked_resource')) || GETPOST('roworder', 'alpha', 2));
	$wemustcheckpermissionfordeletedraft = ((GETPOST("action", "aZ09") == 'confirm_delete' && GETPOST("confirm", "aZ09") == 'yes') || GETPOST("action", "aZ09") == 'delete');

	if ($wemustcheckpermissionforcreate || $wemustcheckpermissionfordeletedraft) {
		foreach ($featuresarray as $feature) {
			if ($feature == 'contact') {
				if (empty($user->rights->societe->contact->creer)) {
					$createok = 0;
					$nbko++;
				}
			} elseif ($feature == 'produit|service') {
				if (empty($user->rights->produit->creer) && empty($user->rights->service->creer)) {
					$createok = 0;
					$nbko++;
				}
			} elseif ($feature == 'prelevement') {
				if (!$user->rights->prelevement->bons->creer) {
					$createok = 0;
					$nbko++;
				}
			} elseif ($feature == 'commande_fournisseur') {
				if (empty($user->rights->fournisseur->commande->creer) || empty($user->rights->supplier_order->creer)) {
					$createok = 0;
					$nbko++;
				}
			} elseif ($feature == 'banque') {
				if (empty($user->rights->banque->modifier)) {
					$createok = 0;
					$nbko++;
				}
			} elseif ($feature == 'cheque') {
				if (empty($user->rights->banque->cheque)) {
					$createok = 0;
					$nbko++;
				}
			} elseif ($feature == 'import') {
				if (empty($user->rights->import->run)) {
					$createok = 0;
					$nbko++;
				}
			} elseif ($feature == 'ecmcustom') {
				if (!$user->rights->ecmcustom->upload) {
					$createok = 0;
					$nbko++;
				}
			} elseif (!empty($feature2)) {														// This is for permissions on one level
				foreach ($feature2 as $subfeature) {
					if ($subfeature == 'user' && $user->id == $objectid && $user->rights->user->self->creer) {
						continue; // User can edit its own card
					}
					if ($subfeature == 'user' && $user->id == $objectid && $user->rights->user->self->password) {
						continue; // User can edit its own password
					}
					if ($subfeature == 'user' && $user->id != $objectid && $user->rights->user->user->password) {
						continue; // User can edit another user's password
					}

					if (empty($user->rights->$feature->$subfeature->creer)
					&& empty($user->rights->$feature->$subfeature->write)
					&& empty($user->rights->$feature->$subfeature->create)) {
						$createok = 0;
						$nbko++;
					} else {
						$createok = 1;
						// Break to bypass second test if the first is ok
						break;
					}
				}
			} elseif (!empty($feature)) {												// This is for permissions on 2 levels ('creer' or 'write')
				//print '<br>feature='.$feature.' creer='.$user->rights->$feature->creer.' write='.$user->rights->$feature->write; exit;
				if (empty($user->rights->$feature->creer)
				&& empty($user->rights->$feature->write)
				&& empty($user->rights->$feature->create)) {
					$createok = 0;
					$nbko++;
				}
			}
		}

		// If a or and at least one ok
		if (preg_match('/\|/', $features) && $nbko < count($featuresarray)) {
			$createok = 1;
		}

		if ($wemustcheckpermissionforcreate && !$createok) {
			if ($mode) {
				return 0;
			} else {
				accessforbidden();
			}
		}
		//print "Write access is ok";
	}

	// Check create user permission
	$createuserok = 1;
	if (GETPOST('action', 'aZ09') == 'confirm_create_user' && GETPOST("confirm", 'aZ09') == 'yes') {
		if (!$user->rights->user->user->creer) {
			$createuserok = 0;
		}

		if (!$createuserok) {
			if ($mode) {
				return 0;
			} else {
				accessforbidden();
			}
		}
		//print "Create user access is ok";
	}

	// Check delete permission from module
	$deleteok = 1;
	$nbko = 0;
	if ((GETPOST("action", "aZ09") == 'confirm_delete' && GETPOST("confirm", "aZ09") == 'yes') || GETPOST("action", "aZ09") == 'delete') {
		foreach ($featuresarray as $feature) {
			if ($feature == 'contact') {
				if (!$user->rights->societe->contact->supprimer) {
					$deleteok = 0;
				}
			} elseif ($feature == 'produit|service') {
				if (!$user->rights->produit->supprimer && !$user->rights->service->supprimer) {
					$deleteok = 0;
				}
			} elseif ($feature == 'commande_fournisseur') {
				if (!$user->rights->fournisseur->commande->supprimer) {
					$deleteok = 0;
				}
			} elseif ($feature == 'payment_supplier') {	// Permission to delete a payment of an invoice is permission to edit an invoice.
				if (!$user->rights->fournisseur->facture->creer) {
					$deleteok = 0;
				}
			} elseif ($feature == 'payment') {	// Permission to delete a payment of an invoice is permission to edit an invoice.
				if (!$user->rights->facture->creer) {
						$deleteok = 0;
				}
			} elseif ($feature == 'banque') {
				if (empty($user->rights->banque->modifier)) {
					$deleteok = 0;
				}
			} elseif ($feature == 'cheque') {
				if (empty($user->rights->banque->cheque)) {
					$deleteok = 0;
				}
			} elseif ($feature == 'ecmcustom') {
				if (!$user->rights->ecmcustom->delete) {
					$deleteok = 0;
				}
			} elseif ($feature == 'ftp') {
				if (!$user->rights->ftp->write) {
					$deleteok = 0;
				}
			} elseif ($feature == 'salaries') {
				if (!$user->rights->salaries->delete) {
					$deleteok = 0;
				}
			} elseif ($feature == 'adherent') {
				if (empty($user->rights->adherent->supprimer)) {
					$deleteok = 0;
				}
			} elseif ($feature == 'paymentbybanktransfer') {
				if (empty($user->rights->paymentbybanktransfer->create)) {	// There is no delete permission
					$deleteok = 0;
				}
			} elseif ($feature == 'prelevement') {
				if (empty($user->rights->prelevement->bons->creer)) {		// There is no delete permission
					$deleteok = 0;
				}
			} elseif (!empty($feature2)) {							// This is for permissions on 2 levels
				foreach ($feature2 as $subfeature) {
					if (empty($user->rights->$feature->$subfeature->supprimer) && empty($user->rights->$feature->$subfeature->delete)) {
						$deleteok = 0;
					} else {
						$deleteok = 1;
						break;
					} // For bypass the second test if the first is ok
				}
			} elseif (!empty($feature)) {							// This is used for permissions on 1 level
				//print '<br>feature='.$feature.' creer='.$user->rights->$feature->supprimer.' write='.$user->rights->$feature->delete;
				if (empty($user->rights->$feature->supprimer)
					&& empty($user->rights->$feature->delete)
					&& empty($user->rights->$feature->run)) {
					$deleteok = 0;
				}
			}
		}

		// If a or and at least one ok
		if (preg_match('/\|/', $features) && $nbko < count($featuresarray)) {
			$deleteok = 1;
		}

		if (!$deleteok && !($isdraft && $createok)) {
			if ($mode) {
				return 0;
			} else {
				accessforbidden();
			}
		}
		//print "Delete access is ok";
	}

	// If we have a particular object to check permissions on, we check if $user has permission
	// for this given object (link to company, is contact for project, ...)
	if (!empty($objectid) && $objectid > 0) {
		$ok = checkUserAccessToObject($user, $featuresarray, $objectid, $tableandshare, $feature2, $dbt_keyfield, $dbt_select, $parentfortableentity);
		$params = array('objectid' => $objectid, 'features' => join(',', $featuresarray), 'features2' => $feature2);
		//print 'checkUserAccessToObject ok='.$ok;
		if ($mode) {
			return $ok ? 1 : 0;
		} else {
			return $ok ? 1 : accessforbidden('', 1, 1, 0, $params);
		}
	}

	return 1;
}