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

$awords             = preg_split("/\t/", $acceptwords);

$page               = new DpPage($projectid, $pagename);
switch($page->Phase()) {
	case "PREP":
	case "P1":
	case "P2":
	case "P3":
		$tatext = norm($tatext);
		break;
	default:
		break;
}

if(count($awords) > 0) {
    $page->SuggestWordsArray($langcode, $awords);
}

if(! $page->UserIsOwner()) {
    $errmsg = "You are trying to save a page that is not checked out to you.
        If you think this is an error, please post to the System Errors topic in the Forums.";
    redirect_to_error_page($errmsg);
    exit;
}

switch($seltodo) {
    case "opt_draft_quit" :
        $page->saveText($tatext);
        redirect_to_project($projectid);
        break;

    case "opt_draft_continue" :
        $page->saveText($tatext);
        redirect_to_proof_page($projectid, $pagename);
        break;

    case "opt_mark_bad" :
        if( $page->MayBeMarkedBadByActiveUser() ) {
            $page->MarkBad($badreason);
            redirect_to_project($projectid);
        }
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
