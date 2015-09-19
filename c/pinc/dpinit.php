<?PHP
global $relPath;
ini_set('display_errors', 1);
error_reporting(E_ALL);

mb_language("uni");
mb_internal_encoding("UTF-8");

//require_once $relPath.'site_vars.php';
require_once $relPath.'theme.inc';
require_once $relPath.'helpers.php';
require_once $relPath . "udb_user.php";
require_once $relPath.'DpDbi.class.php';
$dpdb = new DpDb();
//assert($dpdb);
require_once $relPath.'DpPhpbb3.class.php';
require_once $relPath.'DpUser.class.php';
require_once $relPath.'DpContext.class.php';
require_once $relPath."lists.php";
require_once $relPath."phases.php";
//require_once $relPath."rounds.php";
require_once $relPath."links.php";
require_once $relPath.'DpProject.class.php';
require_once $relPath.'DpPage.class.php';
require_once $relPath.'DpVersion.class.php';

require_once $relPath.'DpTable.class.php';
include_once($relPath.'gettext_setup.inc');
include_once($relPath.'site_news.inc');
//include_once($relPath.'RoundsInfo.php');

//require_once $relPath . "udb_user.php";

global $db_server, $db_user, $db_password;
global $db_name;

mysql_connect($db_server, $db_user, $db_password);
mysql_select_db($db_name);

$u = Arg('userNM');
$p = Arg('userPW');

$User = new DpThisUser($u, $p);

$Context = new DpContext();
$Log     = new DpLog($site_log_path, true);

$basic_header = "
<!DOCTYPE HTML>
<html lang='en'>
<head>
<meta charset='utf-8'>
<link rel='shortcut icon' href='http://www.pgdpcanada.net/c/favicon.ico'>
<title>DPC: Welcome</title>
<link rel='Stylesheet' href='/c/css/dp.css'>
<script type='text/javascript' src='/c/js/dp.js'></script>
</head>
<body >\n";


