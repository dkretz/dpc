<?php
$relPath = "./pinc/";
require_once "pinc/dpinit.php";

$User->IsLoggedIn()
    or redirect_to_home();

$projectid = ArgProjectid();
$pagename  = ArgPageName();
$phase     = Arg("phase");
$version   = Arg("version");

if($pagename == "") {
    $project = new DpProject($projectid);
    $text = ($phase == "all")
        ? $project->ActiveText()
        : $project->RoundText($phase);
}
else {
    $page = new DpPage($projectid, $pagename);
    $text = ($phase != "")
        ? $text = $page->PhaseText($phase)
        : ($version == "")
            ? $page->ActiveText()
            : $page->VersionText($version);
}

echo
"<!DOCTYPE html>
<html lang='en'>
<head>
<meta charset='utf-8'>
<title>$projectid Page $pagename</title>
</head>
<body>\n";

echo "<pre>" . h($text) . "</pre>";

echo "</body></html>";
