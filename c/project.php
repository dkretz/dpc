<?PHP
ini_set('display_errors', 1);
error_reporting(E_ALL);

$relPath='./pinc/';

include_once($relPath.'dpinit.php');
include_once($relPath.'rounds.php');
include_once($relPath.'RoundsInfo.php');
//if(dkretz()) {
//    include_once "pagetable.php";
//}
//else {
    include_once 'pt.php'; // echo_page_table
//}

$User->IsLoggedIn()
	or RedirectToLogin();

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

// Usually, the user arrives here by clicking on the title of a project
// in a list of projects.
// But there are lots of other less-used pages that link here.

$projectid              = Arg('projectid', Arg('id'));
$project                = new DpProject( $projectid );
$level                  = Arg('detail_level', $project->UserMayManage() ? '3' : '2');
$btn_manage_words       = IsArg("btn_manage_words");
$btn_manage_files       = IsArg("btn_manage_files");
$btn_manage_holds       = IsArg("btn_manage_holds");
$linktotopic            = Arg("linktotopic");

$submit_post_comments   = IsArg("submit_post_comments");
$postcomments           = Arg("postcomments");

$srdays                 = ArgInt("srdays");
$issrdays               = IsArg("submitSRtime");
$submit_srcomments      = IsArg("submit_srcomments");
$srcomments             = Arg("srcomments");

$submit_export          = Arg("submit_export");
$submit_view            = Arg("submit_view");
$submit_view_both       = Arg("submit_view_both");
$exportphase            = Arg("exportphase");
$exact                  = Arg("exact");
$exportinclude          = Arg("exportinclude");

if($exportphase == "newest") {
    $exportphase = $project->Phase();
}

if($issrdays) {
    $project->SetSmoothDeadlineDays($srdays);
}

if($submit_srcomments) {
    $project->SetSmoothComments(h($srcomments));
}
if($btn_manage_words) {
    divert(url_for_project_words($projectid));
    exit;
}

if($btn_manage_files) {
    divert(url_for_project_files($projectid));
    exit;
}
if($btn_manage_holds) {
    divert(url_for_project_holds($projectid));
    exit;
}

if($linktotopic) {
    if(! $project->ForumTopicId()) {
        $project->CreateForumThread();
    }
    $topicid = $project->ForumTopicId();
    divert($project->ForumTopicUrl());
    exit;
}

if($submit_view) {
    divert(url_for_view_text($projectid, $exportphase, $exportinclude, $exact));
    exit;
}
if($submit_view_both) {
    divert(url_for_view_text_and_images($projectid));
    exit;
}

if($submit_export || $submit_view) {
    switch($exportphase) {
        case "PP":
        case "PPV":
        case "POSTED":
            $exportphase = "F2";
            break;
        case "P1":
        case "P2":
        case "P3":
        case "F1":
        case "F2":
            break;
        case "newest":
        default:
            $exportphase = $project->Phase();
            break;
    }

    if($Context->PhaseSequence($exportphase) > $project->PhaseSequence()) {
        $exportphase = $project->Phase();
    }

    $text = $project->PhaseExportText($exportphase, $exportinclude, $exact);
    if($submit_export) {
        send_string("{$projectid}_{$exportphase}.txt", $text);
    }
    else {

    }
}
// -----

$project->MaybeAdvanceRound();

// if user submitted comments for post processing, load them
if($submit_post_comments) {
    $project->PrependPostComments($postcomments);
}

// -----------------------------------------------------------------------------

// In a tabbed browser, the page-title passed to theme() will appear in
// the tab, which tends to be small, as soon as you have a few of them.
// So, put the distinctive part of the page-title (i.e. the name of the
// project) first.

switch($project->Phase()) {
	case "F1":
	case "F2":
	$verb = "format";
	$noun = "formatting";
		break;
	default:
		$verb = "proofread";
		$noun = "proofreading";
		break;
}

$title_for_theme = $project->NameOfWork() . _(' project page');

// touch modifieddate whenever PPer views this page
//if($project->UserIsPPer() && $project->Phase() == "PP") {
//    $project->SetModifiedDate();
//}

// confusing call to prepare top and bottom status boxes
list($top_status, $bottom_status) = top_bottom_status($project);

// -------------------------------------------------------------------------------
//   Display
// -------------------------------------------------------------------------------

if ($level == 1) {
    theme($title_for_theme, "header");
    echo "<div id='divproject' class='px1000 lfloat clear'>
            <h1 class='center'>{$project->Title()}</h1>\n";

    detail_level_switch($projectid, $level);
    project_info_table($project, $level);
    detail_level_switch($projectid, $level);

    echo "</div>  <!-- divproject -->\n";
    exit;
}




// don't show the stats column
$no_stats = 1;
theme($title_for_theme, "header");


echo "<div id='divproject' class='px1000 lfloat clear'>\n";
detail_level_switch($projectid, $level);
echo "<div id='divtrace' class='rfloat margined j5'>"
     .link_to_project_trace($projectid, "Project Trace")
     ."</div>  <!-- divtrace -->\n";
echo "<h1 class='center clear'>{$project->Title()}</h1>\n";

echo "
    <div id='status_box_1' class='status_box'>
    $top_status
    </div>   <!-- status_box_1 -->\n";

project_info_table($project, $level);

if($project->UserMayManage()) {
	$vurl = "http://validator.w3.org/check?uri="
	. $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]
	  . "&amp;charset=%28detect+automatically%29&amp;doctype=Inline&amp;group=0";
    echo "<div id='div_edit_validate' class='clear'>"
         . link_to_edit_project($projectid, "Edit above project information")."<br/>"
         . link_to_url($vurl, "Validate this project page", true)
         . "</div>   <!-- div_edit_validate -->\n";
}

echo "
    <div id='status_box_2' class='status_box'>
    $bottom_status
    </div>  <!--  status_box_2 -->
    <br/>\n";

if($project->Phase() == 'PREP'
	&& $project->CPComments() != "") {
	display_cp_comments($project);
}
if($project->Phase() == 'PP' ) {
	solicit_postcomments($project, $level);
	solicit_smooth_reading($project);
}

if( $project->Phase() == 'PPV') {
	solicit_postcomments($project, $level);
}


if($level > 2) {

	if ( $project->UserMayManage() ) {
		show_uploads_box( $project , $level);
	}

    if($project->IsInRounds()) {
        show_page_summary( $project );
    }

	offer_images( $project );

    if($project->IsRoundsComplete()
            && ($User->MayWorkInRound("PP") || $User->MayWorkInRound("PPV"))) {
        offer_pp_downloads( $project);
    }

//    offer_downloads($project) ;
}

if($level > 3) {

//	if ( $project->Phase() == "PPV" && $User->MayPPV() ) {
//		solicit_pp_report_card( $project );
//	}

    offer_text_downloads( $project );

	offer_extra_files( $project );

	show_history( $project );

	echo "</div> <!-- divproject -->\n";

	if($project->PageCount() > 0) {
		show_page_table( $project );
	}
}
echo "</div>  <!-- divproject -->\n";

echo "<hr class='lfloat w50 clear'>\n";
detail_level_switch($projectid, $level);
theme('', 'footer');
exit;

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function detail_level_switch($projectid, $level = 3) {
    echo _("
    <div class='lfloat margined clear'>
        Viewing page at detail level $level.&nbsp;&nbsp;Switch to: ");
        for($i = 1; $i <= 4; $i++) {
        if ( $i != $level ) {
            echo link_to_project_level($projectid, $i, $i);
        }
    }
    echo "</div>  <!-- detail_level_switch (not an id) -->\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

/** @var DpProject $project
 * @return array
 */
function top_bottom_status($project) {
	global $noun;
    /** @var DpProject $project */
    global $User;
    global $dpdb;

	$phase = $project->Phase();
	if($phase == "PP" && $project->UserIsPPer()) {
		$msg = _( "(PP) - Project is yours to Post Process." );
	}
	else if(! $phase == "PPV" && $project->UserIsPPVer()) {
		$msg = _( "($phase} yours to Post Process." );
	}
	else if(! $project->IsAvailable()) {
		$msg = _("$phase - not available.");
	}
	else if(! $project->UserMayProof()) {
		$msg = _("$phase - not available for you to proof.");
	}
	else if(! $project->IsAvailableForActiveUser()) {
		$msg = _("$phase - project is available, but there are no pages for you now.");
	}
	else {
		$msg = "";
	}

	if($msg !== "") {
		return array( $msg, $msg );
	}

	$projectid = $project->ProjectId();
	$username = $User->Username();
	$user_save_time  = $dpdb->SqlOneValue("SELECT IFNULL(MAX(version_time), 0)
									 FROM page_versions
									 WHERE projectid = '$projectid'
									 	AND phase = '$phase'
									 	AND username = '$username'");
    // If there's any proofreading to be done, this is the link to use.
	$label = _("{$project->Phase()} - start $noun");
    $proofreading_link = link_to_proof_next($project->ProjectId(), $label);

    // When was the project info last modified?
//    $last_edit_info = _("Project information last modified:")
//        . " " . $project->LastEditTimeStr()
//        . ($user_save_time == 0 ? "" : "<br>Proofed by you: " . std_date($user_save_time));

    // Other possible components of status:
    $please_scroll_down = _("Please scroll down and read the Project Comments
    for any special instructions <b>before</b> $noun!");

    $the_link_appears_below = _("The 'Start $noun' link appears below
    the Project Comments");

    $info_have_changed =
        "<p class='nomargin red bold'>"
        . _("Project information has changed!")
        . "</p>";

    // ---

    $bottom_status = "$proofreading_link";

//	$mod_time  = $timerow['modifieddate'];
    if (! $user_save_time) {
        // The user has not saved any pages for this project.
        $top_status = "$please_scroll_down
                      <br> $the_link_appears_below";
    }
    else if($user_save_time < $project->LastEditTime()) {
        // The user has saved a page for this project.

            // The latest page-save was before the info was revised.
            // The user probably hasn't seen the revised project info.
            $top_status = "$info_have_changed <br/> $please_scroll_down
                <br>
		    $the_link_appears_below";
	}
	else {
            // The latest page-save was after the info was revised.
            // We'll assume that the user has read the new info.
            $top_status = "$please_scroll_down
                <br> $proofreading_link";
    }

    return array( $top_status, $bottom_status );
}

// -----------------------------------------------

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function project_info_table($project, $level) {
    global $User;

    /** @var DpProject $project */

    $projectid      = $project->ProjectId();
//    $postcomments   = $project->PostComments();
//    $postcomments   = str_replace("\n", "<br />", h($postcomments));



    // -------------------------------------------------------------------------
    // The state of the project

//    $available_for_SR = ( $project->SmoothreadDeadline() > time() );

	$right = $project->RoundDescription();

    echo "
    <div id='div_project_info_table' class='margined padded'>
    <table id='project_info_table'>\n";
    echo_row_left_right( _("Project Status"), $right );
    echo_row_left_right( _("Title"),           $project->NameOfWork() );
    echo_row_left_right( _("Author"),          $project->AuthorsName() );
    echo_row_left_right( _("Language"),        $project->Language() );
	if($project->SecLanguage()) {
		echo_row_left_right( _( "(With Language)" ), $project->SecLanguage() );
	}
    echo_row_left_right( _("Genre"),           $project->Genre() );
    echo_row_left_right( _("Difficulty"),      $project->Difficulty() );
    echo_row_left_right( _("Project ID"), $project->ProjectId() );
	echo_row_left_right( _("Project Manager"), $project->ProjectManager());
	if($level > 3) {
		if ( $project->UserIsPPVer() || $project->UserMayManage() ) {
			echo_row_left_right( _( "Clearance line" ), h( $project->Clearance() ) );
		}
		if ( $project->ImageSource() != "" ) {
			echo_row_left_right( _( "Image Source" ), h( $project->ImageSource() ) );
		}
		echo_row_left_right( _( "Image URL" ), h( $project->ImageLink() ) );
		echo_row_left_right( _("Post Processor"), $project->PPer());
		echo_row_left_right( _("PP Verifier"), $project->PPVer() );
		echo_row_left_right( _("Credits"), h($project->Credits()));
	}
    echo_row_left_right( _("Project info changed"), $project->LastEditTimeStr());
    echo_row_left_right( _("Round changed"), $project->PhaseDate());
    echo_row_left_right( _("Last page saved"), $project->LatestProofTime());

//	echo_row_left_right( _("Topic ID"), $project->ForumTopicId() );
	echo_row_left_right( _("Last Forum Post"), $project->LastForumPostDate() );

    if($project->Phase() == 'POSTED') {
        echo_row_left_right( _( "Posted etext number" ),
            link_to_fadedpage_catalog( $project->PostedNumber(), $project->PostedNumber() ) );
    }

    // -----------------------

	$status = ($project->ForumTopicIsEmpty()
				? _("Start a discussion about this project")
				: _("Discuss this project"));
    $url = "?projectid={$projectid}&amp;linktotopic=1";
    echo_row_left_right( _("Forum"), "<a href='$url'>$status</a>" );

    // -------------------------------------------------------------------------

	$status = _("Images, pages edited, & differences");
	$link = link_to_page_detail($projectid, $status);

	$status2 = _("Just my pages");
	$link2 = link_to_page_detail_mine($projectid, $status2);

	echo_row_left_right( _("Page Detail"), "$link &gt;&gt;$link2 &lt;&lt;");

    $username = $User->Username();

    if (! $project->IsPublishUserNotify()) {
        $caption = _("Click to be notified by email when this project is posted to FadedPage.");
        $link = link_to_notify($username, $projectid, $caption, "set");
    }
    else {
        $caption = _("<p>You ($username) are registered to be notified by email
        when this project has been posted to FadedPage.</p>
        <p>Click here to cancel your registration.</p>");
        $link = link_to_notify($username, $projectid, $caption, "clear");
    }

    // ------------------------------------------------------------

    echo_row_left_right( _("Book Completion:"), $link);

    // -------------------------------------------------------------------------
    // Post Comments

	// used for SR instructions

    /*
	if ( $available_for_SR ) {
		echo_caption_row( _("Instructions for Smooth Reading"));
		echo "<tr><td colspan='2' style='min-height: 1em;'> $postcomments</td></tr> <!-- 3 -->\n";
	}
	else if ( $project->UserIsPPer() || $project->UserIsPPVer() || $project->UserMayManage() ) {
		echo_caption_row( _("Post Processor Comments") );
		echo_row_one_cell( $postcomments );
	}
    */


    // -------------------------------------------------------------------------
    // Project Comments

	// ------------------------------------------------------------

	if($project->UserCheckedOutPageCount() > 0 || $project->UserSavedPageCount() > 0 ) {
		echo "<tr><td colspan='2'>\n";
		echo_your_recent_pages($project);
		echo "</td></tr>\n";
	}

    $comments = $project->Comments();
    echo_row_one_cell( str_replace("&", "&amp;", $comments) );

	echo "</table>
	</div>  <!-- div_project_info_table -->\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function echo_row_left_right( $left, $right) {
    echo "
    <tr><td class='left w25 bgEEEEEE'><b>$left</b></td>
    <td>$right</td></tr> <!-- 1 -->\n";
}

//function echo_caption_row($content) {
//    echo "
//    <tr><td colspan='2' class='center bgEEEEEE'><b>$content</b></td></tr>\n";
//}

function echo_row_one_cell( $content ) {
    echo "<tr><td colspan='2' style='min-height: 1em;'> $content </td></tr> <!-- 3 -->\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function echo_your_recent_pages( $project ) {
    global $User;
    global $dpdb;

    /** @var DpProject $project */
    $username = $User->Username();
    $projectid = $project->ProjectId();


	// -----------------------------------------------------------
	//    Checked Out (top)
	// -----------------------------------------------------------

	$sql = "
        SELECT  pv.projectid,
        		pv.pagename,
        		DATE_FORMAT(FROM_UNIXTIME(pv.version_time), '%b-%e-%y %H:%i') version_time,
        		pp.imagefile
        FROM page_last_versions pv
        JOIN projects p
        ON pv.projectid = p.projectid
		LEFT JOIN pages pp
		ON pv.projectid = pp.projectid
			AND pv.pagename = pp.pagename
        WHERE pv.projectid = '$projectid'
        	AND pv.username = '$username'
            AND pv.state = 'O'
        ORDER BY pv.version_time DESC";

	$checked_out_objs = $dpdb->SqlObjects($sql);

    echo html_comment($sql);

	// ---------
	$bg_color = '#FFEEBB';
	echo "
   <table id='tblpages' class='w100'>
	 <tr><td colspan='5' class='center' style='background-color: $bg_color'>
        <p class='em110 nomargin'><b>Pages Checked Out</b> and not yet completed</p>
	</td></tr>\n";

	echo "<tr>";
	// ------------

	for($i = 0; $i < 5; $i++) {
		if(count($checked_out_objs) > $i) {
			$obj = $checked_out_objs[$i];

			echo "<td class='center w20'>"
			     . link_to_proof_page($projectid, $obj->pagename, "$obj->version_time $obj->imagefile")
			     ."</td>\n";
//			     ." <a href='$eURL'>{$prooftime}: {$obj->image}</a></td>\n";
		}
		else {
			echo "<td>&nbsp;</td>\n";
		}
	}
	echo "</tr>\n";

	// -----------------------------------------------------------
	//    Submitted (bottom)
	// -----------------------------------------------------------

        $sql = "
        SELECT  pv.projectid,
        		pv.pagename,
        		DATE_FORMAT(FROM_UNIXTIME(pv.version_time), '%b-%e-%y %H:%i') version_time,
        		pp.imagefile

        FROM page_last_versions pv

        JOIN projects p
        ON pv.projectid = p.projectid
        	AND pv.phase = p.phase

		JOIN pages pp
		ON pv.projectid = pp.projectid
			AND pv.pagename = pp.pagename

        WHERE pv.projectid = '$projectid'
        	AND pv.username = '$username'
            AND pv.state = 'C'
        ORDER BY pv.version_time DESC
        LIMIT 5";
	echo html_comment($sql);
	$rows = $dpdb->SqlRows($sql);

	// ---------------------
	$bg_color = '#D3FFCE';
	echo _("
	  <tr><td colspan='5' class='center' style='background-color: $bg_color'>
	     <p class='em110 nomargin'><b>Pages Submitted</b>
	     but still available to edit or correct</p>
	  </td></tr>\n");
	// --------------------


	echo "<tr>\n";
	for($i = 0; $i < 5; $i++) {
		if(count($rows) > $i) {
			$row = $rows[$i];
			$imagefile = $row['imagefile'];
			$pagename  = $row['pagename'];
			$version_time = $row['version_time'];

			$eURL = url_for_proof_page( $projectid, $pagename );

			echo "<td class='w20 center'>";
			echo "<a href='$eURL'>";
			echo "$version_time $imagefile</a></td>\n";
		}
		else {
			echo "<td class='w20'></td>\n";
		}
	}
	echo "</tr>
	</table>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

/*
 *
 */
function show_uploads_box($project, $level) {
    /** @var DpProject $project */
    $mng_holds = _("Manage Holds");
    $mng_words = _("Manage Words");
    $projectid = $project->ProjectId();
    $nholds = $project->HoldCount();
    $sholds = ($nholds == 0
                    ? "are no Holds"
                    : ($nholds == 1
                        ? "is one Hold"
                        : "are $nholds Holds"));

    echo "
    <div id='divupload' class='clear bordered margined padded'>
    <h3 class='center'>Project Management</h3>
    <form method='POST'>
    <input type='hidden' name='projectid' value='$projectid'>
    <input type='hidden' name='detail_level' value='$level'>
    <ul class='clean w65 center'>
    <li><input type='submit' name='btn_manage_files' id='btn_manage_files' value='Manage Files'>
    "._("Add/Upload page text and image files.")."</li>
    <li><input type='submit' name='btn_manage_holds' id='btn_manage_holds' value='$mng_holds'>
    "._("There $sholds currently in effect.")."</li>
    <li><input type='submit' name='btn_manage_words' id='btn_manage_words' value='$mng_words'>
    "._("WordCheck adminstration.")."</li>
    </ul>
    </form>
    </div>   <!-- divupload -->\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function show_history($project) {
    global $dpdb;

    /** @var DpProject $project */
    $projectid = $project->ProjectId();


    $events = $dpdb->SqlRows("
        SELECT DATE_FORMAT(FROM_UNIXTIME(event_time), '%b-%e-%y %H:%i') timestamp,
            TRIM(event_type) event_type,
            TRIM(details1) details1,
            TRIM(details2) details2
        FROM project_events
        WHERE projectid = '$projectid'
        ORDER BY event_time");

    $tbl = new DpTable("tblevents", "w75 center padded noborder");
    $tbl->AddColumn("<When", "timestamp");
    $tbl->AddColumn("<What", "event_type");
    $tbl->AddColumn("<", "details1");
    $tbl->AddColumn("<", "details2");
    $tbl->SetRows($events);

    echo "<div id='divhistory' class='bordered margined padded'>
     <h3>Project History</h3>\n";
    $tbl->EchoTable();
    echo "</div>   <!-- divhistory -->\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function show_page_summary($project) {
	/** @var DpProject $project */


	echo "
    <div id='div_page_summary' class='margined bordered padded'>
        <h3>"._("Page Summary")."</h3>\n";

	if($project->PageCount() == 0) {
		echo "<p>No pages in this project yet.</p>\n";
	}
	else if($project->IsRoundPhase()) {

		echo "
			<table id='tblpagesummary' class='noborder center'>
			<tr><td class='padded'>Available</td><td class='right padded'>{$project->AvailableCount()}</td><td>&nbsp;</td></tr>
			<tr><td class='padded'>Checked Out</td><td class='right padded'>{$project->CheckedOutCount()}</td>
			 <td class='padded'>(Reclaimable: {$project->ReclaimableCount()})</td></tr>
			<tr><td class='padded'>Completed</td><td class='right padded'>{$project->CompletedCount()}</td><td>&nbsp;</td></tr>
			<tr><td class='padded'>Bad Pages</td><td class='right padded'>{$project->BadCount()}</td><td>&nbsp;</td></tr>
			<tr><td colspan='3'><hr></td></tr> <!-- 6 -->
			<tr><td class='padded'>Total Pages</td><td class='right padded'>{$project->PageCount()}</td><td>&nbsp;</td></tr>
			</table>\n";
	}

	else {
		echo "
			<table id='tblpagesummary' class='noborder lfloat'>
			<tr><td class='padded'>Total Pages</td><td class='right padded'>{$project->PageCount()}</td></tr>
			</table>\n";

	}
	echo "
	</div>   <!-- div_page_summary -->\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function show_page_table($project) {

	/** @var DpProject $project */

	echo "
    <div id='page_table_key' class='clear'>
        <p>". _('Pages edited by you and...') . "</p>
        <div style=' margin-bottom: .4em' class='pg_out bordered padded lfloat clear'>"
	     . _("Checked Out (awaiting completion this round)") . "</div>
        <div style=' margin-bottom: .4em' class='pg_completed bordered padded lfloat'>"
	     . _("Completed (still available for editing this round)") ."</div>
        <div style=' margin-bottom: .4em' class='pg_unavailable bordered padded lfloat'>"
	     . _("Completed in a previous round (no longer available for editing)") ."</div>
    </div>   <!-- div_table_key -->

    <div id='div_page_table' class='lfloat clear'>\n";

	// second arg. indicates to show size of image files.
	echo_page_table($project);
	echo "
    </div>    <!-- div_page_table' -->\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

/** @param $project DpProject */
function offer_images($project) {
    global $code_url, $Context;
//	global $User;

	$projectid = $project->ProjectId();
//    $imgurl = "$code_url/tools/proofers/images_index.php?projectid=$projectid";
//    $imglink = link_to_url($imgurl, "View Images Online");
    $imglink = link_to_image_index($projectid, "View images online", true);
    $textlink = link_to_view_project_text($projectid, "all", "View latest text online", true);
    $image_link = link_to_zipped_images($projectid, "Download zipped images");
    $bothlink = link_to_view_text_and_images($projectid, "View latest text and images online");
//    $ocrlink = link_to_project_text($projectid, "all", "View latest text online", true);
//    $p3link = link_to_project_text($projectid, "P3", "View latest text online", true);
//	$texturl = "$code_url/project_text.php?projectid=$projectid";
//	$textlink = link_to_url($texturl, "View Latest Text Online", true);
	if($project->PhaseSequence() >= $Context->PhaseSequence("P3")) {
		$texturlP3 = "$code_url/project_text.php?projectid=$projectid&amp;phase=P3";
		$textlinkP3 = link_to_url($texturlP3, "View P3 text online", true);
	}

    echo "
    <div id='div_images_text' class='bordered margined padded'>
		<form name='frmimages' method='POST'>
        "._('<h3>Images and Text</h3>')."
        <ul class='clean'>
        <li>$imglink</li>
        <li>$image_link</li>
        <li>$textlink</li>
        <li>$bothlink</li>\n";
	if(isset($textlinkP3)) {
		echo "<li>$textlinkP3</li>\n";
	}
	if(isset($textlinkdk)) {
		echo "<li>$textlinkdk</li>\n";
	}

	echo "
	    </ul>
        </form>
        </div>  <!-- div_images_text -->\n";
}


// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function offer_extra_files($project) {
    global $dpdb;
    /** @var DpProject $project */

	$projectid = $project->ProjectId();
    $path = build_path($project->ProjectPath(), "*");
    $filenames = glob($path);

    $images = $dpdb->SqlValues("
		SELECT imagefile FROM pages
		WHERE projectid = '$projectid'
		ORDER BY pagename");
    $notfiles = array();
    foreach($images as $img) {
        $notfiles[] = basename($img);
    }
	$notfiles[] = "wordcheck";
	$notfiles[] = "text";

	echo _("
	<div id='div_extra_files' class='bordered margined padded clear'>
	<h3>Project Files</h3>\n");

    echo "
    <ul class='clean'>\n";

		foreach ($filenames as $filename) {
			$filename = basename($filename);
			if ( !in_array( $filename, $notfiles ) ) {
				$url = build_path($project->ProjectUrl(), $filename);
				echo "<li>" . link_to_url($url, $filename) . "</li>\n";
			}
		}

    echo "</ul>
	<p>" . link_to_url($project->ProjectUrl(), "Browse the project directory")
      ."</p>
    </div>  <!-- div_extra_files -->
	\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

//function offer_downloads($project) {
//    echo "<div class='bordered margined padded'>
//        <h3 class='center clear'>". _("Text Downloads") . "</h3>
//        <form>\n";
//
//}
function offer_pp_downloads($project) {
//    global $User, $code_url, $Context;
    /** @var DpProject $project */

    $projectid = $project->ProjectId();
    $image_link = link_to_zipped_images($projectid);
    $text_link  = link_to_pp_text($projectid);

    echo "
	<div id='div_pp_downloads' class='bordered margined padded'>
        <h3 class='clear'>". _("Post Processor/Verifier Downloads") . "</h3>
        <ul class='clean'>
            <li>$image_link</li>
            <li>$text_link</li>
        </ul>
    </div>   <!-- div_pp_downloads -->\n";
}
//    $prompt_text =  _("Download Zipped Images");
//    $url = "$code_url/tools/download_images.php"
//            ."?projectid=$projectid"
//            ."&amp;dummy={$projectid}images.zip";
//    echo "<li><a href='$url'>$prompt_text</a></li>\n";
//    if ($project->Phase() == "PP") {
//        echo_download_pp_zip($project, _("Download Concatenated Text") );
//
//    echo "<li>";
//            echo_uploaded_zips($project, '_first_in_prog_', _('partially post-processed'));
//    echo "</li>";
//        }

//    else if ($project->Phase() == "PPV") {
//        echo_download_ppv_zip($project, _("Download Zipped PP Text") );
//        echo "<li>";
//        echo_uploaded_zips($project, '_second_in_prog_', _('partially verified'));
//        echo "</li>";
//    }
//    echo "</ul>\n";
/** @param DpProject $project */
function offer_text_downloads($project)
{
    global $Context, $level;
    $projectid = $project->ProjectId();
    echo "
    <div class='bordered margined padded'>
        <h3>Project Text Downloads</h3>
        <form name='frmdownload' method='post'>
          <input type='hidden' name='projectid' value='$projectid'>
          <input type='hidden' name='detail_level' value='$level'>

            <p>Download concatenated project text from <input type='radio' name='exportphase' value='OCR'>OCR: \n";

    foreach ($Context->Rounds() as $round) {
        $roundid = $round->RoundId();
        echo "
              <input type='radio'  name='exportphase' value='{$roundid}'>{$roundid}&nbsp;\n";
        if ($roundid == $project->Phase()) {
            break;
        }
    }
    echo "<input type='radio' name='exportphase' value='newest' checked>Newest&nbsp;
            </p>\n";

    if ($project->UserMaySeeNames()) {
        echo "
            <p><input type='radio' name='exportinclude' id='exportinclude' value='nothing'>Unbroken text.<br>
            <input type='radio' name='exportinclude' id='exportinclude' value='separator' checked = 'checked'> Page separators.<br>
            <input type='radio' name='exportinclude' id='exportinclude' value='names'> Proofer names in separators.<br>
            <input type='radio' name='exportinclude' id='exportinclude' value='pagetag'> No separator but include &lt;page&gt; tags.</p>\n";
    }
    echo "
            <p><input type='checkbox' id='exact' name='exact'> Include only pages which have completed the Round.<br/>
            (Otherwise, for each page not yet completed, the latest completed  version is included.)</p>
            <p></p>
            \n";

    $prompt1 = _("Download text");
    $prompt2 = _("View text");
    echo "
            <p><input type='submit' id='submit_export' name='submit_export' value='$prompt1'>
            <input type='submit' id='submit_view' name='submit_view' value='$prompt2'></p>
        </form>
    </div>\n";
}
// -----------------------------------------------------------------------------
/*
function echo_uploaded_zips($project, $filetype, $upload_type) {

    $pdir = $project->ProjectPath();

    $done_files = glob("$pdir/*".$filetype."*.zip");
  if ($done_files) {
      echo "<li><ul class='clean'>";
      echo sprintf( _("<li>Download %s file uploaded by:</li>"), $upload_type);
      foreach ($done_files as $filename) {
          $showname = basename($filename,".zip");
          $showname = substr($showname, strpos($showname,$filetype) + strlen($filetype));
          echo_download_zip($project, $showname, $filetype.$showname );
        }
      echo "</ul></li>";
    }
  else {
      echo "<br>" . sprintf( _("No %s results have been uploaded."), $upload_type);
    }

}
*/
// -----------------------------------------------------------------------------

/*
function echo_download_image_zip($project, $link_text) {
    global $code_url;
    $projectid = $project->ProjectId();
    $url = "$code_url/tools/download_images.php"
           ."?projectid=$projectid"
           ."&amp;dummy={$projectid}images.zip";
    echo "<li><a href='$url'>$link_text</a></li>\n";
}

function echo_download_ppv_zip($project, $link_text) {
    $url = build_path($project->ProjectUrl(), $project->ProjectId() . "_post_second.zip");
    echo "<li><a href='$url'>$link_text</a></li>\n";
}
function echo_download_pp_zip($project, $link_text) {
	echo "<submit id='submit_export' name='submit_export' value='Download PP text'>\n";
    $url = build_path($project->ProjectUrl(), $project->ProjectId() . ".zip");
    echo "<li><a href='$url'>$link_text</a></li>\n";
}

function echo_download_zip( $project, $link_text, $filetype ) {

    if ( $filetype == 'images' ) {
        echo_download_image_zip($project, $link_text);
    }
    else {
        echo_download_pp_zip($project, $link_text);
    }
}
*/

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

/*
function solicit_pp_report_card($project) {
    global $code_url;
    $url = "$code_url/tools/post_proofers/ppv_report.php?projectid={$project->ProjectId()}";
    echo "<p>" . link_to_url($url, "Submit a PPV Report Card for this project") . "</p>\n";
}
*/

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

/** @param DpProject $project */
function display_cp_comments($project) {
	echo "
	<div id='div_cp_comments' class='margined bordered padded'>
	" . _("<h3>Content Provider Comments</h3>
	</hr>
	<pre>" . $project->CPComments() . "</pre>
	</div>  <!-- div_cp_comments -->\n");
}

function solicit_postcomments($project, $level) {
    global $forums_url, $User;

    /** @var DpProject $project */

    if(! $project->UserIsPPer() && ! $project->UserIsPPVer() && ! $User->IsSiteManager()) {
        return;
    }

    $projectid = $project->ProjectId();
	$postcomments = $project->PostComments();

	echo "
	<div id='div_pp_comments' class='padded margined bordered'>
	<h3>" . _("Post-Processor's Comments") . "</h3>";

	// Give the PP-er a chance to update the project record
	// (limit of 90 days is mentioned below).
	echo "<p>" . sprintf(_("You can use this text area to enter comments on how you're
				 doing with the post-processing, both to keep track for yourself
				 and so that we will know that there's still work checked out.
				 You will not receive an e-mail reminder about this project for at
				 least another %1\$d days.") .
				 _("You can use this feature to keep track of your progress,
				 missing pages, etc. (if you are waiting on missing images or page
				 scans, please add the details to the <a href='%2\$s'>Missing Page
				 Wiki</a>)."),
				 90, "$forums_url/viewtopic.php?t=7584") . ' ' .
				 _("Note that your old comments will be replaced by those
				 you enter here.") . "</p>
        <form name='pp_update' method='post'>
        <textarea name='postcomments' cols='120' rows='10'>$postcomments</textarea>
        <input type='hidden' name='projectid' value='$projectid'>
		<input type='hidden' name='detail_level' value='$level'>
        <br />
        <input type='submit' name='submit_post_comments'
            value='" . _('Update comment and project status') . "'>
      </form>
      </div>  <!-- div_pp_comments -->\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

//function sr_echo_withdrawal_form($projectid) {
//	$button_text = _("Withdraw SR commitment");
//
//	echo "
//    <form name='sr' method='POST'>
//        <input type='hidden' name='projectid' value='$projectid'>
//        <input type='submit' name='sr_withdraw' value='$button_text'>
//    </form>\n";
//}

function solicit_smooth_reading($project) {

    /** @var DpProject $project */
    $project->MaybeUnzipSmoothZipFile();
    $projectid = $project->ProjectId();

    echo "
    <div id='divsmooth' class='bordered margined padded'>
    <h3>". _('Smooth Reading'). "</h3>
    <form name='srform' id='srform' method='POST' enctype='multipart/form-data'>
    <ul class='clean'>\n";

    if( $project->IsAvailableForSmoothReading() ) {
        echo _("<li>This project is available for smooth reading
				until <b>{$project->SmoothReadDate()}</b>.</li>\n");
        if( count($project->SmoothDownloadFileNames()) > 0) {
            echo "<li></li>
                <li>Download from the following smooth reading files.</li>
                <li>
                <ul class='clean'>\n";
            foreach($project->SmoothDownloadFileNames() as $name) {
                echo "<li>"
                    . link_to_smooth_download_file($project, $name, $name, true) . "</li>\n";
            }
            echo "</ul>
                </li>\n";
        }
        else {
            echo "<li>There are no files to download.</li>\n";
        }
    }
    else {
        echo _("<li>This project is not currently available for smooth reading.</li>
                <li></li>\n");
    }


    echo "
    <li>" . link_to_smoothed_upload($projectid,
            "Upload a text you have smooth-read") ." </li>
     </ul>\n";

        // Project has been made available for SR
//    if ( $project->IsAvailableForSmoothReading()) {


        /*
		if(! $project->UserIsCommittedToSmoothread()) {
            $button_text = _("Commit to SR");
            echo "
            <li>
            <form name='sr' method='POST'>
                <input type='hidden' name='projectid' value='$projectid'>
                "._("You may indicate your commitment to smoothread this project by pressing:")."
                <input type='submit' name='sr_commit' value='$button_text'>
            </form>
            </li>\n";
        }
        else {
            $button_text = _("Withdraw SR commitment");
            echo "
            <li>
            <form name='sr' method='POST'>
                <input type='hidden' name='projectid' value='$projectid'>
                "._("You have committed to smoothread this project. To withdraw your commitment, please press:")."
                <input type='submit' name='sr_withdraw' value='$button_text'>
            </form>
            </li>\n";
        }
        */


        // if($project->IsSmoothDownloadFile()) {
			// echo "<li>" . link_to_smooth_download($projectid,
                            // "Download zipped text for smoothreading") ." </li>\n";
		// }
        // echo "<li>" . link_to_smoothed_upload($projectid, "Upload a text you have smooth-read") ." </li>\n";

//    }
    if ( $project->UserMayManage()) {
        $instructions = $project->SmoothComments();
        $days = $project->SmoothDaysLeft();
        if($days < 0) {
            $days = "";
        }
        echo _("

    <hr class='lfloat w50 clear'>
        <p class='clear'>
            Set the smooth-reading deadline to how many days from today?
            <input type='text' name='srdays' id='srdays' value='$days' size='3'>
            <input type='submit' value='Submit' name='submitSRtime' id='submitSRtime'>
        </p>
        <p>Instructions for smooth-readers</p>
        <textarea name='srcomments' id='srcomments' cols='120' rows='20'>$instructions</textarea>
        <input type='submit' name='submit_srcomments' value='Submit'>\n");

        echo "
        <p>" . link_to_upload_text_to_smooth($projectid, "Upload a file for smooth-reading (new or replacement)") ."</p>\n";

        /*
        $sr_list = $project->CommittedSmoothReaders();

        if (! count($sr_list) ) {
            echo _('<li>No one has committed to smoothread this project.</li>');
        }
        else {
            echo _("
            <li>The following users have committed to smoothread this project:\n");
            foreach ($sr_list as $sr_user) {
                echo "<br />" . link_to_pm($sr_user) . "\n";
            }
            echo "</li>\n";
        }
        */

	    if($nuploaded = count($project->SmoothUploadedFiles()) > 0) {
		    echo "<p>Number of smooth readers who have uploaded the following {$nuploaded} files:</p>\n";
		    foreach($project->SmoothUploadedFiles() as $upfile) {
			    echo "<p>" . link_to_uploaded_smooth_file($project, $upfile) . "</p>\n";
		    }
	    }
	    else {
		    echo _("<p>No one has uploaded yet.</p>\n");
	    }
    }

    echo "
    </form>
    </div> <!-- divsmooth -->\n";
}

/**
 * @param DpProject $project
 * @param string $filename
 *
 * @return string
 * @internal param DpProject $project
 */
function link_to_uploaded_smooth_file($project, $filename) {
	$url = build_path($project->ProjectUrl(), $filename);
	$ary = RegexMatch("_smooth_done_(.*).zip", "ui", $filename, 1);
	$username = $ary[0];
	return "<a href='$url'>$username</a>\n";
}

/**
 * @param DpProject $project
 * @param string $name
 * @param string $prompt
 * @param bool $is_new_tab
 * @return string
 */
function link_to_smooth_download_file($project, $name, $prompt = "", $is_new_tab = false) {
    if($prompt == "")
        $prompt = $name;
    return link_to_url(url_for_smooth_download_file($project, $name), $prompt, $is_new_tab );
}

/**
 * @param DpProject $project
 * @param string $name
 * @return string
 */
function url_for_smooth_download_file($project, $name) {
    return build_path($project->SmoothDirectoryUrl(), $name);
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX


//function PrepActions($project) {
//    global $User;
//    $holds = $project->ActiveHolds();
//    foreach($holds as $hold) {
//        $code = $hold['hold_code'];
//        if($User->MayReleaseHold($code)) {
//            return array("prompt" => "Release {$hold['hold_code']} hold",
//                         "action" => "release.$code");
//        }
//    }
//    return array();
//}

//function export_project($project) {
//	$text = $project->PhaseExportText($project->Phase());
//	send_string($project->ProjectId()."_PP.txt", $text);
//}

// vim: sw=4 ts=4 expandtab

