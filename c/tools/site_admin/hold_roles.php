<?php
ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath = "./../../pinc/";
require_once $relPath."dpinit.php";
require_once $relPath."DpTable.class.php";
require_once $relPath."theme.inc";


$User->IsSiteManager()
    or die("Site managers only.");


$sql = "SELECT  ht.code, r.code,
                r.description, 
                ifnull(ur.id, '') AS urid
        FROM roles r
        LEFT JOIN hold_roles hr
            ON r.
        LEFT JOIN user_roles ur
        ON r.code = ur.rolecode
            AND ur.username = '$username'";
$rows = $dpdb->SqlRows($sql);

$tbl = new DpTable("tblroles");
$tbl->AddColumn("<Role", "code");
$tbl->AddColumn("<Description", "description");
$tbl->AddColumn("<Status", "urid", "estatus");
$tbl->AddColumn("^Grant", null, "egrant");
$tbl->AddColumn("^Revoke", null, "erevoke");
$tbl->SetRows($rows);

echo "<!DOCTYPE html>
<html>
<head>
<title>Roles for {$username}</title>
</head>
<body>

<h1>Roles for {$username}</h1>
<form name='frmroles' id='frmroles'>
<input type='hidden' name='username' id='username' value='$username'\>\n";
$tbl->EchoTable();
echo "</form>\n";
exit;

function ecode($code) {
    return $code;
}
function estatus($urid) {
    return $urid == "" ? "" : "Yes";
}
function egrant($row) {
    return $row["urid"] ? "" : grant_button($row['code']);
}
function erevoke($row) {
    return $row["urid"] ? revoke_button($row['code']) : "";
}

function grant_button($code) {
    return "<input type='submit' value='Grant' name='grant$code' id='grant$code'/>";
}

function revoke_button($code) {
    return "<input type='submit' value='Revoke' name='revoke$code' id='revoke$code'/>";
}

function HandleArgs($username) {
    global $_REQUEST;
    foreach($_REQUEST as $arg => $val) {
        dump("$arg $val");
        if(left($arg, 5) == "grant") {
            handle_grant($username, mid($arg, 5));
        }
        else if(left($arg, 6) == "revoke") {
            handle_revoke($username, mid($arg, 6));
        }
    }
}

function handle_grant($username, $role) {
    global $dpdb;

    dump($role);
    if(! $dpdb->SqlExists("
            SELECT 1 FROM user_roles
            WHERE username = '$username'
                AND rolecode = '$role'")) {
        $dpdb->SqlExecute("
                 INSERT INTO user_roles
                 SET username = '$username',
                     rolecode = '$role'");
   }
}

function handle_revoke($username, $role) {
    global $dpdb;
    $dpdb->SqlExecute("
        DELETE INTO user_roles
        WHERE username = '$username'
            AND rolecode = '$role'");
}


// vim: sw=4 ts=4 expandtab
