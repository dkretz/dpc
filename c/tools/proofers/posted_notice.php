<?PHP
$relPath="./../../pinc/";
include_once $relPath.'dpinit.php';

$User->IsLoggedIn()
	or RedirectToLogin();


$projectid  = Arg('projectid');
$setclear   = Arg('setclear');
$username   = $User->Username();

if($setclear == "set" && ! $dpdb->SqlExists("
            SELECT 1 FROM notify
            WHERE projectid = '$projectid' AND username = '$username'")) {
    $dpdb->SqlExecute("
            INSERT INTO notify (projectid, username)
            VALUES ('$projectid', '$username')");
}
else if($setclear == "clear") {
    $dpdb->SqlExecute("
            DELETE FROM notify 
            WHERE projectid = '$projectid' AND username = '$username'");
}

$projectstate = $dpdb->SqlOneValue("
            SELECT state FROM projects
            WHERE projectid = '$projectid'");

divert(url_for_project($projectid));
?>
