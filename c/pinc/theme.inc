<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
global $relPath;
$mtime = explode(" ", microtime());
$starttime = $mtime[1] + $mtime[0];

$time_comment = array();

//global $User;

/** @var DpThisUser $username */
//$username = $User->Username();

include_once($relPath.'gettext_setup.inc');
include_once($relPath.'stats.php');
//include_once($relPath."templates/theme.tpl");

function theme($nameofpage, $location, $extra_args = array()) {
    global $User, $code_url; // $theme, $theme_name;
    global $no_stats;

	if(! $User->IsLoggedIn()) {
		$no_stats = 1;
	}

	$statsbar_align = "right";

    if (! $no_stats) {
        // if stats bar on left
        if ($statsbar_align == "left") {

            if ($location == "header") {

/*
    -------------------------------------------------
    header when NOT $no_stats and stats bar on left
    -------------------------------------------------
*/

                html_header($nameofpage, $extra_args);
                html_logobar();
                echo "
    <table id='tbl1'>
    <tr>
    <td class='left top navback w25'>\n";
                html_statsbar();
                echo "
    </td>
    <td class='right top mainbody'>
    <img src='$code_url/graphics/r_curve.gif'
    alt='Provides a round curve for a navigation bar'></td>
    <td class='left top mainbody'>\n";
            }

// -------------------------------------------------
//  footer when NOT $no_stats and stats bar on left
// -------------------------------------------------

            else if ($location == "footer") {
                echo "</td></tr></table>\n";
                html_footer();
            }
        }
        else {
            if ($location == "header") {

// -------------------------------------------------
//  header when NOT $no_stats and stats bar on right
//  ********** MOST COMMON CASE ***************
// -------------------------------------------------

                html_header($nameofpage, $extra_args);
                html_logobar();
                echo "
    <table id='tbl2' class='w100'>
    <tr>
    <td id='tbl2c1' class='w75 left top'>\n";
            }

// -------------------------------------------------
//  footer when NOT $no_stats and stats bar on right
// -------------------------------------------------

            else if($location == "footer") {
                echo "
    </td>
    <td id='tbl2c2' class='right top'>
        <img src='$code_url/graphics/r_curve.gif' style='position: relative; top: -3px;' alt=''>
    </td>
    <td id='tbl2c3' class='navbar w25 left top'>\n";
                html_statsbar();
                echo "</td></tr></table>\n";
                html_footer();
            }
        }
    }
    else {
        // is no_stats
        if ($location == "header") {

// -----------------------------------------------------
//  header when IS $no_stats so no stats bar to position
// -----------------------------------------------------

            html_header($nameofpage, $extra_args);
            html_logobar();
            echo "
    <table id='tbl3' class='w100'>
    <tr>
    <td class='left top mainbody'>&nbsp;</td>
    <td class='left top w100 mainbody'>\n";
        }
        else if($location == "footer") {

// -----------------------------------------------------
//  footer when IS $no_stats so no stats bar to position
// -----------------------------------------------------

            echo "</td></tr></table>\n";
            html_footer();
        }
    }
}

function html_header($nameofpage, $extra_args = array()) {
    global $code_url;

    $incl = "";
    $onload = "";
    foreach($extra_args as $key => $val) {
        switch($key) {
            case "hdr_include":
                break;

            case "css_file":
                $incl .= "<link rel='stylesheet' href='$val'>\n";
                break;

            case "css_data":
                $incl .= "<style type='text/css'>$val</style>\n";
                break;

            case "js_text":
            case "js_data":
                $incl .= "<script type='text/javascript'>$val</script>\n";
                break;

            case "js_file":
                $incl .= "<script type='text/javascript' src='$val'></script>\n";
                break;

            case "body_onload":
                $onload .= " onload='$val'";
                break;
        }
    }

    echo
"<!DOCTYPE HTML>
<html>
<head>
<meta charset='utf-8'>
<link rel='shortcut icon' href='$code_url/favicon.ico'>
<script type='text/javascript' src='/c/js/sorttable.js'></script>\n";

echo "
<script type='text/javascript' src='/c/js/dp.js'></script>
<title>DPC: ". (isset($nameofpage)
                    ? "$nameofpage"
                    : "Distributed Proofreaders Canada" )
. "</title>
<link type='text/css' rel='Stylesheet' href='/c/css/dp.css'>\n";
    echo
"$incl
</head>
<body $onload>\n";
}

function html_logobar() {
    global $code_url;
    global $dpdb;
    global $User;
    global $completed_books_offset;

//    $username = $User->Username();
    $numproj = $dpdb->SqlOneValue("
        SELECT COUNT(distinct postednum) FROM projects
        WHERE phase = 'POSTED'");

    echo "
	<table id='tbl_logobar' class='w100'>

    <!--  LOGO BAR -->

    <tr><td class='logobar w50'>
        <a href='http://www.pgdpcanada.net/c/default.php'>
            <img src='$code_url/graphics/dpclogo.png'"
                ." width='336' height='68' alt='Distributed Proofreaders'>
        </a>
    </td>

    <td class='w50 center middle logobar'>
        <span class='logobar'>
        " . sprintf( _('%s titles preserved for the world!'), number_format(($numproj + $completed_books_offset))) ."
        </span>
    </td>
    </tr>

	</table>\n";

	// --------------------------------------------------------------------
	//    navbar
	// --------------------------------------------------------------------

	$divider        = " · ";

	// form has no action because all pages start with dpinit.php, which handles the form variables
	echo "
	<form id='frmlogin' name='frmlogin' method='post'>
	<table id='tblnav'>

    <tr>
    <td>&nbsp;</td>
    <td>\n";

    if (! $User->IsLoggedIn()) {
	    $link_to_forums_login = "<a href='http://www.pgdpcanada.net/forumdpc/ucp.php?mode=login'>Forums</a>";
	    $items = array( link_to_site("DPC"));
	    $items[] = $link_to_forums_login;
	    echo implode($divider, $items);
        echo "
       <td class='right'>ID:
            <input type='hidden' name='destination' id='destionation' value='".ThisFullUrl()."'>
            <input type='text' name='userNM' size='10' tabindex='1' maxlength='50'> &nbsp;" . _("Password:") . "
            <input type='password' name='userPW' size='10' tabindex='2'
            maxlength='50'> &nbsp;
            <input type='submit' value='" . _("Sign In") ."' style='color: black;'>\n";
	    echo link_to_registration();
        echo "&nbsp;
        </td>\n";
    }
    else {
	    $items = array( link_to_site("DPC"));
	    $items[] = link_to_activity_hub();
	    $items[] = link_to_search("Project Search");
	    $items[] = link_to_my_projects();
	    $items[] = link_to_preferences();

        $inbox_text = _("My Inbox");
	    $numofPMs = $User->InboxCount();
        if($numofPMs[0] > 0) {
            $inbox_text .= sprintf(_(" (%s unread)"), $numofPMs[0]);
		}
	    $items[] = link_to_inbox($inbox_text);
	    $items[] = link_to_forums();
	    $items[] = link_to_wiki();
	    $items[] = link_to_logout("Log out ({$User->Username()})");

	    echo implode($divider, $items);

        echo "</td>\n";

        echo "<td class='right'>\n";
        show_quick_links();
        echo "&nbsp;</td>";
    }


    echo "</tr>\n";
    echo "</table>   <!-- tblnav -->  \n";
	echo "</form>\n";
}

function login_form() {
    global $code_url;
    $password   = _("Password: ");
    $signin     = _("Sign In: ");
    return "
        <form name='frmlogin' action='$code_url/accounts/login.php' method='POST'>
            ID:
            <input type='text' name='userNM' size='10'>
            $password
            <input type='text'  name='userPW' size='10'>
            <input type='submit'  value='$signin' size='10'>\n"
        . link_to_registration()
        . link_to_help()
        . "</form>\n";
}

//function headerbar_text($text, $link = '', $with_divider = false) {
//    $out = "";
//
//    if ($with_divider) {
//        $out .= " · ";
//    }
//
//    if(!empty($link)) {
//        $out .= "<a href='$link'>$text</a>\n";
//    }
//    else {
//        $out .= $text;
//    }
//
//    return $out;
//}

function html_statsbar() {
    global $User;
    global $code_url;
    global $pagename;

    // Show statistics that are pertinent to the particular request.
    // (i.e., to the main content of the page).

    if(isset($pagename) && $pagename == "activityhub") {
        // show_backlogs();
    }
    //$round_id = Arg('round_id', Arg('tally_name'));
    $round_id = Arg('roundid', Arg('round_id', Arg('tally_name')));

    if($round_id == "") {
        show_completed_projects();
    }
    else {
        show_round_specific_stats( $round_id );
    }

    if($User->IsLoggedIn()) {
        echo "
        <div class='center sidebar'>
        <a href='$code_url/stats/stats_central.php'>
        ". _("More Statistics")."</a>
        </div>\n";


        echo "<hr class='w75 center'>\n";
        show_user_teams();

        echo "<hr class='w75 center'>\n";
        show_key_help_links();
	    echo "<hr class='w75 center'>\n";
    }
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

/*
function maybe_show_language_selector() {
    global $code_url, $intlang;
    $instl=installed_langs();
    $uninstl=uninstalled_langs();
    if(!(count($instl)>1||count($uninstl)>0))
        return;

    if (empty($userP) || (!(empty($userP)) && !($userP['u_intlang']))) {
        ?>
        <script language="JavaScript" type="text/javascript"><!--
            function submitLang() {
                top.document.getElementForId("langform").submit();
            }
        // --></script>
        <?

        echo "<form name=langform action='$code_url/tools/setlangcookie.php' method=POST>\n<input type=submit value='"._("Set language:")."'>\n<select name=lang onChange='submitLang(this)'>\n";
        foreach($instl as $v)
            echo "<option value='".$v."'".(($v==$intlang)?" selected":"").">".bilingual_name($v)."</option>\n";
        if(count($uninstl)>0) {
            echo "<option value='en_EN' disabled>-------------------</option>\n";
            echo "<option value='en_EN' disabled>(To be translated:)</option>\n";
            foreach($uninstl as $v)
                echo "<option value='"."en_EN"."' disabled>".bilingual_name($v)."</option>\n";
        }
        echo "</select><input type=hidden name=returnto value='".$_SERVER['REQUEST_URI']."'>\n</form>\n";
        echo "Languages below the line are planned to be added to the site, but currently are not; visit <a href='$code_url/faq/translate.php'>this page</a> if you can help us with translating the site into one of them.<hr>\n";
    }
}
*/

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

// This parallels the Activity Hub.
function show_quick_links() {
    global $User;

    if (! $User->IsLoggedIn())
        return;

    $items = array();

    if ($User->IsProjectManager() || $User->IsSiteManager()) {
        $items[] = link_to_project_manager("PM");
    }

//	foreach(array("P1", "P2", "P3", "F1", "F2", "PP", "PPV") as $phs) {
	foreach(array("P1", "P2", "P3", "F1", "F2") as $phs) {
        if($User->MayWorkInRound($phs)) {
	        $items[] = link_to_round($phs);
//	        $title = PhaseCaption($phs);
//	        $link = link_to_round($phs);
//	        echo headerbar_text("<span title='$title'>$text</span>", "$code_url/$rel_url", $divider);
//            $links[] = array(link_to_round($phs), $phs, $phs);
        }
    }
	if($User->MayWorkInRound("PP")) {
		$items[] = link_to_pp();
	}
	if($User->MayWorkInRound("PPV")) {
		$items[] = link_to_ppv();
	}
	$items[] = link_to_smooth_reading("SR");

	$divider        = "\n · ";
	echo implode($divider, $items);

//    if ( count($links) > 0 ) {
//        while ( list($i, list($rel_url, $text, $title)) = each($links) ) {
//            $divider = ($i > 0)
//                    ? true
//                    : false;
//
//                echo headerbar_text("<span title='$title'>$text</span>", "$code_url/$rel_url", $divider);
//            echo "\n";
//        }
//    }
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function show_round_specific_stats( $round_id ) {
    global $User;

    // Put the whole thing in a table, just so we can put a box around it.
    echo "
    <table id='tbl5' class='center w95 sidebar b111'>
    <tr>
    <td>\n";

    // Show the site statistics

    global $dpdb;
    echo "
        <h5 class='center'>" . _("Pages completed in $round_id") . "</h5>
        <h5 class='center'>" . _("Site Statistics:") . "</h5>
        <pre>\n";

    $todaygoal      = number_format(PhaseGoalToday($round_id));
    $yesterdaygoal  = number_format(PhaseGoalYesterday($round_id));
    $monthgoal      = number_format(PhaseGoalMonth($round_id));
    $todaycount     = number_format(PhaseCountToday($round_id));
    $yesterdaycount = number_format(PhaseCountYesterday($round_id));
    $monthcount     = number_format(PhaseCountMonth($round_id));
    $monthpct       = PhaseGoalMonth($round_id) == 0
                        ? 0
                        : PhaseCountMonth($round_id) / PhaseGoalMonth($round_id) * 100;
    $strnow         = strftime("%R");
    $strmonthgoal   = strftime(_("%B's Goal"));
    $strmonthcount  = strftime(_("%B's Total"));
    $strpct         = number_format($monthpct, 1);

    // Today's Numbers
    echo _("Today's Goal: $todaygoal\n")
       . _("Today's Total: $todaycount ($strnow)\n\n")

       . _("Yesterday's Goal: $yesterdaygoal\n")
       . _("Yesterday's Total: $yesterdaycount\n\n")

       . _("$strmonthgoal: $monthgoal\n")
       . _("$strmonthcount: $monthcount ({$strpct}%)\n")

       . "</pre>\n";

    /*
    // Number of users
    $num_users = $dpdb->SqlOneValue("
        SELECT COUNT(1) FROM users" );

    $num_pagers = $dpdb->SqlOneValue("
        SELECT COUNT(DISTINCT username) npagers FROM user_round_pages
        WHERE phase = '$round_id'");

    echo sprintf( _("<p>%s registered users</p>\n"), number_format($num_users) );
    echo sprintf( _("<p>%s users with one page or more</p>\n"), number_format($num_pagers));
    // ---------------------------------------------------------------------

    echo "
    <hr class='w75 center'>\n";
    */

    if ($User->IsLoggedIn()) {
        $rankname = honorific_for_count($round_id, $User->RoundPageCount($round_id));

        echo "<h5>" . _("Personal Statistics:") ."</h5>
            <span> "
                . _("Pages Today: "). number_format($User->RoundTodayCount($round_id))
                ."<br> &nbsp;"
                . _("Total Pages: "). number_format($User->RoundPageCount($round_id))
                ."<br> &nbsp;"
                . _("Current Position: "). number_format($User->RoundRank($round_id))
                ."<br> &nbsp;"
                . _("Current Rank:<br/>") . "$rankname<br>
            </span>
            <div class='center' style='margin-top: 1em;'>"
             .link_to_member_stats($User->Username(), $round_id, "Details")
                    . "</a>
            </div>\n";

        // -------------------------------------------------------------
        //Show the Neighbor table if the user has requested so
        if($User->NeighborRadius()) {
            $neighbors = $User->RoundNeighborhood($round_id, $User->NeighborRadius());
            echo "
            <br>
            <hr class='w75 center'>
            <br>
            <h5>Your Neighborhood:</h5>\n";

            $ntbl = new DpTable("tblneighbors", "center padded sidebar nocaption");
            $ntbl->AddColumn("^Rank", "rank");
            $ntbl->AddColumn("<Name", "username", "ename");
            $ntbl->AddColumn(">Pages", "page_count");
            $ntbl->SetRows($neighbors);
            $ntbl->EchoTable();
        }
    }

    echo "<br>";
    echo "</td></tr></table>";
}

function honorific_for_count($round_id, $count) {
    global $Honorifics;
    $dignity = "Novice";
    foreach($Honorifics[$round_id] as $key => $value) {
        $dignity =  $value;
        if($count < $key) {
            break;
        }
    }
    return $dignity;
}

function ename($name) {
    global $User;
    return $name == $User->Username()
        ? "<b>$name</b>"
        : $name;
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function show_user_teams()
{
    global $code_url;
    global $dpdb;
    global $User;

    $rows = $dpdb->SqlRows("
        SELECT teamname, id FROM user_teams
        WHERE id IN ({$User->Team1()}, {$User->Team2()}, {$User->Team3()})");

    echo "
    <div id='divteams' class='center sidebar'>
    <h5>" .  _("Your Teams:") . "</h5>\n";

    foreach($rows as $row) {
        echo "
    <a href='$code_url/stats/teams/tdetail.php?tid=".$row['id']."'>".$row['teamname']."</a><br/>\n";

    }
    echo "
    <br/>
    <a href='$code_url/stats/teams/teamlist.php'>"._("View all teams")."</a>
    </div>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX


function show_completed_projects() {
    global $dpdb;

    echo _("<h5>Completed Projects:</h5>\n");
    echo "<table id='tbl6' class='sidebar padded margined'>\n";
    $rows = $dpdb->SqlRows("
        SELECT COUNT(1) c, MONTH(d) m, MONTHNAME(d) mname, YEAR(d) y
        FROM (
            SELECT FROM_UNIXTIME(event_time) d
            FROM project_events pe
            WHERE event_type = 'post'
            AND event_time >
                UNIX_TIMESTAMP( DATE( DATE_FORMAT(
                    DATE_ADD( CURRENT_DATE(), INTERVAL -1 YEAR), '%Y-%m-01')))
        ) tbl
        GROUP BY m, y
        ORDER BY y, m");

    foreach($rows as $row) {
        $displaydate = "{$row['mname']} {$row['y']}";
        echo "
        <tr><td class='left'>&nbsp;$displaydate&nbsp;&nbsp;</td>
            <td class='right'>&nbsp;{$row['c']}&nbsp;</td>
        </tr>\n";
    }
    echo "</table>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function show_key_help_links() {
    global $wiki_url;

    echo "
        <div id='divhelp' style='padding: 0 1.5em' class='sidebar margined'>
        <h5>"._("Key Help Documents:")."</h5>
		<a href='{$wiki_url}/FAQ_Central'>"._("Frequently Asked Questions")."</a>
		<br>
		".link_to_proofing_guidelines()."
		<br>
		".link_to_formatting_guidelines()."
		</div>\n";
}

function show_backlogs() {
    echo "
          <iframe src='backlog.php' style='width: 400px; height: 300px;
            overflow: hidden; border; none;>
          </iframe>\n";
}


// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function timer_string() {
    global $starttime;
    $mtime = explode(" ", microtime());
    $endtime = $mtime[1] + $mtime[0];
    $totaltime = ($endtime - $starttime);
    return left($totaltime, 5);
}

function timer_array() {
    global $time_comment;
    return $time_comment;
}

function timer_milestone($remark) {
    global $time_comment;
    $time_comment[] = "$remark : " . timer_string();
}

function html_footer() {
    global $User;
    global $code_version;

    $strtime = timer_string();

    echo "<div id='divfooter' class='w100 em80 center white noserif redback'>"
        . _("Copyright Distributed Proofreaders Canada")
        . _(" (Page Build Time: {$strtime} ")
        . _(" Version $code_version")
        . "</div> <!-- divfooter -->\n";
    if($User->Username() == 'dkretz') {
        echo implode("<br>\n", timer_array());
    }

    echo "\n</body>\n";
    echo "</html>\n";
}
