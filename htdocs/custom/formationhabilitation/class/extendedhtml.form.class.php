<?php
/* Copyright (C) 2024		Metzger Lény			<l.metzger@optim-industries.fr>
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
 * \file       custom/formationhabilitation/class/extendedhtml.form.class.php
 * \ingroup    formationhabilitation
 * \brief      File of class with all html predefined components
 */

class ExtendedForm extends Form
{
    /**
	 *     Show a confirmation HTML form or AJAX popup.
	 *     Easiest way to use this is with useajax=1.
	 *     If you use useajax='xxx', you must also add jquery code to trigger opening of box (with correct parameters)
	 *     just after calling this method. For example:
	 *       print '<script nonce="'.getNonce().'" type="text/javascript">'."\n";
	 *       print 'jQuery(document).ready(function() {'."\n";
	 *       print 'jQuery(".xxxlink").click(function(e) { jQuery("#aparamid").val(jQuery(this).attr("rel")); jQuery("#dialog-confirm-xxx").dialog("open"); return false; });'."\n";
	 *       print '});'."\n";
	 *       print '</script>'."\n";
	 *
	 * @param string 		$page 				Url of page to call if confirmation is OK. Can contains parameters (param 'action' and 'confirm' will be reformated)
	 * @param string 		$title 				Title
	 * @param string 		$question 			Question
	 * @param string 		$action 			Action
	 * @param array|string 	$formquestion 		An array with complementary inputs to add into forms: array(array('label'=> ,'type'=> , 'size'=>, 'morecss'=>, 'moreattr'=>'autofocus' or 'style=...'))
	 *                                   		'type' can be 'text', 'password', 'checkbox', 'radio', 'date', 'datetime', 'select', 'multiselect', 'morecss',
	 *                                   		'other', 'onecolumn' or 'hidden'...
	 * @param int|string 	$selectedchoice 	'' or 'no', or 'yes' or '1', 1, '0' or 0
	 * @param int|string 	$useajax 			0=No, 1=Yes use Ajax to show the popup, 2=Yes and also submit page with &confirm=no if choice is No, 'xxx'=Yes and preoutput confirm box with div id=dialog-confirm-xxx
	 * @param int|string 	$height 			Force height of box (0 = auto)
	 * @param int 			$width 				Force width of box ('999' or '90%'). Ignored and forced to 90% on smartphones.
	 * @param int 			$disableformtag 	1=Disable form tag. Can be used if we are already inside a <form> section.
	 * @param string 		$labelbuttonyes 	Label for Yes
	 * @param string 		$labelbuttonno 		Label for No
	 * @return string                        	HTML ajax code if a confirm ajax popup is required, Pure HTML code if it's an html form
	 */
	public function formconfirm($page, $title, $question, $action, $formquestion = '', $selectedchoice = '', $useajax = 0, $height = 0, $width = 500, $disableformtag = 0, $labelbuttonyes = 'Yes', $labelbuttonno = 'No', $addclass = '')
	{
		global $langs, $conf;

		$more = '<!-- formconfirm - before call, page=' . dol_escape_htmltag($page) . ' -->';
		$formconfirm = '';
		$inputok = array();
		$inputko = array();

		// Clean parameters
		$newselectedchoice = empty($selectedchoice) ? "no" : $selectedchoice;
		if ($conf->browser->layout == 'phone') {
			$width = '95%';
		}

		// Set height automatically if not defined
		if (empty($height)) {
			$height = 220;
			if (is_array($formquestion) && count($formquestion) > 2) {
				$height += ((count($formquestion) - 2) * 24);
			}
		}

		if (is_array($formquestion) && !empty($formquestion)) {
			// First add hidden fields and value
			foreach ($formquestion as $key => $input) {
				if (is_array($input) && !empty($input)) {
					if ($input['type'] == 'hidden') {
						$moreattr = (!empty($input['moreattr']) ? ' ' . $input['moreattr'] : '');
						$morecss = (!empty($input['morecss']) ? ' ' . $input['morecss'] : '');

						$more .= '<input type="hidden" id="' . dol_escape_htmltag($input['name']) . '" name="' . dol_escape_htmltag($input['name']) . '" value="' . dol_escape_htmltag($input['value']) . '" class="' . $morecss . '"' . $moreattr . '>' . "\n";
					}
				}
			}

			// Now add questions
			$moreonecolumn = '';
			$more .= '<div class="tagtable paddingtopbottomonly centpercent noborderspacing">' . "\n";
			foreach ($formquestion as $key => $input) {
				if (is_array($input) && !empty($input)) {
					$size = (!empty($input['size']) ? ' size="' . $input['size'] . '"' : '');    // deprecated. Use morecss instead.
					$moreattr = (!empty($input['moreattr']) ? ' ' . $input['moreattr'] : '');
					$morecss = (!empty($input['morecss']) ? ' ' . $input['morecss'] : '');

					if ($input['type'] == 'text') {
						$more .= '<div class="tagtr"><div class="tagtd' . (empty($input['tdclass']) ? '' : (' ' . $input['tdclass'])) . '">' . $input['label'] . '</div><div class="tagtd"><input type="text" class="flat' . $morecss . '" id="' . dol_escape_htmltag($input['name']) . '" name="' . dol_escape_htmltag($input['name']) . '"' . $size . ' value="' . (empty($input['value']) ? '' : $input['value']) . '"' . $moreattr . ' /></div></div>' . "\n";
					} elseif ($input['type'] == 'password') {
						$more .= '<div class="tagtr"><div class="tagtd' . (empty($input['tdclass']) ? '' : (' ' . $input['tdclass'])) . '">' . $input['label'] . '</div><div class="tagtd"><input type="password" class="flat' . $morecss . '" id="' . dol_escape_htmltag($input['name']) . '" name="' . dol_escape_htmltag($input['name']) . '"' . $size . ' value="' . (empty($input['value']) ? '' : $input['value']) . '"' . $moreattr . ' /></div></div>' . "\n";
					} elseif ($input['type'] == 'textarea') {
						/*$more .= '<div class="tagtr"><div class="tagtd'.(empty($input['tdclass']) ? '' : (' '.$input['tdclass'])).'">'.$input['label'].'</div><div class="tagtd">';
						$more .= '<textarea name="'.$input['name'].'" class="'.$morecss.'"'.$moreattr.'>';
						$more .= $input['value'];
						$more .= '</textarea>';
						$more .= '</div></div>'."\n";*/
						$moreonecolumn .= '<div class="margintoponly">';
						$moreonecolumn .= $input['label'] . '<br>';
						$moreonecolumn .= '<textarea name="' . dol_escape_htmltag($input['name']) . '" id="' . dol_escape_htmltag($input['name']) . '" class="' . $morecss . '"' . $moreattr . '>';
						$moreonecolumn .= $input['value'];
						$moreonecolumn .= '</textarea>';
						$moreonecolumn .= '</div>';
					} elseif (in_array($input['type'], ['select', 'multiselect'])) {
						if (empty($morecss)) {
							$morecss = 'minwidth100';
						}

						$show_empty = isset($input['select_show_empty']) ? $input['select_show_empty'] : 1;
						$key_in_label = isset($input['select_key_in_label']) ? $input['select_key_in_label'] : 0;
						$value_as_key = isset($input['select_value_as_key']) ? $input['select_value_as_key'] : 0;
						$translate = isset($input['select_translate']) ? $input['select_translate'] : 0;
						$maxlen = isset($input['select_maxlen']) ? $input['select_maxlen'] : 0;
						$disabled = isset($input['select_disabled']) ? $input['select_disabled'] : 0;
						$sort = isset($input['select_sort']) ? $input['select_sort'] : '';

						$more .= '<div class="tagtr"><div class="tagtd' . (empty($input['tdclass']) ? '' : (' ' . $input['tdclass'])) . '">';
						if (!empty($input['label'])) {
							$more .= $input['label'] . '</div><div class="tagtd left">';
						}
						if ($input['type'] == 'select') {
							$more .= $this->selectarray($input['name'], $input['values'], isset($input['default']) ? $input['default'] : '-1', $show_empty, $key_in_label, $value_as_key, $moreattr, $translate, $maxlen, $disabled, $sort, $morecss);
						} else {
							$more .= $this->multiselectarray($input['name'], $input['values'], is_array($input['default']) ? $input['default'] : [$input['default']], $key_in_label, $value_as_key, $morecss, $translate, $maxlen, $moreattr);
						}
						$more .= '</div></div>' . "\n";
					} elseif ($input['type'] == 'checkbox') {
						$more .= '<div class="tagtr">';
						$more .= '<div class="tagtd' . (empty($input['tdclass']) ? '' : (' ' . $input['tdclass'])) . '"><label for="' . dol_escape_htmltag($input['name']) . '">' . $input['label'] . '</label></div><div class="tagtd">';
						$more .= '<input type="checkbox" class="flat' . ($morecss ? ' ' . $morecss : '') . '" id="' . dol_escape_htmltag($input['name']) . '" name="' . dol_escape_htmltag($input['name']) . '"' . $moreattr;
						if (!is_bool($input['value']) && $input['value'] != 'false' && $input['value'] != '0' && $input['value'] != '') {
							$more .= ' checked';
						}
						if (is_bool($input['value']) && $input['value']) {
							$more .= ' checked';
						}
						if (isset($input['disabled'])) {
							$more .= ' disabled';
						}
						$more .= ' /></div>';
						$more .= '</div>' . "\n";
					} elseif ($input['type'] == 'radio') {
						$i = 0;
						foreach ($input['values'] as $selkey => $selval) {
							$more .= '<div class="tagtr">';
							if (isset($input['label'])) {
								if ($i == 0) {
									$more .= '<div class="tagtd' . (empty($input['tdclass']) ? ' tdtop' : (' tdtop ' . $input['tdclass'])) . '">' . $input['label'] . '</div>';
								} else {
									$more .= '<div clas="tagtd' . (empty($input['tdclass']) ? '' : (' "' . $input['tdclass'])) . '">&nbsp;</div>';
								}
							}
							$more .= '<div class="tagtd' . ($i == 0 ? ' tdtop' : '') . '"><input type="radio" class="flat' . $morecss . '" id="' . dol_escape_htmltag($input['name'] . $selkey) . '" name="' . dol_escape_htmltag($input['name']) . '" value="' . $selkey . '"' . $moreattr;
							if (!empty($input['disabled'])) {
								$more .= ' disabled';
							}
							if (isset($input['default']) && $input['default'] === $selkey) {
								$more .= ' checked="checked"';
							}
							$more .= ' /> ';
							$more .= '<label for="' . dol_escape_htmltag($input['name'] . $selkey) . '" class="valignmiddle">' . $selval . '</label>';
							$more .= '</div></div>' . "\n";
							$i++;
						}
					} elseif ($input['type'] == 'date' || $input['type'] == 'datetime') {
						$more .= '<div class="tagtr"><div class="tagtd' . (empty($input['tdclass']) ? '' : (' ' . $input['tdclass'])) . '">' . $input['label'] . '</div>';
						$more .= '<div class="tagtd">';
						$addnowlink = (empty($input['datenow']) ? 0 : 1);
						$h = $m = 0;
						if ($input['type'] == 'datetime') {
							$h = isset($input['hours']) ? $input['hours'] : 1;
							$m = isset($input['minutes']) ? $input['minutes'] : 1;
						}
						$more .= $this->selectDate($input['value'], $input['name'], $h, $m, 0, '', 1, $addnowlink);
						$more .= '</div></div>'."\n";
						$formquestion[] = array('name'=>$input['name'].'day');
						$formquestion[] = array('name'=>$input['name'].'month');
						$formquestion[] = array('name'=>$input['name'].'year');
						$formquestion[] = array('name'=>$input['name'].'hour');
						$formquestion[] = array('name'=>$input['name'].'min');
					} elseif ($input['type'] == 'other') { // can be 1 column or 2 depending if label is set or not
						$more .= '<div class="tagtr"><div class="tagtd'.(empty($input['tdclass']) ? '' : (' '.$input['tdclass'])).'">';
						if (!empty($input['label'])) {
							$more .= $input['label'] . '</div><div class="tagtd">';
						}
						$more .= $input['value'];
						$more .= '</div></div>' . "\n";
					} elseif ($input['type'] == 'onecolumn') {
						$moreonecolumn .= '<div class="margintoponly">';
						$moreonecolumn .= $input['value'];
						$moreonecolumn .= '</div>' . "\n";
					} elseif ($input['type'] == 'hidden') {
						// Do nothing more, already added by a previous loop
					} elseif ($input['type'] == 'separator') {
						$more .= '<br>';
					} elseif ($input['type'] == 'link') {
						$more .= '<div '.($input['hidden'] ? 'style="display: none;" ' : '').'class="tagtr"><div class="tagtd'.(empty($input['tdclass']) ? '' : (' '.$input['tdclass'])).'">';
						if (!empty($input['label'])) {
							$more .= $input['label'] . '</div><div class="tagtd">';
						}

						// $param_list='ObjectName:classPath[:AddCreateButtonOrNot[:Filter[:Sortfield]]]'
						// Filter can contains some ':' inside.
						$param_list = $input['options'];
						$param_list_array = explode(':', $param_list, 4);
			
						$showempty = (($input['showempty'] ) ? 1 : 0);
			
						$objectfield = $input['element'].($input['module'] ? '@'.$input['module'] : '').':'.$input['code'];
						$more .= $this->selectForForms($param_list_array, $input['name'], 0, $showempty, '', '', $morecss, '', 0, 0, '', $objectfield);

						$more .= '</div></div>' . "\n";
					} else {
						$more .= 'Error type ' . $input['type'] . ' for the confirm box is not a supported type';
					}
				}
			}
			$more .= '</div>' . "\n";
			$more .= $moreonecolumn;
		}

		// JQUERY method dialog is broken with smartphone, we use standard HTML.
		// Note: When using dol_use_jmobile or no js, you must also check code for button use a GET url with action=xxx and check that you also output the confirm code when action=xxx
		// See page product/card.php for example
		if (!empty($conf->dol_use_jmobile)) {
			$useajax = 0;
		}
		if (empty($conf->use_javascript_ajax)) {
			$useajax = 0;
		}

		if ($useajax) {
			$autoOpen = true;
			$dialogconfirm = 'dialog-confirm';
			$button = '';
			if (!is_numeric($useajax)) {
				$button = $useajax;
				$useajax = 1;
				$autoOpen = false;
				$dialogconfirm .= '-' . $button;
			}
			$pageyes = $page . (preg_match('/\?/', $page) ? '&' : '?') . 'action=' . urlencode($action) . '&confirm=yes';
			$pageno = ($useajax == 2 ? $page . (preg_match('/\?/', $page) ? '&' : '?') . 'action=' . urlencode($action) . '&confirm=no' : '');

			// Add input fields into list of fields to read during submit (inputok and inputko)
			if (is_array($formquestion)) {
				foreach ($formquestion as $key => $input) {
					//print "xx ".$key." rr ".is_array($input)."<br>\n";
					// Add name of fields to propagate with the GET when submitting the form with button OK.
					if (is_array($input) && isset($input['name'])) {
						if (strpos($input['name'], ',') > 0) {
							$inputok = array_merge($inputok, explode(',', $input['name']));
						} else {
							array_push($inputok, $input['name']);
						}
					}
					// Add name of fields to propagate with the GET when submitting the form with button KO.
					if (isset($input['inputko']) && $input['inputko'] == 1) {
						array_push($inputko, $input['name']);
					}
				}
			}

			// Show JQuery confirm box.
			$formconfirm .= '<div id="' . $dialogconfirm . '" title="' . dol_escape_htmltag($title) . '" class="'.$addclass.'" style="display: none;">';
			if (is_array($formquestion) && !empty($formquestion['text'])) {
				$formconfirm .= '<div class="confirmtext">' . $formquestion['text'] . '</div>' . "\n";
			}
			if (!empty($more)) {
				$formconfirm .= '<div class="confirmquestions">' . $more . '</div>' . "\n";
			}
			$formconfirm .= ($question ? '<div class="confirmmessage">' . img_help('', '') . ' ' . $question . '</div>' : '');
			$formconfirm .= '</div>' . "\n";

			$formconfirm .= "\n<!-- begin code of popup for formconfirm page=" . $page . " -->\n";
			$formconfirm .= '<script nonce="' . getNonce() . '" type="text/javascript">' . "\n";
			$formconfirm .= "/* Code for the jQuery('#dialogforpopup').dialog() */\n";
			$formconfirm .= 'jQuery(document).ready(function() {
            $(function() {
            	$( "#' . $dialogconfirm . '" ).dialog(
            	{
                    autoOpen: ' . ($autoOpen ? "true" : "false") . ',';
			if ($newselectedchoice == 'no') {
				$formconfirm .= '
						open: function() {
            				$(this).parent().find("button.ui-button:eq(2)").focus();
						},';
			}

			$jsforcursor = '';
			if ($useajax == 1) {
				$jsforcursor = '// The call to urljump can be slow, so we set the wait cursor' . "\n";
				$jsforcursor .= 'jQuery("html,body,#id-container").addClass("cursorwait");' . "\n";
			}

			$postconfirmas = 'GET';

			$formconfirm .= '
                    resizable: false,
                    height: "' . $height . '",
                    width: "' . $width . '",
                    modal: true,
                    closeOnEscape: false,
                    buttons: {
                        "' . dol_escape_js($langs->transnoentities($labelbuttonyes)) . '": function() {
							var options = "token=' . urlencode(newToken()) . '";
                        	var inputok = ' . json_encode($inputok) . ';	/* List of fields into form */
							var page = "' . dol_escape_js(!empty($page) ? $page : '') . '";
                         	var pageyes = "' . dol_escape_js(!empty($pageyes) ? $pageyes : '') . '";

                         	if (inputok.length > 0) {
                         		$.each(inputok, function(i, inputname) {
                         			var more = "";
									var inputvalue;
                         			if ($("input[name=\'" + inputname + "\']").attr("type") == "radio") {
										inputvalue = $("input[name=\'" + inputname + "\']:checked").val();
									} else {
                         		    	if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
                         				inputvalue = $("#" + inputname + more).val();
									}
                         			if (typeof inputvalue == "undefined") { inputvalue=""; }
									console.log("formconfirm check inputname="+inputname+" inputvalue="+inputvalue);
                         			options += "&" + inputname + "=" + encodeURIComponent(inputvalue);
                         		});
                         	}
                         	var urljump = pageyes + (pageyes.indexOf("?") < 0 ? "?" : "&") + options;
            				if (pageyes.length > 0) {';
			if ($postconfirmas == 'GET') {
				$formconfirm .= 'location.href = urljump;';
			} else {
				$formconfirm .= $jsforcursor;
				$formconfirm .= 'var post = $.post(
									pageyes,
									options,
									function(data) { $("body").html(data); jQuery("html,body,#id-container").removeClass("cursorwait"); }
								);';
			}
			$formconfirm .= '
								console.log("after post ok");
							}
	                        $(this).dialog("close");
                        },
                        "' . dol_escape_js($langs->transnoentities($labelbuttonno)) . '": function() {
                        	var options = "token=' . urlencode(newToken()) . '";
                         	var inputko = ' . json_encode($inputko) . ';	/* List of fields into form */
							var page = "' . dol_escape_js(!empty($page) ? $page : '') . '";
                         	var pageno="' . dol_escape_js(!empty($pageno) ? $pageno : '') . '";
                         	if (inputko.length > 0) {
                         		$.each(inputko, function(i, inputname) {
                         			var more = "";
                         			if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
                         			var inputvalue = $("#" + inputname + more).val();
                         			if (typeof inputvalue == "undefined") { inputvalue=""; }
                         			options += "&" + inputname + "=" + encodeURIComponent(inputvalue);
                         		});
                         	}
                         	var urljump=pageno + (pageno.indexOf("?") < 0 ? "?" : "&") + options;
                         	//alert(urljump);
            				if (pageno.length > 0) {';
			if ($postconfirmas == 'GET') {
				$formconfirm .= 'location.href = urljump;';
			} else {
				$formconfirm .= $jsforcursor;
				$formconfirm .= 'var post = $.post(
									pageno,
									options,
									function(data) { $("body").html(data); jQuery("html,body,#id-container").removeClass("cursorwait"); }
								);';
			}
			$formconfirm .= '
								console.log("after post ko");
							}
                            $(this).dialog("close");
                        }
                    }
                }
                );

            	var button = "' . $button . '";
            	if (button.length > 0) {
                	$( "#" + button ).click(function() {
                		$("#' . $dialogconfirm . '").dialog("open");
        			});
                }
            });
            });
            </script>';
			$formconfirm .= "<!-- end ajax formconfirm -->\n";
		} else {
			$formconfirm .= "\n<!-- begin formconfirm page=" . dol_escape_htmltag($page) . " -->\n";

			if (empty($disableformtag)) {
				$formconfirm .= '<form method="POST" action="' . $page . '" class="notoptoleftroright">' . "\n";
			}

			$formconfirm .= '<input type="hidden" name="action" value="' . $action . '">' . "\n";
			$formconfirm .= '<input type="hidden" name="token" value="' . newToken() . '">' . "\n";

			$formconfirm .= '<table class="valid centpercent">' . "\n";

			// Line title
			$formconfirm .= '<tr class="validtitre"><td class="validtitre" colspan="2">';
			$formconfirm .= img_picto('', 'pictoconfirm') . ' ' . $title;
			$formconfirm .= '</td></tr>' . "\n";

			// Line text
			if (is_array($formquestion) && !empty($formquestion['text'])) {
				$formconfirm .= '<tr class="valid"><td class="valid" colspan="2">' . $formquestion['text'] . '</td></tr>' . "\n";
			}

			// Line form fields
			if ($more) {
				$formconfirm .= '<tr class="valid"><td class="valid" colspan="2">' . "\n";
				$formconfirm .= $more;
				$formconfirm .= '</td></tr>' . "\n";
			}

			// Line with question
			$formconfirm .= '<tr class="valid">';
			$formconfirm .= '<td class="valid">' . $question . '</td>';
			$formconfirm .= '<td class="valid center">';
			$formconfirm .= $this->selectyesno("confirm", $newselectedchoice, 0, false, 0, 0, 'marginleftonly marginrightonly', $labelbuttonyes, $labelbuttonno);
			$formconfirm .= '<input class="button valignmiddle confirmvalidatebutton small" type="submit" value="' . $langs->trans("Validate") . '">';
			$formconfirm .= '</td>';
			$formconfirm .= '</tr>' . "\n";

			$formconfirm .= '</table>' . "\n";

			if (empty($disableformtag)) {
				$formconfirm .= "</form>\n";
			}
			$formconfirm .= '<br>';

			if (!empty($conf->use_javascript_ajax)) {
				$formconfirm .= '<!-- code to disable button to avoid double clic -->';
				$formconfirm .= '<script nonce="' . getNonce() . '" type="text/javascript">' . "\n";
				$formconfirm .= '
				$(document).ready(function () {
					$(".confirmvalidatebutton").on("click", function() {
						console.log("We click on button confirmvalidatebutton");
						$(this).attr("disabled", "disabled");
						setTimeout(\'$(".confirmvalidatebutton").removeAttr("disabled")\', 3000);
						//console.log($(this).closest("form"));
						$(this).closest("form").submit();
					});
				});
				';
				$formconfirm .= '</script>' . "\n";
			}

			$formconfirm .= "<!-- end formconfirm -->\n";
		}

		return $formconfirm;
	}
}