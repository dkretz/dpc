<?php
$relPath = "./pinc/";
require_once "pinc/dpinit.php";

$projectid = ArgProjectid();
$pagename  = ArgPageName();
$roundid   = Arg("roundid");
$version   = Arg("version");

$pg = new DpPage($projectid, $pagename);
$text = $pg->VersionText($version);

//if($roundid) {
//	$text = $pg->PhaseText($roundid);
//}
//else {
//	$text = $pg->PhaseText($pg->Phase());
//}

echo
"<!DOCTYPE html>
<html lang=lenl>
<head>
<meta charset='utf-8'>
<title>$projectid Page $pagename</title>
</head>
<body>\n";

echo "<pre>" . h($text) . "</pre>";

echo "</body></html>";
