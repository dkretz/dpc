<?PHP

ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once($relPath . 'dpinit.php');

$User->IsLoggedIn()
	or RedirectToLogin();

$pcheckout      = ArgArray("checkout");     // checkout to PP

// ----------------------------------
// handle transactions
// ----------------------------------

if(count($pcheckout) > 0) {
    foreach($pcheckout as $k => $v) {
        $p = new DpProject($k);
        $p->PPCheckout();
        break;
    }
}

// ----------------------------------
// counts    
// ----------------------------------

$username = $User->Username();

$row = $dpdb->SqlOneRow("
    SELECT  SUM(CASE WHEN LENGTH(postproofer) > 0 THEN 1 ELSE 0 END) navailable,
            SUM(CASE WHEN postproofer = '$username' THEN 1 ELSE 0 END) nmine,
            COUNT(1) total
    FROM projects
    WHERE phase = 'PP'");

$navailable     = $row["navailable"];
$nmine          = $row["nmine"];

// -----------------------------------------------------------------------------

 $no_stats = 1;
ob_start();

//$no_stats = 1;
theme("PP: Post Processing", "header");

// -----------------------------------------------------------------------------
?>

<div id='divpage' class='w100'>
<div id='pp_top' class='w100'>
<h1 class='center'>Post-Processing ("PP")</h1>

<p>Post-Processors take the proofed and formatted pages from the Rounds,
concatenate them together into a single text, and perform an extensive set of
checks and transformations to produce ebooks. They also make sure things are
handled consistently and correctly throughout the entire project.<p>

<p>On this page you'll find the list of projects available for you to select to
post-process. 
<a href='http://www.pgdpcanada.net/wiki/index.php/Post-Processing_FAQ'>Check
the wiki</a> to find out more.</p>

<hr>

<?php

//show_news_for_page("PP");

echo "

    <p class='ph2'>Projects you have checked out to PP</p>\n";
    if($nmine > 0) { 
        echo  "<p>You have $nmine projects checked out. These are now found on
        <a href='http://www.pgdpcanada.net/c/tools/proofers/my_projects.php'>My
        Projects</a> page.</p>";
        echo "<hr/>\n";
    }

?>
    <p class='ph2'>Projects Available to PP</p>
    <form name='ppform' method='POST' action=''>
<?php
    if($navailable == 0) {
        echo "<p>No projects found.</p>";
    }
    else {
        echo_available_projects();
    }
?>

    </form>   <!-- ppform -->
    </div>     <!-- div pp_top -->

</div>    <!-- divpage -->

<?php

theme("", "footer");
exit;

function echo_available_projects() {
    global $dpdb, $User;

//    timer_milestone("start echo available projects");
    $username = $User->Username();
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
            postproofer,
            LOWER(postproofer) ppsort,
            n_pages,
            username AS pm,
            LOWER(username) AS pmsort,
            CASE WHEN username = '$username' THEN 0 ELSE 1 END AS mine,
            DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(phase_change_date)) AS days_avail
        FROM projects p
        LEFT JOIN languages l1 ON p.language = l1.code
        LEFT JOIN languages l2 ON p.seclanguage = l2.code
        WHERE phase = 'PP'
            AND IFNULL(postproofer, '') = ''
        ORDER BY mine, days_avail");

//    timer_milestone("after query");

    $tbl = new DpTable();
    $tbl->AddColumn("<Title", "nameofwork", "etitle");
    $tbl->AddColumn("<Author", "authorsname", "eauthor");
    $tbl->AddColumn("<", "langname", "elanguage");
    $tbl->AddColumn("<Genre", "genre");
    $tbl->AddColumn("^Pages", "n_pages");
    $tbl->AddColumn("<Proj Mgr", "pm", "euser", "sortkey=pmsort");
    $tbl->AddColumn("<PPer", "postproofer", "euser", "sortkey=ppsort");
//    $tbl->AddColumn("^Upload", "projectid", "eupload");
    $tbl->AddColumn("^Days", "days_avail", "edays");
    if($User->MayWorkInRound("PP")) {
        $tbl->AddColumn("^Check Out Project", "projectid", "echeckout");
    }
    $tbl->SetRows($rows);

    $tbl->EchoTable();
//    timer_milestone("after table");
}

function etitle($title, $row) {
//    $title = maybe_convert($title);
    $projectid = $row['projectid'];
    return link_to_project($projectid, $title);
}

function elanguage($langname, $row) {
	return $langname
	       . ($row['seclangname'] == "" ? "" : "/" . $row['seclangname']);
}

function eauthor($author) {
//    return maybe_convert($author);
    return $author;
}

function euser($username) {
    return link_to_pm($username);
}

function esmooth($num) {
    return $num < 0 ? "" : edays($num);
}
function edays($num) {
    return number_format($num);
}

function echeckout($projectid) {
    return "<input name='checkout[$projectid]' type='submit' value='Check Out'>\n";
}

// vim: sw=4 ts=4 expandtab

