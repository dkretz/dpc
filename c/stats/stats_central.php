<?PHP
ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath = './../pinc/';
include_once $relPath . 'dpinit.php';
include_once $relPath . 'site_news.inc';
include_once $relPath . "month_chart.php";
include_once $relPath . "day_chart.php";

$js = array("https://www.google.com/jsapi", "/c/js/chart.js");

$title = _("Statistics Central");

theme($title, "header");

echo "<br><h2 class='center'>" . _("Statistics Central") . "</h2>";

show_news_for_page("STATS");

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
//Member/team stats searches and listings


echo "
<div id='div_stats' class='w90'>
      <div id='div_search' class='lfloat'>
      <form action='$code_url/stats/members/mbr_list.php' method='POST'>
          <input type='text' name='username' size='20'>&nbsp;
          <input type='submit' value='"._("Member Search")."'><br />
          <a href='$code_url/stats/members/mbr_list.php'>"._("Member List")."</a>
      </form>
      </div> <!-- div_search -->

      <div class='rfloat' id='div_teams'>
      <form action='$code_url/stats/teams/teamlist.php' method='POST'>
          <input type='text' name='tname' size='20'>&nbsp;
          <input type='submit' value='"._("Team Search")."'><br />
      <a href='$code_url/stats/teams/teamlist.php'>"._("Team List")."</a>
      </form>
      </div> <!-- div_teams -->
</div>  <!-- div_stats -->
<br>\n";


// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
//General site stats with links to view the queues


    $data = array();
    $totalusers = $dpdb->SqlOneValue("
        SELECT COUNT(1) FROM users
        WHERE t_last_activity >
            UNIX_TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY))");

    $data[] = array( "statistic" => _("Proofreaders active in the last 7 days:"), "value" => $totalusers, "link" => "");

  //get total books posted  in the last 7 days
    $totalbooks = $dpdb->SqlOneValue("
        SELECT COUNT(1) FROM projects
        WHERE phase_change_date >
            UNIX_TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY))
            AND phase = 'POSTED'");

    $data[] = array( "statistic" => _("Books posted in the last 30 days:"), "value" => $totalbooks, "link" => "");

    $stats = $dpdb->SqlOneRow("
        SELECT SUM(CASE WHEN p.phase = 'P1'
                   AND phq1.id IS NULL
               THEN 1 ELSE 0 END) AS p1_avail,
               SUM(CASE WHEN p.phase = 'P1'
                   AND phq1.id IS NOT NULL
                THEN 1 ELSE 0 END) AS p1_queued,
                SUM(CASE WHEN p.phase = 'P1'
                    AND phq1.id IS NOT NULL
                    AND p.language = 'English'
                THEN 1 ELSE 0 END) AS p1_english,
                SUM(CASE WHEN p.PHASE = 'PP'
                    AND IFNULL(postproofer, '') = ''
                THEN 1 ELSE 0 END) AS pp_available,
                SUM(CASE WHEN p.PHASE = 'PP'
                    AND LENGTH(postproofer) > 0
                THEN 1 ELSE 0 END) AS pp_checked_out,
                SUM(CASE WHEN p.PHASE = 'PPV'
                    AND IFNULL(ppverifier, '') = ''
                THEN 1 ELSE 0 END) AS ppv_available,
                SUM(CASE WHEN p.PHASE = 'PPV'
                    AND LENGTH(ppverifier) > 0
                THEN 1 ELSE 0 END) AS ppv_checked_out
         FROM projects p
         LEFT JOIN project_holds phq1
         ON p.projectid = phq1.projectid
            AND hold_code = 'queue'
            AND phq1.phase = 'P1'
         WHERE p.phase != 'DELETED'");

    $data[] = array( "statistic" => _("Non-English Books waiting to be released for first round:"),
                     "value" => $stats['p1_english'], "link" => "");
    $data[] = array( "statistic" => _("Books waiting for post processing:"), "value" => $stats['pp_available'], "link" => "");
    $data[] = array( "statistic" => _("Books being post processed:"), "value" => $stats['pp_checked_out'],
                    "link" => "<a href ='checkedout.php?phase=PP'>view_books</a>");
    $data[] = array( "statistic" => _("Books waiting to be verified:"), "value" => $stats['ppv_available'], "link" => "");
    $data[] = array( "statistic" => _("Books being verified:"), "value" => $stats['ppv_checked_out'],
                    "link" => "<a href ='checkedout.php?phase=PPV'>view_books</a>");
    $tbl = new DpTable("tbl01", "dptable minitab");
    $tbl->NoColumnHeadings();
    $tbl->AddColumn("<", "statistic");
    $tbl->AddColumn(">", "value");
    $tbl->AddColumn("<", "link");
    $tbl->SetRows($data);
    $tbl->SetTitle("General Site Statistics");

    echo "<div id='genstats' class='center w95' style='margin: auto'>\n";
    $tbl->EchoTable();
    echo "</div>  <!-- genstats -->";

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
// Miscellaneous Statistics

    $tbl = new DpTable("tbl02", "dptable minitab", _("Miscellaneous Statistics"));
    $tbl->NoColumnHeadings();
    $rows = array();
    $rows[] = array("col01" => "<a href='release_queue.php'>"
                               .  _("See All Waiting Queues"). "</a>",
                    "col02" => "<a href='requested_books.php'>"
                                . _("Most Requested Books") . "</a>");

    $rows[] = array(
        "col01" => "<a href='pp_stats.php'>" . _("Post-Processing Stats") . "</a>",
        "col02" => "<a href='ppv_stats.php'>" . _("Post-Processing Verification Stats") . "</a>");

    $tbl->AddColumn("<", "col01");
    $tbl->AddColumn("<", "col02");
    $tbl->SetRows($rows);
    echo "<div id='miscstats' class='center w95' style='margin: auto'>\n";
    $tbl->EchoTable();
echo "</div> <!-- miscstats -->";

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
// Pages in Rounds

    $tbl = new DpTable("tbl03", "dptable minitab", _("Pages in Rounds"));
    $tbl->NoColumnHeadings();

    $rows = array();
    $rows[] = array("col01" => _("P1"),
                    "col02" => link_to_round_charts("P1",_("Pages Proofread Charts")),
                    "col03" => link_to_misc_stats("P1", _("Monthly Page Counts.")),
                    "col04" => link_to_round_stats("P1", _("Top Proofreaders")));
    $rows[] = array("col01" => _("P2"),
                    "col02" => link_to_round_charts("P2",_("Pages Proofread Charts")),
                    "col03" => link_to_misc_stats("P2", _("Monthly Page Counts.")),
                    "col04" => link_to_round_stats("P2", _("Top Proofreaders")));
    $rows[] = array("col01" => _("P3"),
                    "col02" => link_to_round_charts("P3",_("Pages Proofread Charts")),
                    "col03" => link_to_misc_stats("P3", _("Monthly Page Counts.")),
                    "col04" => link_to_round_stats("P3", _("Top Proofreaders")));
    $rows[] = array("col01" => _("F1"),
                    "col02" => link_to_round_charts("F1",_("Pages Proofread Charts")),
                    "col03" => link_to_misc_stats("f1", _("Monthly Page Counts.")),
                    "col04" => link_to_round_stats("F1", _("Top Proofreaders")));
    $rows[] = array("col01" => _("F2"),
                    "col02" => link_to_round_charts("F2",_("Pages Proofread Charts")),
                    "col03" => link_to_misc_stats("F2", _("Monthly Page Counts.")),
                    "col04" => link_to_round_stats("F2", _("Top Proofreaders")));
    $rows[] = array("col01" => _("All"),
                    "col02" => link_to_round_charts("ALL",_("Pages Proofread Charts")),
                    "col03" => link_to_misc_stats("ALL", _("Monthly Page Counts.")),
                    "col04" => link_to_round_stats("ALL", _("Top Proofreaders")));

    $tbl->AddColumn("^", "col01");
    $tbl->AddColumn("^", "col02");
    $tbl->AddColumn("^", "col03");
    $tbl->AddColumn("^", "col04");
    $tbl->SetRows($rows);

    echo "<div id='roundstats' class='center w95' style='margin: auto'>\n";
    $tbl->EchoTable();
    echo "</div>  <!-- roundstats -->\n";

    echo "<div id='roundcounts' class='dpchart center w95' style='margin: auto'>";
    echo "<h3>"._("Page Counts in Rounds") . "</h3>\n";

    echo PhaseMonthsChart("P1");
    echo PhaseMonthsChart("P2");
    echo PhaseMonthsChart("P3");
    echo PhaseMonthsChart("F1");
    echo PhaseMonthsChart("F2");
    echo PhaseMonthsChart("All");

    echo PhaseDaysChart("All");

    echo "</div> <!-- roundcounts -->\n";


theme('','footer');

// vim: sw=4 ts=4 expandtab
?>
