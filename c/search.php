<?php

error_reporting(E_ALL);
$relPath = "./pinc/";
require_once $relPath."dpinit.php";

$User->IsLoggedIn()
	or RedirectToLogin();

$qtitle         = Arg("qtitle");
$qauthor        = Arg("qauthor");
$qpm            = Arg("qpm");
$qpp            = Arg("qpp");
$qlang          = ArgArray("qlang");
$qgenre         = ArgArray("qgenre");
$qroundid       = ArgArray("qroundid");
$qstatus        = Arg("qstatus", "both");
$orderby        = Arg("orderby", "nameofwork");
$desc           = ArgBoolean("desc", "1");

$pagenum        = Arg("pagenum", "1");
$rowsperpage    = Arg("rowsperpage", "100");
$cmdPgUp        = IsArg("cmdPgUp");
$cmdPgDn        = IsArg("cmdPgDn");
$dosearch       = IsArg("dosearch");

$sql            = "";
$nprojects      = 0;

if($dosearch || $cmdPgUp || $cmdPgDn) {
	$fmt = "<span id='%s'>%s</span>\n";
	$titleCaption   = sprintf($fmt, "lktitle",    _("Title"));
	$authorCaption  = sprintf($fmt, "lkauthor",   _("Author"));
	$langCaption    = sprintf($fmt, "lklang",     _("Lang"));
	$projidCaption  = sprintf($fmt, "lkprojid",   _("Project"));
	$genreCaption   = sprintf($fmt, "lkgenre",    _("Genre"));
	$pmCaption      = sprintf($fmt, "lkpm",       _("Proj Mgr"));
	//	$ppCaption      = sprintf($fmt, "lkpp",       _("Post Proofer"));
	$diffCaption    = sprintf($fmt, "lkdiff",     _("Difficulty"));
	$stateCaption   = sprintf($fmt, "lkphase",    _("State"));

	$tbl = new DpTable("tblsearch", "dptable w90");
	$tbl->AddColumn("<".$titleCaption,          "title",            "title_link");
	$tbl->AddColumn("<".$authorCaption,         "author");
	$tbl->AddColumn("^".$langCaption,           null,               "all_langs");
	$tbl->AddColumn("^".$genreCaption,          "genre");
	$tbl->AddColumn("^"._("avail<br>total"),    null,               "page_counts", "nosort");
	$tbl->AddColumn("<".$pmCaption,             "project_manager",  "pmlink");
	//	$tbl->AddColumn("<".$ppCaption,             "postproofer",      "pmlink");
	//	$tbl->AddColumn("^PPV",                     "ppverifier",       "pmlink");
	$tbl->AddColumn("^".$diffCaption,           "difficulty");
	$tbl->AddColumn("^".$stateCaption,          "phase",    null,   "sortkey=sequence");
	$tbl->AddColumn(_("^Edit"),                 null,               "edit_link", "nosort");
	//    $tbl->AddColumn("^".$projidCaption, "projectid");

	$awhere = array("p.phase != 'DELETED'");

	if($qtitle) {
		$qqtitle    = mysql_real_escape_string($qtitle);
		$qsql = "(p.nameofwork LIKE '%$qqtitle%')";
		$awhere []  = $qsql;
	}

	if($qauthor) {
		$qqauthor   = mysql_real_escape_string($qauthor);
		$qsql = "(p.authorsname LIKE '%$qqauthor%')";
		$awhere []  = $qsql;
	}


	if(count($qroundid)) {
		$a = array();
		foreach($qroundid as $q) {
			$a[] ="p.phase LIKE '{$q}%'";
		}

		if(count($a) > 1) {
			$awhere [] = "(".implode(" OR ", $a).")";
		}
		else if(count($a) == 1) {
			$awhere [] = $a[0];
		}
	}

	if(is_array($qpm) && count($qpm) > 0) {
		$a = array();
		foreach($qpm as $q) {
			if($q != "") {
				$a[] ="p.username LIKE '{$q}'";
			}
		}

		if(count($a) > 1) {
			$awhere [] = "(".implode(" OR ", $a).")";
		}
		else if(count($a) == 1) {
			$awhere [] = $a[0];
		}
	}

	if($qgenre) {
		$a = array();
		foreach($qgenre as $q) {
			$a[] ="genre LIKE '%{$q}%'";
		}

		if(count($a) > 1) {
			$awhere [] = "(".implode(" OR ", $a).")";
		}
		else if(count($a) == 1) {
			$awhere [] = $a[0];
		}
	}

	if($qlang) {
		$a = array();
		foreach($qlang as $q) {
			$a[] ="language LIKE '%{$q}%' OR seclanguage LIKE '%[$q]%'";
		}

		if(count($a) > 1) {
			$awhere [] = "(".implode(" OR ", $a).")";
		}
		else if(count($a) == 1) {
			$awhere [] = $a[0];
		}
	}

	//    if($qprojectid) {
	//        $qqid = mysql_real_escape_string($qprojectid);
	//        $ids = preg_split("/\s+/", $qqid);
	//        $qsql = "p.projectid IN ('"
	//            . implode("', '", $ids)
	//            . "')";
	//        $awhere [] = $qsql;
	//    }

	$where = implode("\nAND ", $awhere);
	$sql = project_search_view_sql($where, $orderby, $desc);

	$rows = $dpdb->SqlRows($sql);
	if($cmdPgUp) {
		$pagenum = max($pagenum - 1, 1);
	}
	if($cmdPgDn) {
		$pagenum = min($pagenum + 1, ceil(count($rows) / $rowsperpage));
	}

	$nprojects = count($rows);
}

$title = _("Search for Projects to Proof");

$args = array("js_file" => $js_url."/search.js");
//$args = array("js_file" => $js_url."/search.js");
$no_stats = 1;
theme(_("Search projects"), "header", $args);

// get array of code, name
$optlanguages = $Context->ActiveLanguages();

$language_picker = "
    <select class='selsearch' name='qlang[]' id='qlang[]'
                            size='12' multiple='multiple'>
        <option value=''> </option>\n";
foreach($optlanguages as $optlang) {
	$name = $optlang["name"];
	$code = $optlang["code"];
	$language_picker .= "
        <option value='{$code}'"
	                    .(in_array($code, $qlang) ? " selected='selected'" : "")
	                    .">{$name}</option>";
}
$language_picker .= "
    </select>\n";


echo "<h2 class='center m50em'>$title</h2>\n";

echo html_comment($sql);

echo "
<form id='searchform' name='searchform' method='POST'>
<div id='divsearch' class='center' onClick='eSetSort(event)'>
    <input type='hidden' name='rowsperpage' value='$rowsperpage'>
    <input type='hidden' name='pagenum' value='$pagenum'>
    <input type='hidden' name='orderby' id='orderby' value='nameofwork'>
    <input type='hidden' name='desc' id='desc' value='0'>
    <div id='searchtable' class='left w75'>
	    <div id='divsubmit' class='lfloat w35'>
			<div>
				<input type='submit' id='dosearch' name='dosearch' value='Submit'/>
				<input type='button' id='doclear' name='doclear' value='Clear' onclick='eclear()'/>
			</div>
			<div>
				Title
				<input id='qtitle' name='qtitle' type='text'
				class='rfloat' value='$qtitle' size='30' >
			</div>
			<div>
				Author
				<input class='rfloat' id='qauthor' name='qauthor'
				type='text' value='$qauthor' size='30'>
			</div>
			<div>
				Status:<br/>
				<input type='radio' name='qstatus'  value='avail' "
     .($qstatus == "avail" ?  " checked='checked' " : "") .">
					Available<br/>
				<input type='radio' name='qstatus' value='unavail' "
     .($qstatus == "unavail" ?  " checked='checked' " : "") .">
					Unavailable<br/>
				<input type='radio' name='qstatus' value='both' "
     .($qstatus == "both" ?  " checked='checked' " : "") .">
					Both
			</div>
	    </div> <!-- divsubmit -->
        <div class='w15 lfloat'>
			<div>Language</div>
			<div>
				$language_picker
			</div>
		</div>
        <div class='w15 lfloat'>
			<div>Genre</div>
			<div>
        		<select class='selsearch' id='qgenre[]' name='qgenre[]'
        		    multiple='multiple' size='12' >\n";

$optgenres = $Context->ActiveGenreArray();
echo array_to_options($optgenres, true, $qgenre);

echo "
                </select>
            </div>
        </div>

        <div class='w15 lfloat'>
            <div>PM</div>
            <div>
                <select class='selsearch' id='qpm[]' name='qpm[]'
					multiple='multiple' size='12'>\n";

$optpms = $Context->ActivePMArray();
echo array_to_options($optpms, true, $qpm);

echo "
                </select>
            </div>
        </div>

        <div class='w15 lfloat'>
			<div>State</div>
			<div>
				<select class='selsearch' id='qroundid[]' name='qroundid[]'
					   multiple='multiple' size='12'>\n";

// $optroundids = RoundIdsInOrder();
$optroundids = PhasesInOrder();
echo array_to_options($optroundids, true, $qroundid);

echo "
				</select>
			</div>
        </div>
    </div>   <!-- searchtable -->

    <div class='w70 left search-instructions'>
        " ._("For titles and authors, matching uses
          wildcarding; 'ford' matches 'Oxford' and 'Stanford'.<br>
           Task, language, etc. allow multiple choices
          by pressing the control-key.")."
	</div>
</div>
<!--
  $sql
-->
\n";


if ( $nprojects == 0 ) {
	if($dosearch) {
		echo _( "<p class='bold'>No projects matched the search criteria.</p>" );
	}
    echo "</form>\n";

	theme("", "footer");
	exit;
}

$tbl->SetRowCount(count($rows));
$tbl->SetPaging($pagenum, $rowsperpage);
$tbl->SetRows($rows);

echo "<div class='center' onclick='eSetSort(event)'>\n";
$tbl->EchoTable();
echo "</div>
</form>\n";



echo "
</div>
<br />\n";
theme("", "footer");
exit;


function is_available($row) {
	return preg_match("/_avail/", $row['state'])
		? "avail"
		: "";
}

function all_langs($row) {
	return $row['langname']
	       . ( empty($row['seclangname'])
		? ""
		: "/". $row['seclangname']);
}

function roundid($row) {
	return preg_match("/^(.+?)\..*_(.*)$/", $row['state'], $matches) > 0
		? $matches[1]."&nbsp;".$matches[2]
		: "";
}

function status($row) {
	return preg_match("/_(.*)$/", $row['state'], $matches) > 0
		? $matches[1]
		: "";
}


function array_to_options($optarray, $is_blank = true,
	$selected = null) {
	$ret = $is_blank
		? "<option value=''> </option>\n"
		: "";
	// foreach($optarray as $opt => $val) {
	foreach($optarray as $val) {
		// assert($opt);
		$ret .= ("<option value='$val'"
		         . (is_array($selected)
				? (in_array($val, $selected)
					? " selected='selected'"
					: "")
				: (is_string($selected)
				   && $selected == $val
					? " selected='selected'"
					: "")
			) .">$val</option>\n");
	}
	return $ret;
}

function project_search_view_sql($where, $orderby = "nameofwork") {
	$where = trim($where);
	if($where != "") {
		$where = "WHERE \n".$where;
	}
	$orderby = "ORDER BY {$orderby}";
	return  "SELECT
                p.projectid,
                p.nameofwork AS title,
                p.authorsname AS author,
                p.language,
                p.seclanguage,
                IFNULL(l1.name, '') langname,
                IFNULL(l2.name, '') seclangname,
                p.difficulty,
                p.genre,
                p.state,
                p.phase,
                p.username as pm,
                p.postproofer,
                p.ppverifier,
                SUM(1) pagecount,
                SUM(plv.state = 'A') pages_available,
                p.username AS project_manager,
                ph.sequence
            FROM projects AS p
            JOIN page_last_versions plv
            	ON p.projectid = plv.projectid
            LEFT JOIN languages l1 ON p.language = l1.code
            LEFT JOIN languages l2 ON p.seclanguage = l2.code
            JOIN phases ph ON p.phase = ph.phase
            $where
            GROUP BY p.projectid
            $orderby";
}

function title_link($title, $row) {
	return link_to_project($row['projectid'], $row['title']);
}

function page_counts($row) {
	return number_format($row['pages_available']) . "/" . number_format($row['pagecount']);
}

function pmlink($pm) {
	return link_to_pm($pm, $pm);
}

function edit_link($row) {
	global $User;

	return ( $User->IsSiteManager()
	         || $row['pm'] == $User->Username())
		? link_to_edit_project($row['projectid'], "Edit", true)
		: "";
}
