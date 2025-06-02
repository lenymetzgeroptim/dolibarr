<?php
/* Copyright (C) 2024	Soufiane Fadel 	<s.fadel@optim-industries.fr>
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


// /**
//  *   	Return a string with full address formated for output on documents
//  *
//  * 		@param	Translate	          $outputlangs		    Output langs object
//  *   	@param  Societe		          $sourcecompany		Source company object
//  *   	@param  Societe|string|null   $targetcompany		Target company object
//  *      @param  Contact|string|null	  $targetcontact	    Target contact object
//  * 		@param	int			          $usecontact		    Use contact instead of company
//  * 		@param	string  	          $mode				    Address type ('source', 'target', 'targetwithdetails', 'targetwithdetails_xxx': target but include also phone/fax/email/url)
//  *      @param  Object                $object               Object we want to build document for
//  * 		@return	string					    		        String with full address
//  */
// function pdf_build_address_cv($outputlangs, $sourcecompany, $targetcompany = '', $targetcontact = '', $usecontact = 0, $mode = 'source', $object = null)
// {
// 	global $conf, $hookmanager, $db;

// 		$targetcompany = new User($db);
// 		$targetcompany->fetch($sourcecompany->fk_user);

// 	if ($mode == 'source' && !is_object($sourcecompany)) {
// 		return -1;
// 	}
// 	if ($mode == 'target' && !is_object($targetcompany)) {
// 		return -1;
// 	}

// 	if (!empty($sourcecompany->state_id) && empty($sourcecompany->state)) {
// 		$sourcecompany->state = getState($sourcecompany->state_id);
// 	}
// 	if (!empty($targetcompany->state_id) && empty($targetcompany->state)) {
// 		$targetcompany->state = getState($targetcompany->state_id);
// 	}

// 	$reshook = 0;
// 	$stringaddress = '';
// 	if (is_object($hookmanager)) {
// 		$parameters = array('sourcecompany' => &$sourcecompany, 'targetcompany' => &$targetcompany, 'targetcontact' => &$targetcontact, 'outputlangs' => $outputlangs, 'mode' => $mode, 'usecontact' => $usecontact);
// 		$action = '';
// 		$reshook = $hookmanager->executeHooks('pdf_build_address', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
// 		$stringaddress .= $hookmanager->resPrint;
// 	}
// 	if (empty($reshook)) {
// 		if ($mode == 'source') {
// 			$withCountry = 0;
// 			if (isset($targetcompany->country_code) && !empty($sourcecompany->country_code) && ($targetcompany->country_code != $sourcecompany->country_code)) {
// 				$withCountry = 1;
// 			}

// 			$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset(dol_format_address($sourcecompany, $withCountry, "\n", $outputlangs))."\n";

// 			if (empty($conf->global->MAIN_PDF_DISABLESOURCEDETAILS)) {
// 				// Phone
// 				if ($sourcecompany->phone) {
// 					$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("PhoneShort").": ".$outputlangs->convToOutputCharset($sourcecompany->phone);
// 				}
// 				// Fax
// 				if ($sourcecompany->fax) {
// 					$stringaddress .= ($stringaddress ? ($sourcecompany->phone ? " - " : "\n") : '').$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($sourcecompany->fax);
// 				}
// 				// EMail
// 				if ($sourcecompany->email) {
// 					$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($sourcecompany->email);
// 				}
// 				// Web
// 				if ($sourcecompany->url) {
// 					$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($sourcecompany->url);
// 				}
// 			}
// 			// Intra VAT
// 			if (!empty($conf->global->MAIN_TVAINTRA_IN_SOURCE_ADDRESS)) {
// 				if ($sourcecompany->tva_intra) {
// 					$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("VATIntraShort").': '.$outputlangs->convToOutputCharset($sourcecompany->tva_intra);
// 				}
// 			}
// 			// Professionnal Ids
// 			$reg = array();
// 			if (!empty($conf->global->MAIN_PROFID1_IN_SOURCE_ADDRESS) && !empty($sourcecompany->idprof1)) {
// 				$tmp = $outputlangs->transcountrynoentities("ProfId1", $sourcecompany->country_code);
// 				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
// 					$tmp = $reg[1];
// 				}
// 				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof1);
// 			}
// 			if (!empty($conf->global->MAIN_PROFID2_IN_SOURCE_ADDRESS) && !empty($sourcecompany->idprof2)) {
// 				$tmp = $outputlangs->transcountrynoentities("ProfId2", $sourcecompany->country_code);
// 				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
// 					$tmp = $reg[1];
// 				}
// 				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof2);
// 			}
// 			if (!empty($conf->global->MAIN_PROFID3_IN_SOURCE_ADDRESS) && !empty($sourcecompany->idprof3)) {
// 				$tmp = $outputlangs->transcountrynoentities("ProfId3", $sourcecompany->country_code);
// 				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
// 					$tmp = $reg[1];
// 				}
// 				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof3);
// 			}
// 			if (!empty($conf->global->MAIN_PROFID4_IN_SOURCE_ADDRESS) && !empty($sourcecompany->idprof4)) {
// 				$tmp = $outputlangs->transcountrynoentities("ProfId4", $sourcecompany->country_code);
// 				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
// 					$tmp = $reg[1];
// 				}
// 				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof4);
// 			}
// 			if (!empty($conf->global->MAIN_PROFID5_IN_SOURCE_ADDRESS) && !empty($sourcecompany->idprof5)) {
// 				$tmp = $outputlangs->transcountrynoentities("ProfId5", $sourcecompany->country_code);
// 				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
// 					$tmp = $reg[1];
// 				}
// 				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof5);
// 			}
// 			if (!empty($conf->global->MAIN_PROFID6_IN_SOURCE_ADDRESS) && !empty($sourcecompany->idprof6)) {
// 				$tmp = $outputlangs->transcountrynoentities("ProfId6", $sourcecompany->country_code);
// 				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
// 					$tmp = $reg[1];
// 				}
// 				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof6);
// 			}
// 			if (!empty($conf->global->PDF_ADD_MORE_AFTER_SOURCE_ADDRESS)) {
// 				$stringaddress .= ($stringaddress ? "\n" : '').$conf->global->PDF_ADD_MORE_AFTER_SOURCE_ADDRESS;
// 			}
// 		}

// 		if ($mode == 'target' || preg_match('/targetwithdetails/', $mode)) {
// 			if ($usecontact) {
// 				if (is_object($targetcontact)) {
// 					$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset($targetcontact->getFullName($outputlangs, 1));

// 					if (!empty($targetcontact->address)) {
// 						$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset(dol_format_address($targetcontact))."\n";
// 					} else {
// 						$companytouseforaddress = $targetcompany;

// 						// Contact on a thirdparty that is a different thirdparty than the thirdparty of object
// 						if ($targetcontact->socid > 0 && $targetcontact->socid != $targetcompany->id) {
// 							$targetcontact->fetch_thirdparty();
// 							$companytouseforaddress = $targetcontact->thirdparty;
// 						}

// 						$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset(dol_format_address($companytouseforaddress))."\n";
// 					}
// 					// Country
// 					if (!empty($targetcontact->country_code) && $targetcontact->country_code != $sourcecompany->country_code) {
// 						$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcontact->country_code));
// 					} elseif (empty($targetcontact->country_code) && !empty($targetcompany->country_code) && ($targetcompany->country_code != $sourcecompany->country_code)) {
// 						$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcompany->country_code));
// 					}

// 					if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || preg_match('/targetwithdetails/', $mode)) {
// 						// Phone
// 						if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_phone/', $mode)) {
// 							if (!empty($targetcontact->phone_pro) || !empty($targetcontact->phone_mobile)) {
// 								$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Phone").": ";
// 							}
// 							if (!empty($targetcontact->phone_pro)) {
// 								$stringaddress .= $outputlangs->convToOutputCharset($targetcontact->phone_pro);
// 							}
// 							if (!empty($targetcontact->phone_pro) && !empty($targetcontact->phone_mobile)) {
// 								$stringaddress .= " / ";
// 							}
// 							if (!empty($targetcontact->phone_mobile)) {
// 								$stringaddress .= $outputlangs->convToOutputCharset($targetcontact->phone_mobile);
// 							}
// 						}
// 						// Fax
// 						if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_fax/', $mode)) {
// 							if ($targetcontact->fax) {
// 								$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($targetcontact->fax);
// 							}
// 						}
// 						// EMail
// 						if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_email/', $mode)) {
// 							if ($targetcontact->email) {
// 								$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($targetcontact->email);
// 							}
// 						}
// 						// Web
// 						if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_url/', $mode)) {
// 							if ($targetcontact->url) {
// 								$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($targetcontact->url);
// 							}
// 						}
// 					}
// 				}
// 			} else {
// 				if (is_object($targetcompany)) {
// 					$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset(dol_format_address($targetcompany));
// 					// Country
// 					if (!empty($targetcompany->country_code) && $targetcompany->country_code != $sourcecompany->country_code) {
// 						$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcompany->country_code));
// 					} else {
// 						$stringaddress .= ($stringaddress ? "\n" : '');
// 					}

// 					if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || preg_match('/targetwithdetails/', $mode)) {
// 						// Phone
// 						if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_phone/', $mode)) {
// 							if (!empty($targetcompany->offic_phone)) {
// 								$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Phone").": ";
// 							}
// 							if (!empty($targetcompany->office_phone)) {
// 								$stringaddress .= $outputlangs->convToOutputCharset($targetcompany->office_phone);
// 							}
// 						}
// 						// Fax
// 						if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_fax/', $mode)) {
// 							if ($targetcompany->fax) {
// 								$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($targetcompany->fax);
// 							}
// 						}
// 						// EMail
// 						if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_email/', $mode)) {
// 							if ($targetcompany->email) {
// 								$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($targetcompany->email);
// 							}
// 						}
// 						// Web
// 						if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_url/', $mode)) {
// 							if ($targetcompany->url) {
// 								$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($targetcompany->url);
// 							}
// 						}
// 					}
// 				}
// 			}

// 			// Intra VAT
// 			if (empty($conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS)) {
// 				if ($usecontact && is_object($targetcontact) && getDolGlobalInt('MAIN_USE_COMPANY_NAME_OF_CONTACT')) {
// 					$targetcontact->fetch_thirdparty();
// 					if (!empty($targetcontact->thirdparty->id) && $targetcontact->thirdparty->tva_intra) {
// 						$stringaddress .= ($stringaddress ? "\n" : '') . $outputlangs->transnoentities("VATIntraShort") . ': ' . $outputlangs->convToOutputCharset($targetcontact->thirdparty->tva_intra);
// 					}
// 				} elseif ($targetcompany->tva_intra) {
// 					$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("VATIntraShort").': '.$outputlangs->convToOutputCharset($targetcompany->tva_intra);
// 				}
// 			}

// 			// Professionnal Ids
// 			if (!empty($conf->global->MAIN_PROFID1_IN_ADDRESS) && !empty($targetcompany->idprof1)) {
// 				$tmp = $outputlangs->transcountrynoentities("ProfId1", $targetcompany->country_code);
// 				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
// 					$tmp = $reg[1];
// 				}
// 				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof1);
// 			}
// 			if (!empty($conf->global->MAIN_PROFID2_IN_ADDRESS) && !empty($targetcompany->idprof2)) {
// 				$tmp = $outputlangs->transcountrynoentities("ProfId2", $targetcompany->country_code);
// 				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
// 					$tmp = $reg[1];
// 				}
// 				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof2);
// 			}
// 			if (!empty($conf->global->MAIN_PROFID3_IN_ADDRESS) && !empty($targetcompany->idprof3)) {
// 				$tmp = $outputlangs->transcountrynoentities("ProfId3", $targetcompany->country_code);
// 				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
// 					$tmp = $reg[1];
// 				}
// 				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof3);
// 			}
// 			if (!empty($conf->global->MAIN_PROFID4_IN_ADDRESS) && !empty($targetcompany->idprof4)) {
// 				$tmp = $outputlangs->transcountrynoentities("ProfId4", $targetcompany->country_code);
// 				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
// 					$tmp = $reg[1];
// 				}
// 				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof4);
// 			}
// 			if (!empty($conf->global->MAIN_PROFID5_IN_ADDRESS) && !empty($targetcompany->idprof5)) {
// 				$tmp = $outputlangs->transcountrynoentities("ProfId5", $targetcompany->country_code);
// 				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
// 					$tmp = $reg[1];
// 				}
// 				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof5);
// 			}
// 			if (!empty($conf->global->MAIN_PROFID6_IN_ADDRESS) && !empty($targetcompany->idprof6)) {
// 				$tmp = $outputlangs->transcountrynoentities("ProfId6", $targetcompany->country_code);
// 				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
// 					$tmp = $reg[1];
// 				}
// 				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof6);
// 			}

// 			// Public note
// 			if (!empty($conf->global->MAIN_PUBLIC_NOTE_IN_ADDRESS)) {
// 				if ($mode == 'source' && !empty($sourcecompany->note_public)) {
// 					$stringaddress .= ($stringaddress ? "\n" : '').dol_string_nohtmltag($sourcecompany->note_public);
// 				}
// 				if (($mode == 'target' || preg_match('/targetwithdetails/', $mode)) && !empty($targetcompany->note_public)) {
// 					$stringaddress .= ($stringaddress ? "\n" : '').dol_string_nohtmltag($targetcompany->note_public);
// 				}
// 			}
// 		}
// 	}

// 	return $stringaddress;
// }

/**
 *   	Return a string with full address formated for output on documents
 *
 * 		@param	Translate	          $outputlangs		    Output langs object
 *   	@param  Societe		          $sourcecompany		Source company object
 *   	@param  Societe|string|null   $targetcompany		Target company object
 *      @param  Contact|string|null	  $targetcontact	    Target contact object
 * 		@param	int			          $usecontact		    Use contact instead of company
 * 		@param	string  	          $mode				    Address type ('source', 'target', 'targetwithdetails', 'targetwithdetails_xxx': target but include also phone/fax/email/url)
 *      @param  Object                $object               Object we want to build document for
 * 		@return	string					    		        String with full address
 */
function pdf_build_address_cv($outputlangs, $sourcecompany, $targetcompany = '', $targetcontact = '', $usecontact = 0, $mode = 'source', $object = null)
{
	global $conf, $hookmanager, $db;

		$targetcompany = new User($db);
		$targetcompany->fetch($sourcecompany->fk_user);

	if ($mode == 'source' && !is_object($sourcecompany)) {
		return -1;
	}
	if ($mode == 'target' && !is_object($targetcompany)) {
		return -1;
	}

	if (!empty($sourcecompany->state_id) && empty($sourcecompany->state)) {
		$sourcecompany->state = getState($sourcecompany->state_id);
	}
	if (!empty($targetcompany->state_id) && empty($targetcompany->state)) {
		$targetcompany->state = getState($targetcompany->state_id);
	}

	$reshook = 0;
	$stringaddress = '';
	if (is_object($hookmanager)) {
		$parameters = array('sourcecompany' => &$sourcecompany, 'targetcompany' => &$targetcompany, 'targetcontact' => &$targetcontact, 'outputlangs' => $outputlangs, 'mode' => $mode, 'usecontact' => $usecontact);
		$action = '';
		$reshook = $hookmanager->executeHooks('pdf_build_address', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
		$stringaddress .= $hookmanager->resPrint;
	}
	if (empty($reshook)) {
		if ($mode == 'source') {
			$withCountry = 0;
			if (isset($targetcompany->country_code) && !empty($sourcecompany->country_code) && ($targetcompany->country_code != $sourcecompany->country_code)) {
				$withCountry = 1;
			}

			$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset(dol_format_address($sourcecompany, $withCountry, "\n", $outputlangs))."\n";

			if (empty($conf->global->MAIN_PDF_DISABLESOURCEDETAILS)) {
				// Phone
				if ($sourcecompany->phone) {
					$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("PhoneShort").": ".$outputlangs->convToOutputCharset($sourcecompany->phone);
				}
				// Fax
				if ($sourcecompany->fax) {
					$stringaddress .= ($stringaddress ? ($sourcecompany->phone ? " - " : "\n") : '').$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($sourcecompany->fax);
				}
				// EMail
				if ($sourcecompany->email) {
					$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($sourcecompany->email);
				}
				// Web
				if ($sourcecompany->url) {
					$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($sourcecompany->url);
				}
			}
			// Intra VAT
			if (!empty($conf->global->MAIN_TVAINTRA_IN_SOURCE_ADDRESS)) {
				if ($sourcecompany->tva_intra) {
					$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("VATIntraShort").': '.$outputlangs->convToOutputCharset($sourcecompany->tva_intra);
				}
			}
			// Professionnal Ids
			$reg = array();
			if (!empty($conf->global->MAIN_PROFID1_IN_SOURCE_ADDRESS) && !empty($sourcecompany->idprof1)) {
				$tmp = $outputlangs->transcountrynoentities("ProfId1", $sourcecompany->country_code);
				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
					$tmp = $reg[1];
				}
				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof1);
			}
			if (!empty($conf->global->MAIN_PROFID2_IN_SOURCE_ADDRESS) && !empty($sourcecompany->idprof2)) {
				$tmp = $outputlangs->transcountrynoentities("ProfId2", $sourcecompany->country_code);
				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
					$tmp = $reg[1];
				}
				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof2);
			}
			if (!empty($conf->global->MAIN_PROFID3_IN_SOURCE_ADDRESS) && !empty($sourcecompany->idprof3)) {
				$tmp = $outputlangs->transcountrynoentities("ProfId3", $sourcecompany->country_code);
				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
					$tmp = $reg[1];
				}
				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof3);
			}
			if (!empty($conf->global->MAIN_PROFID4_IN_SOURCE_ADDRESS) && !empty($sourcecompany->idprof4)) {
				$tmp = $outputlangs->transcountrynoentities("ProfId4", $sourcecompany->country_code);
				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
					$tmp = $reg[1];
				}
				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof4);
			}
			if (!empty($conf->global->MAIN_PROFID5_IN_SOURCE_ADDRESS) && !empty($sourcecompany->idprof5)) {
				$tmp = $outputlangs->transcountrynoentities("ProfId5", $sourcecompany->country_code);
				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
					$tmp = $reg[1];
				}
				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof5);
			}
			if (!empty($conf->global->MAIN_PROFID6_IN_SOURCE_ADDRESS) && !empty($sourcecompany->idprof6)) {
				$tmp = $outputlangs->transcountrynoentities("ProfId6", $sourcecompany->country_code);
				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
					$tmp = $reg[1];
				}
				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof6);
			}
			if (!empty($conf->global->PDF_ADD_MORE_AFTER_SOURCE_ADDRESS)) {
				$stringaddress .= ($stringaddress ? "\n" : '').$conf->global->PDF_ADD_MORE_AFTER_SOURCE_ADDRESS;
			}
		}

		if ($mode == 'target' || preg_match('/targetwithdetails/', $mode)) {
			if ($usecontact) {
				if (is_object($targetcontact)) {
					$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset($targetcontact->getFullName($outputlangs, 1));

					if (!empty($targetcontact->address)) {
						$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset(dol_format_address($targetcontact))."\n";
					} else {
						$companytouseforaddress = $targetcompany;

						// Contact on a thirdparty that is a different thirdparty than the thirdparty of object
						if ($targetcontact->socid > 0 && $targetcontact->socid != $targetcompany->id) {
							$targetcontact->fetch_thirdparty();
							$companytouseforaddress = $targetcontact->thirdparty;
						}

						$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset(dol_format_address($companytouseforaddress))."\n";
					}
					// Country
					if (!empty($targetcontact->country_code) && $targetcontact->country_code != $sourcecompany->country_code) {
						$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcontact->country_code));
					} elseif (empty($targetcontact->country_code) && !empty($targetcompany->country_code) && ($targetcompany->country_code != $sourcecompany->country_code)) {
						$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcompany->country_code));
					}

					if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || preg_match('/targetwithdetails/', $mode)) {
						// Phone
						if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_phone/', $mode)) {
							if (!empty($targetcontact->phone_pro) || !empty($targetcontact->phone_mobile)) {
								$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Phone").": ";
							}
							if (!empty($targetcontact->phone_pro)) {
								$stringaddress .= $outputlangs->convToOutputCharset($targetcontact->phone_pro);
							}
							if (!empty($targetcontact->phone_pro) && !empty($targetcontact->phone_mobile)) {
								$stringaddress .= " / ";
							}
							if (!empty($targetcontact->phone_mobile)) {
								$stringaddress .= $outputlangs->convToOutputCharset($targetcontact->phone_mobile);
							}
						}
						// Fax
						if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_fax/', $mode)) {
							if ($targetcontact->fax) {
								$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($targetcontact->fax);
							}
						}
						// EMail
						if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_email/', $mode)) {
							if ($targetcontact->email) {
								$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($targetcontact->email);
							}
						}
						// Web
						if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_url/', $mode)) {
							if ($targetcontact->url) {
								$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($targetcontact->url);
							}
						}
					}
				}
			} else {
				// if (is_object($targetcompany)) {
					$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset(dol_format_address($targetcompany));
					// Country
					if (!empty($targetcompany->country_code) && $targetcompany->country_code != $sourcecompany->country_code) {
						$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcompany->country_code));
					} else {
						$stringaddress .= ($stringaddress ? "\n" : '');
					}

					// if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || preg_match('/targetwithdetails/', $mode)) {
						// Phone
						// if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_phone/', $mode)) {
							if (!empty($targetcompany->office_phone)) {
								$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Phone").": ";
							}
							if (!empty($targetcompany->office_phone)) {
								$stringaddress .= $outputlangs->convToOutputCharset($targetcompany->office_phone);
							}

							if (!empty($targetcompany->user_mobile)) {
								$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Mobile").": ";
							}
							if (!empty($targetcompany->user_mobile)) {
								$stringaddress .= $outputlangs->convToOutputCharset($targetcompany->user_mobile);
							}
						// }
						// Fax
						// if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_fax/', $mode)) {
							if ($targetcompany->fax) {
								$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($targetcompany->fax);
							}
						// }
					
						// EMail
						//   if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_email/', $mode)) {
							if ($targetcompany->email) {
								$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($targetcompany->email);
							}
						//  }
						// Web
						if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_url/', $mode)) {
							if ($targetcompany->url) {
								$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($targetcompany->url);
							}
						}
					// }
				// }
			}

			// Intra VAT
			if (empty($conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS)) {
				if ($usecontact && is_object($targetcontact) && getDolGlobalInt('MAIN_USE_COMPANY_NAME_OF_CONTACT')) {
					$targetcontact->fetch_thirdparty();
					if (!empty($targetcontact->thirdparty->id) && $targetcontact->thirdparty->tva_intra) {
						$stringaddress .= ($stringaddress ? "\n" : '') . $outputlangs->transnoentities("VATIntraShort") . ': ' . $outputlangs->convToOutputCharset($targetcontact->thirdparty->tva_intra);
					}
				} elseif ($targetcompany->tva_intra) {
					$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("VATIntraShort").': '.$outputlangs->convToOutputCharset($targetcompany->tva_intra);
				}
			}

			// Professionnal Ids
			if (!empty($conf->global->MAIN_PROFID1_IN_ADDRESS) && !empty($targetcompany->idprof1)) {
				$tmp = $outputlangs->transcountrynoentities("ProfId1", $targetcompany->country_code);
				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
					$tmp = $reg[1];
				}
				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof1);
			}
			if (!empty($conf->global->MAIN_PROFID2_IN_ADDRESS) && !empty($targetcompany->idprof2)) {
				$tmp = $outputlangs->transcountrynoentities("ProfId2", $targetcompany->country_code);
				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
					$tmp = $reg[1];
				}
				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof2);
			}
			if (!empty($conf->global->MAIN_PROFID3_IN_ADDRESS) && !empty($targetcompany->idprof3)) {
				$tmp = $outputlangs->transcountrynoentities("ProfId3", $targetcompany->country_code);
				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
					$tmp = $reg[1];
				}
				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof3);
			}
			if (!empty($conf->global->MAIN_PROFID4_IN_ADDRESS) && !empty($targetcompany->idprof4)) {
				$tmp = $outputlangs->transcountrynoentities("ProfId4", $targetcompany->country_code);
				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
					$tmp = $reg[1];
				}
				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof4);
			}
			if (!empty($conf->global->MAIN_PROFID5_IN_ADDRESS) && !empty($targetcompany->idprof5)) {
				$tmp = $outputlangs->transcountrynoentities("ProfId5", $targetcompany->country_code);
				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
					$tmp = $reg[1];
				}
				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof5);
			}
			if (!empty($conf->global->MAIN_PROFID6_IN_ADDRESS) && !empty($targetcompany->idprof6)) {
				$tmp = $outputlangs->transcountrynoentities("ProfId6", $targetcompany->country_code);
				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
					$tmp = $reg[1];
				}
				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof6);
			}

			// Public note
			if (!empty($conf->global->MAIN_PUBLIC_NOTE_IN_ADDRESS)) {
				if ($mode == 'source' && !empty($sourcecompany->note_public)) {
					$stringaddress .= ($stringaddress ? "\n" : '').dol_string_nohtmltag($sourcecompany->note_public);
				}
				if (($mode == 'target' || preg_match('/targetwithdetails/', $mode)) && !empty($targetcompany->note_public)) {
					$stringaddress .= ($stringaddress ? "\n" : '').dol_string_nohtmltag($targetcompany->note_public);
				}
			}
		}
	}

	return $stringaddress;
}

// /**
//  * Returns the name of the thirdparty
//  *
//  * @param   Societe|Contact     $thirdparty     Contact or thirdparty
//  * @param   Translate           $outputlangs    Output language
//  * @param   int                 $includealias   1=Include alias name after name
//  * @return  string                              String with name of thirdparty (+ alias if requested)
//  */
// function pdfBuildThirdpartyNameCV($thirdparty, Translate $outputlangs, $includealias = 0)
// {
// 	global $conf;

// 	// Recipient name
// 	$socname = '';

// 	if ($thirdparty instanceof CVTec) {
// 		global $db;
// 		$employee = new User($db);
// 		$employee->fetch($thirdparty->fk_user);
// 		$socname = $employee->firstname."  ".$employee->lastname;
// 		if (($includealias || getDolGlobalInt('PDF_INCLUDE_ALIAS_IN_THIRDPARTY_NAME')) && !empty($thirdparty->name_alias)) {
// 			if (getDolGlobalInt('PDF_INCLUDE_ALIAS_IN_THIRDPARTY_NAME') == 2) {
// 				$socname = $thirdparty->firstname."  ".$thirdparty->lastname;
// 			} else {
// 				$socname = $thirdparty->firstname."  ".$thirdparty->lastname;
// 			}
// 		}
// 	} elseif ($thirdparty instanceof Contact) {
// 		if ($thirdparty->socid > 0) {
// 			$thirdparty->fetch_thirdparty();
// 			$socname = $thirdparty->thirdparty->name;
// 			if (($includealias || getDolGlobalInt('PDF_INCLUDE_ALIAS_IN_THIRDPARTY_NAME')) && !empty($thirdparty->thirdparty->name_alias)) {
// 				if (getDolGlobalInt('PDF_INCLUDE_ALIAS_IN_THIRDPARTY_NAME') == 2) {
// 					$socname = $thirdparty->thirdparty->name_alias." - ".$thirdparty->thirdparty->name;
// 				} else {
// 					$socname = $thirdparty->thirdparty->name." - ".$thirdparty->thirdparty->name_alias;
// 				}
// 			}
// 		}
// 	} else {
// 		throw new InvalidArgumentException('Parameter 1 $thirdparty is not a Societe nor Contact');
// 	}

// 	return $outputlangs->convToOutputCharset($socname);
// }

/**
 * Returns the label of the job's and skills's users
 *
 * @param   array     $data      arrids
 * @param   Translate           $outputlangs    Output language
 * @return  string                              String with name of users data
 */
function pdfBuildUserSkillsCV($arrprofil, Translate $outputlangs)
{
	global $conf;

	// Recipient name
	$userskills = '';

	global $db;
	
	// foreach($arrids as $ids) {
	// 	$emp = new User($db);
	// 	$emp->fetch($ids->userid);
	// 	$job = new Job($db);
	// 	$job->fetch($ids->fk_job);
	// 	$skill = new Skill($db);
	// 	$skill->fetch($ids->fk_skill);
	// 	$arr[$emp->id][$job->label][$skill->id] = $skill->label;
	// }
	
	// foreach($arr as $userids => $data) {
	// 	foreach($data as $key => $vals) { 
	// 		$arrprofil[$userids] = array('job' => implode(',', array_keys($data)), 'skill' => implode("\n", $vals));
	// 	}
	// }

	foreach($arrprofil as $vals) {
		foreach($vals as $val) {
			$jobs .= $vals['job'];
			$jobs .= "\n";
			$jobs .= $vals['skill'];
			// $jobs .= "\n";
		}
	}
	
	$userskills = $jobs;
	
	return $outputlangs->convToOutputCharset($userskills);
}



/**
 *      Return a PDF instance object. We create a FPDI instance that instantiate TCPDF.
 *
 *      @param	string		$format         Array(width,height). Keep empty to use default setup.
 *      @param	string		$metric         Unit of format ('mm')
 *      @param  string		$pagetype       'P' or 'l'
 *      @return TCPDF						PDF object
 */
function pdf_getInstanceCustomConstat($object, $format = '', $metric = 'mm', $pagetype = 'P')
{
	global $conf;

	// Define constant for TCPDF
	if (!defined('K_TCPDF_EXTERNAL_CONFIG')) {
		define('K_TCPDF_EXTERNAL_CONFIG', 1); // this avoid using tcpdf_config file
		define('K_PATH_CACHE', DOL_DATA_ROOT.'/admin/temp/');
		define('K_PATH_URL_CACHE', DOL_DATA_ROOT.'/admin/temp/');
		dol_mkdir(K_PATH_CACHE);
		define('K_BLANK_IMAGE', '_blank.png');
		define('PDF_PAGE_FORMAT', 'A4');
		define('PDF_PAGE_ORIENTATION', 'P');
		define('PDF_CREATOR', 'TCPDF');
		define('PDF_AUTHOR', 'TCPDF');
		define('PDF_HEADER_TITLE', 'TCPDF Example');
		define('PDF_HEADER_STRING', "by Dolibarr ERP CRM");
		define('PDF_UNIT', 'mm');
		define('PDF_MARGIN_HEADER', 5);
		define('PDF_MARGIN_FOOTER', 10);
		define('PDF_MARGIN_TOP', 27);
		define('PDF_MARGIN_BOTTOM', 25);
		define('PDF_MARGIN_LEFT', 15);
		define('PDF_MARGIN_RIGHT', 15);
		define('PDF_FONT_NAME_MAIN', 'helvetica');
		define('PDF_FONT_SIZE_MAIN', 10);
		define('PDF_FONT_NAME_DATA', 'helvetica');
		define('PDF_FONT_SIZE_DATA', 8);
		define('PDF_FONT_MONOSPACED', 'courier');
		define('PDF_IMAGE_SCALE_RATIO', 1.25);
		define('HEAD_MAGNIFICATION', 1.1);
		define('K_CELL_HEIGHT_RATIO', 1.25);
		define('K_TITLE_MAGNIFICATION', 1.3);
		define('K_SMALL_RATIO', 2 / 3);
		define('K_THAI_TOPCHARS', true);
		define('K_TCPDF_CALLS_IN_HTML', true);
		if (!empty($conf->global->TCPDF_THROW_ERRORS_INSTEAD_OF_DIE)) {
			define('K_TCPDF_THROW_EXCEPTION_ERROR', true);
		} else {
			define('K_TCPDF_THROW_EXCEPTION_ERROR', false);
		}
	}

	// Load TCPDF
	// require_once TCPDF_PATH.'tcpdf.php';
	require_once DOL_DOCUMENT_ROOT.'/custom/constat/core/modules/constat/doc/tcpdf_custom.php';

	// We need to instantiate tcpdi object (instead of tcpdf) to use merging features. But we can disable it (this will break all merge features).
	if (empty($conf->global->MAIN_DISABLE_TCPDI)) {
		require_once TCPDI_PATH.'tcpdi.php';
	}

	//$arrayformat=pdf_getFormat();
	//$format=array($arrayformat['width'],$arrayformat['height']);
	//$metric=$arrayformat['unit'];

	$pdfa = false; // PDF-1.3
	if (!empty($conf->global->PDF_USE_A)) {
		$pdfa = $conf->global->PDF_USE_A; 	// PDF/A-1 ou PDF/A-3
	}


	// if (class_exists('TCPDI')) {
	// 	$pdf = new TCPDI($pagetype, $metric, $format, true, 'UTF-8', false, $pdfa);
	// } else {
		$pdf = new TCPDFCustom($object, $pagetype, $metric, $format, true, 'UTF-8', false, $pdfa);
	// }
	

	// Protection and encryption of pdf
	if (!empty($conf->global->PDF_SECURITY_ENCRYPTION)) {
		/* Permission supported by TCPDF
		- print : Print the document;
		- modify : Modify the contents of the document by operations other than those controlled by 'fill-forms', 'extract' and 'assemble';
		- copy : Copy or otherwise extract text and graphics from the document;
		- annot-forms : Add or modify text annotations, fill in interactive form fields, and, if 'modify' is also set, create or modify interactive form fields (including signature fields);
		- fill-forms : Fill in existing interactive form fields (including signature fields), even if 'annot-forms' is not specified;
		- extract : Extract text and graphics (in support of accessibility to users with disabilities or for other purposes);
		- assemble : Assemble the document (insert, rotate, or delete pages and create bookmarks or thumbnail images), even if 'modify' is not set;
		- print-high : Print the document to a representation from which a faithful digital copy of the PDF content could be generated. When this is not set, printing is limited to a low-level representation of the appearance, possibly of degraded quality.
		- owner : (inverted logic - only for public-key) when set permits change of encryption and enables all other permissions.
		*/

		// For TCPDF, we specify permission we want to block
		$pdfrights = (!empty($conf->global->PDF_SECURITY_ENCRYPTION_RIGHTS) ?json_decode($conf->global->PDF_SECURITY_ENCRYPTION_RIGHTS, true) : array('modify', 'copy')); // Json format in llx_const

		// Password for the end user
		$pdfuserpass = (!empty($conf->global->PDF_SECURITY_ENCRYPTION_USERPASS) ? $conf->global->PDF_SECURITY_ENCRYPTION_USERPASS : '');

		// Password of the owner, created randomly if not defined
		$pdfownerpass = (!empty($conf->global->PDF_SECURITY_ENCRYPTION_OWNERPASS) ? $conf->global->PDF_SECURITY_ENCRYPTION_OWNERPASS : null);

		// For encryption strength: 0 = RC4 40 bit; 1 = RC4 128 bit; 2 = AES 128 bit; 3 = AES 256 bit
		$encstrength = (!empty($conf->global->PDF_SECURITY_ENCRYPTION_STRENGTH) ? $conf->global->PDF_SECURITY_ENCRYPTION_STRENGTH : 0);

		// Array of recipients containing public-key certificates ('c') and permissions ('p').
		// For example: array(array('c' => 'file://../examples/data/cert/tcpdf.crt', 'p' => array('print')))
		$pubkeys = (!empty($conf->global->PDF_SECURITY_ENCRYPTION_PUBKEYS) ?json_decode($conf->global->PDF_SECURITY_ENCRYPTION_PUBKEYS, true) : null); // Json format in llx_const

		$pdf->SetProtection($pdfrights, $pdfuserpass, $pdfownerpass, $encstrength, $pubkeys);
	}

	return $pdf;
}