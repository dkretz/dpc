<?php
ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
require_once $relPath . "dpinit.php";
$diffPath = '../../Lib/php-text-difference/src/Diff/";Diff.php' ;
require_once '../../Lib/php-text-difference/src/Diff/Diff.php' ;
require_once '../../Lib/php-text-difference/src/Diff/SequenceMatcher.php' ;
require_once '../../Lib/php-text-difference/src/Diff/Renderer/AbstractRenderer.php' ;
require_once '../../Lib/php-text-difference/src/Diff/Renderer/Html/ArrayRenderer.php' ;
require_once '../../Lib/php-text-difference/src/Diff/Renderer/Html/SideBySide.php' ;

$User->IsLoggedIn()
or RedirectToLogin();

$projectid       = ArgProjectId();
$phase           = Arg("phase");
$prevphase       = $Context->PhaseBefore($phase);

if(! $projectid)
    die(_("Project id not provided."));
if(! $phase)
    die(_("Phase not provided."));

$project         = new DpProject($projectid);

$pgnames         = $project->PageNames();

$text0 = $text1 = "";
foreach($pgnames as $pgname) {
	$pg = new DpPage($projectid, $pgname);
	$text0 .= $pg->PhasePreviousText($phase);
	$text1 .= $pg->PhaseText($phase);
}

$project_title   = $project->Title();

$label           = $phase;

$title      = "Project Diff — $project_title ($prevphase to $phase)";
$projlink   = link_to_project($projectid, "Go to project page");

echo "<!DOCTYPE HTML>
<html>
<head>
<meta charset='utf-8'>
<title>$title</title>
<link type='text/css' rel='stylesheet' href='{$css_url}/dp.css'>
<link rel='stylesheet' href='/Lib/php-text-difference/example/styles.css' type='text/css' charset='utf-8'/>
<script type='text/javascript' src='dpdiff.js'></script>
</head>

<body onload='init()'>
<div class='container left'>

<h1 class='center'>$title</h1>

<div id='diffbox' class='center w80'>
    <form id='navform' name='navform' method='POST' 
            accept-charset='UTF-8' class='right' >

    </form>
    <p>{$projlink}</p>
    </div>\n";

	$lines0   = text_lines($text0);
	$lines1   = text_lines($text1);
	$diff     = new \Adaptive\Diff\Diff( $lines0, $lines1 );
	$renderer = new \Adaptive\Diff\Renderer\Html\SideBySide();
	echo $diff->render( $renderer );
echo "
</div>
</body></html>\n";

// ---------------------------------------------------------


// theme_footer();
exit;


// vim: sw=4 ts=4 expandtab
?>
