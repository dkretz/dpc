<?php
/*
    process flow when responding to an uploaded project zip file
    - location of uploaded zip file: $uploadpath
    - original name of (presumably a zip) file - $filename 
    - directory where unzipped files end up - $project->UploadPath()

*/

    ini_set("display_errors", true);
    error_reporting(E_ALL);

    $relPath = "./pinc/";
    require_once $relPath."dpinit.php";


    $projectid      = ArgProjectId();
    $username       = $User->Username();
    $release_holds  = ArgArray("releasehold");
	$setqchold      = IsArg("btnqchold");
	$qcholdnote     = Arg("qcholdnote");
	$clearqchold      = IsArg("btnqcclear");

    $User->IsLoggedIn()
        or RedirectToLogin();

    if(! $projectid) {
        die("No projectid provided.");
    }

    $project   = new DpProject($projectid);
    $project->Exists()
        or die("Project $projectid does not exist.");
    $project->UserMayManage() || $User->MaySetHold("qc")
        or die("Security violation!");

	if($setqchold && ! $project->IsQCHold()) {
		$project->SetQCHold($qcholdnote);
		$project->Refresh();
	}
	if($clearqchold && $project->IsQCHold()) {
		$project->ReleaseQCHold();
		$project->Refresh();
	}
    if(count($release_holds) > 0) {
        foreach($release_holds as $holdid => $dummy) {
            $project->ReleaseHoldId($holdid);
        }
    }
    $rows = $dpdb->SqlRows("
            SELECT  ph.id as holdid,
                    ph.phase,
                    ph.hold_code,
                    ph.set_by,
                    ph.note,
                    DATE(FROM_UNIXTIME(ph.set_time)) set_time,
                    ph.phase
                    -- p.username AS pm,
                    -- hr.role_code,
                    -- ur.id,
                    -- ph.hold_code = 'user' AND ph.set_by = '$username' AS is_my_user_hold,
                    -- ph.hold_code = 'pm' AND p.username = '$username' AS is_my_pm_hold
            FROM project_holds ph
            LEFT JOIN hold_types ht ON ph.hold_code = ht.hold_code
            JOIN projects  p ON ph.projectid = p.projectid
            -- LEFT JOIN hold_roles hr ON ph.hold_code = hr.hold_code
            -- LEFT JOIN user_roles ur ON hr.role_code = ur.role_code
                -- AND ur.username = '$username'
            WHERE ph.projectid = '$projectid'
            GROUP BY ph.id
            ORDER BY ht.sequence, ph.set_time");
    
    $tbl = new DpTable("tblholds", "minitab lfloat dark_border margined padded");
    $tbl->AddColumn("<Hold type", "hold_code");
    $tbl->AddColumn("<Phase", "phase");
    $tbl->AddColumn("<Set by", "set_by", "epm");
    $tbl->AddColumn("^", "set_time");
    $tbl->AddColumn("^Options", "holdid", "eoptions");
    $tbl->AddColumn("^Note", "note");

    $tbl->SetRows($rows);

    theme("Holdmaster - " . $project->Title(), "header");
    // DpHeader("FileMaster - ".$project->Title(), $args);

    echo "<p>".link_to_project($projectid, "Return to Project page")."</p>";
    echo "<p>".link_to_project_manager("Return to Project Manager page")."</p>";
	$qcprompt = "";
	if($project->UserMayManage() || $User->MaySetHold("qc")) {
		if(! $project->IsQCHold()) {
			$qcprompt = "<input type='submit' type='submit' name='btnqchold' id='btnqchold' value='Set QC Hold'>
			Hold Note: <input type='text' name='qcholdnote' id='qcholdnote' value = '$qcholdnote'><br>'";
		}
//		else {
//			$qcholdnote = $project->QCHoldNote();
//			$qcprompt = "<input type='submit' name='btnqcclear' id='btnqcclear' value='Release QC Hold'>
//			<br>Note: $qcholdnote";
//		}
	}

    echo "
  <div class='pagetitle'> {$project->TitleAuthor()} </div>
	<form name='workform' id='workform' method='POST' action=''>
    <br>
    $qcprompt
    <br>
  <pre style='font: inherit'>
  Hold Types

  QC = Quality Control Hold.
        Automatically created for PREP phase when project created.
        These Holds are released by an Inspector.
        Project cannot exit PREP until this Hold released.
  PM = Project Manager Hold.
        Automatically created for PREP phase when project created. PM releases this hold to indicate the project
            can be inspected by QC (but subject to other constraints, e.g. Clearance.)
        Automatically created for P1 phase when project created. PM releases this hold to make project visible
            to Queue Manager.
        Released by the Project Manager.
  User = User Hold.
        Released by the user who set it.
        Available to PMs for their own projects, and administrators.
        There are no automatic User Holds.
  Queue = Queue Hold.
        Released by the P1 Queue Manager.
        Automatically created for P1 phase when project created. (It's dormant through PREP.)

  </pre>
  <p>Warning: Restrictions are not in place yet, so you may be able to release
  holds you aren't responsible for. Please ask first if you're not sure. User
  holds that you orignally set are safe. If you are the PM, pm holds are safe.</p>

  <div id='divwork'>
    <div class='center' id='divworkform'>\n";

        $tbl->EchoTable();
    echo "
  </div> <!-- divwork -->
  </form> <!-- divworkform -->
  </div>\n";

    theme("", "footer");
    exit;

    function epm($username) {
        return link_to_pm($username);
    }

    function eoptions($holdid, $row) {
        global $User;
        global $project;
        $code = $row['hold_code'];
        $user = $row['set_by'];
        if($code == "user") {
            return $User->NameIs($user) || $User->IsSiteManager()
                ? link_to_release_holdid($holdid)
                : "";
        }
        if($code == "pm") {
            return $User->NameIs($project->ProjectManager()) || $User->IsAdmin()
                ? link_to_release_holdid($holdid)
                : "";
        }

        return  $User->MayReleaseHold($code)
            ? link_to_release_holdid($holdid)
            : "";
    }

    function link_to_release_holdid($holdid) {
        return "<input type='submit' name='releasehold[$holdid]' value='Release'>\n";
    }
