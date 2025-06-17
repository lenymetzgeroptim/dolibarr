<?php
/* Copyright (C) 2017  Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * Need to have following variables defined:
 * $object (invoice, order, ...)
 * $action
 * $conf
 * $langs
 *
 * $keyforbreak may be defined to key to switch on second column
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}
if (!is_object($form)) {
	$form = new Form($db);
}



?>
<!-- BEGIN PHP TEMPLATE commonfields_view.tpl.php -->
<?php



$object->fields = dol_sort_array($object->fields, 'position');

$controleClientChecked = isset($object->controleClient) && $object->controleClient == true;
$accordClientChecked = isset($object->accordClient) && $object->accordClient == true;
$infoClientChecked = isset($object->infoClient) && $object->infoClient == true;
$actionChecked = isset($object->actionimmediate) && $object->actionimmediate == true;

if($object->fields['commAccordClient'] && !$accordClientChecked) {
	unset($object->fields['commAccordClient']['visible']);
}else{
	$object->fields['commAccordClient'] = array(
		'type' => 'html',
		'label' => 'Commentaire Accord client',
		'enabled' => '1',
		'position' => 600,
		'notnull' => 0,
		'visible' => 1,
		'cssview' => 'wordbreak',
	);
}
if($object->fields['commControleClient'] && !$controleClientChecked) {
	unset($object->fields['commControleClient']['visible']);
}else{
	$object->fields['commControleClient'] = array(
		'type' => 'html',
		'label' => 'Commentaire Controle client',
		'enabled' => '1',
		'position' => 610,
		'notnull' => 0,
		'visible' => 1,
		'cssview' => 'wordbreak',
	);
}
if($object->fields['commInfoClient'] && !$infoClientChecked) {
	unset($object->fields['commInfoClient']['visible']);
}else{
	$object->fields['commInfoClient'] = array(
		'type' => 'html',
		'label' => 'Commentaire Info client',
		'enabled' => '1',
		'position' => 578,
		'notnull' => 0,
		'visible' => 1,
		'cssview' => 'wordbreak',
	);
}

if($object->fields['actionimmediatecom'] && !$actionChecked) {
	unset($object->fields['actionimmediatecom']['visible']);
	
}else{
	$object->fields['actionimmediatecom'] = array(
		'type' => 'html',
		'label' => 'Action immédiate commentaire',
		'enabled' => '1',
		'position' => 562,
		'notnull' => 0,
		'visible' => 1,
		'cssview' => 'wordbreak',
	);
}

if(empty($object->description)) {
    unset($object->fields['description']);
    
}else{
    $object->fields['description'] = array(
        'type' => 'html',
        'label' => 'Commentaire émetteur',
        'enabled' => '1',
        'position' => 611,
        'notnull' => 0,
        'visible' => 1,
		'cssview' => 'wordbreak',
    );
}

if(empty($object->commRespAff)) {
    unset($object->fields['commRespAff']);
    
}else{
    $object->fields['commRespAff'] = array(
        'type' => 'html',
        'label' => 'Commentaire Responsable Affaire',
        'enabled' => '1',
        'position' => 612,
        'notnull' => 0,
        'visible' => 1,
		'cssview' => 'wordbreak',
    );
}

if(empty($object->commRespQ3)) {
    unset($object->fields['commRespQ3']);
    
}else{
    $object->fields['commRespQ3'] = array(
        'type' => 'html',
        'label' => 'Commentaire Responsable Q3SE',
        'enabled' => '1',
        'position' => 613,
        'notnull' => 0,
        'visible' => 1,
		'cssview' => 'wordbreak',
    );
}

if(empty($object->commServQ3)) {
    unset($object->fields['commServQ3']);
    
}else{
    $object->fields['commServQ3'] = array(
        'type' => 'html',
        'label' => 'Commentaire Service Q3SE',
        'enabled' => '1',
        'position' => 614,
        'notnull' => 0,
        'visible' => 1,
		'cssview' => 'wordbreak',
    );
}

if(empty($object->analyseCauseRacine)) {
    unset($object->fields['analyseCauseRacine']);
    
}else{
    $object->fields['analyseCauseRacine'] = array(
        'type' => 'html',
        'label' => 'Analyse des causes racines',
        'enabled' => '1',
        'position' => 576,
        'notnull' => 0,
        'visible' => 1,
		'cssview' => 'wordbreak',
    );
}

if(empty($object->coutTotal)) {
    unset($object->fields['coutTotal']);
    
}else{
    $object->fields['coutTotal'] = array(
        'type' => 'html',
        'label' => 'Coût Total',
        'enabled' => '1',
        'position' => 550,
        'notnull' => 0,
        'visible' => 1,
		'cssview' => 'wordbreak',
    );
}

if(empty($object->dateCloture)) {
    unset($object->fields['dateCloture']);
    
}else{
    $object->fields['dateCloture'] = array(
        'type' => 'date',
        'label' => 'Date cloture',
        'enabled' => '1',
        'position' => 561,
        'notnull' => 0,
        'visible' => 1,
		'cssview' => 'wordbreak',
    );
}


foreach ($object->fields as $key => $val) {

	if ($key == 'rowid') {
		continue;
	}
	

if ($key == 'status') {
		continue;
	}


	if ($key == 'actionimmediate') {
        include DOL_DOCUMENT_ROOT.'/custom/constat/tpl/extrafields_view.tpl.php';
    }
	
	if (!empty($keyforbreak) && $key == $keyforbreak) {
		break; // key used for break on second column

	}
	


	$value = $object->$key;
	
	print '<tr class="field_'.$key.'"><td';
	print ' class="'.(empty($val['tdcss']) ? 'titlefield' : $val['tdcss']).' fieldname_'.$key;
	//if ($val['notnull'] > 0) print ' fieldrequired';     // No fieldrequired on the view output
	if ($val['type'] == 'text' || $val['type'] == 'html') {
		print ' tdtop';
	}
	print '">';

	$labeltoshow = '';
	if (!empty($val['help'])) {
		$labeltoshow .= $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
	} else {
		if (isset($val['copytoclipboard']) && $val['copytoclipboard'] == 1) {
			$labeltoshow .= showValueWithClipboardCPButton($value, 0, $langs->transnoentitiesnoconv($val['label']));
		} else {
			$labeltoshow .= $langs->trans($val['label']);
		}
	}
	if (empty($val['alwayseditable'])) {
		print $labeltoshow;
	} else {
		print $form->editfieldkey($labeltoshow, $key, $value, $object, 1, $val['type']);
	}
	print '</td>';
	print '<td class="valuefield fieldname_'.$key;
	if ($val['type'] == 'text') {
		print ' wordbreak';
	}
	if (!empty($val['cssview'])) {
		print ' '.$val['cssview'];
	}
	print '">';

	if (empty($val['alwayseditable'])) {
		if (preg_match('/^(text|html)/', $val['type'])) {
			print '<div class="longmessagecut">';
		}
		if ($key == 'lang') {
			$langs->load("languages");
			$labellang = ($value ? $langs->trans('Language_'.$value) : '');
			print picto_from_langcode($value, 'class="paddingrightonly saturatemedium opacitylow"');
			print $labellang;
		} else {
			if (isset($val['copytoclipboard']) && $val['copytoclipboard'] == 2) {
				$out = $object->showOutputField($val, $key, $value, '', '', '', 0);
				print showValueWithClipboardCPButton($out, 0, $out);
			} else {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
			}
		}
		//print dol_escape_htmltag($object->$key, 1, 1);
		if (preg_match('/^(text|html)/', $val['type'])) {
			print '</div>';
		}
	} else {
		print $form->editfieldval($labeltoshow, $key, $value, $object, 1, $val['type']);
	}
	print '</td>';
	print '</tr>';
}

print '</table>';

// We close div and reopen for second column
print '</div>';

$rightpart = '';
$alreadyoutput = 1;
foreach ($object->fields as $key => $val) {

    if ($alreadyoutput) {
        if (!empty($keyforbreak) && $key == $keyforbreak) {
            $alreadyoutput = 0; // key used for break on second column
        } else {
            continue;
        }
    }


	/*$value = $object->$key;

    // Si la valeur est vide, passez à la prochaine itération
    // Si le type est 'html' et la valeur est vide, passez également à la prochaine itération
    if (empty($value) || ($val['type'] == 'html' && trim(strip_tags($value)) == '')) {
        continue;
    }*/


    // Si la valeur est vide et que le type n'est pas 'html', passez à la prochaine itération
    if (empty($value) && $val['type'] != 'html') {
        continue;
    }
	
	$value = $object->$key;

	// Discard if extrafield is a hidden field on form
	if (abs($val['visible']) != 1 && abs($val['visible']) != 3 && abs($val['visible']) != 4 && abs($val['visible']) != 5) {
		continue;
	}

	if (array_key_exists('enabled', $val) && isset($val['enabled']) && !$val['enabled']) {
		continue; // We don't want this field
	}
	if (in_array($key, array('ref', 'status'))) {
		continue; // Ref and status are already in dol_banner
	}

	$rightpart .= '<tr><td';
	$rightpart .= ' class="'.(empty($val['tdcss']) ? 'titlefield' : $val['tdcss']).'  fieldname_'.$key;
	//if ($val['notnull'] > 0) $rightpart .= ' fieldrequired';		// No fieldrequired inthe view output
	if ($val['type'] == 'text' || $val['type'] == 'html') {
		$rightpart .= ' tdtop';
	}
	$rightpart.= '">';
	$labeltoshow = '';
	if (!empty($val['help'])) {
		$labeltoshow .= $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
	} else {
		if (isset($val['copytoclipboard']) && $val['copytoclipboard'] == 1) {
			$labeltoshow .= showValueWithClipboardCPButton($value, 0, $langs->transnoentitiesnoconv($val['label']));
		} else {
			$labeltoshow .= $langs->trans($val['label']);
		}
	}
	if (empty($val['alwayseditable'])) {
		$rightpart .= $labeltoshow;
	} else {
		$rightpart .= $form->editfieldkey($labeltoshow, $key, $value, $object, 1, $val['type']);
	}
	$rightpart .= '</td>';
	$rightpart .= '<td class="valuefield fieldname_'.$key;
	if ($val['type'] == 'text') {
		$rightpart .= ' wordbreak';
	}
	if (!empty($val['cssview'])) {
		$rightpart .= ' '.$val['cssview'];
	}
	$rightpart .= '">';

	if (empty($val['alwayseditable'])) {
		if (preg_match('/^(text|html)/', $val['type'])) {
			$rightpart .= '<div class="longmessagecut">';
		}
		if ($key == 'lang') {
			$langs->load("languages");
			$labellang = ($value ? $langs->trans('Language_'.$value) : '');
			$rightpart .= picto_from_langcode($value, 'class="paddingrightonly saturatemedium opacitylow"');
			$rightpart .= $labellang;
		} else {
			if (isset($val['copytoclipboard']) && $val['copytoclipboard'] == 2) {
				$out = $object->showOutputField($val, $key, $value, '', '', '', 0);
				$rightpart .= showValueWithClipboardCPButton($out, 0, $out);
			} else {
				$rightpart.= $object->showOutputField($val, $key, $value, '', '', '', 0);
			}
		}
		if (preg_match('/^(text|html)/', $val['type'])) {
			$rightpart .= '</div>';
		}
	} else {
		$rightpart .= $form->editfieldval($labeltoshow, $key, $value, $object, 1, $val['type']);
	}

	$rightpart .= '</td>';
	$rightpart .= '</tr>';
}


print '<div class="fichehalfright">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border centpercent tableforfield">';

print $rightpart;

?>


<!-- END PHP TEMPLATE commonfields_view.tpl.php -->
