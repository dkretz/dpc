<?PHP
ini_set("display_errors", 1);
error_reporting(E_ALL);

require "/home/pgdpcanada/public_html/crontab2/dpinit.php";


$dt = $dpdb->SqlOneValue("SELECT CURRENT_DATE()");

$n1 = $dpdb->SqlExecute("
	REPLACE INTO user_round_pages
                ( username, phase, count_time, dateval, page_count )
        SELECT  username,
                PHASE,
                UNIX_TIMESTAMP(DATE(FROM_UNIXTIME(version_time))),
                DATE(FROM_UNIXTIME(version_time)),
                COUNT(1)
        FROM page_versions pv
        WHERE pv.state = 'C'
        AND phase IN ('P1', 'P2', 'P3', 'F1', 'F2')
            AND version_time >= UNIX_TIMESTAMP(DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY))
        GROUP BY username, phase, UNIX_TIMESTAMP(DATE(FROM_UNIXTIME(version_time)))");


$dpdb->SqlExecute("TRUNCATE TABLE total_user_round_pages");

$n2 = $dpdb->SqlExecute("
    INSERT INTO total_user_round_pages
        ( username, phase, count_time, page_count, dateval)
    SELECT username, phase, UNIX_TIMESTAMP(DATE(CURRENT_DATE())), SUM(page_count), CURRENT_DATE()
    FROM user_round_pages
    WHERE count_time < UNIX_TIMESTAMP(CURRENT_DATE())
    GROUP BY username, phase");

echo "

$dt

$n1 user round counts inserted/updated over 30 days.

$n2 user/round counts before today summed in total_user_round_pages.

==========================================================================
";

?>
