<?php
$relPath = "./pinc/";
require_once "pinc/dpinit.php";

$projectid = ArgProjectid();
$pagename  = ArgPageName();

$pg = new DpPage($projectid, $pagename);
$imgpath = $pg->ImageFilePath();
$sfx = FileNameExtension($imgpath);
//$sfx = right($imgpath, 3);

header("Content-length: " . filesize($imgpath));
header("Content-type: image/$sfx");
$fp = fopen($imgpath, "rb");
fpassthru($fp);
fclose($fp);
?>
