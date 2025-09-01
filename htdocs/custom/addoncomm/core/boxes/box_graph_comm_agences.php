<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * addon 2023 Soufiane Fadel <s.fadel@optim-industries.fr>
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
 *	\file       htdocs/core/boxes/box_graph_orders_permonth.php
 *	\ingroup    comm
 *	\brief      Box to show graph of commercial data by year
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
include_once DOL_DOCUMENT_ROOT.'/custom/widgetindicateur/class/indicateur.class.php';
//
include_once DOL_DOCUMENT_ROOT.'/custom/addoncomm/class/linedolgraph.class.php';

/**
 * Class to manage the box to show last orders
 */
class box_graph_comm_agences extends ModeleBoxes
{
	public $boxcode = "commsagences";
	public $boximg = "fa-chart-line";
	public $boxlabel = "Répartition financière par agence";

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;
	
	public $info_box_head = array();
	public $info_box_contents = array();

	public $widgettype = 'graph';


	/**
	 *  Constructor
	 *
	 * 	@param	DoliDB	$db			Database handler
	 *  @param	string	$param		More parameters
	 */
	public function __construct($db, $param)
	{
		global $user;

		$this->db = $db;
		//$this->hidden = !($user->rights->commande->lire);
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
	 *  @return	void
 	 */
	public function loadBox($max = 5)
	{
		global $conf, $user, $langs, $db;
		
		$this->max = $max;
		$refreshaction = 'reset_'.$this->boxcode;

		$modeag       = GETPOST('modeag', 'aZ');
		$mode       = GETPOST('mode', 'aZ');
		$modedate  = GETPOST('modedate', 'aZ');
	
		// $modedate = GETPOST('modedate', 'aZ');

		$dolclass = new LineDolGraph($db);
		// $agences = $dolclass->getAgences();
	
		$text = "Répartition financière par agence";
		$this->info_box_head = array(
				'text' => $text,
				'limit'=> dol_strlen($text),
				'graph'=> 1,
				'sublink'=>'',
				'subtext'=>$langs->trans("Filter"),
				'subpicto'=>'filter.png',
				'subclass'=>'linkobject boxfilter',
				'target'=>'none'	// Set '' to get target="_blank"
		);
	
		$prefix = '';
		$socid = 0;
		if ($user->socid) {
			$socid = $user->socid;
		}
		if (empty($user->rights->societe->client->voir) || $socid) {
			$prefix .= 'private-'.$user->id.'-'; // If user has no permission to see all, output dir is specific to user
		}
	
		if($user->hasRight('addoncomm', 'box_graph_comm_agences', 'repartition')) { 
			$langs->load("orders");

			$param_year = 'DOLUSERCOOKIE_box_'.$this->boxcode.'_year';
			$param_shownb = 'DOLUSERCOOKIE_box_'.$this->boxcode.'_shownb';
			
			include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
			
			$autosetarray = preg_split("/[,;:]+/", GETPOST('DOL_AUTOSET_COOKIE'));
			if (in_array('DOLUSERCOOKIE_box_'.$this->boxcode, $autosetarray)) {
				$endyear = GETPOST($param_year, 'int');
				$shownb = GETPOST($param_shownb, 'alpha');
			} else {
				$tmparray = (!empty($_COOKIE['DOLUSERCOOKIE_box_'.$this->boxcode]) ? json_decode($_COOKIE['DOLUSERCOOKIE_box_'.$this->boxcode], true) : array());
				$endyear = (!empty($tmparray['year']) ? $tmparray['year'] : '');
				$shownb = (!empty($tmparray['shownb']) ? $tmparray['shownb'] : '');
			}
			if (empty($shownb)) {
				$shownb = 1;
			}
			$nowarray = dol_getdate(dol_now(), true);
			if (empty($endyear)) {
				$endyear = $nowarray['year'];
			}
			$startyear = $endyear - (empty($conf->global->MAIN_NB_OF_YEAR_IN_WIDGET_GRAPH) ? 2 : ($conf->global->MAIN_NB_OF_YEAR_IN_WIDGET_GRAPH - 1));

			// $mode = 'customer';
			$WIDTH = (($shownb) || !empty($conf->dol_optimize_smallscreen)) ? '880' : '220';
			// if ($modeag == 'on') {
			// $HEIGHT = '500';
			// }
			// if ($modeag == 'off' || $modeag == '') {
				$HEIGHT = '486';
			// }

			$userid = GETPOST('userid', 'int');
			$useridtofilter = $userid; // Filter from parameters
			if ($userid < 0) {
				$userid = 0;
			}
	
			//data for feltring by date
			$now = dol_now();
			$date_ag_startmonth = GETPOST('start_ag_datemonth', 'int');
			$date_ag_startday = GETPOST('start_ag_dateday', 'int');
			$date_ag_startyear = GETPOST('start_ag_dateyear', 'int');
			$date_ag_endmonth = GETPOST('end_ag_datemonth', 'int');
			$date_ag_endday = GETPOST('end_ag_dateday', 'int');
			$date_ag_endyear = GETPOST('end_ag_dateyear', 'int');
		
			$date_ag_start = dol_mktime(-1, -1, -1, $date_ag_startmonth, $date_ag_startday, $date_ag_startyear);
			$date_ag_end = dol_mktime(-1, -1, -1, $date_ag_endmonth, $date_ag_endday, $date_ag_endyear);
		
			if (empty($date_ag_start)) {
				$date_ag_start = dol_get_first_day($db->idate($now, 'y'));
			}
			
			if (empty($date_ag_end)) {
				$date_ag_end = $now;
			}


			$agencesdata = $dolclass->getAgencesValues("Year", $date_ag_start, $date_ag_end);
			
			$form = new Form($db);
			
			$i = 0;
			if ($shownb) {
				if ($modeag == 'off' || $modeag == '') {
					foreach($agencesdata as $key => $values) {
						foreach($values as $value) {
							if($key == 'invoices') {
								if(!is_null($value['agence']))
								$nomsoc = $value['agence'];
								$caforagence = $value['amount'];
								$totalag += $value['amount'];
								$dataseries[$nomsoc] = array($nomsoc, $caforagence);
								$datacolors[] = $value['color_ca'];
							}
						}
					}
				}

				if($modeag == 'on') {
					// $vals['expense'] = null;
					// $vals['facture'] = null;
					// $vals['agence'] = null;
					// $vals['soc'] = null;
					// $vals['note'] = null;
					// $vals['donation'] = null;
					// $vals['vartious'] = null;
					// $vals['loan'] = null;

					foreach($agencesdata as $name => $values) {
						foreach($values as $expense) {
							switch ($name) {
								case 'salaries':
									$vals1[$expense['agence']]['salaries'] = $expense['amount']; 
								break;
								case 'invoices':
									$vals1[$expense['agence']]['invoices'] = $expense['amount']; 
									// $vals['agence'] = $expense['agence']; 
								break;
								case 'facture_fourn':
									$vals1[$expense['agence']]['invoices_supplier'] = $expense['amount']; 
									
								break;
								// case 'soc':
								// 	strcasecmp($expense['date'], $key) == 0 ? $vals['soc'] = $expense['amount'] : 0; 
								// break;
								// case 'note':
								// 	strcasecmp($expense['date'], $key) == 0 ? $vals['note'] = $expense['amount'] : 0; 
								// break;
								// case 'donation':
								// 	strcasecmp($expense['date'], $key) == 0 ? $vals['donation'] = $expense['amount'] : 0; 
								// break;
								// case 'various':
								// 	strcasecmp($expense['date'], $key) == 0 ? $vals['various'] = $expense['amount'] : 0; 
								// break;
								// case 'loan':
								// 	strcasecmp($expense['date'], $key) == 0 ? $vals['loan'] = $expense['amount'] : 0; 
								// break;
							}
						}
						
					}
					
					$datacolors = array('#177F00', '#D0D404', '#29D404', '#36FF09', '#FF0202', '#9E2B40', '#FD7F7F', '#FCCACA', '#04D0D4', '#0005FF');
					foreach($vals1 as $key => $values) {
						if($key != null) {
							$dataseries[] = array($key, $vals1[$key]['invoices'], $vals1[$key]['salaries'] + $vals1[$key]['invoices_supplier']);
							$totalag += $vals1[$key]['invoices'];
							$totaldep += $vals1[$key]['salaries'] + $vals1[$key]['invoices_supplier'];
						}
					}
				}
				include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';			
			
				if ($conf->use_javascript_ajax) {
					if($modeag == 'on') {
						$labels = array('CA', 'Dépenses');
					}
					
					
					if ($shownb) {
					$px1 = new DolGraph();
					$mesg = $px1->isGraphKo();
						if (!$mesg) {
							if ($conf->use_javascript_ajax) {
								$px1 = new DolGraph();
								$px1->SetData(array_values($dataseries));
								//$px1->setShowLegend(1);
								$px1->setShowPercent(1);
								if ($modeag == 'off' || $modeag == '') {
								$px1->SetType(array('pie'));
								$px1->SetDataColor(array_values($datacolors));
								}
								if ($modeag == 'on') {
									$px1->SetType(array('bars'));
									$px1->SetLegend(array_values($labels));
									$px1->SetDataColor(array_values($datacolors));
								}
								$px1->setHeight($HEIGHT);
								$px1->SetWidth($WIDTH);
								$px1->mode = 'depth';
								$px1->draw('infographcaforagences');
							}
						}
					}		
				}			
			}

			if (empty($conf->use_javascript_ajax)) {
				$langs->load("errors");
				$mesg = $langs->trans("WarningFeatureDisabledWithDisplayOptimizedForBlindNoJs");
			}

			if (!$mesg) {
				// filtring form by date
				$stringtoshow = '';
				$stringtoshow .= '<script type="text/javascript">
					jQuery(document).ready(function() {
						jQuery("#idsubimg'.$this->boxcode.'").click(function() {
							jQuery("#idfiltcaag'.$this->boxcode.'").toggle();
						});
					});
					</script>';
				
				$stringtoshow .= '<div class="center hideobject" id="idfiltcaag'.$this->boxcode.'">'; // hideobject is to start hidden
				$stringtoshow .= '<form class="flat formboxfilter" method="POST" action="'.$_SERVER["PHP_SELF"].'?&mode='.$mode.'&modedate='.$modedate.'modeag='.$modeag.'#boxhalfleft">';
				$stringtoshow .= '<input type="hidden" name="token" value="'.newToken().'">';
				$stringtoshow .= '<input type="hidden" name="action" value="'.$refreshaction.'">';
				$stringtoshow .= '<input type="hidden" name="page_y" value="">';
				$stringtoshow .= $form->selectDate($date_ag_start, 'start_ag_date', 0, 0, 1, '', 1, 0).' &nbsp; '.$form->selectDate($date_ag_end, 'end_ag_date', 0, 0, 0, '', 1, 0);
				$stringtoshow .= ' &nbsp; ';
				// if ($modeag == 'off' || $modeag == '') {
				// 	$stringtoshow .= ' &nbsp; ';
				// 	$stringtoshow .= '<a href="' . $_SERVER['PHP_SELF'] . '?modeag=on&#boxhalfleft">';
				// 	$stringtoshow .= '<span style="float: right;margin-right:5px;" class="fas fa-toggle-off" title="Cliquer pour aficher les recettes et dépenses dans le temps"></span>';
				// 	$stringtoshow .= '</a>';
				//  }
				//  if ($modeag == 'on') {
				// 	$stringtoshow .= '<a href="' . $_SERVER['PHP_SELF'] . '?modeag=off&#boxhalfleft">';
				// 	$stringtoshow .= '<span style="float: right;margin-right:5px;" class="fas fa-toggle-on" title="Cliquer pour afficher la rentabilité dans le temps"></span>';
				// 	$stringtoshow .= '</a>';
				//  }
				$stringtoshow .= '<br><br>';
				$stringtoshow .= '<input type="submit" class="smallpaddingimp button" name="submit" value="Rafraichir">';
				$stringtoshow .= '<br>';
				$stringtoshow .= '</form>';
				$stringtoshow .= '</div>';

				if ($shownb) {
					$stringtoshow .= '<div class="fichecenter">';
					$stringtoshow .= '<div class="fichehacenter">';
				}
				if ($shownb) {
				
					$stringtoshow .= $px1->show();
					$stringtoshow .= ' &nbsp; ';
					if ($modeag == 'on') {
						$stringtoshow .= '<div style="line-height: 1 !important;">';
						$stringtoshow .= '<span style="color:#888888;">'.$langs->trans("Total CA").' : '.price($totalag).' €</span>';
						$stringtoshow .= ' &nbsp; | &nbsp;';
						$stringtoshow .= '<span style="color:#888888;">'.$langs->trans("Total Dép.").' : '.price($totaldep).' €</span>';
						$stringtoshow .= '</div>';
					}
					if ($modeag == 'off' || $modeag == '') {
						
						$stringtoshow .= '<div style="line-height: 1 !important;"><span style="color:#888888;">'.$langs->trans("Total").' : '.price($totalag).' €</span></div>';
					}
				}
				if(empty($dataseries)) {
					$stringtoshow .= '<div>'.$langs->trans("Enregistrement non trouvé").'</div>';
				}
			
				if ($shownb) {
					$stringtoshow .= '</div>';
					$stringtoshow .= '<div">';
				}
				
				if ($shownb) {
					$stringtoshow .= '</div>';
					$stringtoshow .= '</div>';
				}
				$this->info_box_contents[0][0] = array(
					'tr'=>'class="oddeven nohover"',
					'td' => 'class="nowraponall center"',
					'textnoformat'=>$stringtoshow,
				);
			} else {
				$this->info_box_contents[0][0] = array(
					'tr'=>'class="oddeven nohover"',
					'td' => 'class="nohover left"',
					'maxlength'=>500,
					'text' => $mesg,
				);
			}
		} else {
			$this->info_box_contents[0][0] = array(
				'td' => 'class="nohover opacitymedium left"',
				'text' => $langs->trans("ReadPermissionNotAllowed")
			);
		}
	}


	/**
	 *  Method to show box
	 *
	 *  @param	array	$head       Array with properties of box title
	 *  @param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *  @return	string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		global $user;

		if($user->hasRight('addoncomm', 'box_graph_comm_agences', 'repartition')) { 
			return $this->showBoxCustom($this->info_box_head, $this->info_box_contents, $nooutput);
		}
	}

	/**
	 * Standard method to show a box (usage by boxes not mandatory, a box can still use its own showBox function)
	 *
	 * @param   array   $head       Array with properties of box title
	 * @param   array   $contents   Array with properties of box lines
	 * @param	int		$nooutput	No print, only return string
	 * @return  string
	 */
	public function showBoxCustom($head = null, $contents = null, $nooutput = 0)
	{
		global $langs, $user, $conf;

		if (!empty($this->hidden)) {
			return '\n<!-- Box ".get_class($this)." hidden -->\n'; // Nothing done if hidden (for example when user has no permission)
		}

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$MAXLENGTHBOX = 60; // Mettre 0 pour pas de limite

		$cachetime = 900; // 900 : 15mn
		$cachedir = DOL_DATA_ROOT.'/boxes/temp';
		$fileid = get_class($this).'id-'.$this->box_id.'-e'.$conf->entity.'-u'.$user->id.'-s'.$user->socid.'.cache';
		$filename = '/box-'.$fileid;
		$refresh = dol_cache_refresh($cachedir, $filename, $cachetime);
		$out = '';
		$mode       = GETPOST('mode', 'aZ');
		$modedate  = GETPOST('modedate', 'aZ');
		$modeag       = GETPOST('modeag', 'aZ');
	

		if ($refresh) {
			dol_syslog(get_class($this).'::showBox');

			// Define nbcol and nblines of the box to show
			$nbcol = 0;
			if (isset($contents[0])) {
				$nbcol = count($contents[0]);
			}
			$nblines = count($contents);

			$out .= "\n<!-- Box ".get_class($this)." start -->\n";

			$out .= '<div class="box boxdraggable" id="boxto_'.$this->box_id.'">'."\n";

			if (!empty($head['text']) || !empty($head['sublink']) || !empty($head['subpicto']) || $nblines) {
				$out .= '<table summary="boxtable'.$this->box_id.'" width="100%" class="noborder boxtable">'."\n";
			}

			// Show box title
			if (!empty($head['text']) || !empty($head['sublink']) || !empty($head['subpicto'])) {
				$out .= '<tr class="liste_titre box_titre">';
				$out .= '<td';
				if ($nbcol > 0) {
					$out .= ' colspan="'.$nbcol.'"';
				}
				$out .= '>';
				if (!empty($conf->use_javascript_ajax)) {
					//$out.= '<table summary="" class="nobordernopadding" width="100%"><tr><td class="tdoverflowmax150 maxwidth150onsmartphone">';
					$out .= '<div class="tdoverflowmax400 maxwidth250onsmartphone float">';
				}
				if (!empty($head['text'])) {
					$s = dol_trunc($head['text'], isset($head['limit']) ? $head['limit'] : $MAXLENGTHBOX);
					$out .= $s;
				}
				if (!empty($conf->use_javascript_ajax)) {
					$out .= '</div>';
				}
				//$out.= '</td>';

				if (!empty($conf->use_javascript_ajax)) {
					$sublink = '';
					if (!empty($head['sublink'])) {
						$sublink .= '<a href="'.$head['sublink'].'"'.(empty($head['target']) ? '' : ' target="'.$head['target'].'"').'>';
					}
					if (!empty($head['subpicto'])) {
						$sublink .= img_picto($head['subtext'], $head['subpicto'], 'class="opacitymedium marginleftonly '.(empty($head['subclass']) ? '' : $head['subclass']).'" id="idsubimg'.$this->boxcode.'"');
					}
					if (!empty($head['sublink'])) {
						$sublink .= '</a>';
					}

					//$out.= '<td class="nocellnopadd boxclose right nowraponall">';
					$out .= '<div class="nocellnopadd boxclose floatright nowraponall">';
					$out .= $sublink;
					// The image must have the class 'boxhandle' beause it's value used in DOM draggable objects to define the area used to catch the full object
					$out .= img_picto($langs->trans("MoveBox", $this->box_id), 'grip_title', 'class="opacitymedium boxhandle hideonsmartphone cursormove marginleftonly"');
					$out .= img_picto($langs->trans("CloseBox", $this->box_id), 'close_title', 'class="opacitymedium boxclose cursorpointer marginleftonly" rel="x:y" id="imgclose'.$this->box_id.'"');
					$label = $head['text'];
					//if (! empty($head['graph'])) $label.=' ('.$langs->trans("Graph").')';
					if (!empty($head['graph'])) {
						$label .= ' <span class="opacitymedium fa fa-bar-chart"></span>';
					}
					$out .= '<input type="hidden" id="boxlabelentry'.$this->box_id.'" value="'.dol_escape_htmltag($label).'">';
					//$out.= '</td></tr></table>';
					$out .= '</div>';
				}
				$out .= '<div class="nocellnopadd boxclose floatright nowraponall">';
				if ($modeag == 'off' || $modeag == '') {
					$out .= ' &nbsp; ';
					$out .= '<a class="opacitymedium marginleftonly" href="' . $_SERVER['PHP_SELF'] . '?mode='.$mode.'&modedate='.$modedate.'&modeag=on&#boxhalfleft">';
					$out .= '<span style="font-size: 1.6em;" class="fas fa-toggle-off" title="Cliquer pour aficher les recettes et dépenses par agence"></span>';
					$out .= '</a>';
				 }
				 if ($modeag == 'on') {
					$out .= '<a class="opacitymedium marginleftonly" href="' . $_SERVER['PHP_SELF'] . '?mode='.$mode.'&modedate='.$modedate.'&modeag=off&#boxhalfleft">';
					$out .= '<span style="font-size: 1.6em;" class="fas fa-toggle-on" title="Cliquer pour afficher la rentabilité par agence"></span>';
					$out .= '</a>';
				 }
				
				 $out .= "</div>";
				
				$out .= "</td>";
				$out .= "</tr>\n";
			}

			// Show box lines
			if ($nblines) {
				// Loop on each record
				for ($i = 0, $n = $nblines; $i < $n; $i++) {
					if (isset($contents[$i])) {
						// TR
						if (isset($contents[$i][0]['tr'])) {
							$out .= '<tr '.$contents[$i][0]['tr'].'>';
						} else {
							$out .= '<tr class="oddeven">';
						}

						// Loop on each TD
						$nbcolthisline = count($contents[$i]);
						for ($j = 0; $j < $nbcolthisline; $j++) {
							// Define tdparam
							$tdparam = '';
							if (!empty($contents[$i][$j]['td'])) {
								$tdparam .= ' '.$contents[$i][$j]['td'];
							}

							$text = isset($contents[$i][$j]['text']) ? $contents[$i][$j]['text'] : '';
							$textwithnotags = preg_replace('/<([^>]+)>/i', '', $text);
							$text2 = isset($contents[$i][$j]['text2']) ? $contents[$i][$j]['text2'] : '';
							$text2withnotags = preg_replace('/<([^>]+)>/i', '', $text2);

							$textnoformat = isset($contents[$i][$j]['textnoformat']) ? $contents[$i][$j]['textnoformat'] : '';
							//$out.= "xxx $textwithnotags y";
							if (empty($contents[$i][$j]['tooltip'])) {
								$contents[$i][$j]['tooltip'] = "";
							}
							$tooltip = isset($contents[$i][$j]['tooltip']) ? $contents[$i][$j]['tooltip'] : '';

							$out .= '<td'.$tdparam.'>'."\n";

							// Url
							if (!empty($contents[$i][$j]['url']) && empty($contents[$i][$j]['logo'])) {
								$out .= '<a href="'.$contents[$i][$j]['url'].'"';
								if (!empty($tooltip)) {
									$out .= ' title="'.dol_escape_htmltag($langs->trans("Show").' '.$tooltip, 1).'" class="classfortooltip"';
								}
								//$out.= ' alt="'.$textwithnotags.'"';      // Pas de alt sur un "<a href>"
								$out .= isset($contents[$i][$j]['target']) ? ' target="'.$contents[$i][$j]['target'].'"' : '';
								$out .= '>';
							}

							// Logo
							if (!empty($contents[$i][$j]['logo'])) {
								$logo = preg_replace("/^object_/i", "", $contents[$i][$j]['logo']);
								$out .= '<a href="'.$contents[$i][$j]['url'].'">';
								$out .= img_object($langs->trans("Show").' '.$tooltip, $logo, 'class="classfortooltip"');
							}

							$maxlength = $MAXLENGTHBOX;
							if (!empty($contents[$i][$j]['maxlength'])) {
								$maxlength = $contents[$i][$j]['maxlength'];
							}

							if ($maxlength) {
								$textwithnotags = dol_trunc($textwithnotags, $maxlength);
							}
							if (preg_match('/^<(img|div|span)/i', $text) || !empty($contents[$i][$j]['asis'])) {
								$out .= $text; // show text with no html cleaning
							} else {
								$out .= $textwithnotags; // show text with html cleaning
							}

							// End Url
							if (!empty($contents[$i][$j]['url'])) {
								$out .= '</a>';
							}

							if (preg_match('/^<(img|div|span)/i', $text2) || !empty($contents[$i][$j]['asis2'])) {
								$out .= $text2; // show text with no html cleaning
							} else {
								$out .= $text2withnotags; // show text with html cleaning
							}

							if (!empty($textnoformat)) {
								$out .= "\n".$textnoformat."\n";
							}

							$out .= "</td>\n";
						}

						$out .= "</tr>\n";
					}
				}
			}

			if (!empty($head['text']) || !empty($head['sublink']) || !empty($head['subpicto']) || $nblines) {
				$out .= "</table>\n";
			}

			// If invisible box with no contents
			if (empty($head['text']) && empty($head['sublink']) && empty($head['subpicto']) && !$nblines) {
				$out .= "<br>\n";
			}

			$out .= "</div>\n";

			$out .= "<!-- Box ".get_class($this)." end -->\n\n";
			if (!empty($conf->global->MAIN_ACTIVATE_FILECACHE)) {
				dol_filecache($cachedir, $filename, $out);
			}
		} else {
			dol_syslog(get_class($this).'::showBoxCached');
			$out = "<!-- Box ".get_class($this)." from cache -->";
			$out .= dol_readcachefile($cachedir, $filename);
		}

		if ($nooutput) {
			return $out;
		} else {
			print $out;
		}

		return '';
	}
}


