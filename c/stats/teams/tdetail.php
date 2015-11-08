<?PHP
$relPath="./../../pinc/";
include_once($relPath.'dpinit.php');

$roundid = Arg("roundid", Arg("tally_name"));
$tid     = Arg("tid");
$range   = Arg("range");

$team = new DpTeam($tid);

if(! $team->Exists()) {
    die("No existing team selected.");
}

$stats = _("Statistics");

theme($team->TeamName() . " " . $stats, "header");
echo "<br>
<div class='center'>";


showTeamProfile($team);

if ( $roundid != "") {
    showTeamStats($team, $roundid);
    showTeamMembers($team, $roundid);

    // Only show the team history if they are more than a day old
    if($team->CreatedDaysAgo() > 1 && (int) $roundid != 0) {
        showTeamHistoryChart($team, $roundid, $range);
    }
}

echo "</div>";
theme("", "footer");
exit;

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function showTeamStats($team, $roundid) {
    /** @var DpTeam $team */

    echo "<div>
        <h4 class='center'>"
        . _("Total $roundid Pages: {$team->RoundPageCount($roundid)} - $roundid Rank: {$team->RoundRank($roundid)}
        </h4></div>\n");
}

function showTeamMembers($team, $roundid) {
    global $dpdb;
    /** @var DpTeam $team */

    $sql = "
   SELECT username, page_count, joined
    FROM (
    SELECT u.username,
        SUM(urp.page_count) page_count,
        DATE_FORMAT(FROM_UNIXTIME(u.date_created), '%b %d %Y') joined
    FROM user_teams t
    JOIN users u ON u.team_1 = t.id
    JOIN user_round_pages urp ON u.username = urp.username
    WHERE t.id = {$team->Id()}
      AND urp.round_id = '$roundid'
    GROUP BY u.username

    UNION ALL

    SELECT u.username,
        SUM(urp.page_count) page_count,
        DATE_FORMAT(FROM_UNIXTIME(u.date_created), '%b %d %Y') joined
    FROM user_teams t
    JOIN users u  ON u.team_2 = t.id
    JOIN user_round_pages AS urp ON u.username = urp.username
    WHERE t.id = {$team->Id()}
      AND urp.round_id = '$roundid'
    GROUP BY u.username

    UNION ALL

    SELECT u.username,
        SUM(urp.page_count) page_count,
        DATE_FORMAT(FROM_UNIXTIME(u.date_created), '%b %d %Y') joined
    FROM user_teams t
    JOIN users AS u  ON u.team_3 = t.id
    JOIN user_round_pages AS urp ON u.username = urp.username
    WHERE t.id = {$team->Id()}
        AND urp.round_id = '$roundid'
    GROUP BY u.username
    ) a
     ORDER BY page_count DESC";

    $rows = $dpdb->SqlRows($sql);

    $tbl = new Dptable("tblteampages", "dptable sortable minitab w50", "Team Member Details");
    $tbl->AddColumn("<Username", "username");
    $tbl->AddColumn(">Pages", "page_count");
    $tbl->AddColumn(">Date joined", "joined");

    $tbl->SetRows($rows);
    $tbl->Echotable();
}

function showTeamHistoryChart($team, $roundid, $range = 30) {
    /** @var DpTeam $team */

    $choices = array();
    foreach ( array( 7, 14, 30, 60, 365 ) as $d ) {
        $text = sprintf( _('Last %d Days'), $d );
//			($d == 'all')
//			? _('Lifetime')
//			: sprintf( _('Last %d Days'), $d );
        $choices[] = "<a href='tdetail.php"
            ."?tid=".$team->Id()
            ."&roundid=$roundid"
            ."&range=$d'>$text</a>";
    }
    $choices_str = join( $choices, ' | ' );

    echo_team_count_chart($team->Id(), $roundid, $range);

    echo "<div>\n";
    echo  "<p>$choices_str</p>
	    </div>\n";
}

function echo_team_count_chart($teamid, $roundid, $range) {
    echo "<div id='divteamchart' class='dpchart'> </div>\n";
    $data = TeamPagesNDays($teamid, $roundid, $range);
    $title = _("Pages Saved-as-Done in Round $roundid");
//        $roundid == "ALL"
//        ? _("Pages Saved-as-Done in All Rounds")
//        : _("Pages Saved-as-Done in Round $roundid");
    makeColumnChart($data, $title, "divteamchart");
}

function makeColumnChart($data, $caption, $div) {

    echo "
<script type='text/javascript' src='https://www.google.com/jsapi'></script>
<script type='text/javascript'>
  google.load('visualization', '1', {packages:['corechart']});
  google.setOnLoadCallback(drawChart);
  function drawChart() {
    // var data = new google.visualization.DataTable();
    var data = new google.visualization.arrayToDataTable(
        {$data}
    );
    var options = {
        title: '$caption',
        width: 900,
        height: 450,
        vAxis: {baseline: 0},
        legend: {position: 'none'}
    };

    var chart = new google.visualization.ColumnChart(document.getElementById('$div'));
    chart.draw(data, options);
}
</script>\n";
}

function TeamPagesNDays($teamid, $roundid, $range) {
    global $dpdb;
    $sql = "
        SELECT  DATE_FORMAT(d.dateval, '%b %e') dateval,
                SUM(IFNULL(page_count, 0)) pages
        FROM days d
        LEFT JOIN team_round_pages trp
        ON d.min_unixtime = trp.count_time
        WHERE trp.round_id = '$roundid'
            AND team_id = $teamid
            AND d.dateval >=  DATE_SUB(CURRENT_DATE(), INTERVAL {$range} DAY)
        GROUP BY d.dateval
        ORDER BY d.dateval";
    $rows = $dpdb->SqlRows($sql);
    $ary = array(array("date", "pages"));
    foreach($rows as $row) {
        $ary[] = array($row["dateval"], (int) $row["pages"]);
    }

    return json_encode($ary);
}

function showTeamProfile($team) {
    /** @var DpTeam $team */
    global $code_url;

    $creator_url    = "$code_url/stats/members/member_stats.php?username=" . $team->CreatedBy();
    $creator_link   = link_to_url($creator_url, $team->CreatedBy());
    $topic_url      = "$code_url/stats/teams/team_topic.php?team={$team->Id()}";
    $topic_link     = link_to_url($topic_url, _("Team Discussion"));

    $links_to_stats = link_to_team_stats($team->Id(), "P1 ")
                        . link_to_team_stats($team->Id(), "P2")
                        . link_to_team_stats($team->Id(), "P3")
                        . link_to_team_stats($team->Id(), "F1")
                        . link_to_team_stats($team->Id(), "F2");

    $tbl = new DpTable("tblteam", "dptable center minitab w75");
    $tbl->NoColumnHeadings();
    $tbl->SetTitle($team->TeamName());
    $rows = array();
    $rows[] = array( _("Created"), $team->CreatedStr() . _(" ({$team->CreatedDaysAgo()} days ago)"));
    $rows[] = array( _("Created by"), $team->CreatedBy());
    $rows[] = array( _("Leader"), $creator_link);
    $rows[] = array( _("Description"), $team->Info());
    $rows[] = array(  _("Website"), "<a href='{$team->WebPage()}' target='_blank'></a>");
    $rows[] = array( _("Forums"), $topic_link);
    $rows[] = array( _("Members") . " <i>("._("Rank").")</i>",
        number_format($team->MemberCount()) . "&nbsp;<i>(#{$team->MemberRank()})</i>" );
    $rows[] = array( _("Current Members"), number_format($team->ActiveMembers()));
    $rows[] = array( _("Retired Members"), number_format($team->RetiredMembers()));

    $tbl->AddColumn("<", 0);
    $tbl->AddColumn("<", 1);
    $tbl->SetRows($rows);


    echo "
	    <div class='center lfloat w25'>
            <img src='{$team->AvatarUrl()}' alt=''>
        </div>
        <div class='center w75'>\n";

    $tbl->EchoTable();

    echo "</div>
        <div class='w100'>
        View team stats for $links_to_stats
        </div>\n";
}
