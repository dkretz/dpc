<?PHP
ini_set("display_errors", true);
error_reporting(E_ALL);
// Give information on smooth reading
// including (most importantly) the list of projects currently available

$relPath = '../../pinc/';
include_once($relPath.'dpinit.php');
include_once($relPath.'site_news.inc');
include_once($relPath.'page_header.inc');

// ---------------------------------------
//Page construction varies with whether the user is logged in or out

$no_stats = 1;
$header_text = $User->IsLoggedIn() 
        ? _("Smooth Reading Projects")
        : _("Smooth Reading Projects Preview");
theme( $header_text, 'header');
dp_page_header( 'SR', $header_text );
show_news_for_page( "SR" );

if ($User->IsLoggedIn()) {
    echo "
    <p> The goal of 'Smooth Reading' is to read the text attentively, as for
        pleasure, with just a little more attention than usual to punctuation,
        etc.  This is NOT full scale proof-reading, and comparison with the
        scans is not needed.  Just read it as your normal,
        sensitized-to-proofing-errors self, and report any problem that
        disrupts the sense or the flow of the book.  Note that some of these
        will be due to the author and/or publisher. </p>

    <p> There are two primary ways to provide feedback.</p>
     <h4>1. Make notes in the text as you read.</h4>
	<h4>2. Take notes in a separate file.</h4>
     <p>If you read using software that allows you to make notes in the text, you can do so.
     Then, when you are done, you should be able to save the text, including your comments, to a file;
     and you should be able to upload the saved file using your browser. You can do so by returning to
     this page and clicking the link in the table below, in the column under 'Upload with notes'
     (third from the right end of the line).<p>
     <p>The way to report errors is by adding a comment of the form
        <blockquote class='red'>
        [**correction or query]<br>
        </blockquote>
    immediately after the problem spot.</p>
    <p>Do not correct or change the problem, just note it in the above format. </p>

    <p>
    Examples:
    <ul>
    <li>that was the end,[**.] However, the next day</li>
    <li>that was the end[**.] However, the next day</li>
    <li>that was the emd.[**end] However, the next day</li>
    </ul>
    </p>
    <p>Just record questions and remarks, and send them to the Project Manager any way convenient to you.
	 You can send a PM by clicking the Project Manager's username anywhere you find it./p>\n";
}
else {
    echo "
    <p>This Preview page shows which books are currently available for Smooth Reading.</p>

    <p>To be able to upload corrections, join DP.  There is a register link near
    the upper right corner of this page.</p>\n";
}


$sql = "
        SELECT p.projectid,
               p.nameofwork,
               p.authorsname,
               p.language,
               p.seclanguage,
               l1.name langname,
               l2.name seclangname,
               p.difficulty,
               p.genre,
               p.n_pages,
               p.postproofer,
               LOWER(p.postproofer) ppsort,
               DATEDIFF(DATE(FROM_UNIXTIME(p.smoothread_deadline)), 
                    CURRENT_DATE()) days_left,
               p.username as PM,
               LOWER(p.username) as pmsort
        FROM projects p
        LEFT JOIN languages l1 ON p.language = l1.code
        LEFT JOIN languages l2 ON p.seclanguage = l2.code
        WHERE DATE(FROM_UNIXTIME(smoothread_deadline)) >= CURRENT_DATE()
        ORDER BY p.smoothread_deadline";

$rows = $dpdb->SqlRows($sql);

$tbl = new DpTable();
$tbl->AddColumn("<Title", "nameofwork", "etitle");
$tbl->AddColumn("<Author", "authorsname");
$tbl->AddColumn("<Language", "langname", "elanguage");
$tbl->AddColumn("<Genre", "genre");
if($User->IsLoggedIn()) {
    $tbl->AddColumn("<PM", "PM", "epm", "sortkey=pmsort");
    $tbl->AddColumn("<PPer", "postproofer", "epm", "sortkey=ppsort");
    $tbl->AddColumn("<Download<br>to read", "projectid", "edownload", "sorttable_nosort");
    $tbl->AddColumn("^Upload<br>with notes", "projectid", "eupload", "sorttable_nosort");
}
$tbl->AddColumn(">Pages", "n_pages");
$tbl->AddColumn("^Days<br/>left", "days_left", "edaysleft");
$tbl->SetRows($rows);
$tbl->EchoTable();

theme('', 'footer');
exit;

function elanguage($langname, $row) {
	return $langname
	       . ($row['seclangname'] == "" ? "" : "/" . $row['seclangname']);
}

function eupload($projectid) {
	return link_to_upload_smoothed_text($projectid, "upload");
//    global $projects_dir;
//    $nup = count(glob("$projects_dir/$projectid/*smooth*"));
//    $nup -= 1;
//    $url = url_for_upload_smoothed_text($projectid);
//    return "<a href='$url'>upload</a>\n";
}
function edownload($projectid) {
    return link_to_smooth_download($projectid, "download");
}

function edaysleft($val) {
    return $val == 0
        ? "ends today"
        : $val;
}

function epm($str) {
    return link_to_pm($str);
}

function etitle($title, $row) {
    $projectid = $row["projectid"];
    return link_to_project($projectid, $title);
}

// vim: sw=4 ts=4 expandtab
?>

