<?PHP
$relPath='./../pinc/';
include($relPath.'dpinit.php');
include($relPath.'project_states.inc');

$title=_("Miscellaneous User Statistics");
theme($title,'header');
echo "<center><h1><i>$title</i></h1></center>";


echo "<center><img src=\"jpgraph_files/average_hour_users_logging_on.php\"></center><br>";
echo "<center><img src=\"jpgraph_files/users_by_language.php\"></center><br>";
echo "<center><img src=\"jpgraph_files/users_by_country.php\"></center><br>";
echo "<center><img src=\"jpgraph_files/users_by_month_joined.php\"></center><br>";

theme('','footer');
?>
