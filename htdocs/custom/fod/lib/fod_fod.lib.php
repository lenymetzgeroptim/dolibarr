<?php
/* Copyright (C) 2021 Lény Metzger  <leny-07@hotmail.fr>
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
 * \file    lib/fod_fod.lib.php
 * \ingroup fod
 * \brief   Library files with common functions for Fod
 */

/**
 * Prepare array of tabs for Fod
 *
 * @param	Fod	$object		Fod
 * @return 	array					Array of tabs
 */
function fodPrepareHead($object)
{
	global $db, $langs, $conf, $user;

	$langs->load("fod@fod");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/fod/fod_card.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	if($object->fk_user_rsr == $user->id || $object->fk_user_pcr == $user->id || $object->fk_user_raf == $user->id || $user->rights->fod->fod->readAll || $user->admin){
		$head[$h][0] = dol_buildpath("/fod/fod_historique.php", 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans("Historique");
		$head[$h][2] = 'historique';
		$h++;
	}

	if($object->status == $object::STATUS_BILAN || $object->status == $object::STATUS_BILANinter || $object->status == $object::STATUS_BILANRSR || $object->status == $object::STATUS_BILANRSRRA || $object->status == $object::STATUS_BILANRSRRAPCR || $object->status == $object::STATUS_CLOTURE || $object->status == $object::STATUS_BILAN_REFUS) {
		$head[$h][0] = dol_buildpath("/fod/fod_bilan.php", 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans("Bilan");
		$head[$h][2] = 'bilan';
		$h++;
	}

	if($object->fk_user_rsr == $user->id || $object->fk_user_pcr == $user->id || $object->fk_user_raf == $user->id || $user->rights->fod->fod->readAll || $user->admin){
		if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
			$nbNote = 0;
			if (!empty($object->note_private)) {
				$nbNote++;
			}
			if (!empty($object->note_public)) {
				$nbNote++;
			}
			$head[$h][0] = dol_buildpath('/fod/fod_note.php', 1).'?id='.$object->id;
			$head[$h][1] = $langs->trans('Notes');
			if ($nbNote > 0) {
				$head[$h][1] .= (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '<span class="badge marginleftonlyshort">'.$nbNote.'</span>' : '');
			}
			$head[$h][2] = 'note';
			$h++;
		}
	}

	//if($object->fk_user_rsr == $user->id || $object->fk_user_pcr == $user->id || $object->fk_user_raf == $user->id || $user->rights->fod->fod->readAll || $user->admin){
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
		$upload_dir = $conf->fod->dir_output."/fod/".dol_sanitizeFileName($object->ref);
		$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
		$nbLinks = Link::count($db, $object->element, $object->id);
		$head[$h][0] = dol_buildpath("/fod/fod_document.php", 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans('Documents');
		if (($nbFiles + $nbLinks) > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
		}
		$head[$h][2] = 'document';
		$h++;
	//}

	/*$head[$h][0] = dol_buildpath("/fod/fod_agenda.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Events");
	$head[$h][2] = 'agenda';
	$h++;*/

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@fod:/fod/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@fod:/fod/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'fod@fod');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'fod@fod', 'remove');

	return $head;
}
