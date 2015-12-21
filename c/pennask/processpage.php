<?php
$relPath="../pinc/";
require_once $relPath."dpinit.php";

if(! $User->IsLoggedIn()) {
    redirect_to_home();
    exit;
}

$projectid          = ArgProjectId();
if(! $projectid)
    die("No projectid");
$pagename           = ArgPageName();
if(! $pagename)
    die("No pagename");

$tatext             = Arg('tatext');
$seltodo            = Arg("seltodo");
$langcode           = Arg("langcode");
$acceptwords        = Arg("acceptwords");
$editor             = Arg("editor");
$badreason          = Arg("badreason", "");

//if($editor != "") {
//	$User->SetInterface($editor);
//}
if(IsArg("opt_submit_continue_x")) {
	$seltodo = "opt_submit_continue";
}
else if(IsArg("opt_return_quit_x")) {
	$seltodo = "opt_return_quit";
}
else if(IsArg("opt_submit_quit_x")) {
	$seltodo = "opt_submit_quit";
}
else if(IsArg("opt_draft_continue_x")) {
    $seltodo = "opt_draft_continue";
}
else if(IsArg("opt_draft_quit_x")) {
	$seltodo = "opt_draft_quit";
}
else if(IsArg("opt_mark_bad_x")) {
	$seltodo = "opt_mark_bad";
}

$awords             = preg_split("/\t/", $acceptwords);

$page               = new DpPage($projectid, $pagename);

if(count($awords) > 0) {
    $page->SuggestWordsArray($langcode, $awords);
}

/** @var DpPage $page */
if(! $page->IsAvailable() && ! $page->UserIsOwner()) {
	LogMsg("Owner is " . $page->Owner() . " and User is " . $User->Username() . "
		Action: $seltodo
		Projectid: $projectid
		Page: $pagename
		Phase: $phase");
}

switch($seltodo) {
    case "opt_draft_quit" :
        $page->SaveText($tatext);
        redirect_to_project($projectid);
        break;

    case "opt_draft_continue" :
        $page->SaveText($tatext);
        redirect_to_proof_page($projectid, $pagename);
        break;

    case "opt_mark_bad" :
		$page->MarkBad($badreason);
		redirect_to_project($projectid);
        break;

    case "opt_submit_continue" :
        $page->saveAsDone($tatext);
        $project = new DpProject($projectid);
        if($project->IsRoundCompleted()) {
            redirect_to_project($projectid, "Round Complete");
            exit;
        }
        if($project->IsAvailableForActiveUser()) {
            redirect_to_proof_next($projectid);
            exit;
        }
        else {
            redirect_no_page_available($projectid);
            exit;
        }
        break;

    case "opt_submit_quit" :
        $page->saveAsDone($tatext);
        redirect_to_project($projectid);
        break;

    case "opt_return_quit" :
        $page->returnToRound();
        redirect_to_project($projectid);
        break;

    default :
        // send it back where it came from
        redirect_to_project($projectid);
        exit;
}

// vim: sw=4 ts=4 expandtab
