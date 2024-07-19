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
 *	\file       htdocs/core/class/html.form.class.php
 *  \ingroup    core
 *	\brief      File of class with all html predefined components
 */


/**
 *	Class to manage generation of HTML components
 *	Only common components must be here.
 *
 *  TODO Merge all function load_cache_* and loadCache* (except load_cache_vatrates) into one generic function loadCacheTable
 */
class ExtendedForm extends Form
{

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return select list of users in a group
	 *
	 *  @param 	string			$group			Nom du groupe dont on souhaite avoir les utilisateurs
	 *  @param	string			$selected       User id or user object of user preselected. If 0 or < -2, we use id of current user. If -1, keep unselected (if empty is allowed)
	 *  @param  string			$htmlname       Field name in form
	 *  @param  int|string		$show_empty     0=list with no empty value, 1=add also an empty value into list
	 *  @param  array			$exclude        Array list of users id to exclude
	 * 	@param	int				$disabled		If select list must be disabled
	 *  @param  array|string	$include        Array list of users id to include. User '' for all users or 'hierarchy' to have only supervised users or 'hierarchyme' to have supervised + me
	 * 	@param	array			$enableonly		Array list of users id to be enabled. If defined, it means that others will be disabled
	 *  @param	string			$force_entity	'0' or Ids of environment to force
	 *  @param	int				$maxlength		Maximum length of string into list (0=no limit)
	 *  @param	int				$showstatus		0=show user status only if status is disabled, 1=always show user status into label, -1=never show user status
	 *  @param	string			$morefilter		Add more filters into sql request (Example: 'employee = 1'). This value must not come from user input.
	 *  @param	integer			$show_every		0=default list, 1=add also a value "Everybody" at beginning of list
	 *  @param	string			$enableonlytext	If option $enableonlytext is set, we use this text to explain into label why record is disabled. Not used if enableonly is empty.
	 *  @param	string			$morecss		More css
	 *  @param  int     		$noactive       Show only active users (this will also happened whatever is this option if USER_HIDE_INACTIVE_IN_COMBOBOX is on).
	 *  @param  int				$outputmode     0=HTML select string, 1=Array
	 *  @param  bool			$multiple       add [] in the name of element and add 'multiple' attribut
	 * 	@return	string							HTML select string
	 *  @see select_dolgroups()
	 */
	public function select_dolusersInGroup($group = array(), $selected = '', $htmlname = '', $show_empty = 0, $exclude = null, $disabled = 0, $include = '', $enableonly = '', $force_entity = '0', $maxlength = 0, $showstatus = 0, $morefilter = '', $show_every = 0, $enableonlytext = '', $morecss = '', $noactive = 0, $outputmode = 0, $multiple = false)
	{
		// phpcs:enable
		global $conf, $user, $langs, $hookmanager;

		// If no preselected user defined, we take current user
		if ((is_numeric($selected) && ($selected < -2 || empty($selected))) && empty($conf->global->SOCIETE_DISABLE_DEFAULT_SALESREPRESENTATIVE)) {
			$selected = $user->id;
		}

		if ($selected === '') {
			$selected = array();
		} elseif (!is_array($selected)) {
			$selected = array($selected);
		}

		$excludeUsers = null;
		$includeUsers = null;

		// Permettre l'exclusion d'utilisateurs
		if (is_array($exclude)) {
			$excludeUsers = implode(",", $exclude);
		}
		// Permettre l'inclusion d'utilisateurs
		if (is_array($include)) {
			$includeUsers = implode(",", $include);
		} elseif ($include == 'hierarchy') {
			// Build list includeUsers to have only hierarchy
			$includeUsers = implode(",", $user->getAllChildIds(0));
		} elseif ($include == 'hierarchyme') {
			// Build list includeUsers to have only hierarchy and current user
			$includeUsers = implode(",", $user->getAllChildIds(1));
		}

		$out = '';
		$outarray = array();

		// Forge request to select users
		$sql = "SELECT DISTINCT tab.rowid, tab.lastname, tab.firstname, tab.status FROM ";
		$sql .= "(SELECT DISTINCT u.rowid, u.lastname as lastname, u.firstname, u.statut as status, u.login, u.admin, u.entity, u.photo, uu.fk_usergroup";
		if (!empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && !$user->entity) {
			$sql .= ", e.label";
		}
		$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql .= " RIGHT JOIN ".MAIN_DB_PREFIX."usergroup_user as uu";
		$sql .= " ON u.rowid=uu.fk_user";
		if (!empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && !$user->entity) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."entity as e ON e.rowid = u.entity";
			if ($force_entity) {
				$sql .= " WHERE u.entity IN (0, ".$this->db->sanitize($force_entity).")";
			} else {
				$sql .= " WHERE u.entity IS NOT NULL";
			}
		} else {
			if (!empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."usergroup_user as ug";
				$sql .= " ON ug.fk_user = u.rowid";
				$sql .= " WHERE ug.entity = ".$conf->entity;
			} else {
				$sql .= " WHERE u.entity IN (0, ".$conf->entity.")";
			}
		}
		if (!empty($user->socid)) {
			$sql .= " AND u.fk_soc = ".((int) $user->socid);
		}
		if (is_array($exclude) && $excludeUsers) {
			$sql .= " AND u.rowid NOT IN (".$this->db->sanitize($excludeUsers).")";
		}
		if ($includeUsers) {
			$sql .= " AND u.rowid IN (".$this->db->sanitize($includeUsers).")";
		}
		if (!empty($conf->global->USER_HIDE_INACTIVE_IN_COMBOBOX) || $noactive) {
			$sql .= " AND u.statut <> 0";
		}
		if (!empty($morefilter)) {
			$sql .= " ".$morefilter;
		}

		//Add hook to filter on user (for exemple on usergroup define in custom modules)
		$reshook = $hookmanager->executeHooks('addSQLWhereFilterOnSelectUsers', array(), $this, $action);
		if (!empty($reshook)) {
			$sql .= $hookmanager->resPrint;
		}

		if (empty($conf->global->MAIN_FIRSTNAME_NAME_POSITION)) {	// MAIN_FIRSTNAME_NAME_POSITION is 0 means firstname+lastname
			$sql .= " ORDER BY u.statut DESC, u.firstname ASC, u.lastname ASC) AS tab";
		} else {
			$sql .= " ORDER BY u.statut DESC, u.lastname ASC, u.firstname ASC) AS tab";
		}

		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."usergroup as us ON fk_usergroup=us.rowid WHERE ";
		$sql .= " us.rowid IN ('".implode("', '", $group)."')";

		dol_syslog(get_class($this)."::select_dolusers", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num) {
				// do not use maxwidthonsmartphone by default. Set it by caller so auto size to 100% will work when not defined
				$out .= '<select class="flat'.($morecss ? ' '.$morecss : ' minwidth200').'" id="'.$htmlname.'" name="'.$htmlname.($multiple ? '[]' : '').'" '.($multiple ? 'multiple' : '').' '.($disabled ? ' disabled' : '').'>';
				if ($show_empty && !$multiple) {
					$textforempty = ' ';
					if (!empty($conf->use_javascript_ajax)) {
						$textforempty = '&nbsp;'; // If we use ajaxcombo, we need &nbsp; here to avoid to have an empty element that is too small.
					}
					if (!is_numeric($show_empty)) {
						$textforempty = $show_empty;
					}
					$out .= '<option class="optiongrey" value="'.($show_empty < 0 ? $show_empty : -1).'"'.((empty($selected) || in_array(-1, $selected)) ? ' selected' : '').'>'.$textforempty.'</option>'."\n";
				}
				if ($show_every) {
					$out .= '<option value="-2"'.((in_array(-2, $selected)) ? ' selected' : '').'>-- '.$langs->trans("Everybody").' --</option>'."\n";
				}

				$userstatic = new User($this->db);

				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);

					$userstatic->id = $obj->rowid;
					$userstatic->lastname = $obj->lastname;
					$userstatic->firstname = $obj->firstname;
					$userstatic->photo = $obj->photo;
					$userstatic->statut = $obj->status;
					$userstatic->entity = $obj->entity;
					$userstatic->admin = $obj->admin;

					$disableline = '';
					if (is_array($enableonly) && count($enableonly) && !in_array($obj->rowid, $enableonly)) {
						$disableline = ($enableonlytext ? $enableonlytext : '1');
					}

					$labeltoshow = '';

					// $fullNameMode is 0=Lastname+Firstname (MAIN_FIRSTNAME_NAME_POSITION=1), 1=Firstname+Lastname (MAIN_FIRSTNAME_NAME_POSITION=0)
					$fullNameMode = 0;
					if (empty($conf->global->MAIN_FIRSTNAME_NAME_POSITION)) {
						$fullNameMode = 1; //Firstname+lastname
					}
					$labeltoshow .= $userstatic->getFullName($langs, $fullNameMode, -1, $maxlength);
					if (empty($obj->firstname) && empty($obj->lastname)) {
						$labeltoshow .= $obj->login;
					}

					// Complete name with more info
					$moreinfo = '';
					if (!empty($conf->global->MAIN_SHOW_LOGIN)) {
						$moreinfo .= ($moreinfo ? ' - ' : ' (').$obj->login;
					}
					if ($showstatus >= 0) {
						if ($obj->status == 1 && $showstatus == 1) {
							$moreinfo .= ($moreinfo ? ' - ' : ' (').$langs->trans('Enabled');
						}
						if ($obj->status == 0 && $showstatus == 1) {
							$moreinfo .= ($moreinfo ? ' - ' : ' (').$langs->trans('Disabled');
						}
					}
					if (!empty($conf->multicompany->enabled) && empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && $conf->entity == 1 && $user->admin && !$user->entity) {
						if (!$obj->entity) {
							$moreinfo .= ($moreinfo ? ' - ' : ' (').$langs->trans("AllEntities");
						} else {
							if ($obj->entity != $conf->entity) {
								$moreinfo .= ($moreinfo ? ' - ' : ' (').($obj->label ? $obj->label : $langs->trans("EntityNameNotDefined"));
							}
						}
					}
					$moreinfo .= ($moreinfo ? ')' : '');
					if ($disableline && $disableline != '1') {
						$moreinfo .= ' - '.$disableline; // This is text from $enableonlytext parameter
					}
					$labeltoshow .= $moreinfo;

					$out .= '<option value="'.$obj->rowid.'"';
					if ($disableline) {
						$out .= ' disabled';
					}
					if ((is_object($selected) && $selected->id == $obj->rowid) || (!is_object($selected) && in_array($obj->rowid, $selected))) {
						$out .= ' selected';
					}
					$out .= ' data-html="';
					$outhtml = '';
					// if (!empty($obj->photo)) {
					$outhtml .= $userstatic->getNomUrl(-3, '', 0, 1, 24, 1, 'login', '', 1).' ';
					// }
					if ($showstatus >= 0 && $obj->status == 0) {
						$outhtml .= '<strike class="opacitymediumxxx">';
					}
					$outhtml .= $labeltoshow;
					if ($showstatus >= 0 && $obj->status == 0) {
						$outhtml .= '</strike>';
					}
					$out .= dol_escape_htmltag($outhtml);
					$out .= '">';
					$out .= $labeltoshow;
					$out .= '</option>';

					$outarray[$userstatic->id] = $userstatic->getFullName($langs, $fullNameMode, -1, $maxlength).$moreinfo;

					$i++;
				}
			} else {
				$out .= '<select class="flat" id="'.$htmlname.'" name="'.$htmlname.'" disabled>';
				$out .= '<option value="">'.$langs->trans("None").'</option>';
			}
			$out .= '</select>';

			if ($num) {
				// Enhance with select2
				include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
				$out .= ajax_combobox($htmlname);
			}
		} else {
			dol_print_error($this->db);
		}

		if ($outputmode) {
			return $outarray;
		}
		return $out;
	}

	/**
	 *    Return a HTML area with the reference of object and a navigation bar for a business object
	 *    Note: To complete search with a particular filter on select, you can set $object->next_prev_filter set to define SQL criterias.
	 *
	 *    @param	object	$object			Object to show.
	 *    @param	string	$paramid   		Name of parameter to use to name the id into the URL next/previous link.
	 *    @param	string	$morehtml  		More html content to output just before the nav bar.
	 *    @param	int		$shownav	  	Show Condition (navigation is shown if value is 1).
	 *    @param	string	$fieldid   		Name of field id into database to use for select next and previous (we make the select max and min on this field compared to $object->ref). Use 'none' to disable next/prev.
	 *    @param	string	$fieldref   	Name of field ref of object (object->ref) to show or 'none' to not show ref.
	 *    @param	string	$morehtmlref  	More html to show after ref.
	 *    @param	string	$moreparam  	More param to add in nav link url. Must start with '&...'.
	 *	  @param	int		$nodbprefix		Do not include DB prefix to forge table name.
	 *	  @param	string	$morehtmlleft	More html code to show before ref.
	 *	  @param	string	$morehtmlstatus	More html code to show under navigation arrows (status place).
	 *	  @param	string	$morehtmlright	More html code to show after ref.
	 * 	  @return	string    				Portion HTML with ref + navigation buttons
	 */
	public function showrefnav_fod($object, $paramid, $morehtml = '', $shownav = 1, $fieldid = 'rowid', $fieldref = 'ref', $morehtmlref = '', $moreparam = '', $nodbprefix = 0, $morehtmlleft = '', $morehtmlstatus = '', $morehtmlright = '')
	{
		global $langs, $conf, $hookmanager, $extralanguages;

		$ret = '';
		if (empty($fieldid)) {
			$fieldid = 'rowid';
		}
		if (empty($fieldref)) {
			$fieldref = 'ref';
		}

		// Preparing gender's display if there is one
		$addgendertxt = '';
		if (!empty($object->gender)) {
			$addgendertxt = ' ';
			switch ($object->gender) {
				case 'man':
					$addgendertxt .= '<i class="fas fa-mars"></i>';
					break;
				case 'woman':
					$addgendertxt .= '<i class="fas fa-venus"></i>';
					break;
				case 'other':
					$addgendertxt .= '<i class="fas fa-genderless"></i>';
					break;
			}
		}

		// Add where from hooks
		if (is_object($hookmanager)) {
			$parameters = array();
			$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object); // Note that $action and $object may have been modified by hook
			$object->next_prev_filter .= $hookmanager->resPrint;
		}
		$previous_ref = $next_ref = '';
		if ($shownav) {
			//print "paramid=$paramid,morehtml=$morehtml,shownav=$shownav,$fieldid,$fieldref,$morehtmlref,$moreparam";
			$object->load_previous_next_ref((isset($object->next_prev_filter) ? $object->next_prev_filter : ''), $fieldid, $nodbprefix);

			$navurl = $_SERVER["PHP_SELF"];
			// Special case for project/task page
			if ($paramid == 'project_ref') {
				if (preg_match('/\/tasks\/(task|contact|note|document)\.php/', $navurl)) {     // TODO Remove this when nav with project_ref on task pages are ok
					$navurl = preg_replace('/\/tasks\/(task|contact|time|note|document)\.php/', '/tasks.php', $navurl);
					$paramid = 'ref';
				}
			}

			// accesskey is for Windows or Linux:  ALT + key for chrome, ALT + SHIFT + KEY for firefox
			// accesskey is for Mac:               CTRL + key for all browsers
			$stringforfirstkey = $langs->trans("KeyboardShortcut");
			if ($conf->browser->name == 'chrome') {
				$stringforfirstkey .= ' ALT +';
			} elseif ($conf->browser->name == 'firefox') {
				$stringforfirstkey .= ' ALT + SHIFT +';
			} else {
				$stringforfirstkey .= ' CTL +';
			}

			$previous_ref = $object->ref_previous ? '<a accesskey="p" title="'.$stringforfirstkey.' p" class="classfortooltip" href="'.$navurl.'?'.$paramid.'='.urlencode($object->ref_previous).$moreparam.'"><i class="fa fa-chevron-left"></i></a>' : '<span class="inactive"><i class="fa fa-chevron-left opacitymedium"></i></span>';
			$next_ref     = $object->ref_next ? '<a accesskey="n" title="'.$stringforfirstkey.' n" class="classfortooltip" href="'.$navurl.'?'.$paramid.'='.urlencode($object->ref_next).$moreparam.'"><i class="fa fa-chevron-right"></i></a>' : '<span class="inactive"><i class="fa fa-chevron-right opacitymedium"></i></span>';
		}

		//print "xx".$previous_ref."x".$next_ref;
		$ret .= '<!-- Start banner content --><div style="vertical-align: middle">';

		// Right part of banner
		if ($morehtmlright) {
			$ret .= '<div class="inline-block floatleft">'.$morehtmlright.'</div>';
		}

		if ($previous_ref || $next_ref || $morehtml) {
			$ret .= '<div class="pagination paginationref"><ul class="right">';
		}
		if ($morehtml) {
			$ret .= '<li class="noborder litext'.(($shownav && $previous_ref && $next_ref) ? ' clearbothonsmartphone' : '').'">'.$morehtml.'</li>';
		}
		if ($shownav && ($previous_ref || $next_ref)) {
			$ret .= '<li class="pagination">'.$previous_ref.'</li>';
			$ret .= '<li class="pagination">'.$next_ref.'</li>';
		}
		if ($previous_ref || $next_ref || $morehtml) {
			$ret .= '</ul></div>';
		}

		$parameters = array();
		$reshook = $hookmanager->executeHooks('moreHtmlStatus', $parameters, $object); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) {
			$morehtmlstatus .= $hookmanager->resPrint;
		} else {
			$morehtmlstatus = $hookmanager->resPrint;
		}
		if ($morehtmlstatus) {
			$ret .= '<div class="statusref">'.$morehtmlstatus.'</div>';
		}

		$parameters = array();
		$reshook = $hookmanager->executeHooks('moreHtmlRef', $parameters, $object); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) {
			$morehtmlref .= $hookmanager->resPrint;
		} elseif ($reshook > 0) {
			$morehtmlref = $hookmanager->resPrint;
		}

		// Left part of banner
		if ($morehtmlleft) {
			if ($conf->browser->layout == 'phone') {
				$ret .= '<!-- morehtmlleft --><div class="floatleft">'.$morehtmlleft.'</div>'; // class="center" to have photo in middle
			} else {
				$ret .= '<!-- morehtmlleft --><div class="inline-block floatleft">'.$morehtmlleft.'</div>';
			}
		}

		//if ($conf->browser->layout == 'phone') $ret.='<div class="clearboth"></div>';
		$ret .= '<div class="inline-block floatleft valignmiddle maxwidth750 refid'.(($shownav && ($previous_ref || $next_ref)) ? ' refidpadding' : '').'">';

		// For thirdparty, contact, user, member, the ref is the id, so we show something else
		if ($object->element == 'societe') {
			$ret .= dol_htmlentities($object->name);

			// List of extra languages
			$arrayoflangcode = array();
			if (!empty($conf->global->PDF_USE_ALSO_LANGUAGE_CODE)) {
				$arrayoflangcode[] = $conf->global->PDF_USE_ALSO_LANGUAGE_CODE;
			}

			if (is_array($arrayoflangcode) && count($arrayoflangcode)) {
				if (!is_object($extralanguages)) {
					include_once DOL_DOCUMENT_ROOT.'/core/class/extralanguages.class.php';
					$extralanguages = new ExtraLanguages($this->db);
				}
				$extralanguages->fetch_name_extralanguages('societe');

				if (!empty($extralanguages->attributes['societe']['name'])) {
					$object->fetchValuesForExtraLanguages();

					$htmltext = '';
					// If there is extra languages
					foreach ($arrayoflangcode as $extralangcode) {
						$htmltext .= picto_from_langcode($extralangcode, 'class="pictoforlang paddingright"');
						if ($object->array_languages['name'][$extralangcode]) {
							$htmltext .= $object->array_languages['name'][$extralangcode];
						} else {
							$htmltext .= '<span class="opacitymedium">'.$langs->trans("SwitchInEditModeToAddTranslation").'</span>';
						}
					}
					$ret .= '<!-- Show translations of name -->'."\n";
					$ret .= $this->textwithpicto('', $htmltext, -1, 'language', 'opacitymedium paddingleft');
				}
			}
		} elseif ($object->element == 'member') {
			$ret .= $object->ref.'<br>';
			$fullname = $object->getFullName($langs);
			if ($object->morphy == 'mor' && $object->societe) {
				$ret .= dol_htmlentities($object->societe).((!empty($fullname) && $object->societe != $fullname) ? ' ('.dol_htmlentities($fullname).$addgendertxt.')' : '');
			} else {
				$ret .= dol_htmlentities($fullname).$addgendertxt.((!empty($object->societe) && $object->societe != $fullname) ? ' ('.dol_htmlentities($object->societe).')' : '');
			}
		} elseif (in_array($object->element, array('contact', 'user', 'usergroup'))) {
			$ret .= dol_htmlentities($object->getFullName($langs)).$addgendertxt;
		} elseif (in_array($object->element, array('action', 'agenda'))) {
			$ret .= $object->ref.'<br>'.$object->label;
		} elseif (in_array($object->element, array('adherent_type'))) {
			$ret .= $object->label;
		} elseif ($object->element == 'ecm_directories') {
			$ret .= '';
		} elseif ($fieldref != 'none') {
			$ret .= dol_htmlentities($object->$fieldref);
		}

		if ($morehtmlref) {
			// don't add a additional space, when "$morehtmlref" starts with a HTML div tag
			if (substr($morehtmlref, 0, 4) != '<div') {
				$ret .= ' ';
			}

			$ret .= $morehtmlref;
		}

		$ret .= '</div>';

		$ret .= '</div><!-- End banner content -->';

		return $ret;
	}

}
