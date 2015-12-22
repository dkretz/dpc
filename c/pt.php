<?php
global $relPath;

/*
 * Note that this table comprises a <form> which submits to "edit_pages.php",
 * which in turn diverts back to project.php
 */
function echo_page_table( $project ) {
    /** @var DpProject $project */
    global $code_url;
    global $dpdb;

    $projectid = $project->ProjectId();
    $projphase = $project->Phase();

    // This project may have skipped some rounds, and/or it may have rounds
    // yet to do, so there may be some round-columns with no data in them.
    // Figure out which ones to display.
    //

    if($project->UserMayManage()) {
        echo "
        <form id='pagesform' name='pagesform' method='post'
                action='$code_url/tools/project_manager/edit_pages.php'>
            <input type='hidden' name='projectid' value='$projectid'>\n";
    }


    // Top header row

    $tbl = new DpTable("tblpages", "dptable bordered em90 margined");

    // Bottom header row

//    if ($project->UserMayManage()) {
//        $tbl->AddCaption("^", 1, "center b-left b-bottom b-top");
//        $tbl->AddColumn("^X", "pagename", "echeck", "b-left b-bottom");
//    }
    $tbl->AddCaption("^", 4, "center b-left b-bottom b-top");
    $tbl->AddColumn("^Page", "pagename", "epage");
	$tbl->AddColumn("^Page<br />state", "state", "estate", "b-right");
    $tbl->AddColumn("^Image", "imagefile", "eimage");
    $tbl->AddColumn(">OCR<br>Text", "P0", "etext", "b-right");

    $colclass = false;
	if($projphase != "PREP") {
		foreach(array("P1", "P2", "P3", "P4", "P5") as $phase) {
			$colclass = ! $colclass;
			// Note: for AddCaption, the third aargument, class, can bd executable.
			// If it is, the !caption text! executes it.
			$tbl->AddCaption(ephasecaption($phase), 4, "b-all center");
			$tbl->AddColumn("^Diff", $phase, "ediff", "b-left");
			$tbl->AddColumn("^Date", $phase, "edate", "em80");
			$tbl->AddColumn("<User", $phase, "euser");
			$tbl->AddColumn(">Text", $phase, "etext", "b-right");
			if(lower($phase) == lower($projphase)) {
				break;
			}
		}
	}

	$ncol = 0;
    if($project->IsInRounds()) {
	    $ncol++;
        $tbl->AddColumn("^Edit", "pagename", "eedit", "b-right");
    }

    if ($project->UserMayManage()) {
        $tbl->AddColumn("^Clear", "pagename", "eclear", "b-right");
        $ncol ++;

        if( ($projphase == 'PREP' || $project->IsInRounds())) {
            $ncol++;
            $tbl->AddColumn("^Delete", "pagename", "edelete", "b-right");
        }
    }
	if($ncol > 0) {
		$tbl->AddCaption("Manage", $ncol, "center b-all");
	}


    $sql = table_sql($projectid);
    echo html_comment($sql);
    $rows = $dpdb->SqlRows( table_sql($projectid));

	if(count($rows) < 1) {
		echo "<h3>No pages in this project yet.</h3>\n";
		return;
	}

	foreach($rows as &$row) {
//		dump($row);
		$projectid = $row['projectid'];
		$pagename = $row['pagename'];
		$P0_version = $row['P0_version'];
		$P1_version = $row['P1_version'];
		$P2_version = $row['P2_version'];
		$P3_version = $row['P3_version'];
		$P4_version = $row['P4_version'];
		$P5_version = $row['P5_version'];
		$P0_text = PageVersionText($projectid, $pagename, $P0_version);
		$P1_text = PageVersionText($projectid, $pagename, $P1_version);
		$P2_text = PageVersionText($projectid, $pagename, $P2_version);
		$P3_text = PageVersionText($projectid, $pagename, $P3_version);
		$P4_text = PageVersionText($projectid, $pagename, $P4_version);
		$P5_text = PageVersionText($projectid, $pagename, $P5_version);
		$row['P1_diff'] = (rtrim($P0_text) != rtrim($P1_text));
		$row['P2_diff'] = (rtrim($P1_text) != rtrim($P2_text));
		$row['P3_diff'] = (rtrim($P2_text) != rtrim($P3_text));
		$row['P4_diff'] = (rtrim($P3_text) != rtrim($P4_text));
		$row['P5_diff'] = (rtrim($P4_text) != rtrim($P5_text));
	}
    $tbl->SetRows($rows);
    $tbl->EchoTable();
    if($project->UserMayManage()) {
        echo "</form>  <!-- pagesform -->\n";
    }
}

function ephasecaption($pfx) {
	switch($pfx) {
		case "P1":
		case "P2":
		case "P3":
			return $pfx;
		case "P4":
			return "F1";
		case "P5":
			return "F2";
		default:
			return "";
	}
}
function echeck($pagename) {
    return "<input type='checkbox' name='chk[$pagename]' />";
}

function efix($pagename, $row) {
    $projectid = $row["projectid"];
    return link_to_view_image($projectid, $pagename, "Upload", true);
}

function epage($pagename, $row) {
    $image = $row["imagefile"];
    return "<div class='bold' title='$image'>$pagename</div>\n";
}

function eimage($imagefile, $row) {
    /** @var DpPage $page */
    $projectid = $row["projectid"];
	$pagename = $row["pagename"];
    $size = ProjectImageFileSize($projectid, $imagefile);
    return link_to_view_image($projectid, $pagename, $size, true);
}

function emaster($len, $row) {
    $projectid = $row['projectid'];
    $pagename = $row['pagename'];
    return link_to_page_text($projectid, $pagename, "PREP", $len, true);
}

function estate($state) {
	switch($state) {
		case "A":
			return "avail";
		case "O":
			return "chk out";
		case "C":
			return "done";
		case "B":
		default:
			return "bad";
	}
}

//function roundtextlenfield($roundid) {
//    return "{$roundid}_length";
//}

// diff is asking about diff with preceding phase
function ediff($phase, $row) {
	switch($phase) {
		case "PREP":
			return "";
		case "P4":
			$phase2 = "F1";
			break;
		case "P5":
			$phase2 = "F2";
			break;
		default:
			$phase2 = $phase;
			break;
	}
	if($row[$phase.'_state'] == 'A') {
		return "";
	}
	$projectid = $row['projectid'];
	$pagename = $row['pagename'];
	return $row[$phase.'_diff']
		? link_to_diff($projectid, $pagename, $phase2, "Diff", "1", true)
			: "";
}
function edate($phase, $row) {
	if($row[$phase.'_state'] == 'A') {
		return "";
	}
	return $row[$phase.'_time'];
}

function color_class($roundid, $phase, $state) {
    if($phase != $roundid) {
        return "pg_unavailable";
    }
    if($state == $roundid . ".page_out") {
        return "pg_out";
    }
    return "pg_completed";
}

function euser($phase, $row) {
    global $User;
//
	if($row[$phase.'_state'] == 'A') {
		return "";
	}
	if($phase == "PREP") {
		return "";
	}
	$phase_user = $row[$phase . '_user'];
	$projphase = $row['project_phase'];
	$privacy = $row[$phase . "_privacy"];
    $state = $row[$phase . "_state"];
    $username = $User->Username();

	switch($projphase) {
		case "F1":
		case "F2":
		case "PP":
		case "PPV":
		case "POSTED":
			$class = "em80 ";
			break;
		default:
			$class = "em100 ";
			break;
	}

    if($privacy) {
        return "<span class='$class'>Anon</span>";
    }

    if(lower($phase_user) != lower($username)) {
        return "<span class='$class'>" . link_to_pm($phase_user, $phase_user, true) . "</span>";
    }

    $class = $class . color_class($phase, $phase, $state);
    return "<div class='$class'>$phase_user</div>";
}

function etext($phase, $row) {
	if($row[$phase.'_state'] == 'A') {
		return "";
	}
	$vsn = $row[$phase.'_version'];
	$version = number_format($vsn);
	if($version == "") {
		return "";
	}
    $projectid = $row['projectid'];
    $pagename = $row['pagename'];
	$text = PageVersionText($projectid, $pagename, $version);
	$lenstr = mb_strlen($text);
    return link_to_version_text($projectid, $pagename, $version, $lenstr, true);
}
function eclear($pagename) {
    return "<input type='submit' name='submit_clear[$pagename]' value='Clear' />\n";
}
function eedit($pagename, $row) {
    global $User;
    $projectid = $row['projectid'];
	$proofer = $row['proofer'];
    if(lower($proofer) == lower($User->Username())
            || lower($row['pm']) == lower($User->Username()) || $User->IsSiteManager()) {
        return link_to_proof_page($projectid, $pagename, "Edit", true);
    }
    return "";
}
function edelete($pagename) {
    return "<input type='submit' name='submit_delete[$pagename]' value='Delete' />\n";
}



// -----------------------------------------------------------------------------

function table_sql($projectid) {
    return "
			SELECT DISTINCT
			pg.projectid,
			pg.pagename,
			pg.imagefile,
			p.phase project_phase,
			p.username AS pm,
			pvlast.version,
			FROM_UNIXTIME(pvlast.version_time) version_time,
			pvlast.state,
			pvlast.username proofer,

			pv0.state P0_state,
	        pv1.state P1_state,
	        pv2.state P2_state,
	        pv3.state P3_state,
	        pv4.state P4_state,
	        pv5.state P5_state,

			pv0.version P0_version,
	        pv1.version P1_version,
	        pv2.version P2_version,
	        pv3.version P3_version,
	        pv4.version P4_version,
	        pv5.version P5_version,

	        pv0.crc32 = pv1.crc32 P1_diff,
	        pv1.crc32 = pv2.crc32 P2_diff,
	        pv2.crc32 = pv3.crc32 P3_diff,
	        pv3.crc32 = pv4.crc32 P4_diff,
	        pv4.crc32 = pv5.crc32 P5_diff,

			FROM_UNIXTIME(pv1.version_time, '%m-%d-%y %H:%i') P1_time,
			FROM_UNIXTIME(pv2.version_time, '%m-%d-%y %H:%i') P2_time,
			FROM_UNIXTIME(pv3.version_time, '%m-%d-%y %H:%i') P3_time,
			FROM_UNIXTIME(pv4.version_time, '%m-%d-%y %H:%i') P4_time,
			FROM_UNIXTIME(pv5.version_time, '%m-%d-%y %H:%i') P5_time,

			pv1.username P1_user,
			pv2.username P2_user,
			pv3.username P3_user,
			pv4.username P4_user,
			pv5.username P5_user,

			pv0.textlen P0_textlen,
			pv1.textlen P1_textlen,
			pv2.textlen P2_textlen,
			pv3.textlen P3_textlen,
			pv4.textlen P4_textlen,
			pv5.textlen P5_textlen,

			u1.u_privacy P1_privacy,
			u2.u_privacy P2_privacy,
			u3.u_privacy P3_privacy,
			u4.u_privacy P4_privacy,
			u5.u_privacy P5_privacy,

			urp1.page_count P1_page_count,
			urp2.page_count P2_page_count,
			urp3.page_count P3_page_count,
			urp4.page_count P4_page_count,
			urp5.page_count P5_page_count

			FROM projects p

			JOIN pages pg
				ON p.projectid = pg.projectid

	    	JOIN page_last_versions pvlast
	    		ON pg.projectid = pvlast.projectid
				AND pg.pagename = pvlast.pagename

			LEFT JOIN page_versions pv0 ON pg.projectid = pv0.projectid
				AND pg.pagename = pv0.pagename AND pv0.phase = 'PREP'
			LEFT JOIN page_versions pv1 ON pg.projectid = pv1.projectid
				AND pg.pagename = pv1.pagename AND pv1.phase = 'P1'
			LEFT JOIN page_versions pv2 ON pg.projectid = pv2.projectid
				AND pg.pagename = pv2.pagename AND pv2.phase = 'P2'
			LEFT JOIN page_versions pv3 ON pg.projectid = pv3.projectid
				AND pg.pagename = pv3.pagename AND pv3.phase = 'P3'
			LEFT JOIN page_versions pv4 ON pg.projectid = pv4.projectid
				AND pg.pagename = pv4.pagename AND pv4.phase = 'F1'
			LEFT JOIN page_versions pv5 ON pg.projectid = pv5.projectid
				AND pg.pagename = pv5.pagename AND pv5.phase = 'F2'

			LEFT JOIN users u1 ON pv1.username = u1.username
			LEFT JOIN users u2 ON pv2.username = u2.username
			LEFT JOIN users u3 ON pv3.username = u3.username
			LEFT JOIN users u4 ON pv4.username = u4.username
			LEFT JOIN users u5 ON pv5.username = u5.username

			LEFT JOIN total_user_round_pages urp1
				ON urp1.phase= 'P1' AND urp1.username = pv1.username

			LEFT JOIN total_user_round_pages urp2
				ON urp2.phase = 'P2' AND urp2.username = pv2.username

			LEFT JOIN total_user_round_pages urp3
				ON urp3.phase = 'P3' AND urp3.username = pv3.username

			LEFT JOIN total_user_round_pages urp4
				ON urp4.phase = 'F1' AND urp4.username = pv4.username

			LEFT JOIN total_user_round_pages urp5
				ON urp5.phase = 'F2' AND urp5.username = pv5.username

		   WHERE p.projectid = '$projectid'
		   GROUP BY p.projectid, pg.pagename
		   ORDER BY pg.pagename ASC";
}


