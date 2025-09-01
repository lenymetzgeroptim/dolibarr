<?php
/* Copyright (C) 2006-2017	Laurent Destailleur 	<eldy@users.sourceforge.net>
 * Copyright (C) 2006		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2007		Patrick Raguin      	<patrick.raguin@gmail.com>
 * Copyright (C) 2010-2012	Regis Houssin       	<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2017	Juanjo Menent       	<jmenent@2byte.es>
 * Copyright (C) 2012		Christophe Battarel		<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2015  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014		Cedric GROSS			<c.gross@kreiz-it.fr>
 * Copyright (C) 2014		Teddy Andreotti			<125155@supinfo.com>
 * Copyright (C) 2015-2016  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2019       Lenin Rivas           	<lenin.rivas@servcom-it.com>
 * Copyright (C) 2020       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2021-2022	Anthony Berton       	<bertonanthony@gmail.com>
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
 * or see https://www.gnu.org/
 */

/**
 *	\file       htdocs/core/lib/pdf.lib.php
 *	\brief      Set of functions used for PDF generation
 *	\ingroup    core
 */

// include_once DOL_DOCUMENT_ROOT.'/core/lib/signature.lib.php';


/**
 *	Show linked objects for PDF generation
 *
 *	@param	TCPDF			$pdf				Object PDF
 *	@param	object		$object				Object
 *	@param  Translate	$outputlangs		Object lang
 *	@param  int			$posx				X
 *	@param  int			$posy				Y
 *	@param	float		$w					Width of cells. If 0, they extend up to the right margin of the page.
 *	@param	float		$h					Cell minimum height. The cell extends automatically if needed.
 *	@param	int			$align				Align
 *	@param	string		$default_font_size	Font size
 *	@return	float                           The Y PDF position
 */
function custom_pdf_writeLinkedObjects(&$pdf, $object, $outputlangs, $posx, $posy, $w, $h, $align, $default_font_size)
{
	$linkedobjects = pdf_getLinkedObjects2($object, $outputlangs);
	if (!empty($linkedobjects)) {
		foreach ($linkedobjects as $linkedobject) {
			$reftoshow = $linkedobject["ref_title"].' : '.$linkedobject["ref_value"];
			if (!empty($linkedobject["date_value"])) {
				$reftoshow .= ' / '.$linkedobject["date_value"];
			}

			$posy += 3;
			$pdf->SetXY($posx, $posy);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->MultiCell($w, $h, $reftoshow, '', $align);
		}
	}

	return $pdf->getY();
}


/**
 * 	Return linked objects to use for document generation.
 *  Warning: To save space, this function returns only one link per link type (all links are concated on same record string). This function is used by pdf_writeLinkedObjects
 *
 * 	@param	object		$object			Object
 * 	@param	Translate	$outputlangs	Object lang for output
 * 	@return	array                       Linked objects
 */
function pdf_getLinkedObjects2(&$object, $outputlangs)
{
	global $db, $hookmanager;

	$linkedobjects = array();

	$object->fetchObjectLinked();

	foreach ($object->linkedObjects as $objecttype => $objects) {
		if ($objecttype == 'facture') {
			// For invoice, we don't want to have a reference line on document. Image we are using recuring invoice, we will have a line longer than document width.
		} elseif ($objecttype == 'propal' || $objecttype == 'supplier_proposal') {
			$outputlangs->load('propal');

			foreach ($objects as $elementobject) {
				$linkedobjects[$objecttype]['ref_title'] = $outputlangs->transnoentities("RefProposal");
				$linkedobjects[$objecttype]['ref_value'] = $outputlangs->transnoentities($elementobject->ref);
				$linkedobjects[$objecttype]['date_title'] = $outputlangs->transnoentities("DatePropal");
				$linkedobjects[$objecttype]['date_value'] = dol_print_date($elementobject->date, 'day', '', $outputlangs);
			}
		} elseif ($objecttype == 'commande' || $objecttype == 'supplier_order') {
			$outputlangs->load('orders');

			if (count($objects) > 1 && count($objects) <= (getDolGlobalInt("MAXREFONDOC") ? getDolGlobalInt("MAXREFONDOC") : 10)) {
				$object->note_public = dol_concatdesc($object->note_public, '<br>'.$outputlangs->transnoentities("RefOrder").' : <br>');
				foreach ($objects as $elementobject) {
					$object->note_public = dol_concatdesc($object->note_public, $outputlangs->transnoentities($elementobject->ref_client));
					//dol_concatdesc($object->note_public, $outputlangs->transnoentities($elementobject->ref));
					// ($elementobject->ref_client ? ' ('.$elementobject->ref_client.')' : '').($elementobject->ref_supplier ? ' ('.$elementobject->ref_supplier.')' : '').' ');
					// $object->note_public = dol_concatdesc($object->note_public, $outputlangs->transnoentities("OrderDate").' : '.dol_print_date($elementobject->date, 'day', '', $outputlangs).'<br>');
				}
			} elseif (count($objects) == 1) {
				$elementobject = array_shift($objects);
				// $linkedobjects[$objecttype]['ref_title'] = $outputlangs->transnoentities("RefOrder");
				$linkedobjects[$objecttype]['ref_title'] = $outputlangs->transnoentities("RefOrder");
				$linkedobjects[$objecttype]['ref_value'] = $outputlangs->transnoentities($elementobject->ref_client);
				// $linkedobjects[$objecttype]['ref_value'] = ($elementobject->ref_client ? ' ('.$elementobject->ref_client.')' : '').($elementobject->ref_supplier ? ' ('.$elementobject->ref_supplier.')' : '');
				// $linkedobjects[$objecttype]['ref_value'] = $outputlangs->transnoentities($elementobject->ref);
				// .($elementobject->ref_client ? ' ('.$elementobject->ref_client.')' : '').($elementobject->ref_supplier ? ' ('.$elementobject->ref_supplier.')' : '');
				// $linkedobjects[$objecttype]['date_title'] = $outputlangs->transnoentities("OrderDate");
				// $linkedobjects[$objecttype]['date_value'] = dol_print_date($elementobject->date, 'day', '', $outputlangs);
			}
		} elseif ($objecttype == 'contrat') {
			$outputlangs->load('contracts');
			foreach ($objects as $elementobject) {
				$linkedobjects[$objecttype]['ref_title'] = $outputlangs->transnoentities("RefContract");
				$linkedobjects[$objecttype]['ref_value'] = $outputlangs->transnoentities($elementobject->ref_customer);
				// $linkedobjects[$objecttype]['date_title'] = $outputlangs->transnoentities("DateContract");
				// $linkedobjects[$objecttype]['date_value'] = dol_print_date($elementobject->date_contrat, 'day', '', $outputlangs);
			}
		} elseif ($objecttype == 'fichinter') {
			$outputlangs->load('interventions');
			foreach ($objects as $elementobject) {
				$linkedobjects[$objecttype]['ref_title'] = $outputlangs->transnoentities("InterRef");
				$linkedobjects[$objecttype]['ref_value'] = $outputlangs->transnoentities($elementobject->ref);
				$linkedobjects[$objecttype]['date_title'] = $outputlangs->transnoentities("InterDate");
				$linkedobjects[$objecttype]['date_value'] = dol_print_date($elementobject->datec, 'day', '', $outputlangs);
			}
		} elseif ($objecttype == 'shipping') {
			$outputlangs->loadLangs(array("orders", "sendings"));

			if (count($objects) > 1) {
				$order = null;
				if (empty($object->linkedObjects['commande']) && $object->element != 'commande') {
					$object->note_public = dol_concatdesc($object->note_public, '<br>'.$outputlangs->transnoentities("RefOrder").' / '.$outputlangs->transnoentities("RefSending").' : <br>');
				} else {
					$object->note_public = dol_concatdesc($object->note_public, '<br>'.$outputlangs->transnoentities("RefSending").' : <br>');
				}
				// We concat this record info into fields xxx_value. title is overwrote.
				foreach ($objects as $elementobject) {
					if (empty($object->linkedObjects['commande']) && $object->element != 'commande') {    // There is not already a link to order and object is not the order, so we show also info with order
						$elementobject->fetchObjectLinked(null, '', null, '', 'OR', 1, 'sourcetype', 0);
						if (! empty($elementobject->linkedObjectsIds['commande'])) {
							include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
							$order = new Commande($db);
							$ret = $order->fetch(reset($elementobject->linkedObjectsIds['commande']));
							if ($ret < 1) {
								$order = null;
							}
						}
					}

					if (! is_object($order)) {
						$object->note_public = dol_concatdesc($object->note_public, $outputlangs->transnoentities($elementobject->ref));
						$object->note_public = dol_concatdesc($object->note_public, '<br>');
					} else {
						$object->note_public = dol_concatdesc($object->note_public, $outputlangs->convToOutputCharset($order->ref).($order->ref_client ? ' ('.$order->ref_client.')' : ''));
						$object->note_public = dol_concatdesc($object->note_public, ' / '.$outputlangs->transnoentities($elementobject->ref));
						$object->note_public = dol_concatdesc($object->note_public, '<br>');
					}
				}
			} elseif (count($objects) == 1) {
				$elementobject = array_shift($objects);
				$order = null;
				// We concat this record info into fields xxx_value. title is overwrote.
				if (empty($object->linkedObjects['commande']) && $object->element != 'commande') {    // There is not already a link to order and object is not the order, so we show also info with order
					$elementobject->fetchObjectLinked(null, '', null, '', 'OR', 1, 'sourcetype', 0);
					if (! empty($elementobject->linkedObjectsIds['commande'])) {
						include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
						$order = new Commande($db);
						$ret = $order->fetch(reset($elementobject->linkedObjectsIds['commande']));
						if ($ret < 1) {
							$order = null;
						}
					}
				}

				if (! is_object($order)) {
					$linkedobjects[$objecttype]['ref_title'] = $outputlangs->transnoentities("RefSending");
					if (! empty($linkedobjects[$objecttype]['ref_value'])) $linkedobjects[$objecttype]['ref_value'] .= ' / ';
					$linkedobjects[$objecttype]['ref_value'] .= $outputlangs->transnoentities($elementobject->ref);
				} else {
					$linkedobjects[$objecttype]['ref_title'] = $outputlangs->transnoentities("RefOrder").' / '.$outputlangs->transnoentities("RefSending");
					if (empty($linkedobjects[$objecttype]['ref_value'])) $linkedobjects[$objecttype]['ref_value'] = $outputlangs->convToOutputCharset($order->ref).($order->ref_client ? ' ('.$order->ref_client.')' : '');
					$linkedobjects[$objecttype]['ref_value'] .= ' / '.$outputlangs->transnoentities($elementobject->ref);
				}
			}
		}
	}

	// For add external linked objects
	if (is_object($hookmanager)) {
		$parameters = array('linkedobjects' => $linkedobjects, 'outputlangs'=>$outputlangs);
		$action = '';
		$hookmanager->executeHooks('pdf_getLinkedObjects', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
		if (!empty($hookmanager->resArray)) {
			$linkedobjects = $hookmanager->resArray;
		}
	}

	return $linkedobjects;
}


