<?php
/* Copyright (C) 2020       Laurent Destailleur     <eldy@users.sourceforge.net>
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
 *       \file       htdocs/public/recruitment/view.php
 *       \ingroup    recruitment
 *       \brief      Public file to show on job
 */

if (!defined('NOLOGIN')) {
	define("NOLOGIN", 1); // This means this output page does not require to be logged.
}
if (!defined('NOCSRFCHECK')) {
	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/recruitment/class/recruitmentjobposition.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "other", "recruitment"));

// Get parameters
$action   = GETPOST('action', 'aZ09');
$cancel   = GETPOST('cancel', 'alpha');
$email    = GETPOST('email', 'alpha');
$backtopage = '';

$ref = GETPOST('ref', 'alpha');

if (GETPOST('btn_view')) {
	unset($_SESSION['email_customer']);
}
if (isset($_SESSION['email_customer'])) {
	$email = $_SESSION['email_customer'];
}

$object = new RecruitmentJobPosition($db);

if (!$action) {
	if (!$ref) {
		print $langs->trans('ErrorBadParameters')." - ref missing";
		exit;
	} else {
		$object->fetch('', $ref);
	}
}

// Define $urlwithroot
//$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
//$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
$urlwithroot = DOL_MAIN_URL_ROOT; // This is to use same domain name than current. For Paypal payment, we can use internal URL like localhost.

?>
<html lang="fr"><head>
<meta charset="utf-8">
<meta name="robots" content="noindex,follow">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="author" content="Dolibarr Development Team">
<meta name="anti-csrf-newtoken" content="8bea055f4aba11435a8311ae431a5f9e">
<meta name="anti-csrf-currenttoken" content="">
<meta name="MAIN_FEATURES_LEVEL" content="1">
<link rel="shortcut icon" type="image/x-icon" href="/erp/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file=logos%2Fthumbs%2FOPTIM-Industries_RondLogo_fondbleu_mini.jpg">
<link rel="manifest" href="/erp/theme/eldy/manifest.json.php">
<meta name="theme-color" content="rgb(40,80,139)">
<title>Identifiant @ 19.0.2</title>
<!-- Includes CSS for JQuery (Ajax library) -->
<link rel="stylesheet" type="text/css" href="/erp/includes/jquery/css/base/jquery-ui.css?layout=classic&amp;version=19.0.2">
<link rel="stylesheet" type="text/css" href="/erp/includes/jquery/plugins/jnotify/jquery.jnotify-alt.min.css?layout=classic&amp;version=19.0.2">
<link rel="stylesheet" type="text/css" href="/erp/includes/jquery/plugins/select2/dist/css/select2.css?layout=classic&amp;version=19.0.2">
<!-- Includes CSS for font awesome -->
<link rel="stylesheet" type="text/css" href="/erp/theme/common/fontawesome-5/css/all.min.css?layout=classic&amp;version=19.0.2">
<!-- Includes CSS for Dolibarr theme -->
<link rel="stylesheet" type="text/css" href="/erp/theme/eldy/style.css.php?lang=fr_FR&amp;theme=eldy&amp;entity=1&amp;layout=classic&amp;version=19.0.2&amp;revision=757">
<!-- Includes CSS added by module fod -->
<link rel="stylesheet" type="text/css" href="/erp/custom/fod/css/fod.css.php?lang=fr_FR&amp;theme=eldy&amp;entity=1&amp;layout=classic&amp;version=19.0.2&amp;revision=757">
<!-- Includes CSS added by module remonteessse -->
<link rel="stylesheet" type="text/css" href="/erp/custom/remonteessse/css/remonteessse.css.php?lang=fr_FR&amp;theme=eldy&amp;entity=1&amp;layout=classic&amp;version=19.0.2&amp;revision=757">
<!-- Includes CSS added by module h2g2 -->
<link rel="stylesheet" type="text/css" href="/erp/h2g2/css/h2g2.css.php?lang=fr_FR&amp;theme=eldy&amp;entity=1&amp;layout=classic&amp;version=19.0.2&amp;revision=757">
<!-- Includes CSS added by module h2g2 -->
<link rel="stylesheet" type="text/css" href="/erp/h2g2/css/c42location.css.php?lang=fr_FR&amp;theme=eldy&amp;entity=1&amp;layout=classic&amp;version=19.0.2&amp;revision=757">
<!-- Includes CSS added by module h2g2 -->
<link rel="stylesheet" type="text/css" href="/erp/h2g2/css/leaflet.css">
<!-- Includes CSS added by module formationhabilitation -->
<link rel="stylesheet" type="text/css" href="/erp/custom/formationhabilitation/css/formationhabilitation.css.php?lang=fr_FR&amp;theme=eldy&amp;entity=1&amp;layout=classic&amp;version=19.0.2&amp;revision=757">
<!-- Includes CSS added by module feuilledetemps -->
<link rel="stylesheet" type="text/css" href="/erp/custom/feuilledetemps/css/feuilledetemps.css.php?lang=fr_FR&amp;theme=eldy&amp;entity=1&amp;layout=classic&amp;version=19.0.2&amp;revision=757">
<!-- Includes CSS added by module feuilledetemps -->
<link rel="stylesheet" type="text/css" href="/erp/feuilledetemps/lib/FixedHeaderTable/css/defaultTheme.css">
<!-- Includes CSS added by module scaninvoices -->
<link rel="stylesheet" type="text/css" href="/erp/custom/scaninvoices/css/index.css">
<!-- Includes CSS added by module workload -->
<link rel="stylesheet" type="text/css" href="/erp/custom/workload/css/workload.css.php?lang=fr_FR&amp;theme=eldy&amp;entity=1&amp;layout=classic&amp;version=19.0.2&amp;revision=757">
<!-- Includes CSS added by module addonusergroup -->
<link rel="stylesheet" type="text/css" href="/erp/custom/addonusergroup/css/addonusergroup.css.php?lang=fr_FR&amp;theme=eldy&amp;entity=1&amp;layout=classic&amp;version=19.0.2&amp;revision=757">
<!-- Includes CSS added by module addoncomm -->
<link rel="stylesheet" type="text/css" href="/erp/addoncomm/css/addoncomm.css.php?lang=fr_FR&amp;theme=eldy&amp;entity=1&amp;layout=classic&amp;version=19.0.2&amp;revision=757">
<!-- Includes CSS added by module fep -->
<link rel="stylesheet" type="text/css" href="/erp/custom/fep/css/fep.css.php?lang=fr_FR&amp;theme=eldy&amp;entity=1&amp;layout=classic&amp;version=19.0.2&amp;revision=757">
<!-- Includes CSS added by module holidaycustom -->
<link rel="stylesheet" type="text/css" href="/erp/custom/holidaycustom/css/holidaycustom.css.php?lang=fr_FR&amp;theme=eldy&amp;entity=1&amp;layout=classic&amp;version=19.0.2&amp;revision=757">
<!-- Includes CSS added by module q3serp -->
<link rel="stylesheet" type="text/css" href="/erp/custom/q3serp/css/q3serp.css.php?lang=fr_FR&amp;theme=eldy&amp;entity=1&amp;layout=classic&amp;version=19.0.2&amp;revision=757">
<!-- Includes CSS added by module quiz -->
<link rel="stylesheet" type="text/css" href="/erp/custom/quiz/css/quiz.css.php?lang=fr_FR&amp;theme=eldy&amp;entity=1&amp;layout=classic&amp;version=19.0.2&amp;revision=757">
<!-- Includes CSS added by module donneesrh -->
<link rel="stylesheet" type="text/css" href="/erp/custom/donneesrh/css/donneesrh.css.php?lang=fr_FR&amp;theme=eldy&amp;entity=1&amp;layout=classic&amp;version=19.0.2&amp;revision=757">
<!-- Includes CSS added by module constat -->
<link rel="stylesheet" type="text/css" href="/erp/custom/constat/css/constat.css.php?lang=fr_FR&amp;theme=eldy&amp;entity=1&amp;layout=classic&amp;version=19.0.2&amp;revision=757">
<!-- Includes JS for JQuery -->
<script nonce="32b195b6" src="/erp/includes/jquery/js/jquery.min.js?layout=classic&amp;version=19.0.2"></script>
<script nonce="32b195b6" src="/erp/includes/jquery/js/jquery-ui.min.js?layout=classic&amp;version=19.0.2"></script>
<script nonce="32b195b6" src="/erp/includes/jquery/plugins/jnotify/jquery.jnotify.min.js?layout=classic&amp;version=19.0.2"></script>
<script nonce="32b195b6" src="/erp/includes/jquery/plugins/select2/dist/js/select2.full.min.js?layout=classic&amp;version=19.0.2"></script>
<script nonce="32b195b6" src="/erp/includes/jquery/plugins/multiselect/jquery.multi-select.js?layout=classic&amp;version=19.0.2"></script>
<!-- Includes JS of Dolibarr -->
<script nonce="32b195b6" src="/erp/core/js/lib_head.js.php?lang=fr_FR&amp;layout=classic&amp;version=19.0.2"></script>
<!-- Include JS added by module fod-->
<script nonce="32b195b6" src="/erp/custom/fod/core/js/fod.js?lang=fr_FR"></script>
<!-- Include JS added by module gpeccustom-->
<script nonce="32b195b6" src="/erp/custom/gpeccustom/js/gpeccustom.js.php?lang=fr_FR"></script>
<!-- Include JS added by module h2g2-->
<script nonce="32b195b6" src="/erp/h2g2/js/h2g2.js.php?lang=fr_FR"></script>
<!-- Include JS added by module h2g2-->
<script nonce="32b195b6" src="/erp/h2g2/js/leaflet.js?lang=fr_FR"></script>
<!-- Include JS added by module formationhabilitation-->
<script nonce="32b195b6" src="/erp/custom/formationhabilitation/js/formationhabilitation.js.php?lang=fr_FR"></script>
<!-- Include JS added by module quiz-->
<script nonce="32b195b6" src="/erp/custom/quiz/js/quiz.js.php?lang=fr_FR"></script>
<!-- Include JS added by module reglementations-->
<script nonce="32b195b6" src="/erp/custom/reglementations/js/ecranRgpd.js.php?lang=fr_FR"></script>
<!-- Includes JS added by page -->
<script nonce="32b195b6" src="/erp/includes/jstz/jstz.min.js?lang=fr_FR"></script>
<script nonce="32b195b6" src="/erp/core/js/dst.js?lang=fr_FR"></script>
<link rel="stylesheet" type="text/css" href="/erp/includes/maximebf/debugbar/src/DebugBar/Resources/debugbar.css">
<link rel="stylesheet" type="text/css" href="/erp/includes/maximebf/debugbar/src/DebugBar/Resources/widgets.css">
<link rel="stylesheet" type="text/css" href="/erp/includes/maximebf/debugbar/src/DebugBar/Resources/openhandler.css">
<link rel="stylesheet" type="text/css" href="/erp/includes/maximebf/debugbar/src/DebugBar/Resources/widgets/sqlqueries/widget.css">
<script type="text/javascript" src="/erp/includes/maximebf/debugbar/src/DebugBar/Resources/debugbar.js"></script>
<script type="text/javascript" src="/erp/includes/maximebf/debugbar/src/DebugBar/Resources/widgets.js"></script>
<script type="text/javascript" src="/erp/includes/maximebf/debugbar/src/DebugBar/Resources/openhandler.js"></script>
<script type="text/javascript" src="/erp/includes/maximebf/debugbar/src/DebugBar/Resources/widgets/sqlqueries/widget.js"></script>
<script type="text/javascript" src="/erp/debugbar/js/widgets.js"></script>

</head>

<!-- BEGIN PHP TEMPLATE LOGIN.TPL.PHP -->
	<body class="body bodylogin" style="background-size: cover; background-position: center center; background-attachment: fixed; background-repeat: no-repeat; background-image: url('<?php echo DOL_URL_ROOT; ?>/custom/quiz/img/Fond_Connexion_2.jpg')">
	
<script>
$(document).ready(function () {
	/* Set focus on correct field */
	$('#username').focus(); 		// Warning to use this only on visible element
});
</script>

<div class="login_center center">
<div class="login_vertical_align">
<?php
session_start();

if (!empty($_SESSION['login_error'])) {
    echo '<div class="error">' . htmlspecialchars($_SESSION['login_error']) . '</div>';
    unset($_SESSION['login_error']);
}
?>

<form id="" name="" method="post" action="/erp/public/recruitment/userPhished.php?ref=test">

<!-- Title with version -->
<div class="login_table_title center" title="Dolibarr 19.0.2">
<a class="login_table_title" href="https://www.dolibarr.org" target="_blank" rel="noopener noreferrer external">Dolibarr 19.0.2</a></div>


<div class="login_table">

<div id="login_line1">

<div id="login_left">
<img alt="" src="<?php echo DOL_URL_ROOT; ?>/custom/quiz/img/OPTIM-Industries_Logo_fondblanc_small.jpg" id="img_logo">
</div>

<br>

<div id="login_right">

<div class="tagtable left centpercent" title="Saisir les informations de connexion">

<!-- Login -->
<div class="trinputlogin">
<div class="tagtd nowraponall center valignmiddle tdinputlogin">
<!-- <span class="span-icon-user">-->
<span class="fa fa-user"></span>
<input type="text" id="username" maxlength="255" placeholder="Identifiant" name="username" class="flat input-icon-user minwidth150" value="" tabindex="1" autofocus="autofocus" autocapitalize="off" autocomplete="on" spellcheck="false" autocorrect="off" required>
</div>
</div>

<!-- Password -->
<div class="trinputlogin">
<div class="tagtd nowraponall center valignmiddle tdinputlogin">
	<!--<span class="span-icon-password">-->
<span class="fa fa-key"></span>
<input type="password" id="password" maxlength="128" placeholder="Mot de passe" name="password" class="flat input-icon-password minwidth150" value="" tabindex="2" autocomplete="off" required>
</div></div>


	<!-- Captcha -->
	<div class="trinputlogin">
	<div class="tagtd none valignmiddle tdinputlogin nowrap">

	<span class="fa fa-unlock"></span>
	<span class="span-icon-security inline-block">
	<input id="securitycode" placeholder="Code sécurité" class="flat input-icon-security width125" type="text" maxlength="5" name="code" tabindex="3" autocomplete="off">
	</span>
	<span class="nowrap inline-block">
	<img class="inline-block valignmiddle" src="/erp/core/antispamimage.php" border="0" width="80" height="32" id="img_securitycode">
	<a class="inline-block valignmiddle" href="/erp/public/recruitment/PlaningConges.php?ref=test&amp;time=20250520121739" tabindex="4" data-role="button"><span class="fas fa-redo" style="" title="Rafraichir" id="captcha_refresh_img"></span></a>
	</span>

	</div></div>
	
</div>

</div> <!-- end div login_right -->

</div> <!-- end div login_line1 -->


<div id="login_line2" style="clear: both">


<!-- Button Connection -->
<br>
<div id="login-submit-wrapper">
<input type="submit" class="button" value="&nbsp; Se connecter &nbsp;" tabindex="5">
</div>


<br><div class="center" style="margin-top: 5px;"><a class="alogin" href="/erp/support/index.php" target="_blank" rel="noopener noreferrer">Besoin d'assistance ?</a></div>
</div> <!-- end login line 2 -->

</div> <!-- end login table -->


</form>


	<div class="center login_main_home paddingtopbottom  backgroundsemitransparent boxshadow" style="max-width: 70%">
	Vous pouvez acceder à votre boite mail&nbsp;<strong><a href="https://optim-industries.fr/webmail/" target="_blank">@optim-industries.fr</a></strong>	</div><br>
	
<!-- authentication mode = dolibarr -->
<!-- cookie name used for this session = DOLSESSID_3f2bb8c5acfc289ac95ef107707f187c76d7386d -->
<!-- urlfrom in this session =  -->

<!-- Common footer is not used for login page, this is same than footer but inside login tpl -->



</div>
</div><!-- end of center -->





</body></html>
<?php