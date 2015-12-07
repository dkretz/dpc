<?php
/**
 * Created by PhpStorm.
 * User: don
 * Date: 8/23/2015
 * Time: 3:18 PM
 */

error_reporting(E_ALL);
$relPath = "./pinc/";
require_once $relPath."dpinit.php";

//$Log->SetOn();

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
$projectid          = @$json->projectid;
$pagename           = @$json->pagename;
$imagepath          = @$json->imagepath;
$textpath           = @$json->textpath;
$username           = @$json->username;
$token              = @$json->token;

/** @var DpProject $project */
$project = new DpProject($projectid);

/** @var DpContext $Context */
switch($querycode) {
	case "addpage":
		$Context->AddPage( $projectid, $pagename, $imagepath, $textpath );
		$rsp = array();
		$rsp["querycode"] = $querycode;
		$rsp["response"] = "ack";
		json_echo($rsp);
		return;
	default:
		break;
}

function json_echo($rsp) {
	$rsp = unampersand(json_encode($rsp));
	$rsp = rawurlencode($rsp);
	echo $rsp;
}

function unampersand($str) {
	return preg_replace("/&/", "~~", $str);
}
