<?php
/*
    js constructs the "new Object()".
    Then serializes it with 
        var jq = 'jsonqry=' + JSON.stringinify(obj);
    ajax POSTs it in form variable jsonqry to wc.php (this file)
    php loads POST with 
        $jq = Arg("jsonqry");
    Then unserializes it with 
        $json = json_decode($jq);
    giving a php assoc. array with the first key = "querycode",
        and other keys as required for the specific query.

    encoding:

    Payloads should be encoded to protect php, javascript, json, etc.
        php rawurlencode is equivalent to js encodeURIComponent.
    A php assoc. array is mapped to a javascript object.

    So we should be able to encode everything with encodeURIComponent after serialization
        in js (var jq) and decode it in PHP with rawurldecode before json_decode in php.
    

    $msgtype spellcheck
    accepts: language 
             text to check
    returns: same text marked up with things to check

    $msgtype goodword
    accepts: 
*/

error_reporting(E_ALL);
$relPath = "./pinc/";
require_once $relPath."dpinit.php";
require_once $relPath."DpProject.class.php";
include_once($relPath.'links.php');

$jq             = Arg("jsonqry");
$jq             = rawurldecode($jq);
$json           = json_decode($jq);

if(! is_object($json)) {
    $err = "wc: json not-an-object error: {$jq}";
    send_alert($err);
    echo "not an object :: $jq";
    exit;
}


$querycode          = $json->querycode;
//$username           = @$json->username;
$projectid          = @$json->projectid;
$token              = @$json->token;

// if a username is provided, reset active User
//if($username) {
//    $User               = new DpUser($username);
//}

// LogMsg("wc recv: $json");

// these queries come from dp_edit.js
switch($querycode) {
    case "wptext":
        $project        = new DpProject($projectid);
		$text           = $project->PrePostText();

        $a["querycode"] = "wctext";
        $a["token"]     = $token;
        $a["wptext"]    = $text;
        json_echo($a);
        exit;

	default:
		die("Unknown query $querycode");
}

function json_echo($rsp) {
    $rsp = unampersand(json_encode($rsp));
    $rsp = rawurlencode($rsp);
    echo $rsp;
}

function unampersand($str) {
    return preg_replace("/&/", "~~", $str);
}

?>
