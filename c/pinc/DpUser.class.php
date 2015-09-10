<?php
ini_set("error_display", true);
error_reporting(E_ALL);

global $relPath;

require_once $relPath . "DpContext.class.php";

define("VERTICAL_LAYOUT_INDEX", 1);
define("HORIZONTAL_LAYOUT_INDEX", 0);

class DpUser
{
    protected $_bb;
	/** @var  ForumUser $_forum_user */
//	protected $_forum_user;
    protected $_username;
    protected $_row;
    protected $_settings;
    protected $_userP;

    public function __construct($username = "") {
        $this->_bb = new DpPhpbb3();

        $this->_username = $username;
        $this->init($this->_username);
    }

	// init() is called from DpUeer constructor
	// and twice from DpThisUser
		// -- when in a session,
		// -- after submitting name and password

    protected function init($username) {
        global $dpdb;

	    if(! $this->_bb->Exists()) {
		    die( "Cannot find user $username in phpBB." );
	    }
        $this->_username = $username;
//	    $bb_users_table = build_forum_users_table();

	    $is_dp_user = DpContext::UserExists($username);

//	        SELECT COUNT(1) FROM users
//	        WHERE username = '$username'");

	    // if not in our database
        if( ! $is_dp_user ) {
	        // assert it's in the phpbb database
//	        assert(! $dpdb->SqlExists("
//						SELECT COUNT(1) FROM $bb_users_table
//						WHERE username_clean = LOWER('$username')"));
//
	        // query for what we need from the phpbb database
//	        $sql = "
//				SELECT  user_id,
//						username,
//						user_email,
//						user_login_attempts,
//						user_lang,
//						user_new_privmsg,
//						user_unread_privmsg
//				FROM $bb_users_table
//				WHERE username_clean = LOWER('$username')";

//	        $row = $dpdb->SqlOneRow($sql);
	        $lang      = $this->_bb->Language();

	        $sql = "
					INSERT INTO users
						(
							username,
							u_intlang,
							t_last_activity,
							date_created)
					VALUES
						(
							?,
							?,
							UNIX_TIMESTAMP(),
							UNIX_TIMESTAMP())";
	        $args = array(&$username, &$lang);
//	        say(html_comment($sql));
	        if($dpdb->SqlExecutePS($sql, $args) != 1) {
		        die( "Create DP User Failed." );
	        }
//            $this->create_dp_user($username);
            assert(DpContext::UserExists($this->Username()));
        }

//	    $this->_forum_user = new ForumUser($username);
    }

    public function FetchUser() {
        global $dpdb;
        $username = $this->Username();
        $sql = "SELECT 
                    u.username,
                    u.real_name,
                    u.date_created,
                    FROM_UNIXTIME(u.date_created) date_created_dt,
                    DATEDIFF(CURRENT_DATE(), DATE(FROM_UNIXTIME(u.date_created))) dp_age,
                    u.t_last_activity,
                    FROM_UNIXTIME(u.t_last_activity) t_last_activity_dt,
                    u.u_privacy,
                    u.u_intlang,
                    u.u_lang,
                    u.emailupdates,
                    u.u_neigh,
                    u.u_top10,
                    u.team_1,
                    u.team_2,
                    u.team_3
            FROM users u
            WHERE u.username = '$username'";
	    $this->_row = $dpdb->SqlOneRow($sql);

        if( ! $this->Exists()) {
            StackDump();
            dump($username);
            die();
        }

    }

    public function IsLoggedIn() {
        return false;
    }

    public function MayWorkInRound($roundid) {
	    if($this->IsSiteManager()) {
		    return true;
	    }

        if($this->HasRole($roundid)) {
            return true;
        }
        switch($roundid) {
            case "P1":
	        case "SR":
				if($this->IsSiteManager()) {
					return true;
				}
                return true;

            case "P2":
            case "F1":
				if($this->IsSiteManager()) {
					return true;
				}
                if($this->DpAge() < 21)
                    return false;
                if($this->PageCount() < 300)
                    return false;
                return true;

            case "P3":
	            if($this->IsSiteManager()) {
		            return true;
	            }
                if($this->PageCount() < 400)
                    return false;
                if($this->RoundPageCount("P2") < 50)
                    return false;
                if($this->RoundPageCount("F1") < 50)
                    return false;
                if($this->DpAge() < 42)
                    return false;
                return $this->HasRole($roundid);

            case "F2":
	            if($this->IsSiteManager()) {
		            return true;
	            }
                if($this->RoundPageCount("F1") < 400)
                    return false;
                if($this->DpAge() < 91)
                    return false;
                return $this->HasRole($roundid);

            case "PP":
            case "PPV":
				if($this->IsSiteManager()) {
					return true;
				}
				return $this->HasRole($roundid);
	        case "POSTED":
            default:
                return false;
        }
    }

	public function NameIs($name) {
		return lower($this->Username()) == lower($name);
	}

    public function MayPP() {
	    return $this->MayWorkInRound("PP");
    }

    public function MayPPV() {
	    return $this->MayWorkInRound("PPV");
    }

	public function PMDefault() {
		return 1;
	}

    public function UserTeam1() {
        static $o = null;
        if(! $o && $this->Team1() > 0) {
            $o = new DpTeam($this->Team1());
        }
        return $o;
    }

    public function UserTeams() {
        $ary = array();
        if($this->Team1()) {
            $ary[] = $this->UserTeam1();
        }
        if($this->Team2()) {
            $ary[] = $this->UserTeam2();
        }
        if($this->Team3()) {
            $ary[] = $this->UserTeam3();
        }
        return $ary;
    }

    public function UserTeam2() {
        static $o = null;
        if(! $o && $this->Team2() > 0) {
            $o = new DpTeam($this->Team2());
        }
        return $o;
    }

    public function UserTeam3() {
        static $o = null;
        if(! $o && $this->Team3() > 0) {
            $o = new DpTeam($this->Team3());
        }
        return $o;
    }

    public function Team1() {
        return empty($this->_row['team_1'])
            ? 0
            : $this->_row['team_1'];
    }

    public function Team2() {
        return empty($this->_row['team_2'])
            ? 0
            : $this->_row['team_2'];
    }

    public function Team3() {
        return empty($this->_row['team_3'])
            ? 0
            : $this->_row['team_3'];
    }

    public function ClearTeam($teamnum) {
        global $dpdb, $User;
        switch($teamnum) {
            case 1:
                $teamfield = "team_1";
                break;
            case 2:
                $teamfield = "team_2";
                break;
            case 3:
                $teamfield = "team_3";
                break;
            default:
                return;
        }
        $dpdb->SqlExecute("
            UPDATE users SET $teamfield = 0
            WHERE username = '{$User->Username()}'");
    }

    public function MayModifyAccess() {
        return $this->MayGrantAccess()
            || $this->MayRevokeAccess();
    }
    public function MayGrantAccess() {
        return $this->IsSiteManager()
        || $this->IsProjectFacilitator();
    }
    public function MayRevokeAccess() {
        return $this->IsSiteManager();
    }
    public function MayReviewWork() {
        if($this->IsSiteManager()) {
            return true;
        }
        return !empty($this->_settings['access_request_reviewer']);
    }

    public function IsSiteNewsEditor() {
	    return $this->HasRole("site_news_editor");
    }

    public function Exists() {
        return count($this->_row) != 0;
    }

    public function ShowStatusBar() {
        return $this->_row['i_statusbar'];
    }

    public function Privacy() {
        return $this->_row['u_privacy'];
    }

    public function PrivateRealName() {
        return $this->Privacy() > 0
            ? "anonymous"
            : $this->RealName();
    }
    public function PrivateUsername() {
        return $this->Privacy() > 0
            ? "anonymous"
            : $this->Username();
    }

    public function HasRole($code) {
        global $dpdb;
//	    if($this->IsSiteManager()) {
//		    return true;
//	    }
        $username = $this->Username();
        $sql = "
            SELECT 1 FROM user_roles
            WHERE username = '$username'
                AND role_code = '$code'";
        return $dpdb->SqlExists($sql);
    }

    public function RoundNeighborhood($roundid, $radius) {
        global $dpdb;
        $count = $this->RoundPageCount($roundid);
        $rank = $this->RoundRank($roundid);
        $username = $this->Username();
        if($count <= 0) {
            return array();
        }
        $rplus = $radius + 1;
 		$usql = "
            SELECT a.username,
                SUM(a.page_count) page_count,
                u.date_created,
                u.u_privacy,
                u1.date_created created1,
                u.date_created created,
                DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(u.date_created)) age_days
            FROM
            (
                SELECT username, page_count FROM total_user_round_pages
                WHERE phase = '$roundid'

                UNION ALL

                SELECT username, COUNT(1) page_count FROM page_events_save
                WHERE phase = '$roundid'
                AND event_time >= UNIX_TIMESTAMP(CURRENT_DATE())
                GROUP BY username
            ) a
            JOIN users u ON a.username = u.username
            JOIN users u1 ON u1.username = '$username'
            GROUP BY a.username
            HAVING page_count > $count
                OR ( page_count = $count
                    AND u.date_created <= u1.date_created
                )
            ORDER BY a.page_count, u.date_created DESC
            LIMIT $rplus";
	    echo html_comment($usql);
        $urows = $dpdb->SqlRows($usql);


		$dsql = "
            SELECT  a.username,
                    SUM(a.page_count) page_count,
                    u.date_created,
                    u.u_privacy,
                    u1.date_created created1,
                    u.date_created created,
                DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(u.date_created)) age_days
            FROM
            (
                SELECT username, page_count FROM total_user_round_pages
                WHERE phase = '$roundid'

                UNION ALL

                SELECT username, COUNT(1) page_count FROM page_events_save
                WHERE phase = '$roundid'
                AND event_time >= UNIX_TIMESTAMP(CURRENT_DATE())
                GROUP BY username
            ) a
            JOIN users u ON a.username = u.username
            JOIN users u1 ON u1.username = '$username'
            GROUP BY a.username
            HAVING page_count < $count
                OR ( page_count = $count
                    AND u.date_created > u1.date_created
                )
            ORDER BY page_count DESC, u.date_created
            LIMIT $radius";
	    echo html_comment($dsql);
        $drows = $dpdb->SqlRows($dsql);

        $rows = array();
        $n = count($urows);
        $irank = $rank - count($urows) ;

        for($i = $n-1; $i >= 0; $i--) {
            $row = $urows[$i];
            $row['rank'] = ++$irank;
            $rows[] = $row;
        }
        foreach($drows as $row) {
            $row['rank'] = ++$irank;
            $rows[] = $row;
        }
        return $rows;
    }

    public function RoundNeighbors($roundid) {
        global $dpdb;
        $username = $this->Username();
        $radius = $this->NeighborRadius();
        $created = $this->DateCreatedInt();
        $ucount = $dpdb->SqlOneValue("
            SELECT page_count  FROM total_user_round_pages t
            WHERE username = '$username' AND round_id = '$roundid'");
        if($ucount <= 0) {
            return array();
        }

        $rank = $dpdb->SqlOneValue("
            SELECT COUNT(1) + 1
            FROM total_user_round_pages t
            JOIN users u ON t.username = u.username
            WHERE t.round_id = '$roundid'
                AND t.page_count > $ucount
                OR (t.page_count = $ucount
                    AND u.date_created < $created)");
        
        $urows = $dpdb->SqlRows("
            SELECT  t.username,
                    t.page_count,
                    u.date_created,
                    u.u_privacy
            FROM total_user_round_pages t
            JOIN users u ON t.username = u.username
            WHERE t.round_id = '$roundid'
                AND t.page_count > $ucount
                OR ( t.page_count = $ucount
                    AND u.date_created < $created)
            ORDER BY page_count, u.date_created DESC
            LIMIT $radius");

        $drows = $dpdb->SqlRows("
            SELECT  t.username,
                    t.page_count,
                    u.date_created,
                    u.u_privacy
            FROM total_user_round_pages t
            JOIN users u ON t.username = u.username
            WHERE t.round_id = '$roundid'
                AND t.page_count < $ucount
                OR ( t.page_count = $ucount
                    AND u.date_created > $created
                )
            ORDER BY page_count DESC, u.date_created
            LIMIT $radius");
        $dpdb->ClearEcho();

        $rows = array();
        $n = count($urows);
        $irank = $rank - count($urows) ;

        for($i = $n-1; $i >= 0; $i--) {
            $row = $urows[$i];
            $row['rank'] = $irank++; 
            $rows[] = $row;
        }
        $rows[] = array("rank" => $rank,
                        "username" => $this->Username(),
                        "date_created" => $created,
                        "page_count" => $ucount,
                        "u_privacy" => $this->Privacy());

        $irank = $rank + 1;
        foreach($drows as $row) {
            $row['rank'] = $irank++;
            $rows[] = $row;
        }
        return $rows;
    }

    public function NeighborRadius() {
        return $this->_row['u_neigh'];
    }

    function DpAge() {
        return $this->_row["dp_age"];
    }

    function DateCreatedInt() {
        return $this->_row['date_created'];
    }   

    public function DateCreated() {
        return date('m-d-Y', $this->DateCreatedInt());
    }

    public function CreatedDaysAgo() {
        return $this->AgeDays() ;
    }
    public function AgeDays() {
        return round( ( time() - $this->DateCreatedInt() )
            / 24 / 60 / 60 ) ;
    }

    public function LastSeenInt() {
        return $this->_row['t_last_activity'];
    }

    public function LastSeenDays() {
        return $this->_daysBetween($this->LastSeenInt(), time());
    }

    public function RealName() {
        return $this->_row['real_name'];
    }

    public function SetRealName($real_name) {
        $this->SetUserString("real_name", $real_name);
    }

    public function EmailAddress() {
        return $this->_bb->Email();
    }

    public function Username() {
        return $this->_username;
    }

    public function InterfaceLanguage() {
	    return $this->_bb->Language();
    }

//    public function Language() {
//        return $this->_row['u_lang'];
//    }

    protected function _daysBetween($earlierdate, $laterdate) {
        return ($laterdate - $earlierdate) / 24 / 60 / 60 ;
    }

    protected function _daysAgo($idate) {
        return $this->_daysBetween($idate, time());
    }

    public function MyPages() {
        global $dpdb ;

        $sql = "SELECT DISTINCT
                    pt.projectid,
                    pt.pagecode,
                    pt.taskcode,
                    p.nameofwork,
                    t.sequencenumber,
                    1 AS pages,
                    1 AS diffcount,
                    1 AS pages_mine,
                    UNIX_TIMESTAMP() AS max_round_timestamp,
                    UNIX_TIMESTAMP() AS date_diffable
                FROM
                    pagetasks AS pt
                JOIN
                    projects AS p
                ON
                    pt.projectid = p.projectid
                JOIN
                    tasks AS t
                ON
                    pt.taskcode = t.taskcode
                WHERE
                    pt.username = '{$this->Username()}'";
        return $dpdb->SqlObjects($sql) ;
    }

    public function IsSiteManager() {
	    global $site_managers;
	    return in_array(lower($this->Username()), $site_managers);
    }

    public function IsProjectManager() {
        return $this->HasRole("PM");
    }

    public function IsAdmin() {
        return $this->IsSiteManager()
        || $this->IsProjectFacilitator()
        || $this->IsProjectManager();
    }

    public function IsProjectFacilitator() {
        return $this->HasRole("PF");
    }

    public function MayCreateProjects() {
        return $this->IsSiteManager()
            || $this->IsProjectFacilitator()
            || $this->IsProjectManager();
    }

    public function IsImageSourcesManager() {
        return $this->HasRole('image_sources_manager');
    }

    public function RoundRank($roundid) {
        global $dpdb;
        $username = $this->Username();
        $count = $this->RoundPageCount($roundid);
		if (true) return 0;
        return $dpdb->SqlOneValue("
            SELECT COUNT(1) + 1 FROM
            (
                SELECT a.username,
                       u.date_created create0,
                       u1.date_created create1,
                       SUM(a.page_count) pagecount 
                FROM (
                    SELECT username, page_count FROM total_user_round_pages
                    WHERE phase = '$roundid'
                    UNION ALL
                    SELECT username, COUNT(1) FROM page_events_save
                    WHERE phase = '$roundid'
                        AND event_time >= UNIX_TIMESTAMP(CURRENT_DATE())
                    GROUP BY username
                ) a
                JOIN users u ON u.username = '$username'
                JOIN users u1 ON a.username = u1.username
                GROUP BY a.username
                HAVING SUM(a.page_count) > $count
                OR (SUM(a.page_count) = $count AND create1 < create0)
            )  b");
    }

    public function PageCount() {
        global $dpdb;
        $username = $this->Username();
        return $dpdb->SqlOneValue("
            SELECT SUM(page_count) FROM (
                SELECT page_count FROM total_user_round_pages
                WHERE username = '$username'
                UNION ALL
                SELECT COUNT(1) FROM page_versions
                    WHERE username = '$username'
                    AND state = 'C'
                    AND version_time >= UNIX_TIMESTAMP(CURRENT_DATE())
            ) a");
    }

    public function RoundPageCount($roundid) {
        global $dpdb;
        $username = $this->Username();
        return $dpdb->SqlOneValue("
            SELECT SUM(page_count) FROM (
                SELECT username, page_count FROM total_user_round_pages
                WHERE username = '$username' AND phase = '$roundid'
                UNION ALL
                SELECT username, COUNT(1) FROM page_versions
                    WHERE username = '$username'
                    	AND phase = '$roundid'
						AND state = 'C'
						AND version_time >= UNIX_TIMESTAMP(CURRENT_DATE())
            ) a");
    }

    public function RoundTodayCount($roundid) {
        global $dpdb;
        return $dpdb->SqlOneValue("
            select count(1) FROM page_versions
            WHERE username = '{$this->Username()}'
				AND state = 'C'
                AND phase = '$roundid'
				AND version_time >= UNIX_TIMESTAMP(CURRENT_DATE())");
//        return $dpdb->SqlOneValue("
//            select page_count FROM user_round_pages_today
//            WHERE username = '{$this->Username()}'
//                AND phase = '$roundid'");
    }

    public function MayManageRoles() {
        return $this->IsSiteManager();
    }

//    public function ForumUserId() {
//        return $this->_bb->UserId();
//    }

    public function IsPostedNotice($projectid) {
        global $dpdb;
        $username = $this->Username();
        return $dpdb->SqlExists("
            SELECT 1 FROM user_posted_notices
            WHERE username = '$username'
                AND projectid = '$projectid'");
    }

    public function SetPostedNotice($projectid) {
        global $dpdb;
        if($this->IsPostedNotice($projectid)) {
            $dpdb->SqlExecute("
                INSERT INTO user_posted_notices
                VALUES ('{$this->Username()}', $projectid)");
        }
    }

    public function ClearPostedNotice($projectid) {
        global $dpdb;
        $username = $this->Username();
        $dpdb->SqlExecute("
            DELETE FROM user_posted_notices
            WHERE username = '$username'
                AND projectid = '$projectid'");
    }

    public function TogglePostedNotice($projectid) {
        if($this->IsPostedNotice($projectid)) {
            $this->ClearPostedNotice($projectid);
        }
        else {
            $this->SetPostedNotice($projectid);
        }
    }



    public function SetCreditName($name) {
        $this->SetUserString("credit_name", $name);
    }

    public function SetUserString($fieldname, $value) {
        global $dpdb;
        $dpdb->SqlExecute( "
                UPDATE users SET $fieldname = '$value'
                WHERE username = '{$this->Username()}'");
    }


//	public function Location() {
//		return $this->_forum_user->Location();
//	}
    public function IsEmailUpdates() {
        return $this->_row['emailupdates'];
    }

		public function Bb() {
			return $this->_bb;
		}

//		public function BbUser() {
//			return $this->_bb->bb_user();
//		}

//		public function UserData() {
//			return $this->_bb->UserData();
//		}

//		public function LoginAttempts() {
//			return $this->_bb->LoginAttempts();
//		}

//		public function Phpbb3UserData() {
//			return $this->_bb->UserData();
//		}

	/*
		protected function create_dp_user($username) {
			global $dpdb;

//			$bb_users_table = build_forum_users_table();

			// A little validation
			if($username == "")
				return;
//			if( ! $dpdb->SqlExists("
//						SELECT 1 FROM $users_table
//						WHERE username_clean = LOWER('$username')"))
//				return;


			// find the user in phpbb

			$row = $dpdb->SqlOneRow("
				SELECT  user_id,
						username,
						user_email,
						user_login_attempts,
						user_lang,
						user_new_privmsg,
						user_unread_privmsg
				FROM $bb_users_table
				WHERE username_clean = LOWER('$username')");

			if(count($row) == 0) {
				die("Unable to find phpBB3 user $username in order to create DP user.");
			}

			// grab the phpbb user key (which we hopefully don't use)
			$bbid      = $row['user_id'];
			$lang      = $row['user_lang'];

			$lang = $this->_bb->Language();

			$sql = "
					INSERT INTO users
						(
							username,
							u_intlang,
							t_last_activity,
							date_created)
					VALUES
						(
							?,
							?,
							UNIX_TIMESTAMP(),
							UNIX_TIMESTAMP())";

			$args = array( &$username, &$lang);
			if($dpdb->SqlExecutePS($sql, $args) != 1) {
				die( "Create DP User Failed." );
			}
		}
	*/

    public function FirstRoundDate($round_id) {
        global $dpdb;
        return $dpdb->SqlOneValue("
            SELECT DATE(FROM_UNIXTIME(MIN(version_time)))
            FROM page_versions
            WHERE username = '{$this->Username()}'
                AND phase = '$round_id'");
    }
    public function FirstRoundTime($round_id) {
        global $dpdb;
        return $dpdb->SqlOneValue("
            SELECT FROM_UNIXTIME(MIN(version_time))
            FROM page_versions
            WHERE username = '{$this->Username()}'
                AND phase = '$round_id'");
    }

    public function FirstRoundDateDays($round_id) {
        global $dpdb;
        return $dpdb->SqlOneValue("
             SETLECT DATEDIFF(CURRENT_DATE(), DATE(FROM_UNIXTIME(MIN(version_time)))
            FROM page_versions
            WHERE username = '{$this->Username()}'
                AND phase = '$round_id'");
    }
    public function FirstRoundTimeDays($round_id) {
        return $this->_DaysAgo($this->FirstRoundTime($round_id));
    }

    public function Teams() {
        $t = array();
        if($this->Team1() != "") {
            $t[] = new DpTeam($this->Team1());
        }
        if($this->Team2() != "") {
            $t[] = new DpTeam($this->Team2());
        }
        if($this->Team3() != "") {
            $t[] = new DpTeam($this->Team3());
        }
        return $t;
    }

    public function BestRoundDay($round_id) {
        global $dpdb;
        $count = $this->BestRoundDayCount($round_id);
        return $count <= 0
            ? 0
            : $dpdb->SqlOneValue("
            SELECT MIN(count_time) FROM user_round_pages
            WHERE username = '{$this->Username()}'
                AND round_id = '$round_id'
                AND page_count = $count");
    }

    public function BestRoundDayCount($round_id) {
        global $dpdb;
        return $dpdb->SqlOneValue("
            SELECT MAX(page_count) FROM user_round_pages
            WHERE username = '{$this->Username()}'
                AND round_id = '$round_id'");
    }


    public function MayMentor() {
        return $this->HasRole("P2mentor");
    }

    public function MayReleaseHold($holdcode) {
        global $dpdb;

        if($this->IsSiteManager()) {
            return true;
        }
        $username = $this->Username();
        return $dpdb->SqlExists("
            SELECT 1
            FROM user_roles ur
            INNER JOIN hold_roles hr
                ON hr.role_code = ur.role_code
            WHERE hr.hold_code ='$holdcode'
                -- AND hr.set_or_release ='R'
                AND ur.username = '$username'");
    }

    public function MaySetHold($holdcode) {
        global $dpdb;

        if($this->IsSiteManager()) {
            return true;
        }
        $username = $this->Username();
        return $dpdb->SqlExists("
            SELECT 1
            FROM user_roles ur
            INNER JOIN hold_roles hr
                ON hr.role_code = ur.role_code
            WHERE hr.hold_code ='$holdcode'
                -- AND hr.set_or_release ='S'
                AND ur.username = '$username'");
    }
    
    public function GrantRole($role) {
        global $dpdb;
        $username = $this->Username();
        $sql = "
            SELECT 1 FROM user_roles
            WHERE username = '$username'
                AND role_code = '$role'";
        if(! $dpdb->SqlExists($sql)) {
             $sql = "
                 INSERT INTO user_roles
                 SET username = '$username',
                     role_code = '$role'";
             $dpdb->SqlExecute($sql);
       }
         LogRoleGrant($username, $role);
    }

    public function RevokeRole($role) {
        global $dpdb;
        $username = $this->Username();
        $dpdb->SqlExecute("
            DELETE FROM user_roles
            WHERE username = '$username'
                AND role_code = '$role'");

        LogRoleRevoke($username, $role);
    }
}

class DpThisUser extends DpUser
{
                    // if both args are missing, try for a session
                    function __construct($username = "", $password = "") {
                        $this->_bb = new DpPhpbb3();

	                    // Is the user in a session?
                        if($this->_bb->IsLoggedIn()) {
	                        //	If so, phpbb should give us a username
                            $username = $this->_bb->Username();

                            $this->init($username);
                            $this->SetLatestVisit();
                            return;
                        }

                        if($username == "" || $password == "") {
                            return;
                        }

//	                    $this->_bb->DoLogin($username, $password);
                        if($this->_bb->DoLogin($username, $password)) {
//                        if($this->_bb->IsLoggedIn()) {
                            $username = $this->_bb->Username();
                            assert($username != "");
                            $this->init($username);
                            $this->SetLatestVisit();
                            return;
                        }
                    }

                    public function LogOut() {
                        $this->_bb->DoLogout();
                    }

                    public function IsLoggedIn() {
                        if($this->_bb->IsLoggedIn()) {
                            assert($this->Username() != "");
                            return true;
                        }
                        return false;
                    }

                    public function SetLatestVisit() {
                        global $dpdb;
                        $dpdb->SqlExecute("
                            UPDATE users
                            SET t_last_activity = UNIX_TIMESTAMP()
                            WHERE username = '$this->_username'");
                    }

                    public function InboxCount() {
                        return $this->_bb->InboxCount();
                    }
}

class DpTeam
{
    public function __construct($team_id) {
        global $dpdb;

        $this->_row = $dpdb->SqlOneRow("
            SELECT ut.id,
                   ut.teamname,
                   ut.team_info,
                   ut.webpage,
                   ut.owner owner_id,
                   ut.createdby,
                   ut.created,
                   FROM_UNIXTIME(ut.created) created_str,
                   DATEDIFF(CURRENT_DATE(), DATE(FROM_UNIXTIME(ut.created))) created_days_ago,
                   ut.member_count,
                   ut.active_members,
                   ut.daily_average,
                   ut.icon,
                   ut.avatar,
                   ut.topic_id,
                   ut.latestUser,
                   uown.username ownername
            FROM user_teams ut
            LEFT JOIN users uown ON ut.ownername = uown.username
            LEFT JOIN users ucre ON ut.createdby = ucre.username
            WHERE ut.id = $team_id");
    }

    public function Exists() {
        return count($this->_row) > 0;
    }

    public function Name() {
        return $this->TeamName();
    }
    public function TeamName() {
        return $this->_row["teamname"];
    }

    public function CreatedDaysAgo() {
        return $this->_row["created_days_ago"];
    }

    public function Id() {
        return $this->_row['id'];
    }

    public function RetiredMembers() {
        return $this->MemberCount() - $this->ActiveMembers();
    }

    public function ActiveMembers() {
        return $this->_row['active_members'];
    }

    public function MemberCount() {
        global $dpdb;
        return
            $dpdb->SqlOneValue("
            SELECT COUNT(1) FROM users
            WHERE team_1 = {$this->Id()}")
            +
            $dpdb->SqlOneValue("
            SELECT COUNT(1) FROM users
            WHERE team_2 = {$this->Id()}")
            +
            $dpdb->SqlOneValue("
            SELECT COUNT(1) FROM users
            WHERE team_3 = {$this->Id()}");
    }

    private function AvatarFile() {
        return $this->_row['avatar'];
    }

    public function AvatarUrl() {
        global $team_avatars_url;
        return build_path($team_avatars_url, $this->AvatarFile());
    }

    public function AvatarLink($prompt = "") {
        return link_to_url($this->AvatarUrl(), $prompt);
    }

    public function IconFile() {
        return $this->_row['icon'];
    }

    public function IconUrl() {
        global $team_icons_url;
        return build_path($team_icons_url, $this->IconFile());
    }

    public function IconLink($prompt = "") {
        return link_to_url($this->IconUrl(), $prompt);
    }

    public function OwnerId() {
        return $this->_row['owner_id'];
    }

    public function OwnerName() {
        return $this->_row['ownername'];
    }

    public function CreatorId() {
        return $this->_row['creatorid'];
    }

    public function CreatedBy() {
        return $this->_row['createdby'];
    }

    public function CreatedStr() {
        return $this->_row['created_str'];
    }

    public function TopicId() {
        return $this->_row['topic_id'];
    }

    public function Info() {
        return $this->_row['team_info'];
    }

    public function WebPage() {
        return $this->_row['webpage'];
    }

    public function MemberRank() {
        global $dpdb;
        return $dpdb->SqlOneValue("
        SELECT 1 + COUNT(1) FROM user_teams
        WHERE member_count > {$this->MemberCount()}");
    }

    public function RoundPageCount($roundid) {
        global $dpdb;
        $sql = "
            SELECT SUM(page_count) FROM team_round_pages
            WHERE team_id = {$this->Id()}
              AND round_id = '{$roundid}'";
        return $dpdb->SqlOneValue($sql);
    }

    public function RoundRank($roundid) {
        global $dpdb;

        return $dpdb->SqlOneValue("
            SELECT 1 + COUNT(1)
            FROM (
                SELECT team_id, SUM(page_count) pages
                FROM team_round_pages
                WHERE round_id = '$roundid'
                GROUP BY team_id
            ) a
            WHERE pages > (
            SELECT SUM(page_count) pages
            FROM team_round_pages
            WHERE round_id = 'P2'
                AND team_id = {$this->Id()}
            )");
    }
}

function LogRoleGrant($username, $role) {
    global $User;
    global $dpdb;
    $actor = $User->Username();
    $dpdb->SqlExecute("
        INSERT INTO access_log
            (timestamp, subject_username, modifier_username, action, activity)
        VALUES
            (UNIX_TIMESTAMP(), '$username', '$actor', 'grant', '$role')");
}

function LogRoleRevoke($username, $role) {
    global $User;
    global $dpdb;
    $actor = $User->Username();
    $dpdb->SqlExecute("
        INSERT INTO access_log
            (timestamp, subject_username, modifier_username, action, activity)
        VALUES
            (UNIX_TIMESTAMP(), '$username', '$actor', 'revoke', '$role')");
}


