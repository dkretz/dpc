<?PHP
$relPath='../pinc/';
include_once($relPath.'dpinit.php');
include_once($relPath.'dpsql.inc');
include_once($relPath.'stages.inc');
include_once($relPath.'theme.inc');

($User->MayManageRoles() || $User->IsSiteManager())
    or die("permission denied");

$title = _('Pending Requests for Access');

theme($title,'header');

echo "<h1>$title</h1>\n";

foreach ( $Stage_for_id_ as $stage )
{
    if ( $stage->after_satisfying_minima == 'REQ-HUMAN' )
    {
        $activity_ids[] = $stage->id;
    }
}

$activity_ids[] = 'P2_mentor';

// Look for unexpected activity_ids
$requests = $dpdb->SqlValues("
    SELECT DISTINCT REPLACE(setting,'.access', '')
    FROM usersettings
    WHERE setting LIKE '%.access' AND value='requested'");

foreach($requests as $activity_id) {
    if ( !in_array( $activity_id, $activity_ids ) ) {
        $activity_ids[] = $activity_id;
    }
}

// ----------------------------------

$dpdb->SqlExecute("
    CREATE TEMPORARY TABLE access_log_summary
    SELECT 
        activity,
        subject_username,
        MAX( timestamp * (action='request'         ) ) AS t_latest_request,
        MAX( timestamp * (action='deny_request_for') ) AS t_latest_deny
    FROM access_log
    GROUP BY activity, subject_username");

foreach ( $activity_ids as $activity_id ) {
    echo "<h3>";
    echo sprintf( _('Users requesting access to %s'), $activity_id );
    echo "</h3>\n";

    $access_name = "$activity_id.access";

    $rows = $dpdb->SqlRows("
        SELECT  usersettings.username,
                users.u_id,
                access_log_summary.t_latest_request,
                access_log_summary.t_latest_deny
        FROM usersettings
            LEFT JOIN users USING (username)
            LEFT JOIN access_log_summary
            ON access_log_summary.subject_username = usersettings.username
                AND access_log_summary.activity = '$activity_id'
        WHERE setting = '$access_name'
            AND value='requested'
        ORDER BY username");

    if ( count($rows) == 0)  {
        $word = _('none');
        echo "($word)";
    }
    else {
        $review_round = get_Round_for_round_id($activity_id);
        if ( $review_round && $review_round->after_satisfying_minima == 'REQ-HUMAN' )
        {
            $can_review_work = TRUE;
            // These users are all requesting access to round Y.  For each, we will
            // provide a link to allow the requestor to review their round X work,
            // by considering each page they worked on in X, and comparing
            // their X result to the subsequent Y result (if it exists yet).
            //
            // (We assume that X is the round immediately preceding Y.)
            $work_round = get_Round_for_round_number($review_round->round_number-1);

            $round_params = "work_round_id={$work_round->id}&amp;review_round_id={$review_round->id}";
        }
        else
        {
            $can_review_work = FALSE;
        }

        echo "<table border='1'>\n";

        {
            echo "<tr>";
            echo "<th>username (link to member stats)</th>";
            if ( $can_review_work )
            {
                echo "<th>link to review work</th>";
            }
            echo "<th>this request</th>";
            echo "<th>prev denial</th>";
            echo "</tr>";
            echo "\n";
        }

        foreach($rows as $row) {
            $username = $row['username'];
            $u_id = $row['u_id'];
            $t_latest_request = $row['t_latest_request'];
            $t_latest_deny = $row['t_latest_deny'];
            $member_stats_url = "$code_url/stats/members/member_stats.php?id=$u_id";
            $t_latest_request_f = strftime('%Y-%m-%d&nbsp;%T', $t_latest_request);
            $t_latest_deny_f = (
                $t_latest_deny == 0
                ? ''
                : strftime('%Y-%m-%d&nbsp;%T', $t_latest_deny)
            );

            echo "<tr>";
            echo   "<td align='center'>";
            echo     "<a href='$member_stats_url'>$username</a>";
            echo   "</td>";
            if ( $can_review_work )
            {
                $review_work_url = "$code_url/tools/proofers/review_work.php?username=$username&amp;$round_params";
                echo   "<td align='center'>";
                echo     "<a href='$review_work_url'>rw</a>";
                echo   "</td>";
            }
            echo   "<td align='center'>";
            echo     $t_latest_request_f;
            echo   "</td>";
            echo   "<td align='center'>";
            echo     $t_latest_deny_f;
            echo   "</td>";
            echo "</tr>";
            echo "\n";
        }
        echo "</table>\n";
    }
}

echo '<br>';

theme('','footer');

// vim: sw=4 ts=4 expandtab
?>
