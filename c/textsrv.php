<?php
$relPath = "./pinc/";
require_once "pinc/dpinit.php";

$projectid = ArgProjectid();
$pagename  = ArgPageName();
$roundid   = Arg("roundid");
$phase     = Arg("phase");
$version   = Arg("version");

$pg = new DpPage($projectid, $pagename);

if(isset($version)) {
	$text = $pg->VersionText( $version );
}

else if($roundid) {
		$version = $pg->RoundVersion($roundid);
		$text = $version->VersionText();
}

else if($phase) {
	$version = $pg->PhaseVersion($phase);
	$text = $version->VersionText();
	die("No round or version provided");
}


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
