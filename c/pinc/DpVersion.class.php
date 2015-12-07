<?php
/**
 * Created by PhpStorm.
 * User: don
 * Date: 8/8/2015
 * Time: 8:50 PM
 * @param $projectid String
 * @param $pagename String
 * @return array
 */

/*
 * Creating a new Version
 *
 * When a project moves into a new Phase, a set of Versions is created with state "A" and task "PROOF" or "FORMAT".
 * While a project is in a Phase, some Pages can have Versions for that Phase. Not required.
 * A Version can be preempted but only if it's Available. But then the preempting Version becomes active (state
 * "A" or "O"). Only one Version max can be Active.
 * Pages could have a queue of proto-Versions to be instantiated when the page gets to that Phase.
 * Pre-empting could put the available Version back at the front of the queue and take its Version Number.
 */

function Versions($projectid, $pagename) {
	global $dpdb;
	$ary = array();
	$sql = "
		  SELECT projectid,
				pagename,
				version,
				version_time,
				username,
				phase,
				task,
				state,
				crc32,
				textlen
			FROM page_versions
			WHERE projectid = '$projectid'
				AND pagename = '$pagename'
			ORDER BY version";
	$rows = $dpdb->SqlRows($sql);
	foreach($rows as $row) {
		$ary[$row['version']] = new DpRowVersion($row);
	}
	return $ary;
}

class DpRowVersion extends DpVersion {
	function __construct($row) {
		$this->_row = $row;
		parent::__construct($row["projectid"], $row["pagename"], $row["version"]);
	}
}

class DpLastVersion extends DpVersion {
	function __construct($projectid, $pagename) {
		global $dpdb;
		$version = $dpdb->SqlOneValue(
				"SELECT version FROM page_last_versions
				WHERE projectid = '$projectid' AND pagename = '$pagename'");
		parent::__construct($projectid, $pagename, $version);
	}
}

class DpVersion {
	protected $_row;
	protected $_version_number;

	function __construct($projectid, $pagename, $version) {
		global $dpdb;
		if(is_null($version)) {
			dump("proj $projectid  pg $pagename    ver $version");
			StackDump();
			die();
		}
		$this->_version_number = $version;

		$sql = "SELECT projectid,
						pagename,
						version,
						version_time,
						username,
						phase,
						task,
						state,
						crc32,
						textlen
			   FROM page_versions
			   WHERE projectid = ?
			   		AND pagename = ?
			   		AND version = ?";
		$args = array(&$projectid, &$pagename, &$version);
		$row = $dpdb->SqlOneRowPS($sql, $args);

		$this->init($row);
	}

	protected function init(&$row) {
		$this->_row = $row;
	}

	public function VersionNumber() {
		return $this->_row['version'];
	}
	public function ProjectId() {
		return $this->_row['projectid'];
	}

	public function PageName() {
		return $this->_row['pagename'];
	}

	private function Version() {
		return $this->_row['version'];
	}

	public function Username() {
		return $this->_row['username'];
	}

	public function TextLength() {
		return $this->_row['textlen'];
	}

	public function CRC32() {
		return $this->_row['crc32'];
	}

	public function ResetCRC() {
		global $dpdb;
		$projectid = $this->ProjectId();
		$pagename = $this->PageName();
		$vnum = $this->VersionNumber();
		$crc = crc32($this->VersionText());
		assert($crc != $this->CRC32());
		$sql = "UPDATE page_versions
				SET crc32 = ?
				WHERE projectid = ?
				AND pagename = ?
				AND version = ?";
		$args = array(&$crc, &$projectid, &$pagename, &$vnum);
		$dpdb->SqlExecutePS($sql, $args);
	}

	public function State() {
		return $this->_row['state'];
	}

	public function Phase() {
		return $this->_row['phase'];
	}

	private function IsAvailable() {
		return $this->State() == 'A';
	}

	public function CheckOutVersion($username) {
		assert($this->IsAvailable());
		global $dpdb;
		$projectid = $this->ProjectId();
		$pagename = $this->PageName();
		$version = $this->Version();
		$sql = "
			UPDATE page_last_versions
			SET username = ?,
				state = 'O',
				version_time = UNIX_TIMESTAMP()
			WHERE projectid = ?
				AND pagename = ?
				AND version = ?
				";
		$args = array(&$username, &$projectid, &$pagename, &$version);
		return $dpdb->SqlExecutePS($sql, $args);
	}

	public function Path() {
		return PageVersionPath($this->ProjectId(), $this->PageName(), $this->Version());

	}


	public function VersionText() {
		$projectid = $this->ProjectId();
		$pagename  = $this->PageName();
		$version   = $this->VersionNumber();
		return PageVersionText($projectid, $pagename, $version);
	}

	public function FixNormalize() {
        global $dpdb;
		$text = $this->VersionText();
		$text2 = norm($text);
		if($text == $text2) {
			return false;
		}
		file_put_contents($this->Path(), $text2);
        $projectid = $this->ProjectId();
        $pagename = $this->PageName();
        $vnum = $this->VersionNumber();
        $crc = crc32($text2);
        $textlen = mb_strlen($text2);
        $sql = "UPDATE page_versions
				SET crc32 = ?,
				    textlen = ?
				WHERE projectid = ?
				AND pagename = ?
				AND version = ?";
        $args = array(&$crc, &$textlen, &$projectid, &$pagename, &$vnum);
        $dpdb->SqlExecutePS($sql, $args);
        return true;
	}
}

/*
function AddPageVersionRows($projectid, $phase, $task, $state) {
	global $dpdb, $User;
	$username = $User->Username();
	$sql = "INSERT INTO page_versions
				(projectid, pagename, version, phase, task, username, state, version_time)
			SELECT projectid, pagename, version+1, ?, ?, ?, ?, UNIX_TIMESTAMP()
			FROM page_last_versions
			WHERE projectid = ?";
	$args = array(&$phase, &$task, &$username, &$state, &$projectid);
	$dpdb->SqlExecutePS($sql, $args);

}

function AddPhasePageVersionTexts($projectid, $phase) {
	global $dpdb;
	$sql = "SELECT projectid,
				   pagename,
		           version,
		           state
			FROM page_versions
			WHERE projectid = '$projectid'
				AND phase = '$phase'";
	$objs = $dpdb->SqlObjects($sql);
	foreach($objs as $pv) {
		$path = PageVersionPath($pv->projectid, $pv->pagename, $pv->version);
		$nextpath = PageVersionPath($pv->projectid, $pv->pagename, $pv->version+1);
		assert(is_file($path));
		assert( copy($path, $nextpath));
	}
}

function AddPageVersionTexts($projectid) {
	global $dpdb;
	$sql = "SELECT projectid,
				   pagename,
		           version,
		           state
			FROM page_last_versions
			WHERE projectid = '$projectid'";
	$objs = $dpdb->SqlObjects($sql);

	foreach($objs as $pv) {
		$path = PageVersionPath($pv->projectid, $pv->pagename, $pv->version);
		$nextpath = PageVersionPath($pv->projectid, $pv->pagename, $pv->version+1);
		assert(is_file($path));
		assert( copy($path, $nextpath));
	}
}
*/
