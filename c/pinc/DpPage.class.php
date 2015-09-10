<?PHP
/*
 * Redesign - revise all references to textField
 */

ini_set("display_errors", true);
error_reporting(E_ALL);

if(!isset($relPath))
    $relPath = "./";

require_once $relPath . "DpProject.class.php";
require_once $relPath . "rounds.php";
require_once $relPath . "DpVersion.class.php";

// PageStatus values

define("PAGE_AVAIL", "page_avail");
define("PAGE_OUT",   "page_out");
//define("PAGE_TEMP",  "page_temp");
define("PAGE_SAVED", "page_saved");
define("PAGE_BAD",   "page_bad");

class DpPage
{
    protected   $_row;
    protected   $_projectid;
    protected   $_pagename;
    protected   $_project;
	protected   $_versions;
	protected   $_version;

    function __construct($projectid, $pagename) {
        global $dpdb;
        if(! $projectid) {
            die( "Projectid argument omitted in DpPage." ) ;
        }
        $this->_projectid   = $projectid;
        // $this->_pagename = imagefile_to_pagename($filename);
        $this->_pagename = $pagename;
        if(! $this->_pagename) {
            die( "pagename argument omitted in DpPage ($projectid $pagename)" ) ;
        }
	    $this->_row = $dpdb->SqlOneRow(
		    "SELECT pg.projectid,
 					pg.pagename,
 					pg.imagefile,
 					pv.version last_version,
 					pv.phase,
 					pv.version_time,
 					pv.state,
 					pv.username,
					puv.version penultimate_version,
 					puv.phase penultimate_phase,
 					puv.version_time penultimate_version_time,
 					puv.state penultimate_state,
 					puv.username penultimate_username
		    FROM pages pg
		    JOIN projects p
		    	ON pg.projectid = p.projectid
		    JOIN page_last_versions pv
		    	ON pg.projectid = pv.projectid
		    	AND pg.pagename = pv.pagename
		    LEFT JOIN page_versions puv
		    	ON pv.projectid = puv.projectid
		    	AND pv.pagename = puv.pagename
		    	AND pv.version > puv.version
		    LEFT JOIN page_versions pv0
		    	ON pv.projectid = puv.projectid
		    	AND pv.pagename = puv.pagename
		    	AND pv.version > pv0.version
		    	AND puv.version < pv0.version
			WHERE pg.projectid = '$projectid'
				AND pg.pagename = '$pagename'
				AND pv0.id IS NULL");
//	    )

//	    $this->_version = new DpVersion($this->ProjectId(), $this->PageName(), $this->_row['last_version']);

	    /*
	     *  change following to : $this->_refresh_row();
	     */

//        if($dpdb->IsTable($projectid)) {
//            $this->_refresh_row();
//        }
//        else {
//            $this->_row = null;
//        }
    }

    private function _refresh_row() {
        global $dpdb;
        $projectid = $this->_projectid;
        $pagename  = $this->_pagename;

        $sql = sql_project_page($projectid, $pagename);
        $this->_row = $dpdb->SqlOneRow($sql);
    }

    public function ProjectId() {
        return $this->_projectid;
    }

    public function ImageFile() {
        return $this->Exists() 
            ? $this->_row['imagefile']
            : "";
    }
    public function Image() {
        return $this->ImageFile();
    }

    public function LinkToView($prompt = "View Image") {
        $prompt = _($prompt);
		return link_to_view_image($this->ProjectId(), $this->PageName(), $prompt);
    }

    public function ImageFilePath($filename = "") {
        return $filename == ""
            ? build_path($this->ProjectPath(),
                            $this->ImageFileName())
            : build_path($this->ProjectPath(), $filename);
    }

    public function IsImageFile() {
        return $this->ImageFileName() != ""
            && file_exists($this->ImageFilePath());
    }

    public function ProjectUrl() {
        global $site_url;
        return $site_url."/projects/".$this->ProjectId();
    }

    public function ProjectPath() {
        return ProjectPath($this->ProjectId());
    }

    public function PM() {
        return $this->ProjectManager();
    }

/*
    public function IsLanguageCode($code) {
        return in_array($code, $this->LanguageCodeArray());
    }
*/
    public function LanguageCode() {
        $p = $this->project();
        return $p->LanguageCode();
    }

    public function RevertToTemp() {
        $this->_refresh_row();
    }

	/*
    public function RevertToOriginal() {
        global $dpdb;
	    global $User;

		if(! $this->ActiveUserIsEditor() && ! $this->UserIsPM() && ! $User->IsSiteManager()) {
            assert(false);
            return;
        }
        $projectid  = $this->_projectid;
        $roundid    = $this->RoundId();
        $pagename   = $this->PageName();
        $textfld    = $this->TextField();
        $from_textfld    = TextFieldForRoundId(PreviousRoundIdForRoundId($this->RoundId()));
        $timefld    = $this->TimeField();
        $state      = $roundid . "." . PAGE_OUT;
        $sql = "
            UPDATE $projectid
            SET ? = ?,
                $timefld = NULL,
                state = ?
            WHERE pagename = '$pagename'";
        $args = array(&$textfld, &$from_textfld, &$state);
        $dpdb->SqlExecutePS($sql, $args);
        $this->_refresh_row();
        $this->RecalcProjectPageCounts();
        // $this->LogPageEvent(PG_EVT_REVERT);
        // dp code doesn't log reverts
    }


    public function ReplaceTextPrevious($text) {
        global $dpdb;

        // strip off blank lines at end
        $idx = $this->ActiveRoundIndex();
        if($idx <= 0) {
            assert(false);
            return;
        }
        $text = preg_replace("/\t/us", "    ", $text);
        $text = preg_replace("/ +?$/m", "", $text);
        $text = preg_replace("/[\n\r\s]+\Z/m", "", $text);
        $projectid = $this->ProjectId();
        $pagename = $this->PageName();
        $textfld = TextFieldForRoundIndex($idx - 1);
        
        $dpdb->SqlExecute("
            UPDATE $projectid
            SET $textfld = '$text'
            WHERE pagename = '$pagename'");

        // $this->LogPageEvent(PG_EVT_REPLACE_TEXT);
    }
    */

    public function CanBeReverted() {
        return $this->IsSaved();
    }

    public function CheckoutTime() {
        $ret = $this->_row['checkouttime'] ;
        return $ret ;
    }

    public function CompletionTime() {
        $ret = $this->_row['completiontime'] ;
        return $ret ;
    }

    public function PageStatus() {
	    return $this->State();
//        if(! isset($this->_row['page_state'])) {
//            return "";
//        }
//        $ary = preg_split("/\./", $this->_row['page_state']);
//        if(count($ary) < 2) {
//            return "";
//        }
//        return $ary[1];
    }

    public function PageState() {
	    return $this->Version()->State();
//        return $this->RoundState();
    }
    public function RoundState() {
        return $this->PageRoundState();
    }

    public function PageRoundState() {
        return sprintf("%s.%s", 
                $this->RoundId(), $this->PageStatus());
    }

//    public function ProjectRoundState() {
//        $p = $this->project();
//        return $p->State();
//    }

    protected function project() {
        if(!$this->_project) {
            $this->_project = new DpProject($this->ProjectId());
        }
        return $this->_project;
    }

    public function Phase() {
        $p = $this->project();
        return $p->Phase();
    }

    public function RoundIndex() {
        return RoundIndexForId($this->RoundId());
    }

    public function RoundId() {
	    return $this->Phase();
    }

    public function PrevPhase() {
        switch($this->Phase()) {
            case "PREP":
                return null;
            case "P1":
                return "PREP";
            case "P2":
                return "P1";
            case "P3":
                return "P2";
            case "F1":
                return "P3";
            case "F2":
                return "F1";
            case "PP":
                return "F2";
            case "PPV":
                return "PP";
            case "POSTED":
                return "PPV";
            default:
                return "POSTED";
        }
    }

    public function PrevRoundId() {
        $idx = RoundIndexForId($this->RoundId());
        return RoundIdForIndex($idx - 1);
    }

    public function RoundIndexText($index) {
        if($index == 0) {
            $colname = "master_text";
        }
        else {
            $colname = sprintf("round%d_text", $index);
        }
        return $this->_row[$colname];
    }

    protected function PrevUser() {
	    $index = $this->LastVersionNumber();
	    return $this->Version($index-1)->Username();
//        $idx = RoundIndexForId($this->RoundId());
//        if($idx <= 0)
//            return "";
//        return $this->RoundUser(RoundIdForIndex($idx-1));
    }

//    public function PrevText() {
//	    return $this->LastVersion()->PreviousText();
//        switch($this->Phase()) {
//            case "PREP":
//            case "P1":
//                return $this->PhaseText("PREP");
//            case "P2":
//                return $this->PhaseText("P1");
//            case "P3":
//                return $this->PhaseText("P2");
//            case "F1":
//                return $this->PhaseText("P3");
//            case "F2":
//                return $this->PhaseText("F1");
//            default:
//                return $this->PhaseText("F2");
//        }
//        $idx = RoundIndexForId($this->RoundId());
//        if($idx <= 0)
//            return "";
//        return $this->RoundText(RoundIdForIndex($idx-1));
//    }

	public function RoundText($roundid) {
		return $this->PhaseText($roundid);
    }

	public function PhaseText($phase) {

	    $projectid = $this->ProjectId();
	    $pagename = $this->PageName();
//	    $sql = "
//	        SELECT version FROM page_versions
//	        WHERE projectid = '$projectid'
//	        AND pagename = '$pagename'
//	        AND phase = '$phase'";
//		$version = $dpdb->SqlOneValue($sql);
		$vnum = $this->PhaseVersionNumber($phase);
		return PageVersionText($projectid, $pagename, $vnum);
    }

	public function PhasePreviousText($phase) {

		$projectid = $this->ProjectId();
		$pagename = $this->PageName();
		$version = $this->PhaseVersion($phase) - 1;
		if($version < 0) {
			return "";
		}
		return PageVersionText($projectid, $pagename, $version);
	}

    public function Text() {
        return $this->ActiveText();
    }

	/*
    private function CopyTextForward() {
        global $dpdb;
        $projectid = $this->ProjectId();

        $from = TextFieldForPhase($this->PrevPhase());
//        $from = TextFieldForRoundId($this->PrevRoundId());
        $to = TextFieldForPhase($this->Phase());
//        $to   = TextFieldForRoundId($this->RoundId());
        $dpdb->SqlExecute("
            UPDATE projects
            SET $to = $from
            WHERE projectid = '$projectid'");
        $this->_refresh_row();
    }
	*/

    public function ActiveText() {
	    return $this->LastVersionText();
//	    return $this->LastVersion()->Text();
//        $phase = $this->Phase();
//        $text = $this->PhaseText($phase);
//        if($text == "") {
//            $text = $this->PrevText();
//            if($text != "") {
//                $this->CopyTextForward();
//            }
//        }
//        return $text;

//        switch($this->PageStatus()) {
//            case PAGE_TEMP:
//            case PAGE_SAVED:
//                $rtntext = $this->RoundText($roundid);
//                break;
//            case PAGE_AVAIL:
//            case PAGE_OUT:
//            case PAGE_BAD:
//                $rtntext = $this->RoundText($prevroundid);
//                break;
//            default:
//                die("Status error: ".$this->PageStatus()
//                ." in ActiveText() page ".$this->PageName());
//        }
//        return $rtntext;
    }

    public function ActiveHtmlText() {
        return h($this->ActiveText());
    }

    public function NextRoundText() {
        return $this->RoundText($this->ActiveRoundIndex() + 1);
    }

    private function ActiveRoundIndex() {
        return RoundIndexForId($this->RoundId());
    }

/*
    public function TextLines() {
        if(! $this->_lines) {
            $this->_lines = text_lines($this->ActiveText());
        }
        return $this->_lines;
    }

    public function LineCount() {
        return count($this->TextLines());
    }
*/

    public function NameOfWork() {
        $p = $this->project();
        return $p->NameOfWork();
    }

    public function AuthorsName() {
        $p = $this->project();
        return $p->AuthorsName();
    }

    public function InitImageFromPath($path) {
        $topath = $this->ImageFilePath();
        copy($path, $topath);
    }

    public function ImageFileName() {
        if(count($this->_row) == 0) {
            return "";
        }
        return $this->_row['imagefile'];
    }

    public function ImageFileSize() {
        return 
        $this->IsImageFile()
            ? filesize($this->ImageFilePath())
            : 0 ;
    }

    public function ImageUrl() {
        return build_path($this->ProjectUrl(), $this->ImageFileName());
    }

    public function PrevImageUrl() {
        global $dpdb;
        $sql = "SELECT MAX(pagename) FROM pages
                WHERE projectid = '{$this->ProjectId()}'
                	AND pagename < '{$this->PageName()}'";
        $pagename = $dpdb->SqlOneValue($sql);
        return url_for_page_image($this->ProjectId(), $pagename);
    }
    public function NextImageUrl() {
        global $dpdb;
        $sql = "SELECT MIN(pagename) FROM pages
                WHERE projectid = '{$this->ProjectId()}'
                	AND pagename > '{$this->PageName()}'";
        $pagename = $dpdb->SqlOneValue($sql);
        return url_for_page_image($this->ProjectId(), $pagename);
    }
    public function Exists() {
        return count($this->_row) > 0;
    }

    public function PageName() {
        return $this->_pagename;
    }
//    public function MakeThumbnail() {
//        //'find' the source image
//        assert(file_exists($this->ImageFilePath()));
//        $src_img = ImageCreateFrompng($this->ImageFilePath());
//
//        //Get original image width and height
//        $src_width = imagesx($src_img);
//        $src_height = imagesy($src_img);
//
//        //Our target output width
//        //(image will scale down completely to this width)
//        $dest_width = 125;
//
//        //Calculate our output height proportionally
//        $dest_height = $src_height * $dest_width / $src_width;
//
//        // create a shell image
//        $dest_img = imagecreate($dest_width, $dest_height);
//
//        imagecopyresampled($dest_img, $src_img,
//                            0, 0, 0 ,0,
//                            $dest_width, $dest_height,
//                            $src_width, $src_height);
//
//        //write it out
//        imagepng($dest_img, $this->ThumbFilePath());
//
//        //clean up memory
//        imagedestroy($src_img);
//        imagedestroy($dest_img);
//    }
//
//    public function ThumbExists() {
//        $path = $this->ThumbFilePath();
//        return file_exists($path);
//    }

//    public function IsThumb() {
//        return substr($this->_path, 0, 5) === "thumb";
//    }
//
//    public function ThumbFileName() {
//        return "thumb.".$this->PageName().".png";
//    }
//
//    // url to thumbnail, optional to create if necessary
//    public function ThumbFilePath($ismake = false) {
//        $path = build_path($this->PagePath(), $this->ThumbFileName());
//        if((! file_exists($path)) && $ismake)
//            $this->MakeThumbnail();
//        return $path;
//    }
//
//    public function ThumbUrl($ismake = false) {
//        global $projects_url;
//        if(! $this->ThumbExists() && $ismake)
//            $this->MakeThumbnail();
//
//        return $projects_url
//            ."/".$this->ProjectId()
//            ."/".$this->PageName()
//            ."/".$this->ThumbFileName();
//    }

    public function ProjectComments() {
        $p = $this->project();
        return $p->Comments();
    }
    
    public function ProjectManager() {
        $p = $this->project();
        return $p->ProjectManager();
    }

    public function Title() {
        $p = $this->project();
        return $p->NameOfWork();
    }

    public function Author() {
        $p = $this->project();
        return $p->AuthorsName();
    }

    public function MasterText() {
        if(! $this->Exists() || $this->MaxVersionNumber() < 0) {
            return null;
        }
	    return $this->VersionText(0);
    }

	public function Delete() {
		global $dpdb;

		$projectid = $this->ProjectId();
		$pagename = $this->PageName();

		/*
		 * DELETE from project_pages WHERE projectid = '$projectid' AND pagename = '$pagename'
		 */
		$dpdb->SqlExecute( "
				DELETE FROM page_versions
				WHERE projectid = '$projectid' AND pagename = '$pagename'");

		// log it before the image is erased
		$this->LogDelete();
		$this->_refresh_row();
		// $this->LogPageEvent(PG_EVT_DELETE);
	}

    
    public function CheckOutPage() {
        global $User;
        global $dpdb;

        $projectid  = $this->ProjectId();
        $pagename   = $this->PageName();
        $username   = $User->Username();
        $phase      = $this->Phase();

        $sql = "
            UPDATE page_versions
            SET username = ?,
                version_time = UNIX_TIMESTAMP(),
                state = 'O'
            WHERE projectid = ?
             	AND pagename = ?
             	AND phase = ?";

	    $args = array(&$username, &$projectid, &$pagename, &$phase);
        $nrecs = $dpdb->SqlExecutePS( $sql, $args );
        $this->RecalcProjectPageCounts();
        $this->_refresh_row();
	    assert($this->State() == "O");
	    $this->LogCheckOut();
	    return $nrecs;
    }

    protected function TimeField() {
        return TimeFieldForRoundId($this->RoundId());
    }
    protected function UserField() {
        return UserFieldForRoundId($this->RoundId());
    }
    protected function TextField() {
        return TextFieldForRoundId($this->RoundId());
    }

    // Resume - only used to continue my own uncompleted page.
    // probably only DPItaly if at all
	/*
    public function ResumePage() {
        global $dpdb;
	    global $User;

		if(! $this->ActiveUserIsEditor() && ! $this->UserIsPM() && ! $User->IsSiteManager()) {
            assert(false);
            return;
        }

//        if( ! $this->IsSaved()) {
//            return;
//        }
        // if already out, just update the timestamp and drop an event

        $projectid  = $this->ProjectId();
        $pagename = $this->PageName();
        $timefield  = $this->TimeField();
        $roundid    = $this->RoundId();
        $outstate   = $roundid . "." . PAGE_OUT;

        $sql = "
                UPDATE $projectid
                SET $timefield = UNIX_TIMESTAMP(),
                    state = '$outstate'
                WHERE pagename = '$pagename'";
        $dpdb->SqlExecute($sql);
        $this->LogReOpen();
        $this->_refresh_row();
        $this->RecalcProjectPageCounts();
    }
	*/

    public function SaveText($text) {
	    global $Context;
	    return $Context->UpdateVersion($this->ProjectId(), $this->PageName(), $this->LastVersionNumber(), "O", $text);
//	    global $Context;
//	    $text = norm($text);
//	    $projectid = $this->ProjectId();
//	    $pagename  = $this->PageName();
//	    $version = $this->LastVersionNumber();
//
//	    $ret = $Context->PutPageVersionText( $projectid, $pagename, $version, $text );
//	    $this->Project()->MaybeAdvanceRound();
//	    return $ret;
    }

    public function SaveTemp($text) {
	    return $this->SaveText($text);
	}
//	    return $this->Version()->UpdateText($text);
//        global $User;
//        global $dpdb;
//
//        $projectid = $this->ProjectId();
//        $pagename  = $this->PageName();
//        $username  = $User->UserName();
//	    $phase     = $this->Phase();
//
//	    $text = rtrim($text);
//        $text = preg_replace("/\t/us", "    ", $text);
//        $text = preg_replace("/ +?$/sm", "", $text);
//        $text = preg_replace("/\s*[\n\r]+\Z/m", "", $text);
//
//		$sql = "UPDATE page_versions
//				SET text = ?,
//				version_time = UNIX_TIMESTAMP()
//				WHERE projectid = ?
//					AND pagename = ?
//					AND phase = ?
//					AND username = ?
//					AND state = 'O'";
//	    $args = array(&$text, &$projectid, &$pagename, &$phase, &$username);
//	    assert($ret = $dpdb->SqlExecutePS($sql, $args) == 1);
//
//        $this->LogSaveAsInProgress();
//        $this->RecalcProjectPageCounts();
//        $this->_refresh_row();
//        return $ret;
//    }

	public function LastVersion() {
		assert($this->_version);
		return $this->_version;
	}

	public function Version($vnum = -1) {
		if($vnum != $this->LastVersionNumber() && $vnum >= 0) {
			return new DpVersion($this->ProjectId(), $this->PageName(), $vnum);
		}
		if(! $this->_version) {
			$this->_version = new DpVersion( $this->ProjectId(), $this->PageName(), $this->LastVersionNumber() );
		}
		return $this->_version;
	}

	public function VersionText($vnum) {
		assert($vnum != "");
		return PageVersionText($this->ProjectId(), $this->PageName(), $vnum);
	}

    public function SaveAsDone($text) {
        global $User, $Context;

        if(! $this->ActiveUserIsEditor() && ! $this->UserIsPM() && ! $User->IsSiteManager()) {
            assert(false);
            return;
        }

//        $text       = rtrim($text);
//        $phase      = $this->Phase();

//        $text       = preg_replace("/\t/us", "    ", $text);
//        $text       = preg_replace("/ +?$/m", "", $text);
//        $text       = preg_replace("/\s*[\n\r]+\Z/m", "", $text);


	    $Context->UpdateVersion($this->ProjectId(), $this->PageName(), $this->LastVersionNumber(), "C", $text);
//	    $ret = $this->LastVersion()->SaveTextState("C", $text);
//	    $ret = $this->AddVersion($phase, $phase, $text);
//		$sql = "INSERT INTO page_versions
//                    SET projectid = ?,
//                    	pagename = ?,
//                    	phase = ?,
//                    	task = ?,
//                    	username = ?,
//                    	version_time = UNIX_TIMESTAMP()
//            		FROM page_versions v
//            		WHERE projectid = ?
//            			AND pagename = ?";
//
//		$args = array(&$text, &$projectid, &$pagename, &$phase, &$task, &$username, &$projectid, &$pagename );
//        $ret = $dpdb->SqlExecutePS($sql, $args);
//	    if($this->Project()->IsRoundCompleted()) {
//		    $this->Project()->MaybeAdvanceRound();
//		    $this->_refresh_row();
//	    }

        $this->LogSaveAsDone();
        //
        // Don't advance here
        // 
        // make a new project - old one may be out of date
        // $this->_project = new DpProject($this->ProjectId());
        // $this->_project->MaybeAdvanceRound();
    }

    public function ReturnToRound() {
        global $dpdb;
        $projectid  = $this->ProjectId();
        $pagename   = $this->PageName();
	    $phase      = $this->Phase();

        $sql = "
                UPDATE page_versions
                SET state = 'A',
                	version_time = UNIX_TIMESTAMP(),
                	username = NULL
                WHERE projectid = ?
                	AND pagename = ?
                	AND phase = ?";
	    $args = array(&$projectid, &$pagename, &$phase);
        $dpdb->SqlExecutePS($sql, $args);

        $this->LogReturnToRound();
        $this->RecalcProjectPageCounts();
    }

    private function RecalcProjectPageCounts() {
        $proj = $this->Project();
        $proj->RecalcPageCounts();
    }

	public function State() {
		return $this->_row['state'];
	}

    public function ActiveRoundUser() {
        global $User;
        $roundid = $this->RoundId();
        if($roundid == "PREP") {
            return $User->Username();
        }
	    switch($this->State()) {
		    case "O":
		    case "C":
				return $this->_row['username'];
//			    return $this->Version()->Username();
		    default:
			    return "";
	    }
//        $field = $this->UserField();
//        return $this->_row[$field];
    }

	public function Versions() {
		if(! $this->_versions) {
			$this->_versions = Versions($this->ProjectId(), $this->PageName());
		}
		return $this->_versions;
	}

    public function RoundUser($roundid = "") {
	    global $dpdb;
        if($roundid == "") {
            $roundid = $this->RoundId();
        }
	    $sql = "SELECT username FROM page_versions WHERE phase = '$roundid'";
	    return $dpdb->SqlOneValue($sql);
    }

	public function PhaseVersion($phase) {
		global $dpdb;
		$projectid = $this->ProjectId();
		$pagename = $this->PageName();

		$sql = "
			SELECT MAX(version) FROM page_versions
			WHERE projectid = '$projectid'
				AND pagename = '$pagename'
				AND phase = '$phase'";
		$vnum = $dpdb->SqlOneValue($sql);
		if(is_null($vnum)) {
			$vnum = $dpdb->SqlOneValue("
				SELECT version FROM page_last_versions
				WHERE projectid = '$projectid'
					AND pagename = '$pagename'");
		}
		return $this->Version($vnum);
	}

	public function PenultimateVersion() {
		static $_obj;
		if(! $_obj) {
			$_obj = new DpVersion($this->ProjectId(), $this->PageName(), $this->PenultimateVersionNumber());
		}
		return $_obj;
	}

	public function PenultimatePhase() {
		return $this->_row['penultimate_phase'];
//		return $this->PenultimateVersion()->Phase();
	}
	public function LastUsername() {
		return $this->_row['username'];
	}
	public function PenultimateUsername() {
		return $this->_row['penultimate_username'];
//		return $this->PenultimateVersion()->Username();
	}
	public function PenultimateVersionNumber() {
		return $this->_row['penultimate_version'];
	}
	public function PhaseVersionNumber($phase) {
		$vsn = $this->PhaseVersion($phase);
		return $vsn->VersionNumber();
	}
    public function UserIsOwner() {
        global $User;
        return lower($User->Username()) == lower($this->Owner());
    }
    
    public function Owner() {
	    return $this->LastUsername();
//        switch($this->PageStatus()) {
//	        case "C":
//	        case "O":
//                return $this->ActiveRoundUser();
//            default:
//                return null;
//        }
    }

    public function UserIsPM() {
        global $User;
        return lower( $User->UserName()) == lower($this->PM() ) ;
    }

	protected function MaxVersionNumber() {
		if(! isset($this->_row['maxversion'])) {
			return - 1;
		}
		return $this->_row['maxversion'];
	}

	public function LastVersionNumber() {
		global $dpdb;
		static $_vsn;
		if ( ! $_vsn ) {
			$projectid = $this->ProjectId();
			$pagename = $this->PageName();
			$_vsn = $dpdb->SqlOneValue( "
				SELECT MAX(version) FROM page_versions
				WHERE projectid = '$projectid'
					AND pagename = '$pagename'
				GROUP BY projectid, pagename" );
			}
			if(is_null($_vsn)) {
				$_vsn = -1;
			}
		return number_format($_vsn);
	}

    // the final user, regardless of current state
    public function LastUser() {
        return $this->_row['username'];
    }

    // the final text, regardless of current state
    public function LastVersionText() {
	    return PageVersionText($this->ProjectId(), $this->PageName(), $this->LastVersionNumber());
//	    return $this->LastVersion()->Text();
    }

	public function Proofers() {
		return implode(", ", $this->ProofersArray());
	}
    public function ProofersArray() {
        $ary = array();
	    /** @var DpVersion $version */
	    foreach($this->Versions() as $version) {
		    $ary[] = $version->Username();
	    }
	    array_shift($ary);
//        foreach(array("P1", "P2", "P3", "F1", "F2") as $rid) {
//            $usr = $this->RoundUser($rid);
//            if($usr != "") {
//                $ary[] = $usr;
//            }
//        }
        return $ary;
    }

    // May the current user select this page for editing?
    // Yes, checked out by them or saved by them in the current round.
    public function MayBeSelectedByActiveUser() {
        global $User;
	    $msgs = array();
	    if(! $this->ActiveUserIsEditor()) {
		    $msgs[] = "?Page is owned by " . $this->Owner() . " and you are " . $User->Username();
		    return $msgs;
	    }
	    return $this->ActiveUserIsEditor() || $this->UserIsPM() || $User->IsSiteManager() ;
    }

    public function UserMayManage() {
        $p = $this->project(); 
        return $p->UserMayManage();
    }

    public function MayBeMarkedBadByActiveUser() {
	    return true;
    }

    public function ActiveUserIsEditor() {
        global $User;
	    return lower($this->Owner()) == lower($User->Username())
//        return
//            lower($this->RoundUser($this->RoundId())) == lower($User->Username())
            && ($this->IsSaved() || $this->IsCheckedOut());
    }

    public function BadReporter() {
        return $this->_row['b_user'];
    }

    // the current user may be assigned this page - ie
    // the page is available, and the user is not the prior user,
    // and the user is qualified,
    protected function IsAvailableForActiveUser() {
        global $User;
        if(! $this->IsAvailable())
            return false;
        // shouldn't need this - shouldn't be available
        if(preg_match("/^PREP/", $this->Phase()))
            return false;
        if(lower($User->Username()) == lower($this->PrevUser())) {
            return false;
        }
        return true;
    }

    /*
    public function BadReporterUserName() {
        return $this->_row['b_user'];
    }
    */

    public function PageHoldCount() {
        return 0;
    }

    public function ProjectHoldCount() {
        $proj = $this->project();
        return $proj->ActiveHoldCount();
    }

    public function HoldCount() {
        return $this->PageHoldCount()
            + $this->ProjectHoldCount();
    }

    public function IsOnHold() {
        return $this->HoldCount() > 0;
    }

    protected function IsAvailable() {
        switch($this->Phase()) {
            case "P1":
            case "P2":
            case "P3":
            case "F1":
            case "F2":
                return ! $this->IsOnHold();
                break;

            default:
                break;
        }
        return false;
    }
    protected function IsCheckedOut() {
        return $this->PageStatus() == "O";
    }

//    protected function IsSaveTemp() {
//        return $this->PageStatus() == PAGE_TEMP;
//    }
    public function IsSaved() {
        return $this->PageStatus() == "C";
    }
    public function IsBad() {
        return $this->PageStatus() == "B";
    }

    public function MarkBad($reason = "none") {
        global $User;
        global $dpdb;


        $projectid = $this->ProjectId();
        $pagename  = $this->PageName();
	    $phase     = $this->Phase();
	    $username  = $User->UserName();
//        $badstate  = $this->RoundId() . "." . PAGE_BAD;

        $dpdb->SqlExecute( "
                UPDATE page_versions
                SET state = 'B'
                WHERE projectid = '$projectid'
                	AND pagename = '$pagename'
                	AND phase = '$phase'");

//	    $isreason = $dpdb->IsTableColumn($this->ProjectId(), "b_resson");
//        if($isreason) {
//            $dpdb->SqlExecute("
//                UPDATE $projectid
//                SET b_reason = '$reason'
//                WHERE pagename = '{$this->PageName()}'");
//        }

        $this->LogMarkAsBad($reason);
        // $this->LogPageEvent( PG_EVT_SET_BAD );
         $this->SendBadPageEmail($username, $reason);
    }

    private function SendBadPageEmail($username, $reason) {
        global $Context;
        $Context->SendUserEmail($this->PM(),
            "DP Canada "
            ._("notification {$this->Title()} - page marked bad"),
            _("
To the project manager for ".$this->Title().",

User ".$username." has marked page " .$this->PageName()
." as a 'bad page' in this project.

Reason: {$reason}

Thanks!

The Administration"));
    }

/*
    private function SetPageAvailable($status) {
        global $dpdb;
        $projectid = $this->ProjectId();
        $pagename = $this->PageName();
        $roundid = $this->RoundId();
        $pagestate = "{$roundid}.".PAGE_AVAIL;
        $dpdb->SqlExecute("
            UPDATE $projectid
            SET state = '$pagestate'
            WHERE projectid = '$projectid'
                AND pagename = '$pagename'");
    }

    public function ClearBad() {
        global $dpdb;
        $projectid = $this->ProjectId();
        $pagename = $this->PageName();
        $roundid =  $this->RoundId();
        $pagestate = $roundid . "." . PAGE_AVAIL;
        $dpdb->SqlExecute( "
                UPDATE $projectid
                SET b_user = NULL,
                    state      = '$pagestate'
                WHERE pagename = '$pagename'");
        $this->LogEraseBadMark();
        $this->RecalcProjectPageCounts();
        // $this->LogPageEvent( PG_EVT_CLEAR_BAD );

    }
*/

//    public function ClearRound() {
//        $this->Clear($this->RoundId());
//    }

    public function Clear() {
	    switch ( $this->Phase() ) {
		    case "P1":
		    case "P2":
		    case "P3":
		    case "F1":
		    case "F2":
			    $this->ClearPhase( $this->Phase() );
			    break;
		    case "PP":
			    $this->ClearPP();
			    break;
		    default:
			    // can't clear
			    return;
	    }
    }

	// Unselected pages keep their round and status; which if the project is reverted
    //      means they hold in place until it's this round again.
    // example: p3.available will still be p3 available while the project is backed up in p2.
    // If the page is available in the current round and is cleared,
    //      we need to set it back to checked out to the proofer in the previous round.
    //     example: p3.available beomes p2.checked-out.
    //      and revert the project to the previous round.
    // If the page is checked out or saved in the current round,
    //      we need to set it back to checked out in the previous round.
	// Example: P2 can be cleared while the project is in P2 or P3.
	//      Or I suppose P1 if we've backed it up that far. But we can't consequentially
	//      clear subsequent rounds, so it doesn't make sense to clear P2 if the project is in P3.
    private function ClearPhase($phase) {
        global $dpdb;
	    global $Context;

	    $proj = $this->project();
	    $projphase = $proj->Phase();
	    if($Context->PhaseSequence($projphase) < $Context->PhaseSequence($phase)){
		    $proj->RevertPhase();
//		    $state = $phase . "." . PAGE_OUT;
	    }
//	    else {
//		    $state = $phase . "." . PAGE_AVAIL;
//	    }

        $projectid = $this->ProjectId();
	    $pagename  = $this->PageName();
	    $phase     = $this->Phase();
        $sql = "
            UPDATE page_versions
            SET state = 'A',
            	username = NULL,
            	version_time = NULL
            WHERE projectid = ?
            	AND pagename = ?
            	AND phase = ?
            	AND state != 'A'";
	    $args = array(&$projectid, &$pagename, &$phase);
        $dpdb->SqlExecutePS($sql, $args);
        $this->LogClearRound($phase);
        $this->RecalcProjectPageCounts();
    }

	// special case to return to pre-PP Phase
	// making the final Version available again.
	private function ClearPP() {
		global $dpdb;

		$projectid  = $this->ProjectId();
		$pagename   = $this->PageName();
		$phase      = $this->Phase();
		$sql = "
            UPDATE page_versions
            SET version_time UNIX_TIMESTAMP(),
            	state = 'O'
            WHERE projectid = ?
            	AND pagename = ?
            	AND phase = ?
            	AND state = 'C'";
		$args = array(&$projectid, &$pagename, &$phase);
		$dpdb->SqlExecutePS($sql, $args);
		$this->LogClearRound("PP");
		$p = $this->project();
		$p->RevertPhase(false);
		$this->RecalcProjectPageCounts();
	}

    private function log_page_event($event_code, $phase) {
	    global $dpdb, $User;

	    $projectid = $this->ProjectId();
	    $pagename = $this->PageName();
	    $version  = $this->LastVersionNumber();
	    $username = $User->Username();

	    $sql = "
        INSERT INTO page_events (
         	event_time,
            projectid,
            pagename,
            version,
            event_type,
            username,
            phase
		)
		values(UNIX_TIMESTAMP(), ?, ?, ?, ?, ?, ?)";

	    $args = array( &$projectid, &$pagename, &$version, &$event_code, &$username, &$phase);
	    return $dpdb->SqlExecutePS($sql, $args);
    }

	public static function dp_log_page_event( $projectid, $pagename, $event_type,
								$username, $phase, $version = null, $remark = null ) {
		global $dpdb;

		$sql = "
        INSERT INTO page_events (
         	event_time,
            projectid,
            pagename,
            version,
            event_type,
            username,
            phase,
            remark
		)
		values(UNIX_TIMESTAMP(), ?, ?, ?, ?, ?, ?, ?)";
		$args = array(
			&$projectid,
			&$pagename,
			&$version,
			&$event_type,
			&$username,
			&$phase,
			&$remark );
		$dpdb->SqlExecutePS($sql, $args);
	}
    protected function LogEraseBadMark() {
        $this->log_page_event( "eraseBadMark", $this->RoundId());
    }
    protected function LogMarkAsBad() {
        $this->log_page_event( "markAsBad", $this->RoundId());
    }
    protected function LogClearRound($roundid) {
        $this->log_page_event( "clearRound", $roundid );
    }
    protected function LogReclaim() {
        $this->log_page_event( "reclaim", $this->RoundId());
    }
    protected function LogReturnToRound() {
        $this->log_page_event( "returnToRound", $this->RoundId());
    }
    protected function LogReOpen() {
        $this->log_page_event( "reopen", $this->RoundId());
    }
    protected function LogSaveAsDone() {
        $this->log_page_event( "saveAsDone", $this->RoundId());
    }
    protected function LogSaveAsInProgress() {
        $this->log_page_event( "saveAsInProgress", $this->RoundId());
    }
    protected function LogCheckOut() {
        $this->log_page_event( "checkout", $this->RoundId());
    }
    protected function LogModifyText() {
        $this->log_page_event( "modifyText", null);
    }
    protected function LogReplaceText() {
        $this->log_page_event( "replaceText", null);
    }
    protected function LogReplaceImage() {
        $this->log_page_event( "replaceImage", null);
    }
    protected function LogDelete() {
        $this->log_page_event( "delete", null);
    }
    protected function LogAdd() {
        $this->log_page_event( "add", null);
    }


    /*
    protected function LogPageEvent($event_type, $note = "") {
        global $dpdb;
        global $User;

        $username = $User->Username();
        assert($username != "");

        $dpdb->SqlExecute("
            INSERT INTO page_events
            SET event_time = UNIX_TIMESTAMP(),
                projectid  = '{$this->ProjectId()}',
                page_name  = '{$this->PageName()}',
                username   = '$username',
                task_code  = '{$this->RoundId()}',
                event_type = '$event_type',
                note       = ".SqlQuote($note));
    }

    protected function LogPageLoadEvent($event_type, $note = "") {
        global $dpdb;

        $dpdb->SqlExecute("
            INSERT INTO page_events
            SET event_time = UNIX_TIMESTAMP(),
                projectid  = '{$this->ProjectId()}',
                page_name  = '{$this->PageName()}',
                username   = 'extern_load',
                task_code  = '{$this->RoundId()}',
                event_type = '$event_type',
                note       = ".SqlQuote($note));
    }
    */

/*
    private function LogPageError($errmsg, $comment) {
        global $dpdb;
        $dpdb->SqlExecute("
            INSERT INTO page_errors
            SET eventtime = UNIX_TIMESTAMP(),
                errormessage = '$errmsg',
                comment = '$comment'");
    }
*/

    // accept a new text version from UI, save temp, and check it.
    public function WordCheckText($langcode, $text) {
        // spellcheck the submitted text
//        $this->SaveText($text);
        // break page text into lines

        $ptn = array("/</", "/>/");
        $rpl = array("&lt;", "&gt;");
        $text = preg_replace($ptn, $rpl, $text);

        $tlines = text_lines($text);

        // collect lists
	    $fwa = $this->FlagWordsArray($langcode);
        $swa = $this->SuggestedWordsArray($langcode);
        $bwa = $this->BadWordsArray($langcode);

        // for each line
        $nwc = $nwcs = $nwcb = 0;
        for($i = 0; $i < count($tlines); $i++) {
            $tline = $tlines[$i];
            //
            $rwo = RegexStringsOffsets($tline, $langcode);
            if(count($rwo) == 0) {
                continue;
            }
            for($j = count($rwo)-1; $j >= 0; $j--) {
                list($wd, $locn) = $rwo[$j];
                if(! in_array($wd, $fwa)) {
                    continue;
                }
                if(in_array($wd, array("sc", "hr", "b", "f", "g"))) {
                    continue;
                }
                if(in_array($wd, $swa)) {
                    $nwcs++;
                    $class = "wcs";
                }
                else if(in_array($wd, $bwa)) {
                    $nwcb++;
                    $class = "wcb";
                }
                else {
                    $nwc++;
                    $class = "wc";
                }
                $tline = bleft($tline, $locn)
                    ."<span id='wc_{$i}_{$j}'"
                    ." class='{$class}'>" .$wd."</span>"
                    .bmid($tline, $locn + strlen($wd));
            }
            $tlines[$i] = $tline;
        }
        return array($nwc, $nwcs, $nwcb, implode("\n", $tlines));
    }


    public function AcceptWordsArray($langcode, $acceptwords) {
        $p = $this->project();
        $p->SuggestWordsArray($langcode, $acceptwords);
    }
    public function FlagWordsArray($langcode) {
        /** @var $p DpProject */
        $p = $this->project();
        return $p->FlagWordsArray($langcode);
    }

    public function SuggestedWordsArray($langcode) {
        /** @var $proj DpProject */
        $proj = $this->project();
        return $proj->SuggestedWordsArray($langcode);
    }

    public function BadWordsArray($langcode) {
        /** @var $proj DpProject */
        $proj = $this->project();
        return $proj->BadWordsArray($langcode);
    }
}

// A new page to be added from scratch

class DpProtoPage extends DpPage
{
    private $_extimgfilepath;
    private $_exttextfilepath;
//    private $_extimgfile;
//    private $_exttextfile;

    public function IsExternalImageFile() {
        return file_exists($this->_extimgfilepath);
//        $f = $this->ExternalImageFile();
//        return $f ? $f->Exists() : false;
    }

    public function IsExternalTextFile() {
        return file_exists($this->_exttextfilepath);
//        $f = $this->ExternalTextFile();
//        return $f ? $f->Exists() : false;
    }

    public function SetExternalImageFilePath($f) {
        $this->_extimgfilepath = $f;
    }

    public function SetExternalTextFilePath($f) {
        $this->_exttextfilepath = $f;
    }

    public function Dispose() {
        if($this->IsExternalImageFile() ||  $this->IsExternalTextFile()) {
            $this->DisposeExternalFiles();
        }
        else {
            $this->Delete();
        }
    }

    public function DisposeExternalFiles() {
        $this->DisposeExternalImageFile();
        $this->DisposeExternalTextFile();
    }

    public function DisposeExternalTextFile() {
        if( $this->IsExternalTextFile()) {
            unlink($this->ExternalTextFilePath());
        }
    }

    private function DisposeExternalImageFile() {
        if($this->IsExternalImageFile()) {
            unlink($this->ExternalImageFilePath());
        }
//        $f = $this->ExternalImageFile();
//        if( ! is_null($f)) {
//            if(file_exists($f->filePath())) {
//                unlink($f->FilePath());
//            }
//        }
    }

    public function ExternalTextFilePath() {
        return $this->_exttextfilepath;
//        if(isset($this->_exttextfile)) {
//            return $this->_exttextfile;
//        }
//        $path = build_path($this->UploadPath(),
//                            $this->PageName().".txt");
//
//        if(file_exists($path)) {
//            $this->SetExternalTextFile(new DpFile($path));
//            return $this->_exttextfile;
//        }
//        return null;
    }

    public function ExternalImageFilePath() {
        return $this->_extimgfilepath;
//        if(isset($this->_extimgfile)) {
//            return $this->_extimgfile;
//        }
//
//        $path = build_path($this->UploadPath(), $this->ImageFile());
//        if(file_exists($path)) {
//            $this->SetExternalImageFile(new DpFile($path));
//            return $this->_extimgfile;
//        }
//
//        return null;
    }

//    public function ExternalImageFilePath() {
//        $f = $this->ExternalImageFile();
//        return $f ? $f->FilePath() : "";
//    }

    public function ExternalImageFileName() {
        return basename($this->ExternalImageFilePath());
//        $f = $this->ExternalImageFile();
//        return $f ? $f->FileName() : "";
    }

//    private function UploadPath() {
//        return ProjectUploadPath($this->ProjectId());
//    }

//    public function ExternalImageUrl() {
//        $f = $this->ExternalImageFile();
//        return $f ? $f->Url() : "";
//    }

    public function ExternalTextFileName() {
        return basename($this->ExternalTextFilePath());
//        $f = $this->ExternalTextFile();
//        return $f ? $f->FileName() : "";
    }

//    public function ExternalTextFilePath() {
//        $f = $this->ExternalTextFile();
//        return $f ? $f->FilePath() : "";
//    }

    public function UploadedTextFilePath() {
        return $this->ExternalTextFilePath();
    }

    public function IsExternalText() {
        return $this->IsExternalTextFile();
//        return $f ? $f->Exists() : false;
    }

    public function ExternalImageFileSize() {
        return filesize($this->ExternalImageFilePath());
//        $f = $this->ExternalImageFile();
//        return $f ? $f->Size() : "";
    }

	private function remove_utf8_bom($text) {
		$bom = pack('H*','EFBBBF');
		$text = preg_replace("/^$bom/", '', $text);
		return $text;
	}

    public function ExternalText() {
        $text = file_get_contents($this->ExternalTextFilePath());
	    $text = $this->remove_utf8_bom($text);
	    $text = preg_replace("/\t/", " ", $text);
	    return $text;
    }

	/*
	 * Needs to be rewritten entirely
	 */
	public function SetImageFile($path) {
		if(! file_exists($path)) {
			return;
		}
		$imgpath = $this->ImageFilePath();
		if(file_exists($imgpath)) {
			unlink($imgpath);
		}
		$topath = $this->ImageFilePath($path);
		copy($path, $topath);
	}

//    public function ReplaceText() {
//	    $this->AddOrReplace();
//    }

//    public function AddOrReplace() {
//        if($this->Exists()) {
//            if($this->IsExternalImageFile()) {
//                $this->ReplaceImageFile($this->ExternalImageFilePath());
//            }
//
//            if($this->IsExternalText()) {
//                $this->ReplaceTextFile($this->ExternalTextFilePath());
//            }
//        }
//        else {
//            $this->AddPage();
//        }
//    }

//    public function ReplaceImageFile($path) {
//    }
//	public function ReplaceTextFile($path) {
//	}

    public function ImagePath() {
        return $this->ImageFile() == ""
            ? ""
            : build_path($this->ProjectPath(), $this->ImageFile());
    }


}


// end DpProtoPage

function sql_project_page($projectid, $pagename) {
    return "
        SELECT
            pg.projectid,
            pg.pagename,
            pg.imagefile,
            pv.state,

            pv.username,
            pv.version_time,
            pv.version,
            pv.phase

        FROM pages pg

        JOIN projects p
        	ON pg.projectid = p.projectid

        LEFT JOIN page_versions pv
        	ON pg.projectid = pv.projectid
        	AND pg.pagename = pv.pagename
        	AND p.phase     = pv.phase

        WHERE pg.projectid = '$projectid'
        	AND pg.pagename = '{$pagename}'";
}

/*
function sql_project_page2($projectid, $pagename) {
	return "
        SELECT
            '$projectid' AS projectid,
            pagename,
            pagename,
            imagefile AS image,
            imagefile,
            status,

            round1_user,
            round2_user,
            round3_user,
            round4_user,
            round5_user,

            round1_time,
            round2_time,
            round3_time,
            round4_time,
            round5_time

        FROM project_pages
        WHERE projectid = '$projectid'
        	AND pagename = '{$pagename}' ";
}
*/

function dp_log_page_event( $projectid, $pagename, $event_type, $phase, $version = null, $remark = null ) {
    global $dpdb, $User;

	$username = $User->Username();

	$sql = "
        INSERT INTO page_events (                     k
         	event_time,
            projectid,
            pagename,
            version,
            event_type,
            username,
            phase,
            remark
		)
		values(UNIX_TIMESTAMP(), ?, ?, ?, ?, ?, ?, ?)";

	$args = array( &$projectid, &$pagename, &$version, &$event_type, &$username, &$phase, &$remark);
	$dpdb->SqlExecutePS($sql, $args);

}


function imagefile_to_pagename($str) {
    return preg_replace("/\.[^\.]*$/", "", $str);
}

function RegexStringsOffsets(&$text ) {
    $ptn = "~".UWR."~iu";
    preg_match_all($ptn, $text, $m, PREG_OFFSET_CAPTURE);
    return $m[0];
}

function RegexStringOffsets($str, &$text ) {
    preg_match_all($str, $text, $m, PREG_OFFSET_CAPTURE);
    return $m[0];
}


// Class Versions - nothing but statics - virtual namespace


