<?php

require_once DOL_DOCUMENT_ROOT.'/custom/constat/class/constat.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

class actionsForm extends Form 
{
    /**
	 *  Show linked object block.
	 *
	 *  @param	CommonObject	$object		      Object we want to show links to
	 *  @param  string          $morehtmlright    More html to show on right of title
	 *  @param  array           $compatibleImportElementsList  Array of compatibles elements object for "import from" action
	 *  @return	int							      <0 if KO, >=0 if OK
	 */
	public function showLinkedObjectBlock($object, $morehtmlright = '', $compatibleImportElementsList = false)
	{
		global $conf, $langs, $hookmanager;
		global $bc, $action;

		// $object->fetchObjectLinked();
        $object->fetchObjectLinked2();
		

		// Bypass the default method
		$hookmanager->initHooks(array('commonobject'));
		$parameters = array(
			'morehtmlright' => $morehtmlright,
			'compatibleImportElementsList' => &$compatibleImportElementsList,
		);
		$reshook = $hookmanager->executeHooks('showLinkedObjectBlock', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

		if (empty($reshook)) {
			$nbofdifferenttypes = count($object->linkedObjects);

			print '<!-- showLinkedObjectBlock -->';
			print load_fiche_titre($langs->trans('RelatedObjects'), $morehtmlright, '', 0, 0, 'showlinkedobjectblock');


			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder allwidth" data-block="showLinkedObject" data-element="'.$object->element.'"  data-elementid="'.$object->id.'"   >';

			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Type").'</td>';
			print '<td>'.$langs->trans("Ref").'</td>';
			print '<td class="center"></td>';
			print '<td class="center">'.$langs->trans("Date").'</td>';
			print '<td class="right">'.$langs->trans("Libellé").'</td>';
			print '<td class="right">'.$langs->trans("Status").'</td>';
			print '<td></td>';
			print '</tr>';

			$nboftypesoutput = 0;
			
			foreach ($object->linkedObjects as $objecttype => $objects) {
				$tplpath = $element = $subelement = $objecttype;

				// to display inport button on tpl
				$showImportButton = false;
				if (!empty($compatibleImportElementsList) && in_array($element, $compatibleImportElementsList)) {
					$showImportButton = true;
				}

				$regs = array();
				if ($objecttype != 'supplier_proposal' && preg_match('/^([^_]+)_([^_]+)/i', $objecttype, $regs)) {
					$element = $regs[1];
					$subelement = $regs[2];
					$tplpath = $element.'/'.$subelement;
				}
				$tplname = 'linkedobjectblock';

				// To work with non standard path
				if ($objecttype == 'facture') {
					$tplpath = 'compta/'.$element;
					if (empty($conf->facture->enabled)) {
						continue; // Do not show if module disabled
					}
				} elseif ($objecttype == 'facturerec') {
					$tplpath = 'compta/facture';
					$tplname = 'linkedobjectblockForRec';
					if (empty($conf->facture->enabled)) {
						continue; // Do not show if module disabled
					}
				} elseif ($objecttype == 'propal') {
					$tplpath = 'comm/'.$element;
					if (empty($conf->propal->enabled)) {
						continue; // Do not show if module disabled
					}
				} elseif ($objecttype == 'supplier_proposal') {
					if (empty($conf->supplier_proposal->enabled)) {
						continue; // Do not show if module disabled
					}
				} elseif ($objecttype == 'shipping' || $objecttype == 'shipment') {
					$tplpath = 'expedition';
					if (empty($conf->expedition->enabled)) {
						continue; // Do not show if module disabled
					}
				} elseif ($objecttype == 'reception') {
					$tplpath = 'reception';
					if (empty($conf->reception->enabled)) {
						continue; // Do not show if module disabled
					}
				} elseif ($objecttype == 'delivery') {
					$tplpath = 'delivery';
					if (empty($conf->expedition->enabled)) {
						continue; // Do not show if module disabled
					}
				} elseif ($objecttype == 'mo') {
					$tplpath = 'mrp/mo';
					if (empty($conf->mrp->enabled)) {
						continue; // Do not show if module disabled
					}
				} elseif ($objecttype == 'ficheinter') {
					$tplpath = 'fichinter';
					if (empty($conf->ficheinter->enabled)) {
						continue; // Do not show if module disabled
					}
                } elseif ($objecttype == 'constat') {
                        $tplpath = 'custom/'.$element;
                        if (empty($conf->constat->enabled)) {
                            continue; // Do not show if module disabled
                        }
                }elseif ($objecttype == 'action') {
                    $tplpath = 'custom/'.$element;
                    if (empty($conf->action->enabled)) {
                        continue; // Do not show if module disabled
                    }
                }elseif ($objecttype == 'actions_action') {
                    $tplpath = 'custom/'.$element;
                    if (empty($conf->propal->enabled)) {
                        continue; // Do not show if module disabled
                    }
				} elseif ($objecttype == 'invoice_supplier') {
					$tplpath = 'fourn/facture';
				} elseif ($objecttype == 'order_supplier') {
					$tplpath = 'fourn/commande';
				} elseif ($objecttype == 'expensereport') {
					$tplpath = 'expensereport';
				} elseif ($objecttype == 'subscription') {
					$tplpath = 'adherents';
				} elseif ($objecttype == 'conferenceorbooth') {
					$tplpath = 'eventorganization';
				} elseif ($objecttype == 'conferenceorboothattendee') {
					$tplpath = 'eventorganization';
                } elseif ($objecttype == 'actions_action') {
					$tplpath = 'custom/actions/core';
				} elseif ($objecttype == 'mo') {
					$tplpath = 'mrp';
					if (empty($conf->mrp->enabled)) {
						continue; // Do not show if module disabled
					}
				}

				global $linkedObjectBlock;
				$linkedObjectBlock = $objects;

				// Output template part (modules that overwrite templates must declare this into descriptor)
				$dirtpls = array_merge($conf->modules_parts['tpl'], array('/'.$tplpath.'/tpl'));
               
				foreach ($dirtpls as $reldir) {
					if ($nboftypesoutput == ($nbofdifferenttypes - 1)) {    // No more type to show after
						global $noMoreLinkedObjectBlockAfter;
						$noMoreLinkedObjectBlockAfter = 1;
					}

					// $res = @include dol_buildpath($reldir.'/'.$tplname.'.tpl.php');
                     $res = @include DOL_DOCUMENT_ROOT.'/custom/constat/tpl/linkedobjectblock.tpl.php';
               
					if ($res) {
						$nboftypesoutput++;
						break;
					}
				}
			}

			if (!$nboftypesoutput) {
				print '<tr><td class="impair" colspan="7"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
			}

			print '</table>';

			if (!empty($compatibleImportElementsList)) {
				$res = @include dol_buildpath('core/tpl/ajax/objectlinked_lineimport.tpl.php');
			}


			print '</div>';

			return $nbofdifferenttypes;
		}
	}
}


