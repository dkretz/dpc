<?php
$relPath = "./pinc/";
require_once "pinc/dpinit.php";

if(! $User->IsLoggedIn()) {
    die("Please log in.");
}
$projectid = ArgProjectid();
$project   = new DpProject($projectid);
if(! $project->Exists()) {
    die("Project doesn't exist.");
}
$phase      = Arg("phase", "last");
$exact      = ArgBoolean("exact");
$include    = Arg("include");
$ispagetags = ArgBoolean("ispagetags");

switch($phase) {
    case "PREP":
    case "P1":
    case "P2":
    case "P3":
    case "F1":
    case "F2":
        break;
    case "PP":
    case "PPV":
    case "POSTED":
        $phase = "F2";
        break;
    default:
        $phase = "last";
        break;
}


$filename = "{$projectid}_{$phase}.txt";
if($phase == 'last') {
    $text = $project->LastCompletedText();
}
else {
    $text = $project->PhaseExportText($phase, $include, $exact, $ispagetags);
}

send_string($filename, $text);
//}
//else {
//	$text = $pg->PhaseText($pg->Phase());
//}

//header("Content-Type: application/octet-stream");    //
//header("Content-Length: " . strlen($text));
//header('Content-Disposition: attachment; filename='.$filename);
//echo $text;
exit;
