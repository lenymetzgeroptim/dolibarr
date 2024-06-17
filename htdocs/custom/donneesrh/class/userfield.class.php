<?php
/* Copyright (C) 2023 METZGER Leny <l.metzger@optim-industries.fr>
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

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 * Class for UserField
 */
class UserField extends CommonObject
{
    public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;
	}

		/**
	 *  format Système for deplacement import
	 *
	 * @param   array       $arrayrecord        Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @param   array       $listfields         Fields list to add
	 * @param 	int			$record_key         Record key
	 * @return  mixed							Value
	 */
	public function formatSysteme(&$arrayrecord, $listfields, $record_key)
	{
		$value = $arrayrecord[$record_key]['val'];
		
		if($value == 'ANCIEN') {
			return 1;
		}
		elseif($value == 'NK') {
			return 2;
		}
		elseif($value == 'SO') {
			return 3;
		}

		return null;
	}

	/**
	 *  format Date for deplacement import
	 *
	 * @param   array       $arrayrecord        Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @param   array       $listfields         Fields list to add
	 * @param 	int			$record_key         Record key
	 * @return  mixed							Value
	 */
	public function formatDateString(&$arrayrecord, $listfields, $record_key)
	{
		$value = dol_print_date(dol_stringtotime($arrayrecord[$record_key]['val']), '%Y-%m-%d');
		return $value;
	}

	/**
	 *  format Panier for deplacement import
	 *
	 * @param   array       $arrayrecord        Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @param   array       $listfields         Fields list to add
	 * @param 	int			$record_key         Record key
	 * @return  mixed							Value
	 */
	public function formatPanier(&$arrayrecord, $listfields, $record_key)
	{
		$value = $arrayrecord[$record_key]['val'];
		
		if(!empty($value)) {
			return 1;
		}
		else {
			return 0;
		}
	}

	/**
	 *  format Type GD for deplacement import
	 *
	 * @param   array       $arrayrecord        Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @param   array       $listfields         Fields list to add
	 * @param 	int			$record_key         Record key
	 * @return  mixed							Value
	 */
	public function formatTypeGD(&$arrayrecord, $listfields, $record_key)
	{
		$value = $arrayrecord[$record_key]['val'];
		
		if($value == 'REGIONAL') {
			return 1;
		}
		elseif($value == 'GRAND D') {
			return 2;
		}

		return null;
	}

	/**
	 *  format Véhicule for deplacement import
	 *
	 * @param   array       $arrayrecord        Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @param   array       $listfields         Fields list to add
	 * @param 	int			$record_key         Record key
	 * @return  mixed							Value
	 */
	public function formatVehicule(&$arrayrecord, $listfields, $record_key)
	{
		$value = $arrayrecord[$record_key]['val'];
		
		if($value == 'VP') {
			return 1;
		}
		elseif($value == 'VS') {
			return 2;
		}
		elseif($value == 'VF') {
			return 3;
		}

		return null;
	}

	/**
	 *  format Contrat Travail for Contrat import
	 *
	 * @param   array       $arrayrecord        Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @param   array       $listfields         Fields list to add
	 * @param 	int			$record_key         Record key
	 * @return  mixed							Value
	 */
	public function formatContratTravail(&$arrayrecord, $listfields, $record_key)
	{
		$value = $arrayrecord[$record_key]['val'];

		if($value == 'CDI' && $arrayrecord[9]['val'] != 151.67) {
			return 2;
		}
		elseif($value == 'CDI') {
			return 1;
		}
		elseif($value == 'CDD') {
			return 3;
		}
		elseif($value == 'Apprentissage') {
			return 6;
		}

		return null;
	}

	/**
	 *  format College for Contrat import
	 *
	 * @param   array       $arrayrecord        Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @param   array       $listfields         Fields list to add
	 * @param 	int			$record_key         Record key
	 * @return  mixed							Value
	 */
	public function formatCollege(&$arrayrecord, $listfields, $record_key)
	{
		$value = $arrayrecord[$record_key]['val'];
		
		if(!empty($value)) {
			return 3;
		}
		else {
			return 2;
		}
	}

	/**
	 *  format Position for Contrat import
	 *
	 * @param   array       $arrayrecord        Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @param   array       $listfields         Fields list to add
	 * @param 	int			$record_key         Record key
	 * @return  mixed							Value
	 */
	public function formatPositionEtam(&$arrayrecord, $listfields, $record_key)
	{
		$value = $arrayrecord[$record_key]['val'];
		$cadre = (!empty($arrayrecord[8]['val']) ? 1 : 0); 

		if($value == '1.1' && !$cadre) {
			return 1;
		}
		elseif($value == '1.2' && !$cadre) {
			return 2;
		}
		elseif($value == '1.3' && !$cadre) {
			return 3;
		}
		elseif($value == '2.1' && !$cadre) {
			return 4;
		}
		elseif($value == '2.2' && !$cadre) {
			return 5;
		}
		elseif($value == '2.3' && !$cadre) {
			return 6;
		}
		elseif($value == '3.1' && !$cadre) {
			return 7;
		}
		elseif($value == '3.2' && !$cadre) {
			return 8;
		}
		elseif($value == '3.3' && !$cadre) {
			return 9;
		}
		elseif($value == '1.1' && $cadre) {
			return 10;
		}
		elseif($value == '1.2' && $cadre) {
			return 11;
		}
		elseif($value == '2.1' && $cadre) {
			return 12;
		}
		elseif($value == '2.2' && $cadre) {
			return 13;
		}
		elseif($value == '2.3' && $cadre) {
			return 14;
		}
		elseif($value == '3.1' && $cadre) {
			return 15;
		}
		elseif($value == '3.2' && $cadre) {
			return 16;
		}
		elseif($value == '3.3' && $cadre) {
			return 17;
		}
	

		return null;
	}

	/**
	 *  format Coefficient for Contrat import
	 *
	 * @param   array       $arrayrecord        Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @param   array       $listfields         Fields list to add
	 * @param 	int			$record_key         Record key
	 * @return  mixed							Value
	 */
	public function formatCoefficientEtam(&$arrayrecord, $listfields, $record_key)
	{
		$value = $arrayrecord[$record_key]['val'];

		if($value == '230') {
			return 1;
		}
		elseif($value == '240') {
			return 2;
		}
		elseif($value == '250') {
			return 3;
		}
		elseif($value == '275') {
			return 4;
		}
		elseif($value == '310') {
			return 5;
		}
		elseif($value == '355') {
			return 6;
		}
		elseif($value == '400') {
			return 7;
		}
		elseif($value == '450') {
			return 8;
		}
		elseif($value == '500') {
			return 9;
		}
		elseif($value == '95') {
			return 10;
		}
		elseif($value == '100') {
			return 11;
		}
		elseif($value == '105') {
			return 12;
		}
		elseif($value == '115') {
			return 13;
		}
		elseif($value == '130') {
			return 14;
		}
		elseif($value == '150') {
			return 15;
		}
		elseif($value == '170') {
			return 16;
		}
		elseif($value == '210') {
			return 17;
		}
		elseif($value == '270') {
			return 18;
		}

		return null;
	}

	/**
	 *  format Date for Contrat import
	 *
	 * @param   array       $arrayrecord        Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @param   array       $listfields         Fields list to add
	 * @param 	int			$record_key         Record key
	 * @return  mixed							Value
	 */
	public function formatDateExcel(&$arrayrecord, $listfields, $record_key)
	{
		$value = $arrayrecord[$record_key]['val'];
		
		$date = ($value - 25569) * 86400;
		if($date > 0) {
			return dol_print_date($date, '%Y-%m-%d');
		}

		return null;
	}

	/**
	 *  format Horaire Hebdomadaire for Contrat import
	 *
	 * @param   array       $arrayrecord        Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @param   array       $listfields         Fields list to add
	 * @param 	int			$record_key         Record key
	 * @return  mixed							Value
	 */
	public function formatHoraireHebdomadaire(&$arrayrecord, $listfields, $record_key)
	{
		$value = $arrayrecord[$record_key]['val'];
		return ($value != 151.67 ? $value * 0.230769230769231 : null);
	}
}
