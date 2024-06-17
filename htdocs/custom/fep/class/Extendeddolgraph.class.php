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
 *	\file       htdocs/core/class/dolgraph.class.php
 *  \ingroup    core
 *	\brief      File for class to generate graph
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';

class ExtendedDolGraph extends DolGraph
{

	/**
	 * Build a graph into memory using correct library  (may also be wrote on disk, depending on library used)
	 *
	 * @param	string	$file    	Image file name to use to save onto disk (also used as javascript unique id)
	 * @param	string	$fileurl	Url path to show image if saved onto disk
	 * @return	integer|null
	 */
	public function draw($file, $fileurl = '')
	{
		if (empty($file)) {
			$this->error = "Call to draw method was made with empty value for parameter file.";
			dol_syslog(get_class($this) . "::draw " . $this->error, LOG_ERR);
			return -2;
		}
		if (!is_array($this->data)) {
			$this->error = "Call to draw method was made but SetData was not called or called with an empty dataset for parameters";
			dol_syslog(get_class($this) . "::draw " . $this->error, LOG_ERR);
			return -1;
		}
		if (count($this->data) < 1) {
			$this->error = "Call to draw method was made but SetData was is an empty dataset";
			dol_syslog(get_class($this) . "::draw " . $this->error, LOG_WARNING);
		}
		$call = "draw_chart";
		call_user_func_array(array($this, $call), array($file, $fileurl));
	}
	
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Build a graph using Chart library. Input when calling this method should be:
	 *	$this->data  = array(array(0=>'labelxA',1=>yA),  array('labelxB',yB));
	 *	$this->data  = array(array(0=>'labelxA',1=>yA1,...,n=>yAn), array('labelxB',yB1,...yBn));   // or when there is n series to show for each x
	 *  $this->data  = array(array('label'=>'labelxA','data'=>yA),  array('labelxB',yB));			// Syntax deprecated
	 *  $this->legend= array("Val1",...,"Valn");													// list of n series name
	 *  $this->type  = array('bars',...'lines', 'linesnopoint'); or array('pie') or array('polar') or array('piesemicircle');
	 *  $this->mode = 'depth' ???
	 *  $this->bgcolorgrid
	 *  $this->datacolor
	 *  $this->shownodatagraph
	 *
	 * @param	string	$file    	Image file name to use to save onto disk (also used as javascript unique id)
	 * @param	string	$fileurl	Url path to show image if saved onto disk. Never used here.
	 * @return	void
	 */
	private function draw_chart($file, $fileurl)
	{
		// phpcs:enable
		global $conf, $langs;

		dol_syslog(get_class($this) . "::draw_chart this->type=" . join(',', $this->type) . " this->MaxValue=" . $this->MaxValue);

		if (empty($this->width) && empty($this->height)) {
			print 'Error width or height not set';
			return;
		}

		$showlegend = $this->showlegend;

		$legends = array();
		$nblot = 0;
		if (is_array($this->data)) {
			foreach ($this->data as $valarray) {      // Loop on each x
				$nblot = max($nblot, count($valarray) - 1); // -1 to remove legend
			}
		}
		//var_dump($nblot);
		if ($nblot < 0) {
			dol_syslog('Bad value for property ->data. Must be set by mydolgraph->SetData before calling mydolgrapgh->draw', LOG_WARNING);
		}
		$firstlot = 0;
		// Works with line but not with bars
		//if ($nblot > 2) $firstlot = ($nblot - 2);        // We limit nblot to 2 because jflot can't manage more than 2 bars on same x

		$serie = array();
		$arrayofgroupslegend = array();
		//var_dump($this->data);

		$i = $firstlot;
		while ($i < $nblot) {	// Loop on each serie
			$values = array(); // Array with horizontal y values (specific values of a serie) for each abscisse x (with x=0,1,2,...)
			$serie[$i] = "";

			// Fill array $values
			$x = 0;
			foreach ($this->data as $valarray) {	// Loop on each x
				$legends[$x] = (array_key_exists('label', $valarray) ? $valarray['label'] : $valarray[0]);
				$array_of_ykeys = array_keys($valarray);
				$alabelexists = 1;
				$tmpykey = explode('_', ($array_of_ykeys[$i + ($alabelexists ? 1 : 0)]), 3);
				if (isset($tmpykey[2]) && (!empty($tmpykey[2]) || $tmpykey[2] == '0')) {		// This is a 'Group by' array
					$tmpvalue = (array_key_exists('y_' . $tmpykey[1] . '_' . $tmpykey[2], $valarray) ? $valarray['y_' . $tmpykey[1] . '_' . $tmpykey[2]] : $valarray[$i + 1]);
					$values[$x] = (is_numeric($tmpvalue) ? $tmpvalue : null);
					$arrayofgroupslegend[$i] = array(
						'stacknum' => $tmpykey[1],
						'legend' => $this->Legend[$tmpykey[1]],
						'legendwithgroup' => $this->Legend[$tmpykey[1]] . ' - ' . $tmpykey[2]
					);
				} else {
					$tmpvalue = (array_key_exists('y_' . $i, $valarray) ? $valarray['y_' . $i] : $valarray[$i + 1]);
					//var_dump($i.'_'.$x.'_'.$tmpvalue);
					$values[$x] = (is_numeric($tmpvalue) ? $tmpvalue : null);
				}
				$x++;
			}
			//var_dump($values);
			$j = 0;
			foreach ($values as $x => $y) {
				if (isset($y)) {
					$serie[$i] .= ($j > 0 ? ", " : "") . $y;
				} else {
					$serie[$i] .= ($j > 0 ? ", " : "") . 'null';
				}
				$j++;
			}

			$values = null; // Free mem
			$i++;
		}
		//var_dump($serie);
		//var_dump($arrayofgroupslegend);

		$tag = dol_escape_htmltag(dol_string_unaccent(dol_string_nospecial(basename($file), '_', array('-', '.'))));

		$this->stringtoshow = '<!-- Build using chart -->' . "\n";
		if (!empty($this->title)) {
			$this->stringtoshow .= '<div class="center dolgraphtitle' . (empty($this->cssprefix) ? '' : ' dolgraphtitle' . $this->cssprefix) . '">' . $this->title . '</div>';
		}
		if (!empty($this->shownographyet)) {
			$this->stringtoshow .= '<div style="width:' . $this->width . (strpos($this->width, '%') > 0 ? '' : 'px') . '; height:' . $this->height . 'px;" class="nographyet"></div>';
			$this->stringtoshow .= '<div class="nographyettext margintoponly">' . $langs->trans("NotEnoughDataYet") . '...</div>';
			return;
		}

		// Start the div that will contains all the graph
		$dolxaxisvertical = '';
		if (count($this->data) > 20) {
			$dolxaxisvertical = 'dol-xaxis-vertical';
		}
		// No height for the pie grah
		$cssfordiv = 'dolgraphchart';
		if (isset($this->type[$firstlot])) {
			$cssfordiv .= ' dolgraphchar' . $this->type[$firstlot];
		}
		$this->stringtoshow .= '<div id="placeholder_' . $tag . '" style="min-height: ' . $this->height . (strpos($this->height, '%') > 0 ? '' : 'px') . '; width:' . $this->width . (strpos($this->width, '%') > 0 ? '' : 'px') . ';" class="' . $cssfordiv . ' dolgraph' . (empty($dolxaxisvertical) ? '' : ' ' . $dolxaxisvertical) . (empty($this->cssprefix) ? '' : ' dolgraph' . $this->cssprefix) . ' center"><canvas id="canvas_' . $tag . '"></canvas></div>' . "\n";

		$this->stringtoshow .= '<script id="' . $tag . '">' . "\n";
		$i = $firstlot;
		if ($nblot < 0) {
			$this->stringtoshow .= '<!-- No series of data -->';
		} else {
			while ($i < $nblot) {
				//$this->stringtoshow .= '<!-- Series '.$i.' -->'."\n";
				//$this->stringtoshow .= $serie[$i]."\n";
				$i++;
			}
		}
		$this->stringtoshow .= "\n";

		// Special case for Graph of type 'pie', 'piesemicircle', or 'polar'
		if (isset($this->type[$firstlot]) && (in_array($this->type[$firstlot], array('pie', 'polar', 'piesemicircle')))) {
			$type = $this->type[$firstlot]; // pie or polar
			$this->stringtoshow .= 'var options = {' . "\n";
			$legendMaxLines = 0; // Does not work
			if (empty($showlegend)) {
				$this->stringtoshow .= 'legend: { display: false }, ';
			} else {
				$this->stringtoshow .= 'legend: { position: \'' . ($showlegend == 2 ? 'right' : 'top') . '\'';
				if (!empty($legendMaxLines)) {
					$this->stringtoshow .= ', maxLines: ' . $legendMaxLines . '';
				}
				/* This has no effect on chartjs version with dol v14
				$this->stringtoshow .= ', labels: {
					color: \'rgb(255, 0, 0)\',
					// This more specific font property overrides the global property
					font: {
						size: 24
					}
				}';
				*/
				$this->stringtoshow .= ' }, ' . "\n";
			}

			/* This has no effect on chartjs version with dol v14
			$this->stringtoshow .= 'plugins: {
				legend: {
					display: true,
					labels: {
						color: \'rgb(255, 0, 0)\',
						// This more specific font property overrides the global property
						font: {
							size: 24
						}
					}
				}
			},'."\n"; */

			if ($this->type[$firstlot] == 'piesemicircle') {
				$this->stringtoshow .= 'circumference: Math.PI,' . "\n";
				$this->stringtoshow .= 'rotation: -Math.PI,' . "\n";
			}
			$this->stringtoshow .= 'elements: { arc: {' . "\n";
			// Color of earch arc
			$this->stringtoshow .= 'backgroundColor: [';
			$i = 0;
			$foundnegativecolor = 0;
			foreach ($legends as $val) {	// Loop on each serie
				if ($i > 0) {
					$this->stringtoshow .= ', ' . "\n";
				}
				if (is_array($this->datacolor[$i])) {
					$color = 'rgb(' . $this->datacolor[$i][0] . ', ' . $this->datacolor[$i][1] . ', ' . $this->datacolor[$i][2] . ')'; // If datacolor is array(R, G, B)
				} else {
					$tmp = str_replace('#', '', $this->datacolor[$i]);
					if (strpos($tmp, '-') !== false) {
						$foundnegativecolor++;
						$color = '#FFFFFF'; // If $val is '-123'
					} else {
						$color = "#" . $tmp; // If $val is '123' or '#123'
					}
				}
				$this->stringtoshow .= "'" . $color . "'";
				$i++;
			}
			$this->stringtoshow .= '], ' . "\n";
			// Border color
			if ($foundnegativecolor) {
				$this->stringtoshow .= 'borderColor: [';
				$i = 0;
				foreach ($legends as $val) {	// Loop on each serie
					if ($i > 0) {
						$this->stringtoshow .= ', ' . "\n";
					}
					if (is_array($this->datacolor[$i])) {
						$color = 'null'; // If datacolor is array(R, G, B)
					} else {
						$tmp = str_replace('#', '', $this->datacolor[$i]);
						if (strpos($tmp, '-') !== false) {
							$color = '#' . str_replace('-', '', $tmp); // If $val is '-123'
						} else {
							$color = 'null'; // If $val is '123' or '#123'
						}
					}
					$this->stringtoshow .= ($color == 'null' ? "'rgba(0,0,0,0.2)'" : "'" . $color . "'");
					$i++;
				}
				$this->stringtoshow .= ']';
			}
			$this->stringtoshow .= '} } };' . "\n";

			$this->stringtoshow .= '
				var ctx = document.getElementById("canvas_' . $tag . '").getContext("2d");
				var chart = new Chart(ctx, {
			    // The type of chart we want to create
    			type: \'' . (in_array($type, array('pie', 'piesemicircle')) ? 'doughnut' : 'polarArea') . '\',
				// Configuration options go here
    			options: options,
				data: {
					labels: [';

			$i = 0;
			foreach ($legends as $val) {	// Loop on each serie
				if ($i > 0) {
					$this->stringtoshow .= ', ';
				}
				$this->stringtoshow .= "'" . dol_escape_js(dol_trunc($val, 25)) . "'";	// Lower than 25 make some important label (that we can't shorten) to be truncated
				$i++;
			}

			$this->stringtoshow .= '],
					datasets: [';
			$i = 0;
			$i = 0;
			while ($i < $nblot) {	// Loop on each serie
				$color = 'rgb(' . $this->datacolor[$i][0] . ', ' . $this->datacolor[$i][1] . ', ' . $this->datacolor[$i][2] . ')';
				//$color = (!empty($data['seriescolor']) ? json_encode($data['seriescolor']) : json_encode($datacolor));

				if ($i > 0) {
					$this->stringtoshow .= ', ' . "\n";
				}
				$this->stringtoshow .= '{' . "\n";
				//$this->stringtoshow .= 'borderColor: \''.$color.'\', ';
				//$this->stringtoshow .= 'backgroundColor: \''.$color.'\', ';
				$this->stringtoshow .= '  data: [' . $serie[$i] . ']';
				$this->stringtoshow .= '}' . "\n";
				$i++;
			}
			$this->stringtoshow .= ']' . "\n";
			$this->stringtoshow .= '}' . "\n";
			$this->stringtoshow .= '});' . "\n";
		} else {
			// Other cases, graph of type 'bars', 'lines', 'linesnopoint'
			$type = 'bar';
			if (!isset($this->type[$firstlot]) || $this->type[$firstlot] == 'bars') {
				$type = 'bar';
			}
			if (isset($this->type[$firstlot]) && $this->type[$firstlot] == 'horizontalbars') {
				$type = 'horizontalBar';
			}
			if (isset($this->type[$firstlot]) && ($this->type[$firstlot] == 'lines' || $this->type[$firstlot] == 'linesnopoint')) {
				$type = 'line';
			}

			$this->stringtoshow .= 'var options = { maintainAspectRatio: false, aspectRatio: 2.5, ';
			if (empty($showlegend)) {
				$this->stringtoshow .= 'legend: { display: false }, ';
			}
			$this->stringtoshow .= 'scales: { xAxes: [{ ';
			if ($this->hideXValues) {
				$this->stringtoshow .= ' ticks: { display: false }, display: true,';
			}
			//$this->stringtoshow .= 'type: \'time\', ';		// Need Moment.js
			$this->stringtoshow .= 'distribution: \'linear\'';
			if ($type == 'bar' && count($arrayofgroupslegend) > 0) {
				$this->stringtoshow .= ', stacked: true';
			}
			$this->stringtoshow .= ' }]';
			$this->stringtoshow .= ', yAxes: [{ ticks: { beginAtZero: true, max: 3 }';
			if ($type == 'bar' && count($arrayofgroupslegend) > 0) {
				$this->stringtoshow .= ', stacked: true';
			}
			$this->stringtoshow .= ' }] }';

			// Add a callback to change label to show only positive value
			if (is_array($this->tooltipsLabels) || is_array($this->tooltipsTitles)) {
				$this->stringtoshow .= ', tooltips: { mode: \'nearest\',
					callbacks: {';
				if (is_array($this->tooltipsTitles)) {
					$this->stringtoshow .='
							title: function(tooltipItem, data) {
								var tooltipsTitle ='.json_encode($this->tooltipsTitles).'
								return tooltipsTitle[tooltipItem[0].datasetIndex];
							},';
				}
				if (is_array($this->tooltipsLabels)) {
					$this->stringtoshow .= 'label: function(tooltipItem, data) {
								var tooltipslabels ='.json_encode($this->tooltipsLabels).'
								return tooltipslabels[tooltipItem.datasetIndex]
							}';
				}
				$this->stringtoshow .='}},';
			}
			$this->stringtoshow .= '};';
			$this->stringtoshow .= '
				var ctx = document.getElementById("canvas_' . $tag . '").getContext("2d");
				var chart = new Chart(ctx, {
			    // The type of chart we want to create
    			type: \'' . $type . '\',
				// Configuration options go here
    			options: options,
				data: {
					labels: [';

			$i = 0;
			foreach ($legends as $val) {	// Loop on each serie
				if ($i > 0) {
					$this->stringtoshow .= ', ';
				}
				$this->stringtoshow .= "'" . dol_escape_js(dol_trunc($val, 32)) . "'";
				$i++;
			}

			//var_dump($arrayofgroupslegend);

			$this->stringtoshow .= '],
					datasets: [';

			global $theme_datacolor;
			//var_dump($arrayofgroupslegend);
			$i = 0;
			$iinstack = 0;
			$oldstacknum = -1;
			while ($i < $nblot) {	// Loop on each serie
				$foundnegativecolor = 0;
				$usecolorvariantforgroupby = 0;
				// We used a 'group by' and we have too many colors so we generated color variants per
				if (!empty($arrayofgroupslegend) && is_array($arrayofgroupslegend[$i]) && count($arrayofgroupslegend[$i]) > 0) {	// If we used a group by.
					$nbofcolorneeds = count($arrayofgroupslegend);
					$nbofcolorsavailable = count($theme_datacolor);
					if ($nbofcolorneeds > $nbofcolorsavailable) {
						$usecolorvariantforgroupby = 1;
					}

					$textoflegend = $arrayofgroupslegend[$i]['legendwithgroup'];
				} else {
					$textoflegend = $this->Legend[$i];
				}

				if ($usecolorvariantforgroupby) {
					$newcolor = $this->datacolor[$arrayofgroupslegend[$i]['stacknum']];
					// If we change the stack
					if ($oldstacknum == -1 || $arrayofgroupslegend[$i]['stacknum'] != $oldstacknum) {
						$iinstack = 0;
					}

					//var_dump($iinstack);
					if ($iinstack) {
						// Change color with offset of $$iinstack
						//var_dump($newcolor);
						if ($iinstack % 2) {	// We increase agressiveness of reference color for color 2, 4, 6, ...
							$ratio = min(95, 10 + 10 * $iinstack); // step of 20
							$brightnessratio = min(90, 5 + 5 * $iinstack); // step of 10
						} else {				// We decrease agressiveness of reference color for color 3, 5, 7, ..
							$ratio = max(-100, -15 * $iinstack + 10); // step of -20
							$brightnessratio = min(90, 10 * $iinstack); // step of 20
						}
						//var_dump('Color '.($iinstack+1).' : '.$ratio.' '.$brightnessratio);

						$newcolor = array_values(colorHexToRgb(colorAgressiveness(colorArrayToHex($newcolor), $ratio, $brightnessratio), false, true));
					}
					$oldstacknum = $arrayofgroupslegend[$i]['stacknum'];

					$color = 'rgb(' . $newcolor[0] . ', ' . $newcolor[1] . ', ' . $newcolor[2] . ', 0.9)';
					$bordercolor = 'rgb(' . $newcolor[0] . ', ' . $newcolor[1] . ', ' . $newcolor[2] . ')';
				} else { // We do not use a 'group by'
					if (is_array($this->datacolor[$i])) {
						$color = 'rgb(' . $this->datacolor[$i][0] . ', ' . $this->datacolor[$i][1] . ', ' . $this->datacolor[$i][2] . ', 0.9)';
					} else {
						$color = $this->datacolor[$i];
					}
					if (is_array($this->bordercolor[$i])) {
						$color = 'rgb(' . $this->bordercolor[$i][0] . ', ' . $this->bordercolor[$i][1] . ', ' . $this->bordercolor[$i][2] . ', 0.9)';
					} else {
						if ($type != 'horizontalBar') {
							$bordercolor = $color;
						} else {
							$bordercolor = $this->bordercolor[$i];
						}
					}
				}
				if ($i > 0) {
					$this->stringtoshow .= ', ';
				}
				$this->stringtoshow .= "\n";
				$this->stringtoshow .= '{';
				$this->stringtoshow .= 'dolibarrinfo: \'y_' . $i . '\', ';
				$this->stringtoshow .= 'label: \'' . dol_escape_js(dol_string_nohtmltag($textoflegend)) . '\', ';
				$this->stringtoshow .= 'pointStyle: \'' . ((!empty($this->type[$i]) && $this->type[$i] == 'linesnopoint') ? 'line' : 'circle') . '\', ';
				$this->stringtoshow .= 'fill: ' . ($type == 'bar' ? 'true' : 'false') . ', ';
				if ($type == 'bar' || $type == 'horizontalBar') {
					$this->stringtoshow .= 'borderWidth: \''.$this->borderwidth.'\', ';
				}
				$this->stringtoshow .= 'borderColor: \'' . $bordercolor . '\', ';
				$this->stringtoshow .= 'backgroundColor: \'' . $color . '\', ';
				if (!empty($arrayofgroupslegend) && !empty($arrayofgroupslegend[$i])) {
					$this->stringtoshow .= 'stack: \'' . $arrayofgroupslegend[$i]['stacknum'] . '\', ';
				}
				$this->stringtoshow .= 'data: [';

				$this->stringtoshow .= $this->mirrorGraphValues ? '[' . -$serie[$i] . ',' . $serie[$i] . ']' : $serie[$i];
				$this->stringtoshow .= ']';
				$this->stringtoshow .= '}' . "\n";

				$i++;
				$iinstack++;
			}
			$this->stringtoshow .= ']' . "\n";
			$this->stringtoshow .= '}' . "\n";
			$this->stringtoshow .= '});' . "\n";
		}

		$this->stringtoshow .= '</script>' . "\n";
	}

	/**
	 * Output HTML string to show graph
	 *
	 * @param	int|string		$shownographyet    Show graph to say there is not enough data or the message in $shownographyet if it is a string.
	 * @return	string							   HTML string to show graph
	 */
	public function show($shownographyet = 0)
	{
		global $langs;

		if ($shownographyet) {
			$s = '<div class="nographyet" style="width:' . (preg_match('/%/', $this->width) ? $this->width : $this->width . 'px') . '; height:' . (preg_match('/%/', $this->height) ? $this->height : $this->height . 'px') . ';"></div>';
			$s .= '<div class="nographyettext margintoponly">';
			if (is_numeric($shownographyet)) {
				$s .= $langs->trans("NotEnoughDataYet") . '...';
			} else {
				$s .= $shownographyet . '...';
			}
			$s .= '</div>';
			return $s;
		}

		return $this->stringtoshow;
	}
}
