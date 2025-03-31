<?php
/* Copyright (c) 2024  Lény METZGER    <l.metzger@optim-industries.fr>
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
 * \file       htdocs/custom/holidaycustom/class/extendedhtml.form.class.php
 * \ingroup    custom
 * \brief      File of class with all html predefined components
 */


/**
 * Class to manage generation of HTML components
 */
class ExtendedFormHoliday extends Form
{
	/**
	 *  Function to show a form to select a duration on a page
	 *
	 * @param string $prefix Prefix for input fields
	 * @param int|string $iSecond Default preselected duration (number of seconds or '')
	 * @param int $disabled Disable the combo box
	 * @param string $typehour If 'select' then input hour and input min is a combo,
	 *                         If 'text' input hour is in text and input min is a text,
	 *                         If 'textselect' input hour is in text and input min is a combo
	 * @param integer $minunderhours If 1, show minutes selection under the hours
	 * @param int $nooutput Do not output html string but return it
	 * @return    string                        HTML component
	 */
	public function select_duration($prefix, $iSecond = '', $disabled = 0, $typehour = 'select', $minunderhours = 0, $nooutput = 0)
	{
		// phpcs:enable
		global $langs;

		$retstring = '<span class="nowraponall">';

		$hourSelected = '';
		$minSelected = '';

		// Hours
		if ($iSecond != '') {
			require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

			$hourSelected = convertSecondToTime($iSecond, 'allhour');
			$minSelected = convertSecondToTime($iSecond, 'min');
		}

		if ($typehour == 'select') {
			$retstring .= '<select class="flat" id="select_' . $prefix . 'hour" name="' . $prefix . 'hour"' . ($disabled ? ' disabled' : '') . '>';
			for ($hour = 0; $hour <= 8; $hour++) {    // For a duration, we allow 24 hours
				$retstring .= '<option value="' . $hour . '"';
				if (is_numeric($hourSelected) && $hourSelected == $hour) {
					$retstring .= " selected";
				}
				$retstring .= ">" . $hour . "</option>";
			}
			$retstring .= "</select>";
		} elseif ($typehour == 'text' || $typehour == 'textselect') {
			$retstring .= '<input placeholder="' . $langs->trans('HourShort') . '" type="number" min="0" name="' . $prefix . 'hour"' . ($disabled ? ' disabled' : '') . ' class="flat maxwidth50 inputhour right" value="' . (($hourSelected != '') ? ((int) $hourSelected) : '') . '">';
		} else {
			return 'BadValueForParameterTypeHour';
		}

		if ($typehour != 'text') {
			$retstring .= ' ' . $langs->trans('HourShort');
		} else {
			$retstring .= '<span class="">:</span>';
		}

		// Minutes
		if ($minunderhours) {
			$retstring .= '<br>';
		} else {
			if ($typehour != 'text') {
				$retstring .= '<span class="hideonsmartphone">&nbsp;</span>';
			}
		}

		if ($typehour == 'select' || $typehour == 'textselect') {
			$retstring .= '<select class="flat" id="select_' . $prefix . 'min" name="' . $prefix . 'min"' . ($disabled ? ' disabled' : '') . '>';
			for ($min = 0; $min <= 55; $min = $min + 15) {
				$retstring .= '<option value="' . $min . '"';
				if (is_numeric($minSelected) && $minSelected == $min) {
					$retstring .= ' selected';
				}
				$retstring .= '>' . $min . '</option>';
			}
			$retstring .= "</select>";
		} elseif ($typehour == 'text') {
			$retstring .= '<input placeholder="' . $langs->trans('MinuteShort') . '" type="number" min="0" name="' . $prefix . 'min"' . ($disabled ? ' disabled' : '') . ' class="flat maxwidth50 inputminute right" value="' . (($minSelected != '') ? ((int) $minSelected) : '') . '">';
		}

		if ($typehour != 'text') {
			$retstring .= ' ' . $langs->trans('MinuteShort');
		}

		$retstring .= "</span>";

		if (!empty($nooutput)) {
			return $retstring;
		}

		print $retstring;

		return '';
	}
}
