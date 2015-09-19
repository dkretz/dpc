<?PHP
// This page covers all project-related activities of the site.
// For each, it:
// -- describes the activity;
// -- briefly summarizes its current state; and
// -- gives a link to the particular page for that activity.
//
// (Leaves out non-project-related activities like:
// forums, documentation/faqs, development, admin.)

ini_set('display_errors', 1);
error_reporting(E_ALL);

$relPath = "./pinc/";
include_once($relPath.'dpinit.php');
//include_once($relPath.'stages.inc');
include_once($relPath.'site_news.inc');
//include_once($relPath.'mentorbanner.inc');
include_once($relPath.'RoundsInfo.php');

$User->IsLoggedIn()
	or RedirectToLogin();

$pagename = "activityhub";
$link_projectid = Arg("link_projectid");

if(IsArg('cmdprojectid') && $link_projectid != "") {
    divert( url_for_project( $link_projectid ) );
    exit;
}

/*
if(IsArg("cmd_uncookie")) {
	$keys = array_keys($_COOKIE);
	foreach($keys as $key) {
		if(left($key,6) == "phpbb3") {
			DeleteCookie($key);
		}
	}
}
*/

$ahtitle = _("Activity Hub");

$opts = array("js_file" => "/c/js/dp_ajax.js");
//$opts = array("hdr_include" => "c.php");
theme($ahtitle, "header", $opts);

echo "<script type='text/javascript'>
	var diveditor = $('diveditor');
	addEvent(diveditor, 'click', eEditor);

	function eEditor(e) {
		if(! e) e = window.event;
		switch(e.target.name) {
			case 'pennask':
			case 'ahmic':
			case 'whistler':
			case 'blackcomb':
			case 'standard':
			case 'enhanced':
				break;
			default:
				return;
		}
		var qry = {};
		qry['querycode']    = 'seteditor';
		qry['editor']       = e.target.name;
		writeAjax(qry);
	}

	function ajax_handler(rsp) {
		switch(rsp.querycode) {
			case 'seteditor':
				eEditor(rsp);
				return;
			default:
				return;
		}
	}

	initAjax(ajax_handler);

	</script>\n";


echo "
    <div class='center overflow'>
        <img src='$code_url/graphics/Activity_Hub.jpg' alt='$ahtitle'>
        <p class='center'>
        ".link_to_metal_list("Gold", "Recently Published Ebooks")."
        </p>
    </div>\n";

/*
echo "
<div class='lfloat'>
	<form id='frmcookies' name='frmcookies' action='' method='POST'>
        Trouble with login?
        <input type='submit' name='cmd_uncookie' id='cmd_uncookie' value='Delete Cookies'>
    </form>
</div>\n";
*/

show_admin_links();

if ( $User->PageCount() <= 300 && $User->InboxCount() > 0 ) {
        echo
        "<hr class='w75'>
        <div>\n";

        echo _("
        <p class='red'>You have received a private message in your Inbox!</p>");
        echo _("<p>This could be from somebody sending you feedback on some of the
        pages you had proofread earlier. We strongly recommend you READ your
        messages. In the links at the top of this page, there is one that says
        My Inbox. Just click on that to open your Inbox.</p>");
        echo _("<p>(After a while this explanatory paragraph will not appear when
        you have new messages, but the link to your Inbox will always be up
        there and when you have new messages that will be shown in the link)</p>");
        echo "</div>";
}



if ($User->PageCount() <= 100) {
    echo "
        <hr class='w75'>
        <div>
        <h1 class='blue'>"
        ._("Welcome")
        ."</h1>\n"
        ._("<p>Please see our ") ."<a href='$beginners_site_forum_url'>"
        ._("Beginner's Forum")
        ."</a>". _(" for answers to common questions.</p>
        </div>\n");
}


// Site News
echo "
<div>
<hr class='w75'>\n";

show_news_for_page("HUB");

echo "</div>\n";

$feedback_url = "$forums_url/viewtopic.php?f=3&amp;t=388";
echo "
<div>
<hr class='w75'>\n"
._("<h4>New Proofreaders</h4>")
."<p><a href='$feedback_url'>"
._("What did you think of the Mentor feedback you received?")
."</a></p>
</div>

<hr class='w75'>
<ul>\n";

if ( $User->IsProjectManager() ) {
    echo "
    <li>" . link_to_project_manager("Manage My Projects") . "</li>\n";
}

// ----------------------------------

$acounts = array();
$rows = $dpdb->SqlObjects("
    SELECT phase, COUNT(1) AS scount
    FROM projects
    GROUP BY phase
    ORDER BY phase");
foreach($rows as $row) {
    $acounts[$row->phase] = $row->scount;
}



// Providing Content
echo "
    <li>" . _("Providing Content") 
    . "<br>"
    . _("Want to help out the site by providing material for us to proofread? ")
    . "<a href='$code_url/faq/cp.php'>"
    . _("Find out how!")
    . "</a>
    </li>\n";

/** @var Round $round */
foreach ( $Context->Rounds() as $round ) {
	$roundid = $round->RoundId();
    $phase_icon_path = "$dyn_dir/stage_icons/$roundid.jpg";
    $phase_icon_url  = "$dyn_url/stage_icons/$roundid.jpg";
    if ( file_exists($phase_icon_path) ) {
        $round_img = "<img src='$phase_icon_url' alt='($roundid)' align='middle'>";
    }
    else {
        $round_img = "($roundid)";
    }
	$rname = RoundIdName($roundid);
    $rdesc = RoundIdDescription($roundid);
    $rlink = link_to_round($roundid, $rname);

    echo "
        <li> 
        <hr class='w75'>
        $round_img $rlink <br> $rdesc <br /><br />\n";

    summarize_projects($roundid);
}

$phase = "PP";
$rname = NameForPhase($phase);
$rdesc = DescriptionForPhase($phase);
$rlink = link_to_pp($rname);
echo "
        <li>
        <hr class='w75'>
        ($phase) $rlink <br> $rdesc <br /><br />\n";
summarize_projects( $phase );

$n_checked_out = $dpdb->SqlOneValue("
        SELECT COUNT(1) FROM projects
        WHERE postproofer='{$User->Username()}'
            AND phase='PP'");
if ($n_checked_out) {
    echo sprintf( _("You currently have %d projects checked out in this phase."), $n_checked_out );
    echo "<br>\n";
}


$phase = "SR";
$rname = _("Smooth Reading");
$rdesc = _("Nearly completed projects are often made available for reading and checkproofing before posting.");
$rlink = link_to_smooth_reading($rname);
echo "
        <li>
        <hr class='w75'>
        ($phase) $rlink <br> $rdesc <br /><br />\n";


// ----------------------------------------------------

$phase = "PPV";
$rname = NameForPhase($phase);
$rdesc = DescriptionForPhase($phase);
$rlink = link_to_ppv($rname);
echo "
        <hr class='w75'>
        <li> ($phase) $rlink <br> $rdesc <br /><br />\n";
summarize_projects( $phase );

$n_checked_out = $dpdb->SqlOneValue("
        SELECT COUNT(1) FROM projects
        WHERE ppverifier='{$User->Username()}'
            AND phase='PPV'");
if ($n_checked_out) {
    echo sprintf( _("You currently have %d projects checked out in this phase."), $n_checked_out );
    echo "<br>\n";
}

echo "
    </li>
</ul>\n";


theme("", "footer");

function summarize_projects( $phase) {
    global $dpdb;

    if($phase == "P1" || $phase == "P2" || $phase == "P3" || $phase == "F1" || $phase == "F2") {
        $row = $dpdb->SqlOneRow("
            SELECT SUM(CASE WHEN NOT EXISTS (SELECT 1 FROM project_holds
                                             WHERE projectid = p.projectid
                                                 AND phase = '$phase')
                            THEN 1 ELSE 0
                       END) navail,
                   COUNT(1) ntotal
            FROM projects p WHERE p.phase = '$phase'");
        $navail = $row["navail"];
        $ntotal   = $row["ntotal"];
        $nwaiting = $ntotal - $navail;

        echo _("
            <table class='bordered hub_table'>
            <tr class='navbar'><td rowspan='2'>All projects</td>
                               <td>On Hold</td>
                               <td>Available</td>
                               <td>Total<br>Projects</td></tr>

            <tr><td>$nwaiting</td><td>$navail</td><td>$ntotal</td></tr>
            </table>\n");
        return;
    }


    if($phase == "PP") {
        $row = $dpdb->SqlOneRow("
             SELECT SUM(IFNULL(postproofer, '') = '') navail,
                    COUNT(1) ntotal,
                    SUM(smoothread_deadline > UNIX_TIMESTAMP() ) nsmooth
             FROM projects where phase = '$phase'");
        $navail = $row["navail"];
        $ntotal = $row["ntotal"];
        $nchecked_out = $ntotal - $navail;
        $nsmooth = $row["nsmooth"];
        echo _("
        <table class='bordered hub_table'>
        <tr class='navbar'><td rowspan='2'>All projects</td>
                           <td>Available<br>for $phase</td>
                           <td>Checked<br>Out</td>
                           <td>Avail for<br>Smooth Reading</td>
                           <td>Total<br>Projects</td></tr>\n");
        echo "
        <tr><td>$navail</td><td>$nchecked_out</td><td>$nsmooth</td><td>$ntotal</td></tr>
        </table>\n";
    }
    else {
        $row = $dpdb->SqlOneRow("
             SELECT SUM(CASE WHEN IFNULL(ppverifier, '') = '' THEN 1 ELSE 0 END) navail,
                    COUNT(1) ntotal
             FROM projects where phase = '$phase'");
        $navail = $row["navail"];
        $ntotal = $row["ntotal"];
        $nchecked_out = $ntotal - $navail;
        echo _("
        <table class='bordered hub_table'>
        <tr class='navbar'><td rowspan='2'>All projects</td>
                           <td>Available<br>for $phase</td>
                           <td>Checked<br>Out</td>
                           <td>Total<br>Projects</td></tr>\n");
        echo "
        <tr><td>$navail</td><td>$nchecked_out</td><td>$ntotal</td></tr>
        </table>\n";
    }



}

function show_admin_links() {
    global $User;
    echo "
    <div id='admin_links' class='center w50 overflow'>
    <h5>Admin Links</h5>
    <pre class='left'>
    <a href='http://www.pgdpcanada.net/c/tools/prep.php'>Projects Before P1</a>
    <a href='http://www.pgdpcanada.net/c/tools/p1counts.php'>P1 Genre Counts</a>
    <a href='http://www.pgdpcanada.net/c/tools/active_projects.php'>Active Project List</a>
    <a href='http://www.pgdpcanada.net/c/tools/holds.php'>Projects In The Rounds On Hold</a>
    <a href='http://www.pgdpcanada.net/c/search2.php'>Extended Project Search</a>\n";
    if($User->IsSiteManager() || $User->IsProjectFacilitator() || $User->IsProjectManager()) {
            echo "
    <a href='http://www.pgdpcanada.net/c/user_pages.php'>User Diffs for a Round</a>
    <a href='http://www.pgdpcanada.net/c/tools/site_admin/user_roles.php'>Manage Roles for a User</a>
    <a href='http://www.pgdpcanada.net/c/tools/p1release.php'>Project P1 Release</a>
    <a href='http://www.pgdpcanada.net/c/tools/pper.php'>User Post Processor Report</a>
    <a href='http://www.pgdpcanada.net/c/tools/bad.php'>Bad Pages</a>
    <a href='http://www.pgdpcanada.net/c/tools/trace.php'>Project Trace</a>
    <a href='http://www.pgdpcanada.net/c/tools/site_admin/sitenews.php'>Site News Central</a>";
    }
    echo "
    </pre>\n";
    if($User->IsSiteManager()
            || $User->IsProjectFacilitator()
            || $User->IsProjectManager()
            || $User->IsProjectManager()) {
        echo "
        <form id='frmadmin' name='frmadmin' action='' method='POST'>
        <div class='lfloat'>
             <p>Project Page for project ID:
            <input type='text' name='link_projectid' id='link_projectid'>
            <input type='submit' name='cmdprojectid' id='cmdprojectid' value='Submit'></p>
		</div>
        </form>\n";
    }
    echo "
    </div>
    <hr class='left clear hidden' />\n";

}
