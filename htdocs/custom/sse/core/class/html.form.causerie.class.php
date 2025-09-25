<?php
/* Copyright (c) 2002-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2004       Sebastien Di Cintio     <sdicintio@ressource-toi.org>
 * Copyright (C) 2004       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2017  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2006       Andre Cianfarani        <acianfa@free.fr>
 * Copyright (C) 2006       Marc Barilley/Ocebo     <marc@ocebo.com>
 * Copyright (C) 2007       Franky Van Liedekerke   <franky.van.liedekerker@telenet.be>
 * Copyright (C) 2007       Patrick Raguin          <patrick.raguin@gmail.com>
 * Copyright (C) 2010       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2010-2021  Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2011       Herve Prot              <herve.prot@symeos.com>
 * Copyright (C) 2012-2016  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2012       Cedric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2015  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014-2020  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2018-2022  Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2018-2021  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2018       Nicolas ZABOURI	        <info@inovea-conseil.com>
 * Copyright (C) 2018       Christophe Battarel     <christophe@altairis.fr>
 * Copyright (C) 2018       Josep Lluis Amador      <joseplluis@lliuretic.cat>
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
 *	personalized class by SFA
 * \file       htdocs/custom/sse/core/class/html.form.class.php
 *  \ingroup    core
 *	\brief      File of class with all html predefined components
 */


/**
 *	Class to manage generation of HTML components
 *	Only common components must be here.
 *
 *  TODO Merge all function load_cache_* and loadCache* (except load_cache_vatrates) into one generic function loadCacheTable
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

class FormCauserie extends Form
{


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return the HTML select list of users
	 *
	 *  @param	string			$selected       Id user preselected
	 *  @param  string			$htmlname       Field name in form
	 *  @param  int				$show_empty     0=liste sans valeur nulle, 1=ajoute valeur inconnue
	 *  @param  array			$exclude        Array list of users id to exclude
	 * 	@param	int				$disabled		If select list must be disabled
	 *  @param  array|string	$include        Array list of users id to include. User '' for all users or 'hierarchy' to have only supervised users or 'hierarchyme' to have supervised + me
	 * 	@param	int				$enableonly		Array list of users id to be enabled. All other must be disabled
	 *  @param	string			$force_entity	'0' or Ids of environment to force
	 * 	@return	void
	 *  @deprecated		Use select_dolusers instead
	 *  @see select_dolusers()
	 */
	public function select_extern_user($selected = '', $htmlname = 'userid', $show_empty = 0, $exclude = null, $disabled = 0, $include = '', $enableonly = '', $force_entity = '0')
	{
		// phpcs:enable
		//print $this->select_extern_users($selected, $htmlname, $show_empty, $exclude, $disabled, $include, $enableonly, $force_entity);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return select list of users
	 *
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
	public function select_externusers($selected = '', $htmlname = 'externid', $show_empty = 0, $exclude = null, $disabled = 0, $include = '', $enableonly = '', $force_entity = '0', $maxlength = 0, $showstatus = 0, $morefilter = '', $show_every = 0, $enableonlytext = '', $morecss = '', $noactive = 0, $outputmode = 0, $multiple = false)
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

		//Forge request to select users
		$sql = "SELECT DISTINCT u.rowid, u.lastname as lastname, u.firstname, u.status as status";
		// if (!empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && !$user->entity) {
		// 	$sql .= ", e.label";
		// }
		$sql .= " FROM ".MAIN_DB_PREFIX."sse_causerieuser as u";
		// if (!empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && !$user->entity) {
		// 	// $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."entity as e ON e.rowid = u.entity";
		// 	// if ($force_entity) {
		// 	// 	$sql .= " WHERE u.entity IN (0, ".$this->db->sanitize($force_entity).")";
		// 	// } else {
		// 	// 	$sql .= " WHERE u.entity IS NOT NULL";
		// 	// }
		// } else {
		// 	if (!empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
		// 		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."usergroup_user as ug";
		// 		$sql .= " ON ug.fk_user = u.rowid";
		// 		$sql .= " WHERE ug.entity = 3";
		// 	} else {
		// 		//$sql .= " WHERE ug.entity IN (3)";
		// 	}
		// }
		if (!empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."sse_causerieattendance as ug";
			$sql .= " ON ug.fk_user = u.rowid";
			$sql .= " WHERE ug.entity = 2";
		}
		// if (!empty($user->socid)) {
		// 	$sql .= " AND u.fk_soc = ".((int) $user->socid);
		// }
		if (is_array($exclude) && $excludeUsers) {
			$sql .= " WHERE u.rowid NOT IN (".$this->db->sanitize($excludeUsers).")";
		}
		if ($includeUsers) {
			$sql .= " AND u.rowid IN (".$this->db->sanitize($includeUsers).")";
		}
		if (!empty($conf->global->USER_HIDE_INACTIVE_IN_COMBOBOX) || $noactive) {
			$sql .= " AND u.status <> 0";
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
			$sql .= " ORDER BY u.status DESC, u.firstname ASC, u.lastname ASC";
		} else {
			$sql .= " ORDER BY u.status DESC, u.lastname ASC, u.firstname ASC";
		}

		dol_syslog(get_class($this)."::select_externusers", LOG_DEBUG);

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
					$userstatic->status = $obj->status;
					$userstatic->entity = $obj->entity;

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
 * Convert a html select field into an ajax combobox.
 * Use ajax_combobox() only for small combo list! If not, use instead ajax_autocompleter().
 * TODO: It is used when COMPANY_USE_SEARCH_TO_SELECT and CONTACT_USE_SEARCH_TO_SELECT are set by html.formcompany.class.php. Should use ajax_autocompleter instead like done by html.form.class.php for select_produits.
 *
 * @param	string	$htmlname					Name of html select field ('myid' or '.myclass')
 * @param	array	$events						More events option. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
 * @param  	int		$minLengthToAutocomplete	Minimum length of input string to start autocomplete
 * @param	int		$forcefocus					Force focus on field
 * @param	string	$widthTypeOfAutocomplete	'resolve' or 'off'
 * @param	string	$idforemptyvalue			'-1'
 * @return	string								Return html string to convert a select field into a combo, or '' if feature has been disabled for some reason.
 * @see selectArrayAjax() of html.form.class
 */
function ajax_combobox($htmlname, $events = array(), $minLengthToAutocomplete = 0, $forcefocus = 0, $widthTypeOfAutocomplete = 'resolve', $idforemptyvalue = '-1')
{
	global $conf;

	// select2 can be disabled for smartphones
	if (!empty($conf->browser->layout) && $conf->browser->layout == 'phone' && !empty($conf->global->MAIN_DISALLOW_SELECT2_WITH_SMARTPHONE)) {
		return '';
	}

	if (!empty($conf->global->MAIN_DISABLE_AJAX_COMBOX)) {
		return '';
	}
	if (empty($conf->use_javascript_ajax)) {
		return '';
	}
	if (empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) && !defined('REQUIRE_JQUERY_MULTISELECT')) {
		return '';
	}
	if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
		return '';
	}

	if (empty($minLengthToAutocomplete)) {
		$minLengthToAutocomplete = 0;
	}

	$tmpplugin = 'select2';
	$msg = "\n".'<!-- JS CODE TO ENABLE '.$tmpplugin.' for id = '.$htmlname.' -->
          <script>
        	$(document).ready(function () {
        		$(\''.(preg_match('/^\./', $htmlname) ? $htmlname : '#'.$htmlname).'\').'.$tmpplugin.'({
        		    dir: \'ltr\',
        			width: \''.$widthTypeOfAutocomplete.'\',		/* off or resolve */
					minimumInputLength: '.$minLengthToAutocomplete.',
					language: select2arrayoflanguage,
    				containerCssClass: \':all:\',					/* Line to add class of origin SELECT propagated to the new <span class="select2-selection...> tag */
					selectionCssClass: \':all:\',					/* Line to add class of origin SELECT propagated to the new <span class="select2-selection...> tag */
					templateResult: function (data, container) {	/* Format visible output into combo list */
	 					/* Code to add class of origin OPTION propagated to the new select2 <li> tag */
						if (data.element) { $(container).addClass($(data.element).attr("class")); }
					    //console.log($(data.element).attr("data-html"));
					    if (data.id == '.((int) $idforemptyvalue).' && $(data.element).attr("data-html") == undefined) {
							return \'&nbsp;\';
						}
						if ($(data.element).attr("data-html") != undefined) return htmlEntityDecodeJs($(data.element).attr("data-html"));		// If property html set, we decode html entities and use this
						return data.text;
					},
					templateSelection: function (selection) {		/* Format visible output of selected value */
						if (selection.id == '.((int) $idforemptyvalue).') return \'<span class="placeholder">\'+selection.text+\'</span>\';
						return selection.text;
					},
					escapeMarkup: function(markup) {
						return markup;
					},
					dropdownCssClass: \'ui-dialog\'
				})';
	if ($forcefocus) {
		$msg .= '.select2(\'focus\')';
	}
	$msg .= ';'."\n";

	if (is_array($events) && count($events)) {    // If an array of js events to do were provided.
		$msg .= '
			jQuery("#'.$htmlname.'").change(function () {
				var obj = '.json_encode($events).';
		   		$.each(obj, function(key,values) {
	    			if (values.method.length) {
	    				runJsCodeForEvent'.$htmlname.'(values);
	    			}
				});
			});

			function runJsCodeForEvent'.$htmlname.'(obj) {
				var id = $("#'.$htmlname.'").val();
				var method = obj.method;
				var url = obj.url;
				var htmlname = obj.htmlname;
				var showempty = obj.showempty;
			    console.log("Run runJsCodeForEvent-'.$htmlname.' from ajax_combobox id="+id+" method="+method+" showempty="+showempty+" url="+url+" htmlname="+htmlname);
				$.getJSON(url,
						{
							action: method,
							id: id,
							htmlname: htmlname,
							showempty: showempty
						},
						function(response) {
							$.each(obj.params, function(key,action) {
								if (key.length) {
									var num = response.num;
									if (num > 0) {
										$("#" + key).removeAttr(action);
									} else {
										$("#" + key).attr(action, action);
									}
								}
							});
							$("select#" + htmlname).html(response.value);
							if (response.num) {
								var selecthtml_str = response.value;
								var selecthtml_dom=$.parseHTML(selecthtml_str);
								if (typeof(selecthtml_dom[0][0]) !== \'undefined\') {
                                   	$("#inputautocomplete"+htmlname).val(selecthtml_dom[0][0].innerHTML);
								}
							} else {
								$("#inputautocomplete"+htmlname).val("");
							}
							$("select#" + htmlname).change();	/* Trigger event change */
						}
				);
			}';
	}

	$msg .= '});'."\n";
	$msg .= "</script>\n";

	return $msg;
}

}
