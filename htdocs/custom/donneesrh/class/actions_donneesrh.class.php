<?php
/* Copyright (C) 2023 METZGER Leny <l.metzger@optim-industries.fr>
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
 * \file    donneesrh/class/actions_donneesrh.class.php
 * \ingroup donneesrh
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

require_once DOL_DOCUMENT_ROOT.'/custom/donneesrh/class/userfield.class.php';


/**
 * Class ActionsDonneesRH
 */
class ActionsDonneesRH
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var int		Priority of hook (50 is used if value is not defined)
	 */
	public $priority;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	// public function showOptionals($parameters, &$object, &$action, $hookmanager)
	// {
	// 	global $conf, $user, $langs, $db;

	// 	$error = 0; // Error counter
	// 	$out = '';
		
	// 	if($parameters['currentcontext'] == 'usercard' && $action == 'create') {
	// 		$extrafields = new ExtraFields($db);

	// 		$table_element = 'donneesrh_test';
	// 		$extrafields->fetch_name_optionals_label($table_element);
	// 		$form = new Form($db);
		
	// 		$params = array();
	// 		$mode = 'create';
	// 		$display_type = 'card';

	// 		if (is_array($extrafields->attributes[$table_element]) && is_array($extrafields->attributes[$table_element]['label']) && count($extrafields->attributes[$table_element]['label']) > 0) {
	// 			$out .= "\n";
	// 			$out .= '<!-- commonobject:showOptionals --> ';
	// 			$out .= "\n";

	// 			$nbofextrafieldsshown = 0;
	// 			$extrafields_collapse_num = '';
	// 			$e = 0;	// var to manage the modulo (odd/even)

	// 			foreach ($extrafields->attributes[$table_element]['label'] as $key => $label) {
	// 				// Show only the key field in params
	// 				if (is_array($params) && array_key_exists('onlykey', $params) && $key != $params['onlykey']) {
	// 					continue;
	// 				}

	// 				// Test on 'enabled' ('enabled' is different than 'list' = 'visibility')
	// 				$enabled = 1;
	// 				if ($enabled && isset($extrafields->attributes[$table_element]['enabled'][$key])) {
	// 					$enabled = dol_eval($extrafields->attributes[$table_element]['enabled'][$key], 1, 1, '2');
	// 				}
	// 				if (empty($enabled)) {
	// 					continue;
	// 				}

	// 				$visibility = 1;
	// 				if ($visibility && isset($extrafields->attributes[$table_element]['list'][$key])) {
	// 					$visibility = dol_eval($extrafields->attributes[$table_element]['list'][$key], 1, 1, '2');
	// 				}

	// 				$perms = 1;
	// 				if ($perms && isset($extrafields->attributes[$table_element]['perms'][$key])) {
	// 					$perms = dol_eval($extrafields->attributes[$table_element]['perms'][$key], 1, 1, '2');
	// 				}

	// 				if (($mode == 'create') && abs($visibility) != 1 && abs($visibility) != 3) {
	// 					continue; // <> -1 and <> 1 and <> 3 = not visible on forms, only on list
	// 				} elseif (($mode == 'edit') && abs($visibility) != 1 && abs($visibility) != 3 && abs($visibility) != 4) {
	// 					continue; // <> -1 and <> 1 and <> 3 = not visible on forms, only on list and <> 4 = not visible at the creation
	// 				} elseif ($mode == 'view' && empty($visibility)) {
	// 					continue;
	// 				}
	// 				if (empty($perms)) {
	// 					continue;
	// 				}
	// 				// Load language if required
	// 				if (!empty($extrafields->attributes[$table_element]['langfile'][$key])) {
	// 					$langs->load($extrafields->attributes[$table_element]['langfile'][$key]);
	// 				}

	// 				$colspan = 0;
	// 				if (is_array($params) && count($params) > 0 && $display_type=='card') {
	// 					if (array_key_exists('cols', $params)) {
	// 						$colspan = $params['cols'];
	// 					} elseif (array_key_exists('colspan', $params)) {	// For backward compatibility. Use cols instead now.
	// 						$reg = array();
	// 						if (preg_match('/colspan="(\d+)"/', $params['colspan'], $reg)) {
	// 							$colspan = $reg[1];
	// 						} else {
	// 							$colspan = $params['colspan'];
	// 						}
	// 					}
	// 				}
	// 				$colspan = intval($colspan);

	// 				switch ($mode) {
	// 					case "view":
	// 						$value = ((!empty($this->array_options) && array_key_exists("options_".$key.$keysuffix, $this->array_options)) ? $this->array_options["options_".$key.$keysuffix] : null); // Value may be cleaned or formated later
	// 						break;
							
	// 					case "create":
	// 					case "edit":
	// 						// We get the value of property found with GETPOST so it takes into account:
	// 						// default values overwrite, restore back to list link, ... (but not 'default value in database' of field)
	// 						$check = 'alphanohtml';
	// 						if (in_array($extrafields->attributes[$table_element]['type'][$key], array('html', 'text'))) {
	// 							$check = 'restricthtml';
	// 						}
	// 						$getposttemp = GETPOST($keyprefix.'options_'.$key.$keysuffix, $check, 3); // GETPOST can get value from GET, POST or setup of default values overwrite.
	// 						// GETPOST("options_" . $key) can be 'abc' or array(0=>'abc')
	// 						if (is_array($getposttemp) || $getposttemp != '' || GETPOSTISSET($keyprefix.'options_'.$key.$keysuffix)) {
	// 							if (is_array($getposttemp)) {
	// 								// $getposttemp is an array but following code expects a comma separated string
	// 								$value = implode(",", $getposttemp);
	// 							} else {
	// 								$value = $getposttemp;
	// 							}
	// 						} else {
	// 							$value = (!empty($this->array_options["options_".$key]) ? $this->array_options["options_".$key] : ''); // No GET, no POST, no default value, so we take value of object.
	// 						}
	// 						//var_dump($keyprefix.' - '.$key.' - '.$keysuffix.' - '.$keyprefix.'options_'.$key.$keysuffix.' - '.$this->array_options["options_".$key.$keysuffix].' - '.$getposttemp.' - '.$value);
	// 						break;
	// 				}

	// 				$nbofextrafieldsshown++;

	// 				// Output value of the current field
	// 				if ($extrafields->attributes[$table_element]['type'][$key] == 'separate') {
	// 					$extrafields_collapse_num = '';
	// 					$extrafield_param = $extrafields->attributes[$table_element]['param'][$key];
	// 					if (!empty($extrafield_param) && is_array($extrafield_param)) {
	// 						$extrafield_param_list = array_keys($extrafield_param['options']);

	// 						if (count($extrafield_param_list) > 0) {
	// 							$extrafield_collapse_display_value = intval($extrafield_param_list[0]);

	// 							if ($extrafield_collapse_display_value == 1 || $extrafield_collapse_display_value == 2) {
	// 								$extrafields_collapse_num = $extrafields->attributes[$table_element]['pos'][$key];
	// 							}
	// 						}
	// 					}

	// 					// if colspan=0 or 1, the second column is not extended, so the separator must be on 2 columns
	// 					$out .= $extrafields->showSeparator($key, $this, ($colspan ? $colspan + 1 : 2), $display_type, $mode);
	// 				} else {
	// 					$class = (!empty($extrafields->attributes[$table_element]['hidden'][$key]) ? 'hideobject ' : '');
	// 					$csstyle = '';
	// 					if (is_array($params) && count($params) > 0) {
	// 						if (array_key_exists('class', $params)) {
	// 							$class .= $params['class'].' ';
	// 						}
	// 						if (array_key_exists('style', $params)) {
	// 							$csstyle = $params['style'];
	// 						}
	// 					}

	// 					// add html5 elements
	// 					$domData  = ' data-element="extrafield"';
	// 					$domData .= ' data-targetelement="'.$this->element.'"';
	// 					$domData .= ' data-targetid="'.$this->id.'"';

	// 					$html_id = (empty($this->id) ? '' : 'extrarow-'.$this->element.'_'.$key.'_'.$this->id);
	// 					if ($display_type=='card') {
	// 						if (!empty($conf->global->MAIN_EXTRAFIELDS_USE_TWO_COLUMS) && ($e % 2) == 0) {
	// 							$colspan = 0;
	// 						}

	// 						if ($action == 'selectlines') {
	// 							$colspan++;
	// 						}
	// 					}

	// 					// Convert date into timestamp format (value in memory must be a timestamp)
	// 					if (in_array($extrafields->attributes[$table_element]['type'][$key], array('date'))) {
	// 						$datenotinstring = null;
	// 						if (array_key_exists('options_'.$key, $this->array_options)) {
	// 							$datenotinstring = $this->array_options['options_'.$key];
	// 							if (!is_numeric($this->array_options['options_'.$key])) {	// For backward compatibility
	// 								$datenotinstring = $this->db->jdate($datenotinstring);
	// 							}
	// 						}
	// 						$datekey = $keyprefix.'options_'.$key.$keysuffix;
	// 						$value = (GETPOSTISSET($datekey)) ? dol_mktime(12, 0, 0, GETPOST($datekey.'month', 'int', 3), GETPOST($datekey.'day', 'int', 3), GETPOST($datekey.'year', 'int', 3)) : $datenotinstring;
	// 					}
	// 					if (in_array($extrafields->attributes[$table_element]['type'][$key], array('datetime'))) {
	// 						$datenotinstring = null;
	// 						if (array_key_exists('options_'.$key, $this->array_options)) {
	// 							$datenotinstring = $this->array_options['options_'.$key];
	// 							if (!is_numeric($this->array_options['options_'.$key])) {	// For backward compatibility
	// 								$datenotinstring = $this->db->jdate($datenotinstring);
	// 							}
	// 						}
	// 						$timekey = $keyprefix.'options_'.$key.$keysuffix;
	// 						$value = (GETPOSTISSET($timekey)) ? dol_mktime(GETPOST($timekey.'hour', 'int', 3), GETPOST($timekey.'min', 'int', 3), GETPOST($timekey.'sec', 'int', 3), GETPOST($timekey.'month', 'int', 3), GETPOST($timekey.'day', 'int', 3), GETPOST($timekey.'year', 'int', 3), 'tzuserrel') : $datenotinstring;
	// 					}
	// 					// Convert float submited string into real php numeric (value in memory must be a php numeric)
	// 					if (in_array($extrafields->attributes[$table_element]['type'][$key], array('price', 'double'))) {
	// 						if (GETPOSTISSET($keyprefix.'options_'.$key.$keysuffix) || $value) {
	// 							$value = price2num($value);
	// 						} elseif (isset($this->array_options['options_'.$key])) {
	// 							$value = $this->array_options['options_'.$key];
	// 						}
	// 					}

	// 					// HTML, text, select, integer and varchar: take into account default value in database if in create mode
	// 					if (in_array($extrafields->attributes[$table_element]['type'][$key], array('html', 'text', 'varchar', 'select', 'int', 'boolean'))) {
	// 						if ($action == 'create') {
	// 							$value = (GETPOSTISSET($keyprefix.'options_'.$key.$keysuffix) || $value) ? $value : $extrafields->attributes[$table_element]['default'][$key];
	// 						}
	// 					}

	// 					$labeltoshow = $langs->trans($label);
	// 					$helptoshow = $langs->trans($extrafields->attributes[$table_element]['help'][$key]);

	// 					if ($display_type == 'card') {
	// 						$out .= '<tr '.($html_id ? 'id="'.$html_id.'" ' : '').$csstyle.' class="field_options_'.$key.' '.$class.$this->element.'_extras_'.$key.' trextrafields_collapse'.$extrafields_collapse_num.(!empty($this->id)?'_'.$this->id:'').'" '.$domData.' >';
	// 						if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER) && ($action == 'view' || $action == 'valid' || $action == 'editline' || $action == 'confirm_valid' || $action == 'confirm_cancel')) {
	// 							$out .= '<td></td>';
	// 						}
	// 						$out .= '<td class="'.(empty($params['tdclass']) ? 'titlefieldcreate' : $params['tdclass']).' wordbreak';
	// 					} elseif ($display_type == 'line') {
	// 						$out .= '<div '.($html_id ? 'id="'.$html_id.'" ' : '').$csstyle.' class="fieldline_options_'.$key.' '.$class.$this->element.'_extras_'.$key.' trextrafields_collapse'.$extrafields_collapse_num.(!empty($this->id)?'_'.$this->id:'').'" '.$domData.' >';
	// 						$out .= '<div style="display: inline-block; padding-right:4px" class="wordbreak';
	// 					}
	// 					//$out .= "titlefield";
	// 					//if (GETPOST('action', 'restricthtml') == 'create') $out.='create';
	// 					// BUG #11554 : For public page, use red dot for required fields, instead of bold label
	// 					$tpl_context = isset($params["tpl_context"]) ? $params["tpl_context"] : "none";
	// 					if ($tpl_context != "public") {	// Public page : red dot instead of fieldrequired characters
	// 						if ($mode != 'view' && !empty($extrafields->attributes[$table_element]['required'][$key])) {
	// 							$out .= ' fieldrequired';
	// 						}
	// 					}
	// 					$out .= '">';
	// 					if ($tpl_context == "public") {	// Public page : red dot instead of fieldrequired characters
	// 						if (!empty($extrafields->attributes[$table_element]['help'][$key])) {
	// 							$out .= $form->textwithpicto($labeltoshow, $helptoshow);
	// 						} else {
	// 							$out .= $labeltoshow;
	// 						}
	// 						if ($mode != 'view' && !empty($extrafields->attributes[$table_element]['required'][$key])) {
	// 							$out .= '&nbsp;<span style="color: red">*</span>';
	// 						}
	// 					} else {
	// 						if (!empty($extrafields->attributes[$table_element]['help'][$key])) {
	// 							$out .= $form->textwithpicto($labeltoshow, $helptoshow);
	// 						} else {
	// 							$out .= $labeltoshow;
	// 						}
	// 					}

	// 					$out .= ($display_type == 'card' ? '</td>' : '</div>');

	// 					$html_id = !empty($this->id) ? $this->element.'_extras_'.$key.'_'.$this->id : '';
	// 					if ($display_type == 'card') {
	// 						// a first td column was already output (and may be another on before if MAIN_VIEW_LINE_NUMBER set), so this td is the next one
	// 						$out .= '<td '.($html_id ? 'id="'.$html_id.'" ' : '').' class="valuefieldcreate '.$this->element.'_extras_'.$key.'" '.($colspan ? ' colspan="'.$colspan.'"' : '').'>';
	// 					} elseif ($display_type == 'line') {
	// 						$out .= '<div '.($html_id ? 'id="'.$html_id.'" ' : '').' style="display: inline-block" class="valuefieldcreate '.$this->element.'_extras_'.$key.' extra_inline_'.$extrafields->attributes[$table_element]['type'][$key].'">';
	// 					}
						
	// 					switch ($mode) {
	// 						case "view":
	// 							$out .= $extrafields->showOutputField($key, $value, '', $table_element);
	// 							break;
	// 						case "create":
	// 							$out .= $extrafields->showInputField($key, $value, '', $keysuffix, '', 0, $this->id, $table_element);
								
	// 							break;
	// 						case "edit":
	// 							$out .= $extrafields->showInputField($key, $value, '', $keysuffix, '', 0, $this->id, $table_element);
	// 							break;
	// 					}

	// 					$out .= ($display_type=='card' ? '</td>' : '</div>');

	// 					if (!empty($conf->global->MAIN_EXTRAFIELDS_USE_TWO_COLUMS) && (($e % 2) == 1)) {
	// 						$out .= ($display_type=='card' ? '</tr>' : '</div>');
	// 					} else {
	// 						$out .= ($display_type=='card' ? '</tr>' : '</div>');
	// 					}

	// 					$e++;
	// 				}
	// 			}
	// 			$out .= "\n";

	// 			// Add code to manage list depending on others
	// 			if (!empty($conf->use_javascript_ajax)) {
	// 				//$out .= $this->getJSListDependancies();
	// 			}

	// 			$out .= '<!-- commonobject:showOptionals end --> '."\n";

	// 			if (empty($nbofextrafieldsshown)) {
	// 				$out = '';
	// 			}
	// 		}
	// 	}

	// 	if (!$error) {
	// 		$this->resprints = $out;
	// 		return 0; // or return 1 to replace standard code
	// 	} else {
	// 		$this->errors[] = 'Error message';
	// 		return -1;
	// 	}
	// }

}
