<?PHP
ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once($relPath.'dpinit.php');

$release = ArgArray("release");

if(count($release) > 0) {
    foreach($release as $key => $value) {
        $project = new DpProject($key);
        $project->ClearQueueHold("P1");
    }
}

$projectids = $dpdb->SqlValues("
    SELECT projectid FROM projects
    WHERE state = 'P1.proj_unavail'");

foreach($projectids as $projectid) {
    $proj = new DpProject($projectid);
    $proj->RecalcPageCounts();
}

// -----------------------------------------------------------------------------

theme("P1: Project Release", "header");
?>

<h1 class='center'>Round P1 Projects Not Currently Available</h1>

<h4 class='center'>What happens in this stage:</h4>

<p>Projects have been released from the Preparation stage and are now
considered suitable for proofing (having passed the QC check.) They are in the
first round state (P1) but are unavailable until DAvid releases them using this
form. To use this form, a user needs to be assigned the Queuer role. (not
enforced yet.)</p>

<p>The old release mechanism is being used for now until we know that DAvid has
the right information available and the necessary conditions are set to really
release these projects. Then, we'll convert this to a "P1 Queue Hold" on the
project (rather than a project state change) and the button will release the
Hold. The behavior appears the same either way.</p>


<h2>Projects Unavailable in P1</h2>

<?php
echo_p1_unavailable_projects();

theme("", "footer");
exit;

function echo_p1_unavailable_projects() {
    global $dpdb, $User;

    $rows = $dpdb->SqlRows("
        SELECT
            p.projectid,
            p.nameofwork,
            p.authorsname,
            p.genre,
            p.n_pages,
            p.username AS pm,
            LOWER(p.username) AS pmsort,
            (   SELECT COUNT(*) FROM project_holds
                WHERE projectid = p.projectid
            ) AS holdcount,
            DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(phase_change_date)) 
                AS days_avail
        FROM projects p
        WHERE p.state = 'P1.proj_unavail'");

    foreach($rows as $row) {
        $projectid = $row['projectid'];
        $p = new DpProject($projectid);
        $p->RecalcPageCounts();
    }

    $tbl = new DpTable();
    $tbl->AddColumn("<Title", "nameofwork", "etitle");
    $tbl->AddColumn("<Author", "authorsname");
    $tbl->AddColumn("^Pages", "n_pages", "enpages");
    $tbl->AddColumn("^Proj Mgr", "pm", "epm", "sortkey=pmsort");
    $tbl->AddColumn("^Days", "days_avail");
    if($User->MayReleaseHold("queue")) {
        $tbl->AddColumn("^Release", "projectid", "erelease");
    }
    $tbl->SetRows($rows);

    echo "<form id='frmprop' target='' method='POST' name='frmprop'>\n";
    $tbl->EchoTable();
    echo "</form>\n";
}


function etitle($title, $row) {
    $projectid = $row['projectid'];
    return link_to_project($projectid, $title);
}

function epm($pm) {
    return $pm == ""
        ? "<span class='red'>--</span>\n"
        : link_to_pm($pm);
}

function enpages($npages) {
    return $npages > 0
        ? $npages
        : "<span class='red'>0</span>\n";

}

function erelease($projectid) {
    return "<input name='release[$projectid]' type='submit' value='Release'>\n";
}

// vim: sw=4 ts=4 expandtab

