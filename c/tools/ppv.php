<?PHP

/*
    Lists:
    echo_my_projects();
    echo_available_projects();
    echo_checked_out_projects();
    echo_completed_projects();
*/

ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once($relPath.'dpinit.php');

$User->IsLoggedIn()
	or RedirectToLogin();

$pretpool   = ArgArray("return_to_pool");
$pretpp     = ArgArray("return_to_pper");
$pcomplete  = ArgArray("complete");
$checkout   = ArgArray("checkout");
$ppost1     = isArg("post1");
$ppost2     = isArg("post2");
$pnum1      = ArgArray("pnum1");
$pnum2      = ArgArray("pnum2");

$username   = $User->Username();

// ----------------------------------
// handle transactions
// ----------------------------------

if($ppost1) {
    foreach($pnum1 as $k => $v) {
        if($v != "") {
            do_set_postednum($k, $v);
        }
    }
}
if($ppost2) {
    foreach($pnum2 as $k => $v) {
        if($v != "") {
            do_set_postednum($k, $v);
        }
    }
}
if(count($pcomplete) > 0) {
    foreach($pcomplete as $k => $v) {
        do_complete_ppv($k);
        break;
    }
}
if(count($pretpool) > 0) {
    foreach($pretpool as $k => $v) {
        do_return_project_to_pool($k);
        break;
    }
}
if(count($pretpp) > 0) {
    foreach($pretpp as $k => $v) {
        do_return_project_to_pper($k);
        break;
    }
}
if(count($checkout) > 0) {
    foreach($checkout as $k => $v) {
        do_checkout_project($k);
        break;
    }
}

// ----------------------------------
// counts    
// ----------------------------------

$navailable = $dpdb->SqlOneValue("
    SELECT COUNT(1) FROM projects
    WHERE phase = 'PPV'
        AND IFNULL(ppverifier, '') = ''");
$ncheckedout = $dpdb->SqlOneValue("
    SELECT COUNT(1) FROM projects
    WHERE phase = 'PPV'
        AND LENGTH(ppverifier) > 0"); 
$ncomplete =  $dpdb->SqlOneValue("
    SELECT COUNT(1) FROM projects
    WHERE phase = 'PPV'
        AND postednum != ''");
$nmine =  $dpdb->SqlOneValue("
    SELECT COUNT(1) FROM projects
    WHERE phase = 'PPV'
        AND ppverifier = '$username'");

$ntotal = $navailable + $ncheckedout + $ncomplete;

// -----------------------------------------------------------------------------

// $args = array("body_onload" => "eSortInit()");
// theme("PPV: Post Processing Verification", "header", $args);
$no_stats = 1;

theme("PPV: Post Processing Verification", "header");

// -----------------------------------------------------------------------------
?>

<h1 class='center'>Post-Processing Verification ("PPV")</h1>

    <p>In this task, experienced volunteers verify texts that have already been
        Post-Processed, and mentor new Post-Processors.  Before working in this task,
        make sure you read the <b>new</b>
        <a href='http://www.pgdpcanada.net/wiki/index.php/Post-Processing_Verification_Guidelines'>Post-Processing Verification Guidelines</a>
        and use the
        <a href='http://www.pgdpcanada.net/c/faq/ppv_report.txt'>PPV Report Card</a>
        for each project you PPV. As always, the
        <a href='http://www.pgdpcanada.net/forum/viewforum.php?f=13'>Post-Processing Forum</a>
        is available for any of your questions.</p>

    <hr/>

<?php
// -----------------------------------------------------------------------------

echo "
    <ul>
    <li>Projects in PPV: Available, $navailable. Checked out, $ncheckedout.
    Complete, $ncomplete. Total, $ntotal.  Yours, $nmine. </li>
    <li>Your PPV permission status: you "
        . ($User->MayWorkInRound("PPV") ? "may" : "may not")
        . " work in PPV.<br/>
    (You may have other roles providing access.)</li>
    </ul>\n";

show_news_for_page("PPV");

// -----------------------------------------------------------------------------

echo "
<form name='ppvform' method='POST' action=''>

<p class='ph2'>Projects you have checked out to PPV</p>\n";
if($nmine == 0) {
    echo "<p>You have no projects checked out.</p>\n";
}
else {
    echo_my_projects();
}
echo "<hr/>\n";

echo "<p class='ph2'>Projects Available to PPV</p>\n";

if($navailable == 0) {
    echo "<p>No projects found.</p>\n";
}else {
    echo_available_projects();
}
echo "
<hr/>
<p class='ph2'>Projects Being Verified</p>\n";

if($ncheckedout == 0) {
    echo "<p>No projects found.</p>\n";
}
else {
    echo_checked_out_projects();
}
echo "<hr/>\n";


/*
echo "<p class='ph2'>Completed Projects Ready to Post</p>\n";

if($ncomplete == 0) {
    echo "<p>No projects found.</p>\n";
}
else {
    echo_completed_projects();
}
*/

echo "
</form>\n";

theme("", "footer");
exit;

function echo_checked_out_projects() {
    global $dpdb;

    $rows = $dpdb->SqlRows("
        SELECT
            projectid,
            nameofwork,
            authorsname,
            p.language,
            p.seclanguage,
            l1.name langname,
            l2.name seclangname,
            genre,
            n_pages,
            username AS pm,
            LOWER(username) AS pmsort,
            postproofer,
            LOWER(postproofer) AS ppsort,
            ppverifier,
            LOWER(ppverifier) AS ppvsort,
            DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(phase_change_date)) AS days_avail
        FROM projects p
        LEFT JOIN languages l1 ON p.language = l1.code
        LEFT JOIN languages l2 ON p.seclanguage = l2.code
        WHERE phase = 'PPV'
            AND LENGTH(ppverifier) > 0
        ORDER BY days_avail");

    $tbl = new DpTable();
    $tbl->AddColumn("<Title", "nameofwork", "etitle");
    $tbl->AddColumn("<Author", "authorsname");
    $tbl->AddColumn("<Language", "langname", "elangname");
    $tbl->AddColumn("<Genre", "genre");
    $tbl->AddColumn("^Pages", "n_pages");
    $tbl->AddColumn("^Proj Mgr", "pm", "epm", "sortkey=pmsort");
    $tbl->AddColumn("^PP", "postproofer", "epp", "sortkey=ppsort");
    $tbl->AddColumn("^PPV", "ppverifier", "eppv", "sortkey=ppvsort");
    $tbl->SetRows($rows);

    $tbl->EchoTable();
}

/*
function my_PPV_project_rows() {
    static $_rows;
    global $User, $dpdb;
    $username = $User->Username();

    if(! $_rows) {
        $_rows = $dpdb->SqlRows("
            SELECT
                projectid,
                nameofwork,
                authorsname,
                language,
                genre,
                n_pages,
                username AS pm,
                postproofer,
                ppverifier,
                DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(phase_change_date)) AS days_avail
            FROM projects
            WHERE phase = 'PPV'
                AND ppverifier = '$username'
            ORDER BY days_avail");
    }
    return $_rows;
}
*/

function elangname($langname, $row) {
	return $langname
	       . ($row['seclangname'] == "" ? "" : "/" . $row['seclangname']);
}

function echo_my_projects() {
    global $dpdb, $User;

    $username = $User->Username();
    $dpdb->SetEcho();
    $rows = $dpdb->SqlRows("
        SELECT
            projectid,
            nameofwork,
            authorsname,
            p.language,
            p.seclanguage,
            l1.name langname,
            l2.name seclangname,
            genre,
            n_pages,
            username AS pm,
            LOWER(username) AS pmsort,
            postproofer,
            ppverifier,
            IFNULL(postednum, '') postednum,
            DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(phase_change_date)) AS days_avail
        FROM projects p
        LEFT JOIN languages l1 ON p.language = l1.code
        LEFT JOIN languages l2 ON p.seclanguage = l2.code
        WHERE phase = 'PPV'
            AND ppverifier = '$username'
        ORDER BY days_avail");


    $tbl = new DpTable();
    $tbl->AddColumn("<Title", "nameofwork", "etitle");
    $tbl->AddColumn("<Author", "authorsname");
    $tbl->AddColumn("<Language", "langname", "elangname");
    $tbl->AddColumn("<Genre", "genre");
    $tbl->AddColumn("^Pages", "n_pages");
    $tbl->AddColumn("^Proj Mgr", "pm", "epm", "sortkey=pmsort");
    $tbl->AddColumn("^PP", "postproofer", "epp");
    // $tbl->AddColumn("^PPV", "ppverifier", "eppv");
    $tbl->AddColumn("^Days", "days_avail");
    $tbl->AddColumn("^Return<br/>to Pool", "projectid", "ereturn");
    $tbl->AddColumn("^Posted #", "postednum", "epostednum1");
    $tbl->AddColumn("^Post", "projectid", "epost1");
    // $tbl->AddColumn("^Complete!", "projectid", "ecomplete");
    $tbl->SetRows($rows);

    $tbl->EchoTable();
}

/*
function echo_completed_projects() {
    global $dpdb;

    $rows = $dpdb->SqlRows("
        SELECT
            projectid,
            nameofwork,
            authorsname,
            language,
            n_pages,
            IFNULL(postednum, '') postednum,
            username AS pm,
            postproofer,
            IFNULL(ppverifier, '') ppverifier,
            DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(phase_change_date)) AS days_avail
        FROM projects
        WHERE phase = 'PPV'
            AND NOT LENGTH(postednum) > 0
        ORDER BY days_avail");

    $tbl2 = new DpTable();
    $tbl2->AddColumn("<Title", "nameofwork", "etitle");
    $tbl2->AddColumn("<Author", "authorsname");
    $tbl2->AddColumn("<Language", "language");
    $tbl2->AddColumn("^Pages", "n_pages");
    $tbl2->AddColumn("^Proj Mgr", "pm", "epm");
    $tbl2->AddColumn("^PP", "postproofer", "epp");
    $tbl2->AddColumn("^PPV", "ppverifier", "eppv");
    $tbl2->AddColumn("^Days", "days_avail");
    $tbl2->AddColumn("^Posted #", "postednum", "epostednum2");
    $tbl2->AddColumn("^Post", "projectid", "epost2");
    $tbl2->SetRows($rows);

    $tbl2->EchoTable();
}
*/

function echo_available_projects() {
    global $dpdb, $User;


    $rows = $dpdb->SqlRows("
        SELECT
            projectid,
            nameofwork,
            authorsname,
            language,
            seclanguage,
            l1.name langname,
            l2.name seclangname,
            genre,
            n_pages,
            username AS pm,
            LOWER(username) AS pmsort,
            postproofer,
            LOWER(postproofer) AS ppsort,
            DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(phase_change_date)) AS days_avail
        FROM projects p
        LEFT JOIN languages l1 ON p.language = l1.code
        LEFT JOIN languages l2 ON p.seclanguage = l2.code
        WHERE phase = 'PPV'
            AND IFNULL(ppverifier, '') = ''");

    $tbl = new DpTable();
    $tbl->AddColumn("<Title", "nameofwork", "etitle");
    $tbl->AddColumn("<Author", "authorsname", "eauthor");
    $tbl->AddColumn("<Language", "langname", "elangname");
    $tbl->AddColumn("<Genre", "genre");
    $tbl->AddColumn("^Pages", "n_pages");
    $tbl->AddColumn("^Proj Mgr", "pm", "epm", "sortkey=pmsort");
    $tbl->AddColumn("^PP", "postproofer", "epp", "sortkey=ppsort");
    $tbl->AddColumn("^Days", "days_avail", "eint");
    if($User->MayWorkInRound("PPV")) {
        $tbl->AddColumn("^Check Out", "projectid", "echeckout");
    }
    $tbl->SetRows($rows);

    $tbl->EchoTable();
}

function eint($val) {
    return $val ? $val : 0;
}

function etitle($title, $row) {
    // $title = maybe_convert($title);
    $projectid = $row['projectid'];
    return link_to_project($projectid, $title);
}

function eauthor($author) {
    // return maybe_convert($author);
    return $author;
}

function epm($pm) {
    return link_to_pm($pm);
}

function epp($pp) {
   return link_to_pm($pp);
}

function eppv($ppv) {
    return link_to_pm($ppv);
}

function ereturn($projectid) {
    return "<input name='return_to_pool[$projectid]' type='submit' value='Return to Pool'>
            <br/>
            <br/>
            <input name='return_to_pper[$projectid]' type='submit' value='Return to PPer'>\n";
}

function ecomplete($projectid) {
    return "<input name='complete[$projectid]' type='submit' value='PPV Complete'>\n";
}

function echeckout($projectid) {
    return "<input name='checkout[$projectid]' type='submit' class='em80' value='Check Out'>\n";
}

function epostednum2($postednum, $row) {
    if($postednum == null || $postednum == "0")
        $postednum = "";
    $projectid = $row['projectid'];
    return "<input type='text' name='pnum2[$projectid]'
            class='em80' value='$postednum' size='9'/>\n";
}
function epostednum1($postednum, $row) {
    if($postednum == null || $postednum == "0")
        $postednum = "";
    $projectid = $row['projectid'];
    return "<input type='text' name='pnum1[$projectid]'
            class='em80' value='$postednum' size='9'/>\n";
}

function epost1() {
    return "<input name='post1' type='submit' class='em80' value='Post'>\n";
}

//function epost2($projectid) {
//    return "<input name='post2' type='submit' class='em80' value='Post'>\n";
//}

function do_return_project_to_pper($projectid) {
    $p = new DpProject($projectid);
    if($p->Phase() == 'PPV') {
        $p->RevertPhase();
    }
}

function do_return_project_to_pool($projectid) {
    global $User;
    $username = $User->Username();
    if(! $User->MayWorkInRound("PPV")) {
        assert(false);
        return;
    }
    $p = new DpProject($projectid);
    // already set
    if($p->PPVer() != $username) {
        return;
    }
    $p->ClearPPVer();
}

function do_complete_ppv($projectid) {
    $p = new DpProject($projectid);
    $p->PPVSetComplete();
}

function do_checkout_project($projectid) {
    $p = new DpProject($projectid);
    $p->PPVCheckout();
}

/*
function do_post_project($projectid, $postednum) {
    global $User;
    if(! $User->IsSiteManager()) {
        assert(false);
        die("User must be Site Manager.");
    }
    $p = new DpProject($projectid);
    $p->SetPosted($postednum);
}
*/

function do_set_postednum($projectid, $postednum) {
    /** @var DpProject $proj */
    $proj = new DpProject($projectid);
    $proj->SetPostedNumber($postednum);
}


// vim: sw=4 ts=4 expandtab
