<?PHP
ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../../pinc/";
include_once($relPath . 'dpinit.php');

//$otid = Arg("otid", "0");
$tid  = Arg("tid");
$tid or die("No tid provided.");

$User->AddTeamId($tid);
divert(url_for_team_list());

