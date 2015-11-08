<?PHP
ini_set('display_errors', 1);
ini_set('max_execution_time', 0);
error_reporting(E_ALL);

$relPath = "../c/pinc/";
require $relPath . "dpinit.php";

$rows = $dpdb->SqlRows("
    SELECT projectid, nameofwork, authorsname, username, state, comments
     FROM oldprojects
    ORDER BY projectid");
$tbl = new DpTable("tblold", "dptable");
$tbl->AddColumn("Project ID", "projectid");
$tbl->AddColumn("Title", "nameofwork");
$tbl->AddColumn("Author", "authorsname");
$tbl->AddColumn("Proj Mgr", "username");
$tbl->AddColumn("Round", "state");
$tbl->AddColumn("Comments", "comments");
$tbl->SetRows($rows);
$tbl->EchoTable();
