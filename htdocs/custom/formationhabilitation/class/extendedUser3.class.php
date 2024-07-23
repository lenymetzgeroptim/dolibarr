<?php
/* Copyright (C) 2021 Lény Metzger  <leny-07@hotmail.fr>
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
 *  \file       htdocs/user/class/user.class.php
 *	\brief      File of class to manage users
 *  \ingroup	core
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fod/class/fod_user.class.php';

/**
 *	Class to manage Dolibarr users
 */
class ExtendedUser3 extends User {


    /**
	 * 	Create an array of lines
	 *
	 * 	@return array|int		array of lines if OK, <0 if KO
	 */
	public function getLinesArrayFormationHabilitation($type)
	{
        global $sortfield, $sortorder;
		$this->lines = array();

        if($type == 'formation') {
            $objectline = new UserFormation($this->db);
            $result = $objectline->fetchAll($sortorder, $sortfield, 0, 0, array('customsql'=>'fk_user = '.((int) $this->id)));
        }
        elseif($type == 'habilitation'){
            $objectline = new UserHabilitation($this->db);
            $result = $objectline->fetchAll('ASC', 'rowid', 0, 0, array('customsql'=>'fk_user = '.((int) $this->id)));
        }
        elseif($type == 'autorisation'){
            $objectline = new UserAutorisation($this->db);
            $result = $objectline->fetchAll('ASC', 'rowid', 0, 0, array('customsql'=>'fk_user = '.((int) $this->id)));
        }
        else {
            return -1;
        }

		if (is_numeric($result)) {
			$this->error = $objectline->error;
			$this->errors = $objectline->errors;
			return $result;
		} else {
			$this->lines = $result;
			return $this->lines;
		}
	}


    /**
	 *  Delete a line of object in database
	 *
	 *	@param  User	$user       User that delete
	 *  @param	int		$idline		Id of line to delete
	 *  @param 	bool 	$notrigger  false=launch triggers after, true=disable triggers
	 *  @return int         		>0 if OK, <0 if KO
	 */
	public function deleteLine(User $user, $idline, $notrigger = false)
	{
		if ($this->status < 0) {
			$this->error = 'ErrorDeleteLineNotAllowedByObjectStatus';
			return -2;
		}

        global $conf, $onglet;

        $error = 0;

        $this->db->begin();

        if(empty($onglet) || $onglet == 'formation') {
            $sql = "DELETE FROM ".MAIN_DB_PREFIX.'formationhabilitation_userformation';
            $sql .= " WHERE rowid = ".((int) $idline);
        }
        elseif($onglet== 'habilitation'){
            $sql = "DELETE FROM ".MAIN_DB_PREFIX.'formationhabilitation_userhabilitation';
            $sql .= " WHERE rowid = ".((int) $idline);
        }
        elseif($onglet== 'autorisation'){
            $sql = "DELETE FROM ".MAIN_DB_PREFIX.'formationhabilitation_userautorisation';
            $sql .= " WHERE rowid = ".((int) $idline);
        }

        dol_syslog(get_class($this)."::deleteLine", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->error = "Error ".$this->db->lasterror();
            $error++;
        }

        if (empty($error)) {
            $this->db->commit();
            return 1;
        } else {
            dol_syslog(get_class($this)."::deleteLineCommon ERROR:".$this->error, LOG_ERR);
            $this->db->rollback();
            return -1;
        }	
    }

    public function printObjectLine($action, $line, $var, $num, $i, $dateSelector, $seller, $buyer, $selected = 0, $extrafields = null, $defaulttpldir = '/core/tpl')
    {
        global $conf, $langs, $user, $object, $hookmanager;
        global $form;
        global $object_rights, $disableedit, $disablemove, $disableremove; // TODO We should not use global var for this !

        $object_rights = $this->getRights();

        $element = $this->element;

        $text = '';
        $description = '';

        // Line in view mode
        if ($action != 'editline' || $selected != $line->id) {
            // Product
            if ($line->fk_product > 0) {
                $product_static = new Product($this->db);
                $product_static->fetch($line->fk_product);

                $product_static->ref = $line->ref; //can change ref in hook
                $product_static->label = $line->label; //can change label in hook

                $text = $product_static->getNomUrl(1);

                // Define output language and label
                if (!empty($conf->global->MAIN_MULTILANGS)) {
                    if (property_exists($this, 'socid') && !is_object($this->thirdparty)) {
                        dol_print_error('', 'Error: Method printObjectLine was called on an object and object->fetch_thirdparty was not done before');
                        return;
                    }

                    $prod = new Product($this->db);
                    $prod->fetch($line->fk_product);

                    $outputlangs = $langs;
                    $newlang = '';
                    if (empty($newlang) && GETPOST('lang_id', 'aZ09')) {
                        $newlang = GETPOST('lang_id', 'aZ09');
                    }
                    if (!empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE) && empty($newlang) && is_object($this->thirdparty)) {
                        $newlang = $this->thirdparty->default_lang; // To use language of customer
                    }
                    if (!empty($newlang)) {
                        $outputlangs = new Translate("", $conf);
                        $outputlangs->setDefaultLang($newlang);
                    }

                    $label = (!empty($prod->multilangs[$outputlangs->defaultlang]["label"])) ? $prod->multilangs[$outputlangs->defaultlang]["label"] : $line->product_label;
                } else {
                    $label = $line->product_label;
                }

                $text .= ' - '.(!empty($line->label) ? $line->label : $label);
                $description .= (!empty($conf->global->PRODUIT_DESC_IN_FORM) ? '' : dol_htmlentitiesbr($line->description)); // Description is what to show on popup. We shown nothing if already into desc.
            }

            $line->pu_ttc = price2num($line->subprice * (1 + ($line->tva_tx / 100)), 'MU');

            // Output template part (modules that overwrite templates must declare this into descriptor)
            // Use global variables + $dateSelector + $seller and $buyer
            // Note: This is deprecated. If you need to overwrite the tpl file, use instead the hook printObjectLine and printObjectSubLine.
            $dirtpls = array_merge($conf->modules_parts['tpl'], array($defaulttpldir));
            foreach ($dirtpls as $module => $reldir) {
                if (!empty($module)) {
                    $tpl = dol_buildpath($reldir.'/objectline_view.tpl.php');
                } else {
                    $tpl = DOL_DOCUMENT_ROOT.$reldir.'/objectline_view.tpl.php';
                }

                if (empty($conf->file->strict_mode)) {
                    $res = @include $tpl;
                } else {
                    $res = include $tpl; // for debug
                }
                if ($res) {
                    break;
                }
            }
        }

        // Line in update mode
        if ($action == 'editline' && $selected == $line->id) {
            $label = (!empty($line->label) ? $line->label : (($line->fk_product > 0) ? $line->product_label : ''));

            $line->pu_ttc = price2num($line->subprice * (1 + ($line->tva_tx / 100)), 'MU');

            // Output template part (modules that overwrite templates must declare this into descriptor)
            // Use global variables + $dateSelector + $seller and $buyer
            // Note: This is deprecated. If you need to overwrite the tpl file, use instead the hook printObjectLine and printObjectSubLine.
            $dirtpls = array_merge($conf->modules_parts['tpl'], array($defaulttpldir));
            foreach ($dirtpls as $module => $reldir) {
                if (!empty($module)) {
                    $tpl = dol_buildpath($reldir.'/objectline_edit.tpl.php');
                } else {
                    $tpl = DOL_DOCUMENT_ROOT.$reldir.'/objectline_edit.tpl.php';
                }

                if (empty($conf->file->strict_mode)) {
                    $res = @include $tpl;
                } else {
                    $res = include $tpl; // for debug
                }
                if ($res) {
                    break;
                }
            }
        }
    }
}