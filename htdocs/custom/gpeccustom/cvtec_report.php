<?php
/*
 * Copyright (C) 2015 delcroip <patrick@pmpd.eu>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY;without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
//include 'core/lib/includeMain.lib.php';
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/lib/timesheet.lib.php';

require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';


// load module libraries
require_once __DIR__.'/class/cvtec.class.php';

$htmlother = new FormOther($db);
//$objmodelexport=new ModeleExports($db);
$id                 = GETPOST('id', 'int');
$action                 = GETPOST('action', 'alpha');
//$dateStart         = GETPOST('dateStart', 'alpha');
$exportfriendly = GETPOST('exportfriendly', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');
$short = GETPOST('short', 'int');
$mode = GETPOST('mode', 'alpha');
$model = GETPOST('model', 'alpha');
$arrproj = GETPOST('arrproj', 'alpha');

if(empty($mode)){
    $mode = 'UTD';
}

if (!$user->rights->feuilledetemps->timespent->readall) {
	accessforbidden();
}


$year = GETPOST('year', 'int');
$month = GETPOST('month', 'alpha');//strtotime(str_replace('/', '-', $_POST['Date']))
$langs->load("main");
$langs->load("projects");
$langs->load('timesheet@timesheet');
//find the right week
//find the right week
$dateStart = strtotime(GETPOST('dateStart', 'alpha'));
$dateStartday = GETPOST('dateStartday', 'int');// to not look for the date if action not goTodate
$dateStartmonth = GETPOST('dateStartmonth', 'int');
$dateStartyear = GETPOST('dateStartyear', 'int');
$dateStart = parseDate($dateStartday, $dateStartmonth, $dateStartyear, $dateStart);
$dateEnd = strtotime(GETPOST('dateEnd', 'alpha'));
$dateEndday = GETPOST('dateEndday', 'int');// to not look for the date if action not goTodate
$dateEndmonth = GETPOST('dateEndmonth', 'int');
$dateEndyear = GETPOST('dateEndyear', 'int');
$dateEnd = parseDate($dateEndday, $dateEndmonth, $dateEndyear, $dateEnd);
$invoicabletaskOnly = GETPOST('invoicabletaskOnly', 'int');

$arr_job = GETPOST('arr_job');

$arr_skill = GETPOST('arr_skill');
if(!empty($arr_skill)) {
	foreach($arr_skill as $id_skill) {
		$arr_level[$id_skill] = GETPOST('arr_level_'.$id_skill.'');
	}
}


// if(empty($dateStart) || empty($dateEnd) || empty($projectSelectedId)) {
if(empty($dateStart) || empty($dateEnd) || (empty($arrproj) && empty($projectSelectedId))) {
    $step = 0;
    $dateStart = strtotime("first day of previous month", time());
    $dateEnd = strtotime("last day of previous month", time());
}

$userid = is_object($user) ? $user->id : $user;



// start code for filtring (custom)
$cv = new CVTec($db);
$skills = $cv->getSkills();
$jobskills = $cv->getJobs();

if(count($skills) > 0) {
	foreach($skills as $key => $val) {
		$arrskills[$val->skillid] = $val->label;
		$skilluser[] = $val->userid.'_'.$val->skillid;

		if(in_array($val->skillid, $arr_skill)) {
			$arrskilllevel[] = $val->skillid.'_'.$val->rankorder.' - '.$val->skill_level;
		}
		
	}
 }

 $arrskilllevel = array_unique($arrskilllevel);



 foreach($arrskilllevel as $level) {
	$level = explode('_', $level);
	$rank = explode('-', $level[1]);

	$arr[$level[0]][intval($rank[0])] = $level[1];
	
 }



 if(count($jobskills) > 0) {
	foreach($jobskills as $key => $val) {
	
		$arrjobs[$val->fk_job] = $val->job_label;

		if(in_array($val->fk_job, $arr_job) && isset($val->skillid)) {
			$arrjobskills[] = $val->fk_job.'_'.$val->skillid.'_'.$val->rankorder.' - '.$val->skill_level;
		}
	}
 }

//  var_dump($arrjobskills);


 foreach($arrjobskills as $value) {
	
	$jobskills = explode('_', $value);
	$jobs = $jobskills[0];
	$skills = $jobskills[1];

	$rank = explode('-', $jobskills[2]);
// var_dump($rank);
	$arrjs[$jobs][$skills][intval($rank[0])] = $jobskills[2];
	
 }

 $arrjs = array_filter($arrjs);


if(!empty($arr_job)) {
	foreach($arrjs as $id_job => $vals) {
		if(in_array($id_job, $arr_job)) {
			foreach($vals as $id_skill => $val) {
				$arr_level_js[$id_job][$id_skill] = GETPOST('arr_level_js_'.$id_job.'_'.$id_skill.'');
			}
		}
	}
}


// $reportStatic = new TimesheetReport($db);

// $reportStatic->initBasic($projectIdlist, '', '', $dateStart, $dateEnd, $mode, $invoicabletaskOnly);

// $reportStatic2 = new TimesheetReport($db);
// $reportStatic2->initBasic(implode($arrproj), '', '', $dateStart, $dateEnd, $mode, $invoicabletaskOnly);

// if($action == 'getpdf') {
//     $pdf = new pdf_rat($db);
//     //$outputlangs = $langs;
//     if($pdf->writeFile($reportStatic2, $langs)>0) {
//         header("Location: ".DOL_URL_ROOT."/document.php?modulepart=feuilledetemps&file=reports/".$reportStatic2->name.".pdf");
//         return;
//     }
//     ob_end_flush();
//     exit();
// }elseif($action == 'getExport'){
//     $max_execution_time_for_export = (empty($conf->global->EXPORT_MAX_EXECUTION_TIME)?300:$conf->global->EXPORT_MAX_EXECUTION_TIME);    // 5mn if not defined
//     $max_time = @ini_get("max_execution_time");
//     if($max_time && $max_time < $max_execution_time_for_export)
//     {
//         @ini_set("max_execution_time", $max_execution_time_for_export); // This work only if safe mode is off. also web servers has timeout of 300
//     }
//     $name=$reportStatic->buildFile($model, false);
//     if(!empty($name)){
//         header("Location: ".DOL_URL_ROOT."/document.php?modulepart=export&file=".$name);
//         return;
//     }
//     ob_end_flush();
//     exit();
// }
//$_SESSION["dateStart"] = $dateStart ;
?>
<style>
	span.select2.select2-container.select2-container--default {
		width:270!important; 
	}

	.sub_title {
		font-size: 14px;
		/* font-weight: 600; */
    	letter-spacing: 2px;
		background-clip: text;
		-webkit-background-clip: text;
	}



	nav {
	 display: block;
	}
	.right {
		float: right;
		margin-left: 1em;
	}
	@font-face {
		font-family: 'icomoon';
		/* src: url('https://dl.dropbox.com/u/26865764/icomoon.eot');
		src: url('https://dl.dropbox.com/u/26865764/icomoon.eot?#iefix') format('embedded-opentype'), url('https://dl.dropbox.com/u/26865764/icomoon.dev.svg#icomoon') format('svg'), url('https://dl.dropbox.com/u/26865764/icomoon.woff') format('woff'), url('https://dl.dropbox.com/u/26865764/icomoon.ttf') format('truetype'); */
		font-weight: normal;
		font-style: normal;
	}
	.ico-heart:before, .ico-letter:before, .ico-file:before, .ico-real:before, .ico-pen:before, .ico-user:before, .ico-rocket:before, .ico-arrow-right:before, .ico-arrow-left:before, .ico-arrow-left-2:before, .ico-arrow-right-2:before, .ico-caret-down:before, .ico-caret-up:before, .ico-caret-left:before, .ico-caret-right:before, .ico-facebook:before, .ico-twitter:before, .ico-google-plus:before {
		font-family: 'icomoon';
		speak: none;
		font-style: normal;
		font-weight: normal;
		line-height: 1;
		-webkit-font-smoothing: antialiased;
	}
	.ico-caret-down:before {
		content: "\f0d7";
	}
	.ico-caret-up:before {
		content: "\f0d8";
	}
	.filter_menu_wrapper {
		position: relative;
		z-index: 10;
		/* font-family: 'Economica', sans-serif; */
		/* font-size: 1.857em; */
		/* text-transform: uppercase;
		padding-top: 2px;
		padding-bottom: 2px;
		background: #b92120; */
	}
	.filter_menu_wrapper .filter_menu {
		margin: 0;
		padding-left: 0;
		list-style: none;
		text-align: center;
	}
	.filter_menu_wrapper .filter_menu > li {
		position: relative;
		padding: 0.7em 0;
		display: inline-block;
	}
	.filter_menu_wrapper ul.filter_menu, .filter_menu_wrapper a.menu-link {
		border-top: 1px dashed #dd4949;
		border-bottom: 1px dashed #dd4949;
	}
	.filter_menu_wrapper a.menu-link {
		display: none;
		padding: 0.7em 0;
		/* background: #b92120; */
	}
	/* .filter_menu_wrapper a {
		display: block;
		position: relative;
		padding: 0 0.5em;
		margin: 0 0.2em;
		line-height: 1.3em;
		color: #ffffff;
		text-decoration: none;
		border-bottom: 1px solid transparent;
	} */
	/* .menu-wrapper a:hover {
		border-bottom: 1px solid rgba(255,255,255,0.9);
		transition: all 0.5s;
	} */
	/* .filter_menu_wrapper .filter_menu > .current-menu-item > a, .menu-wrapper .menu > .current-menu-ancestor > a {
		position: relative;
		z-index: 12;
		background: #f5d4d4;
		color: #b92120;
		border-bottom: 1px solid transparent;
	} */
	/* .menu-wrapper .menu > .current-menu-item > a:hover, .menu-wrapper .menu > .current-menu-ancestor > a:hover {
		background: #fffdfd;
	} */
	/* .menu-wrapper .menu > .current-menu-item > a:hover:after, .menu-wrapper .menu > .current-menu-ancestor > a:hover:after {
		border-top-color: #fffdfd;
	} */
 /* .menu-wrapper .menu > .current-menu-item > a:after, .menu-wrapper .menu > .current-menu-ancestor > a:after {
	 top: 100%;
	 border: solid transparent;
	 content: " ";
	 height: 0;
	 width: 0;
	 position: absolute;
	 pointer-events: none;
	 border-color: rgba(255,255,255,0);
	 border-top-color: #f5d4d4;
	 border-width: 5px;
	 left: 50%;
	 margin-left: -5px;
} */
 /* .filter_menu_wrapper li+ li a:before {
	 content: "*";
	 position: absolute;
	 left: -0.5em;
	 top: 0.2em;
	 color: #ffffff;
	 opacity: 0.8;
	 font-family: Arial, Verdana, sans-serif;
} */
	.filter_menu_wrapper .sub_filter_menu {
		box-sizing: border-box;
		-moz-box-sizing: border-box;
		/* margin: 18px 0 0 -0.7em;
		padding: 3px 0 0 0; */
		position: absolute;
		max-height: 0em;
		overflow: hidden;
		list-style: none outside none;
		text-align: left;
		text-transform: none;
	}
	.filter_menu_wrapper .sub_filter_menu li {
		display: block;
		/* background: #9b1c1b; */
		margin-left: 0.7em;
		margin-right: 0.7em;
	}
	/* .filter_menu_wrapper .sub_filter_menu a {
	 margin: 0;
	 white-space: nowrap;
	 line-height: 1.8em;
	 border: none;
} */
/* .filter_menu_wrapper .sub_filter_menu a:hover {
	 background: #781515;
} */
	.filter_menu_wrapper .sub_filter_menu li:last-child {
		padding-bottom: 0.26em;
	}
 /* .menu-wrapper .sub-menu li:last-child a {
	 border-bottom: 1px dashed #dd4949;
} */
 /* .menu-wrapper li:hover .sub-menu {
	 max-height: 30em;
	 transition: max-height 0.5s;
} */
 /* @media screen and (max-width: 768px) { */
	 .js .filter_menu_wrapper nav[role=navigation] {
		 overflow: hidden;
		 max-height: 0em;
	}
	 .js .filter_menu_wrapper nav[role=navigation].active {
		 max-height: 30em;
		 transition: max-height 0.5s ease-out;
	}
	 .filter_menu_wrapper a.menu-link {
		 display: block;
		 width: auto;
		 padding-right: 15px;
		 padding-left: 10px;
		 border: none;
		 margin: 0;
	}
	 /* .menu-wrapper a.menu-link:hover {
		 background: #9b1c1b;
	} */
	.filter_menu_wrapper a.menu-link.active .ico-caret-down:before {
		 content: "\f0d8";
	}
	 .filter_menu_wrapper .filter_menu .filter_menu {
		 padding-bottom: 5px;
	}
	.filter_menu_wrapper .filter_menu li {
		 display: block;
		 border: 1px solid /* pink */;
		 border: none;
	}
	.filter_menu_wrapper .filter_menu > li {
		 padding: 0px;
		 border: none;
	}
	/* .filter_menu_wrapper .filter_menu > li+li {
		 border-top: 1px dashed #dd4949;
	} */
	/* .filter_menu_wrapper .filter_menu a {
		 padding: 0.5em 0;
		 margin: 0px;
		 border: none;
	} */
	 /* .menu-wrapper .menu a:hover {
		 background: #9b1c1b;
	} */
	.filter_menu_wrapper .filter_menu .rightalign {
		 float: none;
	}
	 /* .menu-wrapper li + li a:before {
		 content: "";
	} */
	 .filter_menu_wrapper li:hover .sub_filter_menu {
		 max-height: 0em;
		 transition: none;
	}
	.filter_menu_wrapper .sub_filter_menu {
		 background: none;
		 margin-left: 0px;
		 margin-top: 1px;
		 display: block;
		 width: 100%;
	}
	.filter_menu_wrapper .sub_filter_menu li/* , .filter_menu_wrapper .sub_filter_menu li a */ {
		 margin: 0;
		 display: block;
		 width: 100%;
	}
	 /* .menu-wrapper .sub-menu a {
		 padding-left: 0.8em;
	} */
	 /* .menu-wrapper .sub-menu a:hover {
		 background: #781515;
	} */
	/* .filter_menu_wrapper .sub_filter_menu li {
		 border-bottom: 1px dashed #dd4949;
	} */
	.filter_menu_wrapper .sub_filter_menu li:last-child {
		 padding: 0;
	}
	 /* .menu-wrapper .sub-menu li:last-child a {
		 border: none;
	} */
	.filter_menu_wrapper .sub_filter_menu .has-subnav {
		 position: relative;
	}
	.filter_menu_wrapper li .sub_filter_menu.active {
		 /* max-height: 30em; */
		 overflow: visible;
		 position: relative;
		 z-index: 9;
		 transition: max-height 0.5s ease-out;
	}
	.filter_menu_wrapper .toggle-link {
		 height: 67px;
		 width: 60px;
		 display: block;
		 position: absolute;
		 right: 0px;
		 z-index: 200;
		 font-size: 0em;
		 cursor: pointer;
		 font-family: 'icomoon';
		 speak: none;
		 font-style: normal;
		 font-weight: normal;
		 line-height: 1;
		 -webkit-font-smoothing: antialiased;
	}
	.filter_menu_wrapper .toggle-link:hover {
		 transition: all 0.2s;
		 background: #cf2524;
	}
	.filter_menu_wrapper .filter_menu .has-subnav > .toggle-link:after {
		 content: "\f0d7";
		 position: absolute;
		 width: 50px;
		 top: 50%;
		 margin-top: -15px;
		 bottom: 50%;
		 right: 4px;
		 font-size: 28px;
		 color: #fff;
	}
	.filter_menu_wrapper .filter_menu .has-subnav > .toggle-link.active:after {
		 content: "\f0d8";
		 margin-top: -18px;
	}
/* } */
 .content {
	 max-width: 950px;
	 min-height: 500px;
	 margin: 30px auto;
}
 

</style>

<script>
	$(document).ready(function() {
      

	  /* the Responsive menu script */
		$('body').addClass('js');
			var $menu = $('#filter_menu'),
				$menulink = $('.menu-link'),
				$menuTrigger = $('.has-subnav > a');
		  
		$menulink.click(function(e) {
			e.preventDefault();
			$menulink.toggleClass('active');
			$menu.toggleClass('active');
		});
	  
		var add_toggle_links = function() {     
		  if ($('.menu-link').is(":visible")){
			if ($(".toggle-link").length > 0){
			}
			else{
			  $('.has-subnav > a').before('<span class="toggle-link"> Ouvrir submenu </span>');
			  $('.toggle-link').click(function(e) {   
				var $this = $(this);
				$this.toggleClass('active').siblings('ul').toggleClass('active');
			  }); 
			}
		  }
		  else{
			$('.toggle-link').empty();
		  }
		 }
		add_toggle_links();
		$(window).bind("resize", add_toggle_links); 
		  
		  });
	  
	  
	  
</script>
</style>
<?php
llxHeader('', $langs->trans('CVTec'), '');

$title = $langs->trans('CVTec');
print_barre_liste($title, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $num, '', 'list');


$querryRes = '';
$employee = new User($db);

$object = new CVTec($db);
// $data = $object->getUserCVData($arr_job, $arr_skill, $arr_level);
$getCv = $cv->setUserBackground();
var_dump($getCv);

foreach($data as $key => $val) {
    $arrskill[$val->userid][$val->fk_skill][$val->rankorder] = $val;
}
// var_dump( $arr_level);
// var_dump( $arrskill);

print '<div class="fichecenter">';
//to test
print '<div class="filter_menu_wrapper">';

	print '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="mode" value="'.$mode.'">';
	
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre" width="40%"><td class="liste_titre">'.$langs->trans("Filter").'</td>';
	
	print '<td class="liste_titre" width="30%">';
	print '<a href="#menu" class="menu-link"> <span class="far fa-caret-square-down right" aria-hidden="true"></span>';
  	print '</a>';
	print '</td>';
	
	print '</tr>';
	print '</table>';

	
	
//for test
print '<nav id="filter_menu">';	
print '<div class="filter_menu">';
	print '<div class="fichecenter"><div class="fichethirdleft">';
	
	print '<table>';
	// Compétences
	// print '<br>';
	print '<tr><br><br><td>'.$langs->trans("skill").'s'.'</td><td class="nowrap tdoverflowmax500">';
	
	print img_picto('', 'skill', 'class="pictofixedwidth"');
	print $form->multiselectarray('arr_skill', $arrskills, $arr_skill, '', '', '', '', '400pxx', '','', 'Tous');
	print '</td>';
	print '</tr>';
	print '</table>';

	print '<br>';
	
	print '<HR>';
	print '<table style="box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 8px 0px;">';
	
	print '<button style="background: #cad2d2;color: #777777;border-radius: 3px;border-collapse: collapse;border: none;" role="button" class="button small" type="submit" name="display_levels" value="start" title="Afficher les niveaux des compétences">'.$langs->trans("Affricher les niveaux ").'<i class="fas fa-level-down-alt"></i></button>';
	print '<button style="background: #cad2d2;color: #777777;border-radius: 3px;border-collapse: collapse;border: none;" role="button" class="button small" type="submit" name="remove_levels" value="start" title="Afficher les niveaux des compétences">'.$langs->trans("Supprimer les niveaux ").'<i class="fas fa-backspace"></i></button>';
	foreach($arr as $key => $val) {
			$ids = explode(' - ', $val);
			if(in_array($key, $arr_skill)) {
				// Niveaux des compétences
				print '<tr>';
				print '<td>'.$arrskills[$key].'</td>';
				print '<td>';
				print $form->selectarray('arr_level_'.$key.'', $val, $arr_level[$key], 1, 0, 0, '', 0, 32);
				print '</td></tr>';
			}
		}
	print '</table>';
	print '<br>';
	print '</div>';
	
	
	
	//jobs 

	print '<div class="fichetwothirdright">';
	// print '<HR>';
	print '<table>';
	// Compétences
	// print '<br>';
	print '<tr><br><br><td>'.$langs->trans("Emploi").'s'.'</td><td class="nowrap tdoverflowmax400">';
	
	print img_picto('', 'skill', 'class="pictofixedwidth"');
	print $form->multiselectarray('arr_job', $arrjobs, $arr_job, '', '', '', '', '300pxx', '','', 'Tous');
	print '</td>';
	print '</tr>';
	print '</table>';
	print '<br>';

	print '<HR>';
	print '<table style="box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 8px 0px;">';
	
	
	print '<button style="background: #cad2d2;color: #777777;border-radius: 3px;border-collapse: collapse;border: none;" role="button" class="button small" type="submit" name="display_levels" value="start" title="Afficher les compétences">'.$langs->trans("Affricher les compétences ").'<i class="fas fa-level-down-alt"></i></button>';
	print '<button style="background: #cad2d2;color: #777777;border-radius: 3px;border-collapse: collapse;border: none;" role="button" class="button small" type="submit" name="remove_levels" value="start" title="Afficher les compétences">'.$langs->trans("Supprimer les compétences ").'<i class="fas fa-backspace"></i></button>';
	// var_dump($arr_level_js);
	foreach($arrjs as $key => $vals) {
			
			if(in_array($key, $arr_job)) {
		
				// compétences
			
				print '<tr>';
				print '<td colspan="2"><br><span class="sub_title"><b>'.$arrjobs[$key].'</b></span><br><HR></td>';
				
				foreach($vals as $k => $val) {
					
					print '<tr>';
					print '<td class="nowrap tdoverflowmax300">';
					print '&nbsp; - '.$arrskills[$k].'</td>';
					print '<td class="nowrap tdoverflowmax300">';
					print $form->selectarray('arr_level_js_'.$key.'_'.$k.'', $val, $arr_level_js[$key][$k], 1, 0, 0, '', 0, 32);
					print '</td>';
					print '</tr>';
				}
				
			}
			
			print '</tr>';
			
			
		}
	print '</table>';
	print '<br>';
	print '</div>';
	print '</div>';
print '</div>';
print '</nav>';
	//
	print '<div class="fichecenter">';
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print '<div class="center">';
	// print '<div style="padding-left: 40%;">';
	print '<button style="background: var(--butactionbg);color: var(--textbutaction);border-radius: 3px;border-collapse: collapse;border: none;" role="button" class="button small" type="submit" name="filter" value="start">'.$langs->trans("Refresh").'';
	print '</div>';
	print '</div>';
	// print '</div>';
	print '</div>';
	print '</form>';
    print '</div>';
print '</div>';
// print '<div class="div-table-responsive"><table class="tagtable nobottomiftotal liste">';
// print '<tr>';

// print '</tr>';

// print '<tr class="oddeven"><td class="nowraponall">';
// print '<a href="/erp/custom/gpeccustom/cvtec_card.php?id=44662&amp;save_lastsearch_values=1" title="<span class=&quot;fa fa-file&quot; style=&quot;&quot;></span> <u>CVTec</u> <span class=&quot;badge  badge-status1 badge-status&quot; title=&quot;Actif&quot;>Actif</span><br><b>Réf.:</b> CVTEC_232" class="classfortooltip">';
// print '<span class="fa fa-file paddingright" style=""></span>CVTEC_232</a></td>';



// // var_dump($arrskill);

// foreach($arrskill as $key => $val) {
//     if(isset($key)) {
//         $employee->fetch($key);
//     }
    
//     // var_dump($employee->lastname.'-'.$employee->firstname);
//     print '<tr class="oddeven">';
//     print '<td>';
    

    
   
//     print $employee->lastname.'-'.$employee->firstname;
   
// print '</td>';
// print '</tr>';
// }


// print '<td><a href="/erp/hrm/job_card.php?id=6&amp;save_lastsearch_values=1" title="<span class=&quot;fas fa-cogs  em080&quot; style=&quot; color: #999;&quot;></span> <u>Job profile</u><br><b>Libellé:</b> Préparateur·trice Chargé·e d\'Affaire travaux neufs" class="classfortooltip">';
// print '<span class="fas fa-cogs  em080 paddingright classfortooltip" style=" color: #999;"></span>Préparateur·trice Chargé·e d\'Affaire travaux neufs</a></td>';
// print '<td><a href="/erp/hrm/skill_card.php?id=5&amp;save_lastsearch_values=1" title="<span class=&quot;fas fa-shapes&quot; style=&quot;&quot;></span> <u>Skill</u><br><b>Libellé:</b> Surveillance Technique" class="classfortooltip"><span class="fas fa-shapes paddingright classfortooltip" style=""></span>Surveillance Technique</a></td><td class="minwidth300"><a class="documentdownload paddingright" href="/erp/document.php?modulepart=gpeccustom&amp;file=cvtec%2FCVTEC_232%2FCVTEC_232.pdf&amp;entity=1" title="CVTEC_232.pdf"><i class="fa fa-file-pdf-o paddingright" title="Fichier: CVTEC_232.pdf"></i>CVTEC_232.pdf</a><a class="pictopreview documentpreview" href="/erp/document.php?modulepart=gpeccustom&amp;attachment=0&amp;file=cvtec%2FCVTEC_232%2FCVTEC_232.pdf&amp;entity=1" mime="application/pdf" target="_blank"><span class="fa fa-search-plus pictofixedwidth" style="color: gray"></span></a></td>';
// print '<td data-key="gpeccustom_cvtec.emploi" title="Préparateur·trice chargé·e d\'affaire en maintenance Préparateur·trice Chargé·e d\'Affaire travaux neufs"><div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr"><li class="select2-search-choice-dolibarr noborderoncategories" style="background: #bbb">Préparateur·trice chargé·e d\'affaire en maintenance</li>';
// print '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #bbb">Préparateur·trice Chargé·e d\'Affaire travaux neufs</li></ul></div></td><td class="nowrap center"><input id="cb44662" class="flat checkforselect" type="checkbox" name="toselect[]" value="44662"></td>';
// print '</tr>';
// print '</table>';
// print '</div>';
?>
<style>
	/* body, html {
	 height: 100%;
} */
 nav {
	 display: block;
}
 .right {
	 float: right;
	 margin-left: 1em;
}
 @font-face {
	 font-family: 'icomoon';
	 /* src: url('https://dl.dropbox.com/u/26865764/icomoon.eot');
	 src: url('https://dl.dropbox.com/u/26865764/icomoon.eot?#iefix') format('embedded-opentype'), url('https://dl.dropbox.com/u/26865764/icomoon.dev.svg#icomoon') format('svg'), url('https://dl.dropbox.com/u/26865764/icomoon.woff') format('woff'), url('https://dl.dropbox.com/u/26865764/icomoon.ttf') format('truetype'); */
	 font-weight: normal;
	 font-style: normal;
}
 .ico-heart:before, .ico-letter:before, .ico-file:before, .ico-real:before, .ico-pen:before, .ico-user:before, .ico-rocket:before, .ico-arrow-right:before, .ico-arrow-left:before, .ico-arrow-left-2:before, .ico-arrow-right-2:before, .ico-caret-down:before, .ico-caret-up:before, .ico-caret-left:before, .ico-caret-right:before, .ico-facebook:before, .ico-twitter:before, .ico-google-plus:before {
	 font-family: 'icomoon';
	 speak: none;
	 font-style: normal;
	 font-weight: normal;
	 line-height: 1;
	 -webkit-font-smoothing: antialiased;
}
 .ico-caret-down:before {
	 content: "\f0d7";
}
 .ico-caret-up:before {
	 content: "\f0d8";
}
 .menu-wrapper {
	 position: relative;
	 z-index: 10;
	 font-family: 'Economica', sans-serif;
	 font-size: 1.857em;
	 text-transform: uppercase;
	 padding-top: 2px;
	 padding-bottom: 2px;
	 /* background: #b92120; */
}
 .menu-wrapper .menu {
	 margin: 0;
	 padding-left: 0;
	 list-style: none;
	 text-align: center;
}
 .menu-wrapper .menu > li {
	 position: relative;
	 padding: 0.7em 0;
	 display: inline-block;
}
 .menu-wrapper ul.menu, .menu-wrapper a.menu-link {
	 border-top: 1px dashed #dd4949;
	 border-bottom: 1px dashed #dd4949;
}
 .menu-wrapper a.menu-link {
	 display: none;
	 padding: 0.7em 0;
	 background: #b92120;
}
 .menu-wrapper a {
	 display: block;
	 position: relative;
	 padding: 0 0.5em;
	 margin: 0 0.2em;
	 line-height: 1.3em;
	 /* color: #ffffff; */
	 text-decoration: none;
	 border-bottom: 1px solid transparent;
}
 .menu-wrapper a:hover {
	 border-bottom: 1px solid rgba(255,255,255,0.9);
	 transition: all 0.5s;
}
 .menu-wrapper .menu > .current-menu-item > a, .menu-wrapper .menu > .current-menu-ancestor > a {
	 position: relative;
	 z-index: 12;
	 background: #f5d4d4;
	 color: #b92120;
	 border-bottom: 1px solid transparent;
}
 .menu-wrapper .menu > .current-menu-item > a:hover, .menu-wrapper .menu > .current-menu-ancestor > a:hover {
	 background: #fffdfd;
}
 .menu-wrapper .menu > .current-menu-item > a:hover:after, .menu-wrapper .menu > .current-menu-ancestor > a:hover:after {
	 border-top-color: #fffdfd;
}
 .menu-wrapper .menu > .current-menu-item > a:after, .menu-wrapper .menu > .current-menu-ancestor > a:after {
	 top: 100%;
	 border: solid transparent;
	 content: " ";
	 height: 0;
	 width: 0;
	 position: absolute;
	 pointer-events: none;
	 border-color: rgba(255,255,255,0);
	 border-top-color: #f5d4d4;
	 border-width: 5px;
	 left: 50%;
	 margin-left: -5px;
}
 .menu-wrapper li+ li a:before {
	 content: "*";
	 position: absolute;
	 left: -0.5em;
	 top: 0.2em;
	 /* color: #ffffff; */
	 opacity: 0.8;
	 font-family: Arial, Verdana, sans-serif;
}
 .menu-wrapper .sub-menu {
	 box-sizing: border-box;
	 -moz-box-sizing: border-box;
	 margin: 18px 0 0 -0.7em;
	 padding: 3px 0 0 0;
	 position: absolute;
	 max-height: 0em;
	 overflow: hidden;
	 list-style: none outside none;
	 text-align: left;
	 text-transform: none;
}
 .menu-wrapper .sub-menu li {
	 display: block;
	 background: #9b1c1b;
	 margin-left: 0.7em;
	 margin-right: 0.7em;
}
 .menu-wrapper .sub-menu a {
	 margin: 0;
	 white-space: nowrap;
	 line-height: 1.8em;
	 border: none;
}
 .menu-wrapper .sub-menu a:hover {
	 background: #781515;
}
 .menu-wrapper .sub-menu li:last-child {
	 padding-bottom: 0.26em;
}
 .menu-wrapper .sub-menu li:last-child a {
	 border-bottom: 1px dashed #dd4949;
}
 .menu-wrapper li:hover .sub-menu {
	 max-height: 30em;
	 transition: max-height 0.5s;
}
 /* @media screen and (max-width: 768px) { */
	 .js .menu-wrapper nav[role=navigation] {
		 overflow: hidden;
		 max-height: 0em;
	}
	 .js .menu-wrapper nav[role=navigation].active {
		 max-height: 30em;
		 transition: max-height 0.5s ease-out;
	}
	 .menu-wrapper a.menu-link {
		 display: block;
		 width: auto;
		 padding-right: 15px;
		 padding-left: 10px;
		 border: none;
		 margin: 0;
	}
	 .menu-wrapper a.menu-link:hover {
		 background: #9b1c1b;
	}
	 .menu-wrapper a.menu-link.active .ico-caret-down:before {
		 content: "\f0d8";
	}
	 .menu-wrapper .menu .menu {
		 padding-bottom: 5px;
	}
	 .menu-wrapper .menu li {
		 display: block;
		 border: 1px solid pink;
		 border: none;
	}
	 .menu-wrapper .menu > li {
		 padding: 0px;
		 border: none;
	}
	 .menu-wrapper .menu > li+li {
		 border-top: 1px dashed #dd4949;
	}
	 .menu-wrapper .menu a {
		 padding: 0.5em 0;
		 margin: 0px;
		 border: none;
	}
	 .menu-wrapper .menu a:hover {
		 background: #9b1c1b;
	}
	 .menu-wrapper .menu .rightalign {
		 float: none;
	}
	 .menu-wrapper li + li a:before {
		 content: "";
	}
	 .menu-wrapper li:hover .sub-menu {
		 max-height: 0em;
		 transition: none;
	}
	 .menu-wrapper .sub-menu {
		 background: none;
		 margin-left: 0px;
		 margin-top: 1px;
		 display: block;
		 width: 100%;
	}
	 .menu-wrapper .sub-menu li, .menu-wrapper .sub-menu li a {
		 margin: 0;
		 display: block;
		 width: 100%;
	}
	 .menu-wrapper .sub-menu a {
		 padding-left: 0.8em;
	}
	 .menu-wrapper .sub-menu a:hover {
		 background: #781515;
	}
	 .menu-wrapper .sub-menu li {
		 border-bottom: 1px dashed #dd4949;
	}
	 .menu-wrapper .sub-menu li:last-child {
		 padding: 0;
	}
	 .menu-wrapper .sub-menu li:last-child a {
		 border: none;
	}
	 .menu-wrapper .menu .has-subnav {
		 position: relative;
	}
	 .menu-wrapper li .sub-menu.active {
		 max-height: 30em;
		 overflow: visible;
		 position: relative;
		 z-index: 9;
		 transition: max-height 0.5s ease-out;
	}
	 .menu-wrapper .toggle-link {
		 height: 67px;
		 width: 60px;
		 display: block;
		 position: absolute;
		 right: 0px;
		 z-index: 200;
		 font-size: 0em;
		 cursor: pointer;
		 font-family: 'icomoon';
		 speak: none;
		 font-style: normal;
		 font-weight: normal;
		 line-height: 1;
		 -webkit-font-smoothing: antialiased;
	}
	 .menu-wrapper .toggle-link:hover {
		 transition: all 0.2s;
		 background: #cf2524;
	}
	 .menu-wrapper .menu .has-subnav > .toggle-link:after {
		 content: "\f0d7";
		 position: absolute;
		 width: 50px;
		 top: 50%;
		 margin-top: -15px;
		 bottom: 50%;
		 right: 4px;
		 font-size: 28px;
		 color: #fff;
	}
	 .menu-wrapper .menu .has-subnav > .toggle-link.active:after {
		 content: "\f0d8";
		 margin-top: -18px;
	}
/* } */
 .content {
	 max-width: 950px;
	 min-height: 500px;
	 margin: 30px auto;
}
 

</style>
<div class="menu-wrapper">

  <a href="#menu" class="menu-link">  Filtrer<span class="far fa-caret-square-down right" aria-hidden="true"></span>
  </a>
  <nav id="menu" role="navigation">
    <div class="menu">
      <ul  class="menu">
        <li>
          <a href="#">Emplois</a>
        </li>
        <li  class=" current-menu-item">
          <a href="#">Compétences</a>
		  <?php
		  print '<table class="filter_table">';
		  // Compétences
		  // print '<br>';
		  print '<tr><br><td>'.$langs->trans("skill").'s'.'</td><td class="nowrap tdoverflowmax800">';
		  
		  print img_picto('', 'skill', 'class="pictofixedwidth"');
		  print $form->multiselectarray('arr_skill1', $arrskills, $arr_skill1, '', '', '', '', '600pxx', '','', 'Tous');
		  print '</td>';
		  print '</tr>';
		  print '</table>';
		  ?>
        </li>
		<li  class="has-subnav">
          <a href="#">Compétences et iveaux</a>
          <ul class="sub-menu">
            <li>
              <a href="#">Rien</a>
				<ul class="sub-menu">
					<li>
						<a href="#">Bloc compétence 2 (test)</a>
					</li>
					<li>
						<a href="#">Test</a>
					</li>
				</ul>
		
            </li>
          </ul>
        </li>
        <li  class="has-subnav">
          <a href="#">Bloc compétence (test)</a>
          <ul class="sub-menu">
            <li>
              <a href="#">Bloc niveau (test)</a>
            </li>
            <li>
              <a href="#">Test</a>
            </li>
            <!-- <li>
              <a href="#">Copywriting</a>
            </li>
            <li>
              <a href="#">SEO & a longer menu item</a>
            </li>
            <li>
              <a href="#">Social networking</a>
            </li> -->
          </ul>
        </li>
        <li>
          <a href="#">Raffrichir</a>
        </li>
        <!-- <li>
          <a href="#">Contact us</a>
        </li> -->
      </ul>
    </div>
  </nav>
</div>
<div class="content">
  <p>Filtre responisive (test).</p>
  <p> (Test) form</p>
</div>
 
<script>
	$(document).ready(function() {
      

	  /* the Responsive menu script */
		$('body').addClass('js');
			var $menu = $('#menu'),
				$menulink = $('.menu-link'),
				$menuTrigger = $('.has-subnav > a');
		  
		$menulink.click(function(e) {
			e.preventDefault();
			$menulink.toggleClass('active');
			$menu.toggleClass('active');
			$( ".current-menu-item" ).show();
		});
	  
		var add_toggle_links = function() {     
		  if ($('.menu-link').is(":visible")){
			
			if ($(".toggle-link").length > 0){
			}
			else{
			  $('.has-subnav > a').before('<span class="toggle-link"> Open submenu </span>');
			  $('.toggle-link').click(function(e) {   
				var $this = $(this);
				$this.toggleClass('active').siblings('ul').toggleClass('active');
				$( ".current-menu-item" ).hide();
				
			  }); 
			}
		  }
		  else{
			$('.toggle-link').empty();
		
		  }
		 }
		add_toggle_links();
		$(window).bind("resize", add_toggle_links); 
		
		  });
	  
	  
	  
</script>

<?php

llxFooter();
$db->close();


// print '<div class="fichecenter">';
// //to test
// print '<div class="filter_menu_wrapper">';

// 	print '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
// 	print '<input type="hidden" name="token" value="'.newToken().'">';
// 	print '<input type="hidden" name="mode" value="'.$mode.'">';
	
// 	print '<table class="noborder centpercent">';
// 	print '<tr class="liste_titre" width="40%"><td class="liste_titre">'.$langs->trans("Filter").'</td>';
	
// 	print '<td class="liste_titre" width="30%">';
// 	print '<a href="#menu" class="menu-link"> <span class="far fa-caret-square-down right" aria-hidden="true"></span>';
//   	print '</a>';
// 	print '</td>';
	
// 	print '</tr>';
// 	print '</table>';

	
	
// //for test
// print '<nav id="filter_menu">';	
// print '<div class="filter_menu">';
// 	print '<div class="fichecenter"><div class="fichethirdleft">';
	
// 	print '<table>';
// 	// Compétences
// 	// print '<br>';
// 	print '<tr><br><br><td>'.$langs->trans("skill").'s'.'</td><td class="nowrap tdoverflowmax500">';
	
// 	print img_picto('', 'skill', 'class="pictofixedwidth"');
// 	print $form->multiselectarray('arr_skill', $arrskills, $arr_skill, '', '', '', '', '400pxx', '','', 'Tous');
// 	print '</td>';
// 	print '</tr>';
// 	print '</table>';

// 	print '<br>';
	
// 	print '<HR>';
// 	print '<table style="box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 8px 0px;">';
	
// 	print '<button style="background: #cad2d2;color: #777777;border-radius: 3px;border-collapse: collapse;border: none;" role="button" class="button small" type="submit" name="display_levels" value="start" title="Afficher les niveaux des compétences">'.$langs->trans("Affricher les niveaux ").'<i class="fas fa-level-down-alt"></i></button>';
// 	print '<button style="background: #cad2d2;color: #777777;border-radius: 3px;border-collapse: collapse;border: none;" role="button" class="button small" type="submit" name="remove_levels" value="start" title="Afficher les niveaux des compétences">'.$langs->trans("Supprimer les niveaux ").'<i class="fas fa-backspace"></i></button>';
// 	foreach($arr as $key => $val) {
// 			$ids = explode(' - ', $val);
// 			if(in_array($key, $arr_skill)) {
// 				// Niveaux des compétences
// 				print '<tr>';
// 				print '<td>'.$arrskills[$key].'</td>';
// 				print '<td>';
// 				print $form->selectarray('arr_level_'.$key.'', $val, $arr_level[$key], 1, 0, 0, '', 0, 32);
// 				print '</td></tr>';
// 			}
// 		}
// 	print '</table>';
// 	print '<br>';
// 	print '</div>';
	
	
	
// 	//jobs 

// 	print '<div class="fichetwothirdright">';
// 	// print '<HR>';
// 	print '<table>';
// 	// Compétences
// 	// print '<br>';
// 	print '<tr><br><br><td>'.$langs->trans("Emploi").'s'.'</td><td class="nowrap tdoverflowmax500">';
	
// 	print img_picto('', 'skill', 'class="pictofixedwidth"');
// 	print $form->multiselectarray('arr_job', $arrjobs, $arr_job, '', '', '', '', '400pxx', '','', 'Tous');
// 	print '</td>';
// 	print '</tr>';
// 	print '</table>';
// 	print '<br>';

// 	print '<HR>';
// 	print '<table style="box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 8px 0px;">';
	
	
// 	print '<button style="background: #cad2d2;color: #777777;border-radius: 3px;border-collapse: collapse;border: none;" role="button" class="button small" type="submit" name="display_levels" value="start" title="Afficher les compétences">'.$langs->trans("Affricher les compétences ").'<i class="fas fa-level-down-alt"></i></button>';
// 	print '<button style="background: #cad2d2;color: #777777;border-radius: 3px;border-collapse: collapse;border: none;" role="button" class="button small" type="submit" name="remove_levels" value="start" title="Afficher les compétences">'.$langs->trans("Supprimer les compétences ").'<i class="fas fa-backspace"></i></button>';
// 	// var_dump($arr_level_js);
// 	foreach($arrjs as $key => $vals) {
			
// 			if(in_array($key, $arr_job)) {
		
// 				// compétences
			
// 				print '<tr>';
// 				print '<td colspan="2"><span class="sub_title"><b>'.$arrjobs[$key].'</b></span><br><HR></td>';
				
// 				foreach($vals as $k => $val) {
					
// 					print '<tr>';
// 					print '<td class="nowrap tdoverflowmax500">';
// 					print '&nbsp; - '.$arrskills[$k].'</td>';
// 					print '<td>';
// 					print $form->selectarray('arr_level_js_'.$key.'_'.$k.'', $val, $arr_level_js[$key][$k], 1, 0, 0, '', 0, 32);
// 					print '</td>';
// 					print '</tr>';

// 				}
				
// 			}
// 			print '</tr>';
			
// 		}
// 	print '</table>';
// 	print '<br>';
// 	print '</div>';
// 	print '</div>';
// print '</div>';
// print '</nav>';
// 	//
// 	print '<div class="fichecenter">';
// 	print '<div class="liste_titre liste_titre_bydiv centpercent">';
// 	print '<div class="center">';
// 	// print '<div style="padding-left: 40%;">';
// 	print '<button style="background: var(--butactionbg);color: var(--textbutaction);border-radius: 3px;border-collapse: collapse;border: none;" role="button" class="button small" type="submit" name="filter" value="start">'.$langs->trans("Refresh").'';
// 	print '</div>';
// 	print '</div>';
// 	// print '</div>';
// 	print '</div>';
// 	print '</form>';
//     print '</div>';
// print '</div>';