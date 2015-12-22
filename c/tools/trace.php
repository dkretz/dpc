<?PHP

ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once($relPath.'dpinit.php');

$User->IsLoggedIn()
	or RedirectToLogin();

$User->IsSiteManager() || $User->IsProjectFacilitator() || $User->IsProjectManager()
    or die("Unauthorized");

$projectid = Arg("projectid");
$postednum = Arg("postednum");

$no_stats = true;
theme("Project Trace", "header");

if($projectid == "") {
    echo "
    <form name='frmflow' method='POST'>
    <div>
    Project ID: <input type='text' name='projectid' value='$projectid' size='30'><input tabindex='1' type='submit'>
    </div>\n";
    theme("", "footer");
    exit;
}

$project = new DpProject($projectid);
if(! $project->UserMayManage()) {
	say("Not your project");
    theme("", "footer");
    exit;
}

$isPPUploadFile = file_exists($project->PPUploadPath()) ? "Yes" : "No";
$isPPVUploadFile = file_exists($project->PPVUploadPath()) ? "Yes" : "No";
$p1_disabled = ($project->Phase() == "P1" ? "" : " disabled");
$p2_disabled = ($project->Phase() == "P2" ? "" : " disabled");
$p3_disabled = ($project->Phase() == "P3" ? "" : " disabled");
$f1_disabled = ($project->Phase() == "F1" ? "" : " disabled");
$f2_disabled = ($project->Phase() == "F2" ? "" : " disabled");
$pp_disabled = ($project->Phase() == "PP" ? "" : " disabled");
$ppv_disabled = ($project->Phase() == "PPV" ? "" : " disabled");
$posted_disabled = ($project->Phase() == "POSTED" ? "" : " disabled");
$submit_backup          = IsArg("submit_backup");
$submit_test_advance    = IsArg("submit_test_advance");
$submit_advance         = IsArg("submit_advance");
$submit_pp_uncheckout   = IsArg("submit_ppv_uncheckout");
$submit_PP_complete     = IsArg("submit_pp_complete");
$submit_ppv_uncheckout  = IsArg("submit_ppv_uncheckout");
$submit_ppv_checkout    = IsArg("submit_ppv_checkout");
$submit_ppv_complete    = IsArg("submit_ppv_complete");
$submit_ppv_post        = IsArg("submit_ppv_complete");
$submit_revert          = IsArg("submit_revert");
$radio_revert           = Arg("radio_revert");
//$submit_adjust_pages    = ArgArray("submit_adjust_pages");
$set_holds              = ArgArray("set_hold");
$release_holds          = ArgArray("release_hold");
$set_qc_hold            = IsArg("set_qc_hold");
$release_qc_hold        = IsArg("release_qc_hold");
$hold_remark            = Arg("hold_remark");

$proj_link = link_to_project($projectid, "Back to project page");
$p1_link   = link_to_round("P1", "link");
$p2_link   = link_to_round("P2", "link");
$p3_link   = link_to_round("P3", "link");
$f1_link   = link_to_round("F1", "link");
$f2_link   = link_to_round("F2", "link");


// dump($_REQUEST);

// -­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­

if($submit_test_advance) {
    $msgs = $project->AdvanceValidateErrors();
}

if($submit_advance) {
    $msgs = $project->MaybeAdvanceRound();
}

//if($submit_backup) {
//    $project->CreateBackupTable();
//}
if($set_qc_hold) {
    $project->SetQCHold();
}
if($release_qc_hold) {
    $project->ClearQCHold();
}
if($submit_revert) {
	$project->RevertPhase($radio_revert == 'clear');
}
if(count($set_holds) > 0) {
    foreach($set_holds AS $key => $value) {
        $project->SetUserHold($key);
    }
}
if(count($release_holds) > 0) {
    foreach($release_holds AS $key => $value) {
        $project->ReleaseHoldId($key);
    }
}
if($submit_PP_complete) {
    $project->PPSetComplete();
}
if($submit_pp_uncheckout) {
    $project->PPUncheckout();
}
if($submit_ppv_checkout) {
    $project->PPCheckout();
}

if($submit_ppv_uncheckout) {
    $project->PPVUncheckout();
}
if($submit_ppv_complete) {
    $project->PPVSetComplete();
}
if($submit_ppv_post) {
    $project->SetPosted($postednum);
}

//if($submit_adjust_pages) {
//    AdjustPhasePages($project);
//}

$holds = $project->HoldRows();
$holdcount = count($holds);
// $holdstr = "";
$nactiveholds = 0;
foreach($holds as $hold) {
    /** @var DpHold $hold */
    if($hold["phase"] == $project->Phase()) {
        $nactiveholds++;
    }
    /** @var DpHold $hold */
    // $holdstr .= ("        " . $hold->ToString() . "\n");
}
$tblholds = new DpTable();
$tblholds->AddColumn("^Phase", "phase");
$tblholds->AddColumn("^Hold type", "hold_code");
$tblholds->AddColumn("^Set by", "set_by");
$tblholds->AddColumn("^When", "set_time");
$tblholds->AddColumn("^Note", "note");

$tblholds->SetRows($holds);

$events = $project->History();

$tblhistory = new DpTable("tblevents", "w50");
$rows = array();

//$historystr = "";
/** @var DpEvent $event */
foreach($events as $event) {
    $rows[] = array($event->EventTime(), $event->EventType(), $event->Username(), $event->Note());
    /** @var DpEvent $event */
//    $historystr .= ("        " . $event->ToString() . "\n");
}
$tblhistory->AddColumn("^", 0);
$tblhistory->AddColumn("<", 1);
$tblhistory->AddColumn("<", 2);
$tblhistory->AddColumn("<", 3);
$tblhistory->SetRows($rows);

// --------------------
// your next page
// --------------------

$nextpage = $project->NextAvailablePage();
if($nextpage) {
	$versions = $nextpage->Versions();
	$ary      = array();
	foreach ( $versions as $version ) {
		/** @var DpVersion $version */
		$ary[] = $version->Phase() . " " . $version->Username();

		echo "<div id='divyournext'>
		<p>Username: " . $User->Username() . "</p>
		<p>Next Page: " . $nextpage->PageName() . "</p>
		<p>Proofers: " . implode( ", ", $ary ) . "</p>
		</div> <!-- divyournext' -->\n";
	}
}

// -­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­

// feedback from advance
if(isset($msgs)) {
    echo "<div class='warning'>\n";

    if(count($msgs) == 0) {
        echo "<h4>Project would advance to {$project->NextPhase()}.</h4>\n";
    }
    else {
        echo "<h4>Project would NOT advance to {$project->NextPhase()}.</h4>\n";
        echo "<pre>" . implode("<br />", $msgs) . "</pre>\n";
    }
    echo "</div>
    <hr />\n";
}

echo "
<form target='' name='frmflow' method='post'>
<div class='w75'>
    Project ID: <input type='text' name='projectid' value='$projectid' size='30'><input tabindex='1' type='submit'>
<p>This page is only available to Adminstrators, and for Project Managers, only for their own projects. For the time being, PMs can view all projects - but be careful.</p>
</div>
<hr>\n";

$is_u_hold_PREP = ($project->UserPhaseHoldId("PREP") != 0);
$is_u_hold_P1   = ($project->UserPhaseHoldId("P1") != 0);
$is_u_hold_P2   = ($project->UserPhaseHoldId("P2") != 0);
$is_u_hold_P3   = ($project->UserPhaseHoldId("P3") != 0);
$is_u_hold_F1   = ($project->UserPhaseHoldId("F1") != 0);
$is_u_hold_F2   = ($project->UserPhaseHoldId("F2") != 0);
$is_u_hold_PP   = ($project->UserPhaseHoldId("PP") != 0);
$is_u_hold_PPV  = ($project->UserPhaseHoldId("PPV") != 0);

echo "
<div>
<pre>
    Project ID: {$project->ProjectId()}  $proj_link
    Title:      {$project->NameOfWork()}
    Author:     {$project->AuthorsName()}
    PM:         {$project->ProjectManager()}
    Phase:      {$project->Phase()}
    Holds:      $holdcount ($nactiveholds this phase)
    PPer:       {$project->PPer()}
    PPVer:      {$project->PPVer()}
    Posted #:   {$project->PostedNumber()}
</pre>
</div>\n";

    $stats = $dpdb->SqlRows("
        SELECT state, COUNT(1) npages
        FROM page_last_versions
        WHERE projectid = '$projectid'
        GROUP BY state");

    $tbl = new DpTable("tblstats", "w25 lfloat dptable");
    $tbl->SetRows($stats);

    echo "<div>\n";
    $tbl->EchoTable();
    echo "</div>\n";


echo "
<div id='test'>
    <h3>Test round advance:</h3>
    <input type='submit' name='submit_test_advance' value='Test' />
    <input type='submit' name='submit_advance' value='Advance' />
</div>

<div id='divholds' class='center w50'>
    <h3>Holds:</h3>\n";

    $tblholds->EchoTable();

    $tbl = new DpTable("tblusrholds", "w35 dptable");
    $tbl->SetTitle("Your User Holds");

    $rows = array();
    foreach(array("PREP", "P1", "P2", "P3", "F1", "F2", "PP", "PPV") as $ph) {
        $id = $project->UserPhaseHoldId($ph);
        $rows[] = array($ph,
             $id == 0
                ? "No" : "Yes",
            $id == 0
                ? "<input type='submit' name='set_hold[$ph]' value='Set'/>"
                : "<input type='submit' name='release_hold[$id]' value='Release'/>");
    }
    $tbl->AddColumn("^", 0);
    $tbl->AddColumn("^", 1);
    $tbl->AddColumn("^", 2);
    $tbl->SetRows($rows);
    $tbl->EchoTable();

    if($User->MayQC()) {
        echo "<div id='divqchold'>
        </div>   !<-- divqchold  ->\n";

    }

echo "</div>    <!-- divholds -->\n";

echo "
<div>
<h4>Post Proofing comments</h4>
<p>These are used for various purposes at various times - smooth-reading instructions
(if populated during the PP phase) and notes for PPVer (during the PPV phase) being two.</p>

<div class='bordered margined padded left w75'>
<pre>
{$project->PostComments()}
</pre>
</div>
</div>

<hr>
<div id='divprep'>\n";

if($project->Phase() == "PREP") {
	echo "
	<pre>
	 PREP   <a href='http://www.pgdpcanada.net/c/tools/prep.php'>link</a>\n";
    $qcholdid = $project->QCHoldId();
    echo "
Advance Requirements:
    Pages loaded: {$project->PageCount()}
    Clearance:    {$project->Clearance()}
    Project Mgr:  {$project->ProjectManager()}
    Language:     {$project->LanguageCode()}
    QC Hold Id:   {$qcholdid}\n";
    echo ($qcholdid == 0 ? "<input type='submit' name='set_qc_hold' value='Set QC Hold'/>\n"
               : "<input type='submit' name='release_qc_hold' value='Release QC Hold'/>\n");
}

$prevphase = $Context->PhaseBefore($project->Phase());
echo "
</pre>
<fieldset id='divrevert'>
     <input type='submit' name='submit_revert' value='Revert to previous Phase ($prevphase)'>
     <br>
     <input type='radio' name='radio_revert' id='radio_revert' value='clear'>
     Clear the pages so they are each available to proof again.<br>
     The previous work will not be lost.
     The new proofers will start with the work of the previous proofer, if any.
     <br>
     <input type='radio' name='radio_revert' id='radio_revert' value='hold' checked='checked'>
     Leave the pages as they are, including pages completed by proofers.<br>
     Set a personal hold on the project to keep it from advancing until you release it.
</fieldset>  <!-- divrevert -->
</div>  <!-- divprep -->
<hr>
<h4>P1 ".link_to_round_diff($projectid, "P1")."</h4>
<pre>\n";

if($project->Phase() == "P1") {
    echo "
<a href='http://www.pgdpcanada.net/c/tools/p1release.php'>P1 Queue Holds</a>";
}


echo "
</pre>
<hr>
<h4>P2' ".link_to_round_diff($projectid, "P2")."</h4>
<hr>
<h4>P3 ".link_to_round_diff($projectid, "P3")."</h4>
<hr>
<h4>F1 ".link_to_round_diff($projectid, "F1")."</h4>
<hr>
<h4>F2</h4>
<hr>
<h4>".link_to_phase('pp', 'PP')."</h4>
<pre>
    PP upload file: {$project->PPUploadPath()}
    Exists?   $isPPUploadFile
</pre>
<hr>
<h4>".link_to_phase('ppv', 'PPV')."</h4>
<pre>
PPV   <a href='http://www.pgdpcanada.net/c/tools/ppv.php'>link</a>
    PPV upload file: {$project->PPVUploadPath()}
    Exists?   $isPPVUploadFile
    <input type='submit' name='submit_ppv_uncheckout' value='PPV return' $ppv_disabled>
    <input type='submit' name='submit_ppv_checkout' value='PPV Check Out' $ppv_disabled>
    <input type='submit' name='submit_ppv_complete' value='PPV complete' $ppv_disabled>
    <input type='submit' name='submit_ppv_post' value='PPV Post' $ppv_disabled>  posted #:<input type='text'   name='postednum'       value='$postednum' $ppv_disabled>
</pre>
<hr>
<pre>
POSTED
</pre>
<hr>
<h3>History</h3>\n";

$tblhistory->EchoTable();
//$historystr
//</pre>
//<hr>\n";

EchoPageStateIssues($project);
echo "
</form>\n";

theme("", "footer");
exit;



function EchoPageStateIssues($project) {
    /** @var DpProject $project */

    switch($project->Phase()) {
        case "P1":
        case "P2":
        case "P3":
        case "F1":
        case "F2":
            break;
        default:
            return;
    }
    global $dpdb;
    $projectid = $project->ProjectId();
    $phase = $project->Phase();
    $pages = $dpdb->SqlRows("
        SELECT pv.state, COUNT(1) page_count
        FROM page_last_versions pv
        JOIN projects p ON pv.projectid = p.projectid
        WHERE pv.projectid = '$projectid'
        AND p.phase != pv.phase
        GROUP BY pv.state
        ORDER BY pv.pagename");
    if(count($pages) == 0) {
        return;
    }
    $tbl = new DpTable();
    $tbl->SetRows($pages);
    echo _("<h4>The following pages don't match the project Phase ($phase):</h4>\n");
    $tbl->EchoTableNumbered();
}


/*
function AdjustPhasePages($project) {

    global $dpdb;

    $projectid = $project->ProjectId();
    $phase = first_phase_with_incomplete_text($project);
//    $state = "{$phase}.proj_avail";
    echo("<p>It appears that the project should be working in $phase.</p>");
    if($project->Phase() != $phase) {
        $project->SetPhase($phase);
    }
//    if($project->State() != $state) {
//        $project->SetState($state);
//    }

//    switch($phase) {
//        case "P1":
//            $round = "round1_";
//            break;
//        case "P2":
//            $round = "round2_";
//            break;
//        case "P3":
//            $round = "round3_";
//            break;
//        case "F1":
//            $round = "round4_";
//            break;
//        case "F2":
//            $round = "round5_";
//            break;
//        default:
//            return;
//    }

//    $phase_user = $round . "user";
//    $phase_time = $round . "time";
//    $phase_text = $round . "text";

//    $avail       = $phase . ".page_avail";
//    $saved       = $phase . ".page_saved";
//    $checked_out = $phase . ".page_out";
//    $temp        = $phase . ".page_temp";

    // any empty field for current round means available.
    $n = $dpdb->SqlExecute("
        UPDATE page_last_versions
        SET state = 'A',
        	version_time = UNIX_TIMESTAMP()
        WHERE username IS NULL
        	AND projectid = '$projectid'");

    echo("<p>Pages set to available: $n</p>");

    $n = $dpdb->SqlExecute("
        UPDATE page_last_versions
        SET state = 'C',
        	version_time = UNIX_TIMESTAMP()
        WHERE LENGTH(username) > 0");

    echo("<p>Pages set to saved:</p>$n");

    return;
    // if for any other round
}
*/

function first_phase_with_incomplete_text($project) {
    /** @var DpProject $project */
    global $dpdb;
    $projectid = $project->ProjectId();
	$sql = "
		SELECT p.phase,
			pp.page_count,
			SUM(pv.state = 'C') completed_count
		FROM
		(
			SELECT projectid, COUNT(1) page_count
			FROM pages
			WHERE projectid = '$projectid'
			GROUP BY projectid
		) pp,

    	phases p

    	LEFT JOIN page_versions pv
        	ON p.phase = pv.phase
        	AND '$projectid' = pv.projectid
        	AND pv.state = 'C'
	GROUP BY p.phase
	ORDER BY p.sequence";

	$rows = $dpdb->SqlRows($sql);

	foreach($rows as $row) {
		if($row['page_count'] < $row['page_count']) {
			return $row['phase'];
		}
	}

    return "PP";
}

function phase_sequence($phase) {
    switch($phase) {
        case "PREP":
            return 0;
        case "P1":
            return 1;
        case "P2":
            return 2;
        case "P3":
            return 3;
        case "F1":
            return 4;
        case "F2":
            return 5;
        case "PP":
            return 6;
        case "PPV":
            return 7;
        case "POSTED":
            return 8;
        default:
            return -1;
    }
}

function link_to_round_diff($projectid, $phase) {
    return link_to_url(url_for_round_diff($projectid, $phase), "Project Diff for $phase");
}

function url_for_round_diff($projectid, $phase) {
    return "/c/tools/projdiff.php?projectid={$projectid}&phase={$phase}";
}


