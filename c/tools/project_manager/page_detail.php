<?php
$relPath="./../../pinc/";
require_once $relPath."dpinit.php";

$projectid          = Arg("projectid")
    or die("No Projectid");

$username = Arg("username", $User->Username());

//$show_image_size    = Arg("show_image_size", 0);
$select_by_user     = Arg("select_by_user");
$is_my_pages        = IsArg("select_by_user");
//$chkfile            = ArgArray("chkfile");

/** @var $User DpThisUser */
$User->IsLoggedIn()
    or redirect_to_home();

$project = new DpProject($projectid);



// ====================================================================================
// display
// ====================================================================================

$title = $project->Title();
$page_title = _('Page details: ').$title;

$args = array("css_data" => "
        .package { text-align: center; }
        .shrink-to-fit { padding: .5em 2.5em; display: inline-block; }
        .lfloat { float: left; }
        .rfloat { float: right;
        .mypagedone {border: 1px solid red; color: red;}
        }\n");

theme($page_title, "header", $args);

echo "
    <div class='w25 left'>"
//     .link_to_project($projectid, "Return to project page")
     ."<br>
    ".($is_my_pages
        ? link_to_page_detail($projectid, "Show all pages")
        : link_to_page_detail_mine($projectid, "Show your pages"))."
    </div>
    <div class='package'>
    <h1>$title</h1>

    <form name='pagesform' id='pagesform' method='POST'>
    <input type='hidden' id='username' name='username' value='$username' />'
    <input type='hidden' id='projectid' name='projectid' value='{$projectid}' />
    <input type='hidden' id='pagename' name='pagename' value='' />";

/*
    echo "
    <div class='shrink-to-fit'>\n";
if($project->UserMayManage()) {
	echo "
        <input type='button' value='Clear selected'  onclick='eClearPages()'
            title='Forget work done in this round and set text to result of previous round' />
        <input type='button' value='Delete selected' onclick='eDeletePages()'
            title='Remove selected pages entirely from the project' />\n";
}
	echo "
        <input type='button' value='View logs' onclick='eViewLogs()'
            title='View event log for all pages in this project' />
    </div>\n";
*/

echo_pagetable($project, $is_my_pages, $username);
echo "
    </form>
</div> <!-- package -->
</table> <!-- hanging tag from theme -->\n";

html_footer();
exit;

function echo_pagetable($project, $is_my_pages, $username) {
	/** @var $project DpProject */
	$tblrows = page_table_rows( $project, $is_my_pages, $username );

//	echo_js( $tblrows );

	$tbl = new DpTable( "tblpages dptable em90" );

//    if($project->UserMayManage()) {
//        $tbl->AddCaption(null, 4);  // leave one for the rownumber?
//        $tbl->AddColumn(chk_caption(), null, "rowchkbox", "width: 4em");
//    }
//    else {
        $tbl->AddCaption(null, 3);
//    }

    $tbl->AddColumn("^"._("Page"), "pagename", "imagelink", "skinny");
    $tbl->AddColumn("^"._("OCR"), "pagename", "mastertextlink");
    $tbl->AddColumn("^"._("Status"), "versionstate", "eState", "w4em");

	$projphase = $project->Phase();

    if($projphase != "PREP") {
        $i = 0;
        foreach(array("P1", "P2", "P3", "F1", "F2") as $phase) {
            $i++;
            $tbl->AddCaption("^".$phase, $project->UserMayManage() ? 4 : 3, "center");
            $tbl->AddColumn("^"._("Diff"), $phase, "ediff");
            $tbl->AddColumn("^"._("Date"), $phase, "ePhaseTime");
            if($project->UserMayManage()) {
                $tbl->AddColumn("<"._("User"), "$phase", "usrlnk", "nopad");
            }
            $tbl->AddColumn(">"._("Text"), $phase, "textlink");
            if($phase == $project->phase())
                break;
        }
    }

	if($project->IsInRounds()) {
		$tbl->AddCaption("^");
		$tbl->AddColumn("^Edit", "pagename", "eedit");
	}
    $tbl->SetRows($tblrows);
    $tbl->EchoTableNumbered();
}

//function chk_caption() {
//    return "All <input type='checkbox' name='ckall'
//                onclick='CheckAll();'>";
//}

function page_table_rows($project, $is_my_pages, $username) {
    global $dpdb;

    /** @var $project DpProject */
	$projectid = $project->ProjectId();

	$user_clause = $is_my_pages ? "
		AND EXISTS (SELECT 1 FROM page_versions
                WHERE projectid = pg.projectid AND pagename = pg.pagename
                    AND username = '$username')
		" : "";

	$sql = "
		SELECT
			p.username pm,
			pg.projectid,
			pg.pagename,
			pg.imagefile,
			plv.state versionstate,
			plv.phase pagephase,
			plv.username lastuser,
		    phv.phase,
			phv.version,
			pv.username,
			DATE_FORMAT(FROM_UNIXTIME(pv.version_time), '%b-%e-%y %H:%i') version_time,
			pv.crc32,
			pv.textlen,
			COUNT(pvu.id) is_user_page

		FROM pages pg

		JOIN projects p ON pg.projectid = p.projectid

		LEFT JOIN page_last_versions plv
		ON pg.projectid = plv.projectid
			AND pg.pagename = plv.pagename

		LEFT JOIN page_versions pvu
		ON pg.projectid = pvu.projectid
			AND pg.pagename = pvu.pagename
			AND pvu.username = '$username'

		LEFT JOIN (
				SELECT 	projectid,
						pagename,
						PHASE,
						MAX(VERSION) VERSION
				FROM page_versions
				GROUP BY projectid, pagename, PHASE
		) phv
		ON pg.projectid = phv.projectid
	    	AND pg.pagename = phv.pagename
		LEFT JOIN page_versions pv
        ON phv.projectid = pv.projectid
            AND phv.pagename = pv.pagename
            AND phv.phase = pv.phase
            AND phv.version = pv.version

		LEFT JOIN phases ON phv.phase = phases.phase

		WHERE pg.projectid = '$projectid'
		$user_clause
		GROUP BY pv.projectid, pv.pagename, phv.phase
		ORDER BY pg.pagename, phases.sequence
	";


	/*
	 *      pagename (phase volume proofer time)(phase volume proofer time)(phase volume proofer time)
	 *      $a['pagename']  name => $name
	 *                          phaase[PREP] => [usernname => fred, version => 0, time => 0, crc => 0, len => 0)
	 */
    $rows = $dpdb->SqlRows($sql);


	$a= array();
	foreach($rows as $row) {
		extract($row, EXTR_OVERWRITE);
		/**  @var $projectid String */
		/**  @var $pagename String */
		/**  @var $imagefile String */
		/**  @var $phase String */
		/**  @var $lastuser String */
		/**  @var $version Int */
		/**  @var $version_time String */
		/**  @var $crc32  String*/
		/**  @var $textlen  String*/
		/**  @var $versionstate  String*/
		/**  @var $pagephase  String*/
		/**  @var $pm  String*/
		$a[$pagename]["pm"]                      = $pm;
		$a[$pagename]["projectid"]               = $projectid;
		$a[$pagename]["pagename"]                = $pagename;
		$a[$pagename]["imagefile"]               = $imagefile;
		$a[$pagename]["versionstate"]            = $versionstate;
		$a[$pagename]["pagephase"]               = $pagephase;
		$a[$pagename]["lastuser"]               = $lastuser;
		$a[$pagename][$phase]["phase"]           = $phase;
		$a[$pagename][$phase]["version"]         = $version;
		$a[$pagename][$phase]["version_time"]    = $version_time;
		$a[$pagename][$phase]["username"]        = $username;
		$a[$pagename][$phase]["crc32"]           = $crc32;
		$a[$pagename][$phase]["textlen"]         = $textlen;
	}

    return $a;
}

//function xrowchkbox($pagerow) {
//    $name = 'chkfile['.$pagerow['pagename'].']';
//    return "<input type='checkbox' name='$name'>";
//}

function imagelink($pagename, $pagerow) {
    $projectid  = $pagerow['projectid'];

    return link_to_view_image($projectid, $pagename, $pagename);
}

function eedit($pagename, $pagerow) {
	global $User;
	$projectid = $pagerow['projectid'];
	//    $phase = $row["phase"];
	//    $usrname = $row[rounduserfield($phase)];
	$proofer = $pagerow['lastuser'];
	if(lower($proofer) == lower($User->Username())
	   || lower($pagerow['pm']) == lower($User->Username()) || $User->IsSiteManager()) {
		return link_to_proof_page($projectid, $pagename, "Edit", true);
	}
	return "";
}

function ephasetime($phase) {
	return $phase["version_time"];
}

function mastertextlink($pagename, $pagerow) {
//    global $pm_url;
    $projectid = $pagerow['projectid'];
	$pagelen  = $pagerow['PREP']['textlen'];
    return link_to_page_text($projectid, $pagename, "PREP", $pagelen, true);
}

function textlink($phase, $pgrow) {
	$prompt = $phase['textlen'];
//    global $User;
//    $idx = substr($idx, 1);
//    $phase = RoundIdForIndex($idx);
    $projectid = $pgrow['projectid'];
    $pagename = $pgrow['pagename'];
	$version = $phase['version'];
	return link_to_version_text($projectid, $pagename, $version, $prompt, true);
//    $key = "textlength_{$idx}";
//    if(! isset($pagerow[$key]))
//        return "";
//	 c/textsrv.php
//    $url = url_for_page_text($projectid, $pagename, $roundid);
//    $userval = $pagerow["user_{$rindex}"];
//    $myclass = ($User->NameIs($userval)
//            ? " class='red'"
//            : " ");
//    return "<a $myclass target='_blank' href='$url'>".$pagerow[$key]."</a>";
}

function eState($state) {
    switch($state) {
        case "A":
            return "<div class='pg_available'>Avail</div>\n";

        case "B":
            return "<div class='danger'>Bad</div>\n";

        case "O":
            return "<div class='pg_out'>Chk Out</div>\n";

        case "C":
            return "<div class='pg_completed'>Done</div>\n";

        default:
            return $state;
    }
}

//function roundtime($val) {
//    return $val == 0
//        ? ""
//        : strftime( "%x %H:%M", intval($val) );
//}

function xclearlink($pagerow) {
    $pgstate = $pagerow['pageroundstate'];  // e.g. P2a.page_avail
    if(right($pgstate, 10) == "page_avail") {
        return "";
    }
    return "<span id='{$pagerow['pagename']}'
                    class='likealink'>"._("Clear")."</a>";
}

function xfixlink($pagerow) {
    global $pm_url;
    $fix = _("Fix");
    $url = "$pm_url/handle_bad_page.php"
            ."?projectid={$pagerow['projectid']}"
            . "&chkfile={$pagerow['imagefile']}";
    return "<a target='_blank' href='{$url}'>$fix</a>";
}

function ediff($phase, $pagerow) {
	switch($phase["phase"]) {
		case "PREP":
			$phase0 = null;
			break;
		case "P1":
			$phase0 = $pagerow["PREP"];
			break;
		case "P2":
			$phase0 = $pagerow["P1"];
			break;
		case "P3":
			$phase0 = $pagerow["P2"];
			break;
		case "F1":
			$phase0 = $pagerow["P3"];
			break;
		case "F2":
		default:
			$phase0 = $pagerow["F1"];
			break;
	}
	$isdiff = ($phase["crc32"] != $phase0["crc32"]);
	if(! $isdiff) {
		return "";
	}
	$projectid = $pagerow['projectid'];
	$pagename = $pagerow['pagename'];
	$phasecode = $phase["phase"];
	return link_to_diff($projectid, $pagename, $phasecode, "Diff", "1", true);
}

function xpageroundid($pagerow) {
    return preg_replace("/\..*$/", "", $pagerow['pageroundstate']);
}

function usrlnk($phase) {
    global $User;
	global $projphase;

	$proofername = $phase['username'];
//    $proofername = $page[$key];
    // index for current round

    if($proofername == "")
        return "";

    if(! $User->NameIs($proofername)) {
        return link_to_pm($proofername);
        // return "<div class='notmypage'>$proofername</div>";
    }

	// from this point proofer is current user
    if($projphase != $phase["phase"]) {
        return "<div class='pg_saved'>$proofername</div>";
    }

    // based on page state ...
    switch($phase) {
        case "O":
            return "<div class='pg_out'>$proofername</div>";
        case "C":
            return "<div class='pg_saved'>$proofername</div>";
        default:
            return "<div class='mystery'>$proofername</div>";
    }
}

function echo_js($rows) {
//    $num_rows = count($rows);
    
    ?>
<script type='text/javascript'>

    var _cks;

    function $(id) {
        return document.getElementById(id);
    }

    var avail_pages = [
    <?php
    foreach($rows as $row) {
        if(pagestate($row) == 'avail') {
            echo "'{$row['pagename']}', ";
            // echo "avail_pages.push('{$row['pagename']}')\n";
        }
    }
    ?>];
    var i;

    function checked_count() {
        var n = 0;
        var pgchecks = $('tblpages').getElementsByTagName('input');
        for(var i = 0; i < pgchecks.length; i++) {
            if(pgchecks[i].checked) {
                n++;
            }
        }
        return n;
    }

    function eDeletePages() {
        var n = checked_count();
        if(n == 0) {
            return false;
        }
        if(! window.confirm('Confirm you want to delete page '
                                    + n.toString() + ' pages')) {
            return false;
        }
        $('pagesform').submit();
        return true;
    }

    function eClearPages() {
        var n = checked_count();
        if(n == 0) {
            return false;
        }
        if(! window.confirm('Confirm you want to clear '
                                + n.toString() + ' pages')) {
            return false;
        }
        $('pagesform').submit();
        return true;
    }

    function eViewLogs() {
        $('pagesform').target = '_blank';
        $('pagesform').action = 'pagelog.php';
        $('pagesform').submit();
        return true;
    }

    function eDeletePage(name) {
        if(! window.confirm('Confirm you want to delete page '  + name)) {
            return false;
        }
        $('pagename').value = name;
        $('todo').value = 'deletePage';
        $('pagesform').submit();
        return true;
    }

    function eClearPage(name) {
        if(! window.confirm('Confirm you want to clear page '  + name)) {
            return false;
        }
        $('pagename').value = name;
        $('todo').value = 'clearPage';
        $('pagesform').submit();
        return true;
    }

    function ePageLog(name) {
        $('pagename').value = name;
        $('todo').value = 'clearPage';
        $('pagesform').submit();
    }

    function eFormClick(e) {
        if(! e) e = window.event;
        var t = e.target;
        if(t.innerHTML == 'Delete') {
            return eDeletePage(t.id);
        }
        else if(t.innerHTML == 'Clear') {
            return eClearPage(t.id);
        }
        else if(t.innerHTML == 'Page log') {
            return ePageLog(t.id);
        }
        return true;
    }

    function CheckAll() {
        if(document.pagesform.ckall.checked) {
            eSelectAll();
        }
        else {
            eClearAll();
        }
    }

    function SetCheckboxes(val) {
        val = val ? 'checked' : '';
        var pgchecks = $('tblpages').getElementsByTagName('input');
        for(var i = 0; i < pgchecks.length; i++) {
            pgchecks[i].checked = val;
        }
    }

    function eSelectAll() {
        SetCheckboxes(true);
    }

    function eClearAll() {
        SetCheckboxes(false);
    }

</script>
<?php
}

// vim: sw=4 ts=4 expandtab
?>


