<?php
ini_set("display_errors", true);
error_reporting(E_ALL);

/**
 * DpContext Class.
 * Singleton resource for
 * session level information.
 *
 *  A session is only possible when logged in.
 *  Logging in requires a database.
 */

class DpContext {
	//    private $_languages;
//	private $_phaserows;
	/** @var Phase[] $_phases */
	private $_phases;
	private $_iphases;
	/** @var Round[] $_rounds */
	private $_rounds;
	private $_irounds;
	private $_holds;

	public function __construct() {
		$this->init_phases();
//		$this->init_holds();
	}

	private function init_phases() {
		global $dpdb;
		$rows = $dpdb->SqlRows( "
            SELECT phase, sequence, description, round_id, caption, round_sequence, default_state, forum_id
            FROM phases
            ORDER BY sequence" );
		$this->_phases    = array();
		$this->_iphases    = array();
		$this->_rounds    = array();
		$this->_irounds    = array();
		foreach ( $rows as $row ) {
			$this->_phases[ $row['phase'] ] = new Phase($row);
			$this->_iphases[ $row['sequence'] ] = new Phase($row);
			if($row['round_id']) {
				$this->_rounds[ $row['round_id'] ]         = new Round($row);
				$this->_irounds[ $row['round_sequence'] ] = new Round($row);
			}
		}
	}

	public function GetPhase($code) {
		return $this->_phases[$code];
	}

	public function DefaultState( $code ) {
		if ( ! isset( $this->_phases[ $code ] ) ) {
			die( "?phase $code" );
		}
		if ( ! isset( $this->_phases[ $code ]["default_state"] ) ) {
			dump( $this->_phases );
			die();
		}

		return $this->_phases[ $code ]["default_state"];
	}

	public function IndexPhase( $index ) {
		/** @var Phase $ph */
		foreach($this->Phases() as $ph) {
			if($ph->Index() == $index) {
				return $ph->Code();
			}
		}
		return "";
	}

	public function PhaseSequence( $code ) {
		/** @var Phase $pc */
		$pc = $this->_phases[$code];
		return $pc->Sequence();
//		return $this->_phases[ $code ]['sequence'];
	}
//
	public function PhaseDescription( $code ) {
		return $this->_phases[ $code ]['description'];
	}

	public function Phases() {
		return $this->_phases;
	}

	public function Rounds() {
		return $this->_rounds;
	}

	public function GetRound($roundid) {
		return $this->_rounds[$roundid];
	}

	public function PhaseIndex( $code ) {
		/** @var Phase $phs */
		$phs = $this->_phases[$code];
		return $phs->Sequence();
	}

	public function PhaseBefore( $code ) {
		/** @var Phase $p */
		$idx = $this->PhaseIndex($code);
		if($idx <= 1) {
			return null;
		}
		$p = $this->_iphases[$idx-1];
		return $p->Code();
	}

	public function PhaseAfter( $code ) {
		/** @var Phase $p */
		$idx = $this->PhaseIndex($code);
		if($idx >= count($this->_phases)) {
			return null;
		}
		$p = $this->_iphases[$idx+1];
		return $p->Code();
	}

	/*
	 *  Problem: idempotency of page request. Disallow resubmission of
	 *          a form requesting a page. So form requires a nonce variable.
	 *  Maybe provide a uuid with each project.php. When the request is submitted,
	 *      we only need to record it to detect duplicates. Don't save at creation.
	 *  Then when rececived associate the page/version.
	 *  User can only have one active nnnce? OK - if nonce received and page sent
	 *  then user state has changed for any other nonce received.
	 *  Goal: Request for a page should be accompanied by a nonce value
	 *  indicating
	 */

	public function Nonce() {
		global $dpdb;

		return $dpdb->SqlOneValue( "SELECT UUID()" );
	}

	public function SetUserNonce($nonce) {
		global $dpdb, $User;
		$username = $User->Username();
		$sql = "
			SELECT id FROM nonces
			WHERE username = ? AND uuid = ?";
		$args = array(&$username, &$nonce);
		$is_dup = $dpdb->SqlOneValuePS($sql, $args);
		if($is_dup) {
			return false;
		}

		$sql = "REPLACE INTO nonces
				SET uuid = ?, username = ? nonce_time = UNIX_TIMESTAMP()";
		$args = array(&$nonce, &$username);
		return $dpdb->SqlExecutePS($sql, $args);

	}

	public function init_holds() {
		global $dpdb;
		$rows         = $dpdb->SqlRows( "
            SELECT hold_code, sequence, description, release_time
            FROM hold_types
            ORDER BY sequence" );
		$this->_holds = array();
		foreach ( $rows as $row ) {
			$this->_holds[ $row['hold_code'] ] = $row;
		}
	}

	public function HoldSequence( $code ) {
		return $this->_holds[ $code ]['sequence'];
	}

	public function HoldDescription( $code ) {
		return $this->_holds[ $code ]['description'];
	}

	public function Holds() {
		return $this->_holds;
	}

	public function UserCount() {
		global $dpdb;

		return $dpdb->SqlOneValue( "
            SELECT COUNT(1) FROM users" );
	}

	public function PostedCount() {
		global $dpdb;

		return $dpdb->SqlOneValue( "
            SELECT COUNT(DISTINCT postednum) FROM projects
            WHERE phase = 'POSTED'" );

	}

	/*
		returns name, code
	*/

	public function ActiveLanguages() {
		static $_langs;
		global $dpdb;

		if ( ! isset( $_langs ) ) {
			$_langs = $dpdb->SqlRows( "
			SELECT DISTINCT a.name, a.code
			FROM (
				SELECT l.name, l.code
                FROM projects p
                JOIN languages l ON p.language = l.code
                WHERE p.phase IN ('PREP', 'P1', 'P2', 'P3', 'F1', 'F2', 'PP', 'PPV', 'POSTED')
                UNION ALL
                SELECT l.name, l.code
                FROM projects p
                JOIN languages l ON p.seclanguage - l.code
                WHERE p.phase IN ('PREP', 'P1', 'P2', 'P3', 'F1', 'F2', 'PP', 'PPV', 'POSTED')
			) a
			ORDER BY NAME" );
		}

		return $_langs;
	}

	private function Bb() {
		global $User;

		return $User->Bb();
	}

	public function LatestTopicPostTime( $topicid ) {
		/** @var DpPhpbb3 $bb */
		if ( ! $topicid ) {
			return null;
		}
		$bb = $this->Bb();
		if ( ! $this->TopicExists( $topicid ) ) {
			return null;
		}

		return  $bb->LatestTopicPostTime( $topicid );
	}

	public function TopicExists( $topicid ) {
		$bb = $this->Bb();

		return $bb->TopicExists( $topicid );
	}

	public function ForumTopicReplyCount( $topicid ) {
		$bb = $this->Bb();

		/** @var DpPhpbb3 $bb */

		return $bb->TopicReplyCount( $topicid );
	}

	public function FontFaces() {
		global $font_faces;

		return $font_faces;
	}

	public function FontSizes() {
		global $font_sizes;

		return $font_sizes;
	}

	public function ActivePMArray() {
		global $dpdb;
		$a = $dpdb->SqlValues( "
            SELECT DISTINCT username
            FROM projects
            WHERE phase IN ('PREP', 'P1', 'P2', 'P3', 'F1', 'F2', 'PP', 'PPV', 'POSTED')
            ORDER BY username" );

		return $a;
	}

	public function ActivePPArray() {
		global $dpdb;
		$a = $dpdb->SqlValues( "
            SELECT DISTINCT postproofer
            FROM projects
            WHERE phase IN ('PREP', 'P1', 'P2', 'P3', 'F1', 'F2', 'PP', 'PPV', 'POSTED')
                AND IFNULL(postproofer, '') != ''
            ORDER BY postproofer" );

		return $a;
	}

	public function ActivePPVArray() {
		global $dpdb;
		$a = $dpdb->SqlValues( "
            SELECT DISTINCT ppverifier
            FROM projects
            WHERE phase IN ('PREP', 'P1', 'P2', 'P3', 'F1', 'F2', 'PP', 'PPV', 'POSTED')
                AND IFNULL(ppverifier, '') != ''
            ORDER BY ppverifier" );

		return $a;
	}

	/**
	 * @return array
	 */
	public function GenreArray() {
		global $include_dir;
		$ary   = array();
		$str   = file_get_contents( build_path( $include_dir, "genre.txt" ) );
		$lines = preg_split( "/[\r\n]+/u", $str );
		foreach ( $lines as $line ) {
			$ary[ $line ] = _( $line );
		}

		return $ary;
	}

	public function ActiveGenreArray() {
		global $dpdb;
		$a = $dpdb->SqlValues( "
            SELECT DISTINCT genre
            FROM projects
            WHERE IFNULL(genre, '') != ''
              AND phase IN ('PREP', 'P1', 'P2', 'P3', 'F1', 'F2', 'PP', 'PPV', 'POSTED')
            ORDER BY genre" );

		return $a;
	}

	public static function UserExists( $username ) {
		global $dpdb;
		if ( empty( $username ) ) {
			return false;
		}

		return $dpdb->SqlExists( "
            SELECT 1 FROM users
            WHERE username = '$username'" );
	}

	public static function TeamExists( $teamname ) {
		global $dpdb;
		if ( empty( $teamname ) ) {
			return false;
		}

		return $dpdb->SqlExists( "
            SELECT 1 FROM teams
            WHERE teamname = '$teamname'" );
	}

	public function TransientDirectory() {
		return TEMP_DIR;
	}

	public function ActiveRoundsArray() {
		global $dpdb;

		return $dpdb->SqlValues( "
            SELECT roundid
            FROM project_round_sequence
            WHERE sequence_code = 'default'
            ORDER BY sequence" );
	}

	public function ZipSaveString( $filename, $text) {
		$topath = build_path( TEMP_DIR, "zip/" . $filename . ".zip" );
		$to_url  = build_path( TEMP_URL, "zip/" . $filename . ".zip" );

		dump($topath);
		dump($to_url);

		$zip = new ZipArchive();
		if ( ( $ret = $zip->open( $topath, ZIPARCHIVE::CREATE ) ) != true ) {
			die( "zip error $ret on open $topath." );
		}
		if ( ! $zip->addFromString( $filename, $text ) ) {
			die( "zip error adding text to $topath." );
		}
		if ( ( $ret = $zip->close() ) != true ) {
			die( "zip error $ret on close." );
		}
		return $to_url;
	}

	public function ZipSendString( $stub, $text ) {
		$zipdir = build_path(TEMP_DIR, "zip");
		assert(file_exists($zipdir));
		assert(is_dir($zipdir));
		assert(is_writable($zipdir));
		$zipfile = $stub . ".zip";
		$textfile  = $stub . ".txt";
		$textpath = build_path($zipdir, $textfile);
		file_put_contents($textpath, $text);
		$zippath = build_path( $zipdir, $zipfile );
		$zip = new ZipArchive();
		if ( ( $ret = $zip->open( $zippath, ZIPARCHIVE::CREATE ) ) != true ) {
			die( "zip error $ret on open $zippath." );
		}
		if ( ! $zip->addFromString( $textfile, $text ) ) {
			die( "zip error adding $textfile to $zipfile." );
		}
		if ( ( $ret = $zip->close() ) != true ) {
			die( "zip error $ret on close." );
		}

		send_file( $zippath );
		unlink($zippath);
	}

	/*
		public function ZipSendFile($stub, $path) {
			$tofile  = $stub.".zip";
			$topath  = build_path( TEMP_DIR, $stub.".zip");
			$zip = new ZipArchive();
			if(($ret = $zip->open($topath, ZIPARCHIVE::CREATE)) != true) {
				die("zip error $ret on open $topath.");
			}
			if(! $zip->addFile($path)) {
				die("zip error adding $path to $tofile.");
			}
			if(($ret = $zip->close()) != true) {
				die("zip error $ret on close.");
			}
			send_file($topath);
		}
	*/

	public function ZipSendFileArray( $stub, $apaths ) {
		$zipfile = $stub . ".zip";
		$zippath = build_path( TEMP_DIR, "zip/$zipfile" );
		@unlink( $zippath );  // in case anything from before
		$zip = new ZipArchive();
		if ( ! ( $zip->open( $zippath, ZIPARCHIVE::CREATE ) ) ) {
			die( "zip error on open $zippath." );
		}
		foreach ( $apaths as $path ) {
			if ( is_file( $path ) ) {
				if ( ! $zip->addFile( $path, basename( $path ) ) ) {
					die( "zip error adding $path to $zipfile." );
				}
			}
		}
		if ( ! $zip->close() ) {
			die( "zip error on close." );
		}
		send_file( $zippath );
		unlink($zippath);
	}

	/*
	public function ZipSendDirectory( $stub, $path ) {
		$tofile = $stub . ".zip";
		$topath = build_path( TEMP_DIR, $stub . ".zip" );
		unlink( $topath );
		$files = glob( "$path/*" );
		$zip   = new ZipArchive();
		if ( ( $ret = $zip->open( $topath, ZIPARCHIVE::CREATE ) ) != true ) {
			die( "zip error $ret on open $topath." );
		}
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				if ( ! $zip->addFile( $file, basename( $file ) ) ) {
					die( "zip error adding $stub to $tofile." );
				}
			}
		}
		if ( ( $ret = $zip->close() ) != true ) {
			die( "zip error $ret on close." );
		}
		send_file( $topath );
	}
	*/


    public function NewProjectId() {
        global $dpdb;
        $root =  "p" . yymmdd();
        $sql = "
            SELECT MAX(projectid) FROM projects
            WHERE projectid LIKE '{$root}%'";
        $pidmax = $dpdb->SqlOneValue($sql);
        if($pidmax == "") {
            $i = 1;
        }
        else {
            $i = intval(right($pidmax, 3)) + 1;
        }
        return $root . sprintf("%03d", $i);
    }

/*
 * A page is added when we hae an image file and text.
 *
 * 1. Copy the image file into the project directory
 * 2. Archive the image file
 * 3. Add a pages record
 * 4. Add a page_versions record, version 0
 * 5. Copy the text file to a version file
 * 6. Archive the text file
 * 7. delete the image file
 * 8. delete the text file
*/

	public function AddPage($projectid, $pagename, $fromimagepath, $fromtextpath) {

		global $dpdb;

//		$fromtextpath = build_path($fromdirectory, $fromtextfile);
//		$fromimagepath = build_path($fromdirectory, $fromimagefile);
		$text = file_get_contents($fromtextpath);
		$text = norm($text);
		$crc32 = crc32($text);
		$textlength = mb_strlen($text);
		$imagefile = basename($fromimagepath);
		$toarchivepath  = build_path(ProjectArchivePath($projectid), $imagefile);
		$toimagepath    = build_path(ProjectPath($projectid), $imagefile);


		$sql  = "
				REPLACE INTO pages
                SET projectid = ?,
                    pagename = ?,
                    imagefile = ?";

		$args = array( &$projectid, &$pagename, &$imagefile );
		$dpdb->SqlExecutePS( $sql, $args );

		$sql = "REPLACE INTO page_versions
                SET projectid = ?,
                    pagename = ?,
                    phase = 'PREP',
                    task = 'LOAD',
                    state = 'C',
                    version_time = UNIX_TIMESTAMP(),
                    dateval = CURRENT_DATE(),
                    crc32 = ?,
                    textlen = ?";

		$args = array( &$projectid, &$pagename, &$crc32, &$textlength );
		$dpdb->SqlExecutePS( $sql, $args );

		if ( file_exists( $toarchivepath ) ) {
			unlink( $toarchivepath );
		}
		copy( $fromimagepath, $toarchivepath );

		if ( file_exists( $toimagepath ) ) {
			unlink( $toimagepath );
		}
		copy( $fromimagepath, $toimagepath );

		@unlink( $fromimagepath );

		$toversionpath = PageVersionPath($projectid, $pagename, 0);
		assert(file_put_contents($toversionpath, $text));

		@unlink($fromtextpath);

	}

	public function PutPageVersionText($projectid, $pagename, $version, $text) {
		$textpath = PageVersionPath($projectid, $pagename, $version);
		return file_put_contents($textpath, $text);
	}

	public function UpdateVersion($projectid, $pagename, $version, $state, $text) {
		global $dpdb, $User;
		$text = norm($text);
		$crc32 = crc32($text);
		$username = $User->Username();
		$len   = mb_strlen($text);
		$sql = "
			UPDATE page_versions
			SET crc32 = ?,
			textlen = ?,
			username = ?,
			state = ?,
			version_time = UNIX_TIMESTAMP(),
			dateval = CURRENT_DATE()
			WHERE projectid = ?
				AND pagename = ?
				AND version = ?
				";
		$args = array(&$crc32, &$len, &$username, &$state, &$projectid, &$pagename, &$version);
		$dpdb->SqlExecutePS($sql, $args);

		return $this->PutPageVersionText($projectid, $pagename, $version, $text);
	}

    public function SendUserEmail($username, $subject, $message) {
        $to = $this->UserEmailAddress($username);
        $headers = 'From: dphelp@www.pgdpcanada.net' . "\r\n" .
            'Reply-To: dphelp@www.pgdpcanada.net' . "\r\n" .
            'X-Mailer: PHP/' . phpversion();

        mail($to, $subject, $message, $headers);
    }

    private function UserEmailAddress($username) {
	    $usr = new DpUser($username);
	    return $usr->EmailAddress();
    }

    public function CreateForumThread($subject, $message, $poster) {
        $bb = $this->Bb();
        /** @var DpPhpbb3 $bb  */
        return $bb->CreateTopic($subject, $message, $poster);
    }

	public function MoveTopicToPhaseForum($topicid, $phasename) {
        $bb = $this->Bb();
//        $f = $this->_phases[$phasename];
        $forumid = $this->_phases[$phasename]->ForumId();
        /** @var DpPhpbb3 $bb  */
        $bb->MoveTopicForumId($topicid, $forumid);
    }

	/** @var DpTeam $team */
	public function CreateTeamTopic($team) {
		$subj = "Create " . $team->TeamName() . " Forum Topic";
		$teamname = $team->TeamName();
		$creator = $team->CreatedBy();
		$info = $team->Info();
		$url = url_for_team_stats($team->Id());

		$msg = "
Team Name: $teamname
Created By: $creator`

Info: $info

Team Page: $url
Use this area to have a discussion with your fellow teammates! :-Dp";

		$id = $this->CreateForumThread($subj, $msg, $creator);
        return $id;
	}


    public function InstalledLanguages() {
    }
}

/**
 * From: http://www.redips.net/php/write-to-log-file/
 * Logging class:
 * - contains setPath, logWrite and logClose public methods
 * - logWrite writes message to the log file
 * - message is written with the following format: (username) [d/M/Y:H:i:s] (script name) message
 * By default inits to "off" so it can be singleton
 * Candidate for all the usual errorlevels, errortypes, etc.
 */
class DpLog {
	private $_log_path;
	private $_is_on = false;

	public function __construct($path = "", $is_on = false) {
		global $site_log_path;
		$this->_log_path = ($path ? $path : $site_log_path);
		$this->_is_on    = $is_on;
	}

	public function logWrite($message) {
		global $User;
		if(! $this->_is_on) {
			return;
		}
		$username = $User->Username();
		$script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);
		// define current time and suppress E_WARNING if using the system TZ settings
		// (don't forget to set the INI setting date.timezone)
		$time = @date('[d/M/Y:H:i:s]');
		// write current time, script name and message to the log file
		file_put_contents($this->_log_path, "($username) $time ($script_name) $message" . PHP_EOL, FILE_APPEND);
	}

	public function SetOn() {
		$this->_is_on = true;
	}

	public function SetOff() {
		$this->_is_on = false;
	}

	public function logClear() {
		file_put_contents($this->_log_path, "");
	}
}

class Phase
{
	private $_row;
	public function __construct($row) {
		$this->_row = $row;
	}
	public function RoundId() {
		return $this->_row['round_id'];
	}
	public function Sequence() {
		return $this->_row['sequence'];
	}
	public function Index() {
		return $this->_row['sequence'];
	}
	public function Description() {
		return $this->_row['description'];
	}
	public function Code() {
		return $this->_row['phase'];
	}
	public function PhaseCode() {
		return $this->_row['phase'];
	}
	public function Caption() {
		return $this->_row['caption'];
	}
    public function ForumId() {
        return $this->_row['forum_id'];
    }
}
class Round
{
	private $_row;
	public function __construct($row) {
		$this->_row = $row;
	}
	public function RoundId() {
		return $this->_row['round_id'];
	}
	public function Sequence() {
		return $this->_row['sequence'];
	}
	public function Description() {
		return $this->_row['description'];
	}
	public function Caption() {
		return $this->_row['caption'];
	}
}

