<?PHP
ini_set('display_errors', 1);
error_reporting(E_ALL);

$relPath='./pinc/';

include_once($relPath.'dpinit.php');
include_once($relPath.'rounds.php');
include_once($relPath.'RoundsInfo.php');
include_once 'pt.inc'; // echo_page_table
include_once($relPath.'smoothread.inc');           // functions for smoothreading

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
$submit_post_comments   = IsArg("submit_post_comments");
//$down_images            = IsArg("down_images");
$submit_export          = Arg("submit_export");
$export_roundid         = Arg("export_roundid", $project->Phase());
$tags                   = Arg("tags", $project->Tags());
$postcomments           = Arg("postcomments");
$exact                  = IsArg("exact");
$is_proofers            = IsArg("proofers");
$linktotopic            = Arg("linktotopic");
$srdays                 = Arg("srdays");
$issrtime               = IsArg("submitSRtime");

if($issrtime && intval($srdays) >= 0) {
    $project->SetSmoothDeadlineDays($srdays);
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

if($submit_export) {
    export_project($project);
}
// -----

$project->MaybeAdvanceRound();

// if user submitted comments for post processing, load them
if($submit_post_comments) {
    $project->SetPostComments($postcomments);
}

//$project->SetTags($tags);

//if($down_images) {
//    $project->SendImageZipFile();
//}

//if($down_text) {
//    $project->SendPPZipFile();
//}

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

    echo "</div>\n";
    exit;
}




// don't show the stats column
$no_stats = 1;
theme($title_for_theme, "header");


echo "<div id='divproject' class='px1000 lfloat clear'>\n";
detail_level_switch($projectid, $level);
echo "<div class='rfloat margined j5'>"
     .link_to_project_trace($projectid, "Project Trace")
     ."</div>\n";
echo "<h1 class='center clear'>{$project->Title()}</h1>\n";

echo "
    <div class='div_project_info'>
    $top_status
    </div>\n";
// show_status_box( $top_status );

project_info_table($project, $level);

if($project->UserMayManage()) {
	$vurl = "http://validator.w3.org/check?uri="
	. $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]
	  . "&amp;charset=%28detect+automatically%29&amp;doctype=Inline&amp;group=0";
    echo "<div class='clear'>"
         . link_to_edit_project($projectid, "Edit above project information")."<br/>"
         . link_to_url($vurl, "Validate this project page", true)
         . "</div>\n";
	/*
	echo "<input type='button' name='btnvalidate' id='btnvalidate' value='Validate'
			onclick='window.open(\"http://validator.w3.org/check?uri=\"+document.URL+\"&charset=%28detect+automatically%29&doctype=Inline&group=0\",
				\"_blank\"); return 0;'>";
	*/
}

echo "
    <div class='div_project_info'>
    $bottom_status
    </div>  <!--  div_project_info -->
    <br/>\n";
// show_status_box( $bottom_status );

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

	echo "<hr class='clear'>\n";
	offer_images( $project );

	offer_post_downloads( $project, $export_roundid , $level);

	if($project->IsInRounds()) {
		show_page_summary( $project );
	}


}

if($level > 3) {

	if ( $project->Phase() == "PPV" && $User->MayPPV() ) {
		solicit_pp_report_card( $project );
	}

	offer_extra_files( $project );

	show_history( $project );

	echo "</div> <!-- divproject -->\n";

	if($project->PageCount() > 0) {
		show_page_table( $project );
	}
}

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
    echo "</div>\n";
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
    global $code_url;
    global $User;

    /** @var DpProject $project */

    $projectid      = $project->ProjectId();
    $postcomments   = $project->PostComments();
    $postcomments   = str_replace("\n", "<br />", h($postcomments));



    // -------------------------------------------------------------------------
    // The state of the project

    $available_for_SR = ( $project->SmoothreadDeadline() > time() );

	$right = $project->RoundDescription();

    echo "<table id='project_info_table'>\n";
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
//    $topic_id = $project->ForumTopicId();
//	$last_post_date = $project->LastForumPostDate();
//    if ($topic_id) {
//        if(! $Context->TopicExists($topic_id)) {
//            $topic_id = "";
//            $project->ClearForumTopicId();
//        }
//        else {
//            $last_post_date = $dpdb->SqlOneValue("
//                SELECT MAX(post_time) FROM forum.bb_posts
//                WHERE topic_id = $topic_id");
//            $last_post_date = std_datetime($last_post_date);
//            echo_row_left_right( _("Last Forum Post"), $last_post_date );
//        }
//    }

    if($project->Phase() == 'POSTED') {
        echo_row_left_right( _( "Posted etext number" ),
            link_to_fadedpage_catalog( $project->PostedNumber(), $project->PostedNumber() ) );
    }

    // -----------------------

	$status = ($project->ForumTopicIsEmpty()
				? _("Start a discussion about this project")
				: _("Discuss this project"));
//    $status = ($topic_id == "")
//                        ? _("Start a discussion about this project")
//                        : _("Discuss this project");
    $url = "?projectid={$projectid}&amp;linktotopic=1";
    echo_row_left_right( _("Forum"), "<a href='$url'>$status</a>" );

    // -------------------------------------------------------------------------
    // Page detail (link to diffs etc.)

//    if($project->PageCount() > 0) {
        // $url = url_for_page_detail($projectid);
	$status = _("Images, pages edited, & differences");
	$link = link_to_page_detail($projectid, $status);

        // $url2 = "$url&amp;my_pages=1";
	$status2 = _("Just my pages");
	$link2 = link_to_page_detail_mine($projectid, $status2);

	echo_row_left_right( _("Page Detail"), "$link &gt;&gt;$link2 &lt;&lt;");
        // "<a href='$url'>$status</a> &gt;&gt;<a href='$url2'>$status2</a>&lt;&lt;");
//    };

    // -------------------------------------------------------------------------
    // Personal data with respect to this project
    // (This is the only section that uses $pguser and $userP.)

    $username = $User->Username();

//	$isnotify = $project->IsUserProjectNotify();
//    $isnotify = $dpdb->SqlExists( "
//             SELECT id FROM notify
//             WHERE projectid = '$projectid'
//                 AND username = '$username'");
    if (! $project->IsUserProjectNotify()) {
        $status = _("Click to register for email notification
        when this is posted.");
        $url = "$code_url/tools/proofers/posted_notice.php" 
                                            ."?projectid=$projectid"
                                            ."&amp;setclear=set";
    }
    else {
        $status = _("<p>You ($username) are registered to be notified by email
        when this project has been posted.</p>
        <p>Click here to cancel your registration.</p>");
        $url = "$code_url/tools/proofers/posted_notice.php" 
                                            ."?projectid=$projectid"
                                            ."&amp;setclear=clear";
    }

    // ------------------------------------------------------------

    echo_row_left_right( _("Book Completion:"), "<a href='$url'>$status</a>" );

    // -------------------------------------------------------------------------
    // Post Comments

	// used for SR instructions

	if ( $available_for_SR ) {
		echo_caption_row( _("Instructions for Smooth Reading"));
		echo "<tr><td colspan='2' style='min-height: 1em;'> $postcomments</td></tr> <!-- 3 -->\n";
	}
        // Postcomments should be shown to those directly involved with the project (the
        // first three conditions). But when the project is available for PPV, the 
        // PPVer should be able to read the PPer's comments without checking out the project.
	else if ( $project->UserIsPPer() || $project->UserIsPPVer() || $project->UserMayManage() ) {
		echo_caption_row( _("Post Processor Comments") );
		echo_row_one_cell( $postcomments );
	}

//	if ( $project->UserIsPPer() || $project->UserIsPPVer() || $project->UserMayManage() ) {
//		echo_caption_row( _("Project Tags") );
//		echo_row_one_cell( tag_box($project) );
//	}

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

	echo "</table>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function echo_row_left_right( $left, $right) {
    echo "
    <tr><td class='left w25 bgEEEEEE'><b>$left</b></td>
    <td>$right</td></tr> <!-- 1 -->\n";
}

function echo_caption_row($content) {
    echo "
    <tr><td colspan='2' class='center bgEEEEEE'><b>$content</b></td></tr>\n";
}

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

	echo html_comment($sql);

	$checked_out_objs = $dpdb->SqlObjects($sql);

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
    <div class='clear bordered margined padded' id='divupload'>
    <h4>Project Management</h4>
    <form method='POST'>
    <input type='hidden' name='projectid' value='$projectid'>
    <input type='hidden' name='detail_level' value='$level'>
    "._("Add/Upload page text and image files.")."
    <input type='submit' name='btn_manage_files' id='btn_manage_files'
            value='Manage Files'>
    <p>There $sholds currently in effect.
    <input type='submit' name='btn_manage_holds' id='btn_manage_holds' value='$mng_holds'>
    </p>
    <p>For WordCheck adminstration.
    <input type='submit' name='btn_manage_words' id='btn_manage_words' value='$mng_words'>
    </p>
    </form>
    </div>\n";
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

    $tbl = new DpTable("tblevents", "w75");
    $tbl->SetClass("lfloat");
    $tbl->AddColumn("^When", "timestamp");
    $tbl->AddColumn("^What", "event_type");
    $tbl->AddColumn(null, "details1");
    $tbl->AddColumn(null, "details2");
    $tbl->SetRows($events);

    echo "<div class='clear'>
     <h4>Project History</h4>\n";
    $tbl->EchoTable();
    echo "</div>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function show_page_summary($project) {
	/** @var DpProject $project */


	echo "
    <div class='lfloat clear'>
        <h3>"._("Page Summary")."</h3>\n";

	if($project->PageCount() == 0) {
		echo "<h4 class='lfloat'>No pages in this project yet.</h4>\n";
	}
	else if($project->IsActivePhase()) {

		echo "
			<table id='tblpagesummary' class='noborder lfloat'>
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
	</div>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function show_page_table($project) {

	/** @var DpProject $project */

	echo "
    <div id='page_table_key' class='clear'>
        <h4 class='lfloat'>". _('Pages edited by you and...') . "</h4>
        <div style=' margin-bottom: .4em' class='pg_out bordered padded lfloat clear'>"
	     . _("Checked Out (awaiting completion this round)") . "</div>
        <div style=' margin-bottom: .4em' class='pg_completed bordered padded lfloat'>"
	     . _("Completed (still available for editing this round)") ."</div>
        <div style=' margin-bottom: .4em' class='pg_unavailable bordered padded lfloat'>"
	     . _("Completed in a previous round (no longer available for editing)") ."</div>
    </div>

    <div class='lfloat clear'>\n";

	// second arg. indicates to show size of image files.
	echo_page_table($project);
	echo "
    </div>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

/** @param $project DpProject */
function offer_images($project) {
    global $code_url, $Context;
//	global $User;

	$projectid = $project->ProjectId();
    $imgurl = "$code_url/tools/proofers/images_index.php?projectid=$projectid";
    $imglink = link_to_url($imgurl, "View Images Online");
	$texturl = "$code_url/project_text.php?projectid=$projectid";
	$textlink = link_to_url($texturl, "View Latest Text Online", true);
	if($project->PhaseSequence() >= $Context->PhaseSequence("P3")) {
		$texturlP3 = "$code_url/project_text.php?projectid=$projectid&amp;phase=P3";
		$textlinkP3 = link_to_url($texturlP3, "View P3 Text Online", true);
	}

    echo "<div class='bordered margined padded'>
		<form name='frmimages' method='POST'>
        "._('<h4>Images and Text</h4>')."
        <p>$imglink</p>
        <p>$textlink</p>\n";
	if(isset($textlinkP3)) {
		echo "<p>$textlinkP3</p>\n";
	}
	if(isset($textlinkdk)) {
		echo "<p>$textlinkdk</p>\n";
	}

	echo "
        </form>
        </div>\n";
}


// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function offer_extra_files($project) {
    global $dpdb;
    /** @var DpProject $project */

	$projectid = $project->ProjectId();
    $path = build_path($project->ProjectPath(), "*");
    $filenames = glob($path);
    if ( count($filenames) == 0 ) {
        echo "<p>(no files in project directory)</p>\n";
        return;
    }

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

	echo _("<div class='bordered margined padded clear'>
	<h4>Project Files</h4>\n");

    echo "
    <div class='w90 left'>
    <ul class='clean'>\n";

		foreach ($filenames as $filename) {
			$filename = basename($filename);
			if ( !in_array( $filename, $notfiles ) ) {
				$url = build_path($project->ProjectUrl(), $filename);
				echo "<li>" . link_to_url($url, $filename) . "</li>\n";
			}
		}

    echo "</ul>
    </div>
	<p>".link_to_url($project->ProjectUrl(), "Browse the project directory")."</p>
	</div>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function offer_post_downloads($project, $export_roundid, $level) {
    global $User, $Context;
    /** @var DpProject $project */

    $projectid = $project->ProjectId();

    if($User->MayWorkInRound("PP") || $User->MayWorkInRound("PPV")) {
        echo "
	<div class='bordered margined padded'>
        <h4 class='clear'>". _("Post Downloads") . "</h4>


        <ul class='clean'>\n";


			echo_download_image_zip($project, _("Download Zipped Images"));
			if ($project->Phase() == "PP") {
				echo_download_pp_zip($project, _("Download Zipped Text"), '' );

				echo "<li>";
					echo_uploaded_zips($project, '_first_in_prog_', _('partially post-processed'));
				echo "</li>";
			}

			else if ($project->Phase() == "PPV") {
				echo_download_ppv_zip($project, _("Download Zipped PP Text"), '_second' );
				echo "<li>";
				echo_uploaded_zips($project, '_second_in_prog_', _('partially verified'));
				echo "</li>";
			}
	    echo "


	    </ul>


	</div>\n";
    }

	echo "
	<div class='bordered margined padded'>
		<h4 class='clear'>". _("Concatenated Text Files")."</h4>


		<ul class='clean'>\n";


    echo "
		<li>
			<form name='frmdownload' method='post'>
			<input type='hidden' name='projectid' value='$projectid'>
			<input type='hidden' name='detail_level' value='$level'>
			<br>
		    <ul>

			    <li>Download concatenated text from
					<input type='radio' name='export_roundid' value='OCR'>OCR: ";

	/** @var Round $round */
			foreach ( $Context->Rounds() as $round ) {
				$roundid = $round->RoundId();
				echo "
				<input type='radio'  name='export_roundid' value='{$roundid}'>{$roundid}&nbsp;\n";
				if ( $roundid == $export_roundid )  {
					break;
				}
			}
			echo "
				<input type='radio' name='export_roundid' value='newest' checked>Newest&nbsp;
				</li>\n";

	echo "

			</ul>
			<ul class='clean'>\n";


    if ( $project->UserMaySeeNames() ) {
        echo "<li><input type='checkbox' name='proofers' id='proofers' />
        Include usernames? </li>\n";
    }
    echo "
    <li><input type='checkbox' id='exact' name='exact'>
    Include only pages which have been completed in the Round.
    (The default is to include completed text from the previous round for uncompleted pages.)</li>\n";

	$prompt = _("Download Round Text");
    echo "
    <li><input type='submit' id='submit_export' name='submit_export' value='$prompt'></li>


				</ul>


    </form>
    </li>

			</ul>

    </div>\n";
}

// -----------------------------------------------------------------------------
function echo_uploaded_zips($project, $filetype, $upload_type) {
    /** @var DpProject $project */

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
// -----------------------------------------------------------------------------

function echo_download_image_zip($project, $link_text) {
    /** @var DpProject $project */
    global $code_url;
    $projectid = $project->ProjectId();
    $url = "$code_url/tools/download_images.php"
           ."?projectid=$projectid"
           ."&amp;dummy={$projectid}images.zip";
    echo "<li><a href='$url'>$link_text</a></li>\n";
}

function echo_download_ppv_zip($project, $link_text) {
    /* @var DpProject $project */
    $url = build_path($project->ProjectUrl(), $project->ProjectId() . "_post_second.zip");
    echo "<li><a href='$url'>$link_text</a></li>\n";
}
function echo_download_pp_zip($project, $link_text) {
    /* @var DpProject $project */
	echo "<submit id='submit_export' name='submit_export' value='Download PP text'>\n";
    $url = build_path($project->ProjectUrl(), $project->ProjectId() . ".zip");
    echo "<li><a href='$url'>$link_text</a></li>\n";
}

function echo_download_zip( $project, $link_text, $filetype ) {
    /** @var DpProject $project */

    if ( $filetype == 'images' ) {
        echo_download_image_zip($project, $link_text);
    }
    else {
        echo_download_pp_zip($project, $link_text);
    }
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function solicit_pp_report_card($project) {
    /** @var DpProject $project */
    global $code_url;
    $url = "$code_url/tools/post_proofers/ppv_report.php?projectid={$project->ProjectId()}";
    echo "<p>" . link_to_url($url, "Submit a PPV Report Card for this project") . "</p>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

/** @param DpProject $project */
function display_cp_comments($project) {
	echo _("<h4>Content Provider Comments</hr>
	<pre>" . $project->CPComments() . "</pre>\n");

}
function solicit_postcomments($project, $level) {
    global $forums_url, $User;

    /** @var DpProject $project */

    if(! $project->UserIsPPer() && ! $project->UserIsPPVer() && ! $User->IsSiteManager()) {
        return;
    }

    $projectid = $project->ProjectId();
	$postcomments = $project->PostComments();

	echo "<h4>" . _("Post-Processor's Comments") . "</h4>";

	// Give the PP-er a chance to update the project record
	// (limit of 90 days is mentioned below).
	echo '<p>' . sprintf(_("You can use this text area to enter comments on how you're
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
        <input type='hidden' name='projectid' value='$projectid' />
		<input type='hidden' name='detail_level' value='$level'>
        <br />
        <input type='submit' name='submit_post_comments'
            value='" . _('Update comment and project status') . "'/>
      </form>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

// pm uploads file named "{projectid}_smooth_avail"
function solicit_smooth_reading($project) {
    global $User;

    $username = $User->Username();

    /** @var DpProject $project */
    if ( $project->Phase() != "PP" && $User->IsSiteManager() == false )
        return;

    $projectid = $project->ProjectId();

    echo "
    <div id='divsmooth' class='bordered margined padded'>
    <h4>", _('Smooth Reading'), "</h4>
    <ul class='clean'>\n";
    if($project->IsAvailableForSmoothReading() || $User->IsSiteManager()) {
        echo _("<li>This project is scheduled to be available for smooth reading
				until <b>{$project->SmoothReadDate()}</b>.</li>\n");
        if(! $project->IsSmoothDownloadFile()) {
            echo _("<li>But there is no file uploaded to read yet.</li>\n");
        }
    }
    else {
        echo _('<li>This project is not currently available for smooth reading.</li>');
    }

        // Project has been made available for SR
    if ( $project->IsAvailableForSmoothReading() || $User->IsSiteManager()) {

        // The upload does not cause the project to change state --
        // it's still checked out to PPer.

        if (!sr_user_is_committed($projectid, $username)) {
            echo _('<li>You may indicate your commitment
                to smoothread this project by pressing:');
            sr_echo_commitment_form($projectid);
            echo "</li>\n";
        }
        else {
            echo _(
                '<li>You have committed to smoothread this project.
                If you want to withdraw your commitment, please press:');
            sr_echo_withdrawal_form($projectid);
            echo "</li>";
        }

        if($project->IsSmoothDownloadFile()) {
			echo "<li>" . link_to_smooth_download($projectid,
                            "Download zipped text for smoothreading") ." </li>\n";
		}
        echo "<li>" . link_to_smoothed_upload($projectid, "Upload a text you have smooth-read") ." </li>\n";

    }
    if ( $project->UserIsPPer() || $User->IsSiteManager()) {
        $days = $project->SmoothDaysLeft();
        if($days < 0) {
            $days = "";
        }
        echo _("
        <li>
          <form name='srform' id='srform' method='POST'>
            Set the smooth-reading deadline to how many days from today?
            <input type='text' name='srdays' id='srdays' value='$days' size='3' />
            <input type='submit' value='Submit' name='submitSRtime' id='submitSRtime' />
          </form></li>\n");
        echo "
        <li>"
            . link_to_upload_text_to_smooth($projectid, "Upload a file for smooth-reading (new or replacement)")
            ."</li>\n";

        $sr_list = sr_get_committed_users($projectid);

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

	    if($nuploaded = count($project->SmoothUploadedFiles()) > 0) {
		    echo "<li>Number of smooth readers who have uploaded the following {$nuploaded} files:</li>\n";
		    foreach($project->SmoothUploadedFiles() as $upfile) {
			    echo "<li>" . link_to_uploaded_smooth_file($project, $upfile) . "</li>\n";
		    }
	    }
	    else {
		    echo _("<li>No one has uploaded yet.</li>\n");
	    }

    }

    echo "</ul>
    </div> <!-- divsmooth -->\n";
}

/**
 * @param DpProject $project
 * @param $filename
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

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX


function PrepActions($project) {
    global $User;
    /** @var DpProject $project */
    $holds = $project->ActiveHolds();
    foreach($holds as $hold) {
        $code = $hold['hold_code'];
        if($User->MayReleaseHold($code)) {
            return array("prompt" => "Release {$hold['hold_code']} hold", 
                         "action" => "release.$code");
        }
    }
    return array();
}

function export_project($project) {
	/** @var DpProject $project */
	$text = $project->ExportText();
	send_string($project->ProjectId()."_PP.txt", $text);
}

// vim: sw=4 ts=4 expandtab

