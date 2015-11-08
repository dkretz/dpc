<?PHP

ini_set('display_errors', 1);
error_reporting(E_ALL);

$relPath = "../../pinc/";
include_once $relPath . 'dpinit.php';

$roundid    = Arg("roundid");
$tname      = Arg("tname");
$tname      = ($tname == ""
                    ? "%"
                    : "%tname%");

if($roundid) {
    $where = "AND urp.phase = ?";
    $args = array(&$roundid);
}
else {
    $where = "";
}

$sql = "
        SELECT
            ut.id,
            ut.teamname,
            IFNULL(u1.ucount, 0)
            + IFNULL(u2.ucount, 0)
            + IFNULL(u3.ucount, 0) usercount,
        SUM(IFNULL(urp.page_count, 0)) pagecount

        FROM user_teams ut
        LEFT JOIN (SELECT team_1, COUNT(1) ucount FROM users GROUP BY team_1) u1 ON ut.id = u1.team_1
        LEFT JOIN (SELECT team_2, COUNT(1) ucount FROM users GROUP BY team_2) u2 ON ut.id = u2.team_2
        LEFT JOIN (SELECT team_3, COUNT(1) ucount FROM users GROUP BY team_3) u3 ON ut.id = u3.team_3

        LEFT JOIN users u ON u.team_1 = ut.id OR u.team_2 = ut.id OR u.team_3 = ut.id
        LEFT JOIN user_round_pages urp
            ON u.username = urp.username
            $where

        GROUP BY ut.id";

if($roundid) {
    $rows = $dpdb->SqlRowsPS($sql, $args);

}
else {
    $rows = $dpdb->SqlRows($sql);
}


$tbl = new DpTable("tblteams", "dptable sortable w75");
// $tbl->AddColumn("^ID", "id");
$tbl->AddColumn("<Team Name", "teamname", "eTeamname");
$tbl->AddColumn("^Members", "usercount");
$tbl->AddColumn(">Pages", "pagecount", "eCount");
$tbl->AddColumn("^", "id", "eJoin");
$tbl->SetRows($rows);

theme(_("Teams"), "header");
// echo_head("teams");

echo _("<h1 class='center'>Teams</h1>\n");
$tbl->EchoTableNumbered();

echo "<h4 class='center'> " . link_to_create_team() . "</h4>\n";

theme("", "footer");
exit;
//  html_end();

function eIcon($iconfile) {
    global $team_icons_url;
    return "<img src='{$team_icons_url}/{$iconfile}' alt=''>";
}

function eTeamname($val, $row) {
    $id = $row['id'];
    return "<a href='tdetail.php?tid={$id}'>$val</a>\n";
}

function eCount($count) {
    return number_format($count);
}

function eJoin($id) {
    global $User;
        return $User->IsTeamMemberOf($id)
            ? link_to_quit_team($id)
            : link_to_join_team($id);
//    if($User->Team1() ==  $id || $User->Team2() == $id || $User->Team3() == $id) {
//        $quit = _("Quit");
//        return "<a href='../teams/jointeam.php?tid={$id}'>$quit</a>\n";
//    }
//    else {
//        $join = _("Join");
//        return "<a href='../teams/jointeam.php?tid={$id}'>$join</a>\n";
//    }
}
            

