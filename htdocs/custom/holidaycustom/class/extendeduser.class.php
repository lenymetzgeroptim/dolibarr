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
 *  \file       htdocs/user/class/user.class.php
 *	\brief      File of class to manage users
 *  \ingroup	core
 */

/**
 *	Class to manage Dolibarr users
 */
class ExtendedUser2 extends User
{

    /**
	 *  Return a link to the user card (with optionaly the picto)
	 * 	Use this->id,this->lastname, this->firstname
	 *
	 *	@param	int		$withpictoimg				Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto, -1=Include photo into link, -2=Only picto photo, -3=Only photo very small)
	 *	@param	string	$option						On what the link point to ('leave', 'accountancy', 'nolink', )
	 *  @param  integer $infologin      			0=Add default info tooltip, 1=Add complete info tooltip, -1=No info tooltip
	 *  @param	integer	$notooltip					1=Disable tooltip on picto and name
	 *  @param	int		$maxlen						Max length of visible user name
	 *  @param	int		$hidethirdpartylogo			Hide logo of thirdparty if user is external user
	 *  @param  string  $mode               		''=Show firstname and lastname, 'firstname'=Show only firstname, 'firstelselast'=Show firstname or lastname if not defined, 'login'=Show login
	 *  @param  string  $morecss            		Add more css on link
	 *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								String with URL
	 */
	public function getNomUrlCustom($withpictoimg = 0, $option = '', $infologin = 0, $notooltip = 0, $maxlen = 24, $hidethirdpartylogo = 0, $mode = '', $morecss = '', $save_lastsearch_value = -1)
	{
		global $langs, $conf, $db, $hookmanager, $user;
		global $dolibarr_main_authentication, $dolibarr_main_demo;
		global $menumanager;

		if (!$user->rights->user->user->lire && $user->id != $this->id) {
			$option = 'nolink';
		}

		if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) && $withpictoimg) {
			$withpictoimg = 0;
		}

		$result = ''; $label = '';
		$companylink = '';

		if (!empty($this->photo)) {
			$label .= '<div class="photointooltip floatright">';
			$label .= Form::showphoto('userphoto', $this, 0, 60, 0, 'photoref photowithmargin photologintooltip', 'small', 0, 1); // Force height to 60 so we total height of tooltip can be calculated and collision can be managed
			$label .= '</div>';
			//$label .= '<div style="clear: both;"></div>';
		}

		// Info Login
		$label .= '<div class="centpercent">';
		$label .= img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("User").'</u>';
		$label .= ' '.$this->getLibStatut(4);
		$label .= '<br><b>'.$langs->trans('Name').':</b> '.dol_string_nohtmltag($this->getFullName($langs, ''));
		if (!empty($this->login)) {
			$label .= '<br><b>'.$langs->trans('Login').':</b> '.dol_string_nohtmltag($this->login);
		}
		if (!empty($this->job)) {
			$label .= '<br><b>'.$langs->trans("Job").':</b> '.dol_string_nohtmltag($this->job);
		}
		$label .= '<br><b>'.$langs->trans("Email").':</b> '.dol_string_nohtmltag($this->email);
		if (!empty($this->office_phone) || !empty($this->office_fax) || !empty($this->fax)) {
			$phonelist = array();
			if ($this->office_phone) {
				$phonelist[] = dol_print_phone($this->office_phone, $this->country_code, $this->id, 0, '', '&nbsp', 'phone');
			}
			if ($this->office_fax) {
				$phonelist[] = dol_print_phone($this->office_fax, $this->country_code, $this->id, 0, '', '&nbsp', 'fax');
			}
			if ($this->user_mobile) {
				$phonelist[] = dol_print_phone($this->user_mobile, $this->country_code, $this->id, 0, '', '&nbsp', 'mobile');
			}
			$label .= '<br><b>'.$langs->trans('Phone').':</b> '.implode('&nbsp;', $phonelist);
		}
		if (!empty($this->admin)) {
			$label .= '<br><b>'.$langs->trans("Administrator").'</b>: '.yn($this->admin);
		}
		if (!empty($this->accountancy_code) || $option == 'accountancy') {
			$label .= '<br><b>'.$langs->trans("AccountancyCode").'</b>: '.$this->accountancy_code;
		}
		$company = '';
		if (!empty($this->socid)) {	// Add thirdparty for external users
			$thirdpartystatic = new Societe($db);
			$thirdpartystatic->fetch($this->socid);
			if (empty($hidethirdpartylogo)) {
				$companylink = ' '.$thirdpartystatic->getNomUrl(2, (($option == 'nolink') ? 'nolink' : '')); // picto only of company
			}
			$company = ' ('.$langs->trans("Company").': '.dol_string_nohtmltag($thirdpartystatic->name).')';
		}
		$type = ($this->socid ? $langs->trans("External").$company : $langs->trans("Internal"));
		$label .= '<br><b>'.$langs->trans("Type").':</b> '.dol_string_nohtmltag($type);
		$label .= '</div>';
		if ($infologin > 0) {
			$label .= '<br>';
			$label .= '<br><u>'.$langs->trans("Session").'</u>';
			$label .= '<br><b>'.$langs->trans("IPAddress").'</b>: '.dol_string_nohtmltag(getUserRemoteIP());
			if (!empty($conf->global->MAIN_MODULE_MULTICOMPANY)) {
				$label .= '<br><b>'.$langs->trans("ConnectedOnMultiCompany").':</b> '.$conf->entity.' (User entity '.$this->entity.')';
			}
			$label .= '<br><b>'.$langs->trans("AuthenticationMode").':</b> '.dol_string_nohtmltag($_SESSION["dol_authmode"].(empty($dolibarr_main_demo) ? '' : ' (demo)'));
			$label .= '<br><b>'.$langs->trans("ConnectedSince").':</b> '.dol_print_date($this->datelastlogin, "dayhour", 'tzuser');
			$label .= '<br><b>'.$langs->trans("PreviousConnexion").':</b> '.dol_print_date($this->datepreviouslogin, "dayhour", 'tzuser');
			$label .= '<br><b>'.$langs->trans("CurrentTheme").':</b> '.dol_string_nohtmltag($conf->theme);
			$label .= '<br><b>'.$langs->trans("CurrentMenuManager").':</b> '.dol_string_nohtmltag($menumanager->name);
			$s = picto_from_langcode($langs->getDefaultLang());
			$label .= '<br><b>'.$langs->trans("CurrentUserLanguage").':</b> '.dol_string_nohtmltag(($s ? $s.' ' : '').$langs->getDefaultLang());
			$label .= '<br><b>'.$langs->trans("Browser").':</b> '.dol_string_nohtmltag($conf->browser->name.($conf->browser->version ? ' '.$conf->browser->version : '').' ('.$_SERVER['HTTP_USER_AGENT'].')');
			$label .= '<br><b>'.$langs->trans("Layout").':</b> '.dol_string_nohtmltag($conf->browser->layout);
			$label .= '<br><b>'.$langs->trans("Screen").':</b> '.dol_string_nohtmltag($_SESSION['dol_screenwidth'].' x '.$_SESSION['dol_screenheight']);
			if ($conf->browser->layout == 'phone') {
				$label .= '<br><b>'.$langs->trans("Phone").':</b> '.$langs->trans("Yes");
			}
			if (!empty($_SESSION["disablemodules"])) {
				$label .= '<br><b>'.$langs->trans("DisabledModules").':</b> <br>'.dol_string_nohtmltag(join(', ', explode(',', $_SESSION["disablemodules"])));
			}
		}
		if ($infologin < 0) {
			$label = '';
		}

		$url = DOL_URL_ROOT.'/user/card.php?id='.$this->id;
		if ($option == 'leave') {
			$url = DOL_URL_ROOT.'/custom/holidaycustom/list.php?id='.$this->id;
		}

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkstart = '<a href="'.$url.'"';
		$linkclose = "";
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$langs->load("users");
				$label = $langs->trans("ShowUser");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';

			/*
			 $hookmanager->initHooks(array('userdao'));
			 $parameters=array('id'=>$this->id);
			 $reshook=$hookmanager->executeHooks('getnomurltooltip',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
			 if ($reshook > 0) $linkclose = $hookmanager->resPrint;
			 */
		}

		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		//if ($withpictoimg == -1) $result.='<div class="nowrap">';
		$result .= (($option == 'nolink') ? '' : $linkstart);
		if ($withpictoimg) {
			$paddafterimage = '';
			if (abs($withpictoimg) == 1) {
				$paddafterimage = 'style="margin-'.($langs->trans("DIRECTION") == 'rtl' ? 'left' : 'right').': 3px;"';
			}
			// Only picto
			if ($withpictoimg > 0) {
				$picto = '<!-- picto user --><span class="nopadding userimg'.($morecss ? ' '.$morecss : '').'">'.img_object('', 'user', $paddafterimage.' '.($notooltip ? '' : 'class="paddingright classfortooltip"'), 0, 0, $notooltip ? 0 : 1).'</span>';
			} else {
				// Picto must be a photo
				$picto = '<!-- picto photo user --><span class="nopadding userimg'.($morecss ? ' '.$morecss : '').'"'.($paddafterimage ? ' '.$paddafterimage : '').'>'.Form::showphoto('userphoto', $this, 0, 0, 0, 'userphoto'.($withpictoimg == -3 ? 'small' : ''), 'mini', 0, 1).'</span>';
			}
			$result .= $picto;
		}
		if ($withpictoimg > -2 && $withpictoimg != 2) {
			if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$result .= '<span class="nopadding usertext'.((!isset($this->statut) || $this->statut) ? '' : ' strikefordisabled').($morecss ? ' '.$morecss : '').'">';
			}
			if ($mode == 'login') {
				$result .= dol_string_nohtmltag(dol_trunc($this->login, $maxlen));
			} else {
				$result .= dol_string_nohtmltag($this->getFullName($langs, '', ($mode == 'firstelselast' ? 3 : ($mode == 'firstname' ? 2 : -1)), $maxlen));
			}
			if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$result .= '</span>';
			}
		}
		$result .= (($option == 'nolink') ? '' : $linkend);
		//if ($withpictoimg == -1) $result.='</div>';

		$result .= $companylink;

		global $action;
		$hookmanager->initHooks(array('userdao'));
		$parameters = array('id'=>$this->id, 'getnomurl'=>$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}


}