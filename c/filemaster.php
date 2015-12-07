<?php
/*
    process flow when responding to an uploaded project zip file
    - location of uploaded zip file: $uploadpath
    - original name of (presumably a zip) file - $filename 
    - directory where unzipped files end up - $project->UploadPath()

*/

/*
 * protopage fields - projectid
 *                    name
 *                    external_text
 *                    external_image
 *                    other - file that's not image or text
 */
ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath = "./pinc/";
require_once $relPath."dpinit.php";

$User->IsLoggedIn()
	or RedirectToLogin();


global $sftp_path;
global $dpscans_path;

//      /var/sftp/dpscans/dkretz
$userpath               = build_path($dpscans_path, $User->Username());

//  showpath   /dpscans (+ ...)
if(IsArg("showpath")) {
	$showpath = Arg( "showpath" );
}
else if(file_exists($userpath) && is_dir($userpath)) {
	$showpath = build_path("/dpscans", $User->Username());
}
else {
	$showpath = "/dpscans";
}

// e.g. /var/sftp/dpscans/....
$truepath               = build_path($sftp_path, $showpath);

$projectid              = ArgProjectId();

// directory to browse from
$chk_text               = ArgArray("chk_text");      // selected files

$chk_other              = ArgArray("chk_other");
$chk_delete             = ArgArray("chk_delete");

$submit_load            = IsArg("submit_load", IsArg("submit_load2"));      // submit button
$submit_delete          = IsArg("submit_delete", IsArg("submit_delete2"));      // submit button
$submit_load_others     = IsArg("submit_load_others", IsArg("submit_load_others2"));      // submit button
$submit_delete_others   = IsArg("submit_delete_others", IsArg("submit_delete_others2"));      // submit button

$newproject             = IsArg("newproject");

$username               = $User->Username();

// -------------------------------------------------------


$project = new DpProject( $projectid );

$project->Exists()
	or die( "Requested project ($projectid) does not exist." );

$project->UserMayManage()
	or die( "Permission problem." );

$readme = readme_data($truepath);
if($readme != "" && $project->CPComments() == "") {
	$project->SetCPComments($readme);
}

// -----------------------------------------------------------------
//  Submit processing
// -----------------------------------------------------------------

// gather protopages as union of database and files
$protopages = gather_page_set($project, $truepath);


// process requested file deletions from project directory
if ( count( $chk_delete ) > 0 ) {
	do_chk_delete($chk_delete, $project);
}

// if requested to include or delete extra files, copy them over or delete them.
if ( count( $chk_other ) > 0 ) {
	// permit_path($project->ProjectPath());
	foreach ( array_keys( $chk_other ) as $otherkey ) {
		$proto = $protopages[$otherkey];
		$otherpath = $proto['external_image'];
		$otherfile = basename($otherpath);
		if ( ! file_exists( $otherpath ) ) {
			assert(false);
			continue;
		}
		if ( $submit_delete_others ) {
			@unlink( $otherpath );
		}
		else if ( $submit_load_others ) {
			$topath = build_path( $project->ProjectPath(), $otherfile );
			// permit_path($topath);
			// permit_path($otherpath);
			if ( file_exists( $topath ) ) {
				unlink( $topath );
			}

			assert(copy( $otherpath, $topath ));

			$archivepath = build_path(ProjectArchivePath($projectid), $otherfile);
			copy( $otherpath, $archivepath);

			@unlink( $otherpath );
		}
	}
}


// load pages requested
if ( $submit_load && count( $chk_text ) > 0 ) {
	LoadPageFiles($project, $chk_text, $protopages);
}

// delete pages requested
if ( $submit_delete && count( $chk_text ) > 0 ) {
	DeletePages($project, $chk_text);
}

// do it again to incorporate changes
$protopages = gather_page_set($project, $truepath);


// combine existing pages with potential pages
// (pairs of files)
/** @var DpProject $project */
function gather_page_set($project, $path) {
	$ary = array();
	$pages = $project->PageRows(true);
	$projectid = $project->ProjectId();
	foreach($pages as $row) {
		$key = "pg_" . $row['pagename'];
		$ary[$key]["row_id"] = $key;
		$ary[$key]["projectid"] = $projectid;
		$ary[$key]["name"] = $row['pagename'];
		$ary[$key]["imagefile"] = $row['imagefile'];
		$ary[$key]["is_in_db"] = true;
	}

	//	$fpaths = glob("$path/*");
	$fpaths = gather_files($path);

	foreach($fpaths as $fpath) {
		//	    $fname = basename($fpath);
		//	    if($fname == "readme.txt" || $fname == "~readme.txt") {
		//		    continue;
		//	    }
		$pgname = rootname($fpath);
		$key = "pg_" . $pgname;
		// may or may not be a match,
		// may or may not add a row
		$ary[$key]["projectid"] = $projectid;
		$ary[$key]["name"] = $pgname;
		$ary[$key]["row_id"] = $key;

		switch(FileNameExtension($fpath)) {
			case "txt":
				$ary[$key]["external_text"] = $fpath;
				continue;

			case "png":
			case "jpg":
			case "gif":
			case "tif":
			case "tiff":
				$ary[$key]["external_image"] = $fpath;
				continue;

			default:
				$ary[$key]["external_other"] = $fpath;
				continue;
		}
	}

	ksort($ary);
//		dump($ary);
//		die();
	return $ary;
}

function gather_files($dir) {
	$a_res = array();
	$fpaths = glob($dir . "/*");
	foreach($fpaths as $fpath) {
		switch($fpath) {
			case "readme.txt":
			case "~readme.txt":
			case ".":
			case "..":
				continue;
			default:
				break;
		}
		if(is_dir($fpath)) {
			continue;
		}
		$a_res[] = $fpath;
	}
	return $a_res;
}


// -----------------------------------------------------------------
//  Setup
// -----------------------------------------------------------------

// build the directory list display


$str_directory_list = " <pre id='directory_list' class='left w50'>\n";
// if not at root, prompt for Up
if($truepath != "/var/sftp/dpscans") {
	$str_directory_list .= "<span class='left likealink' onclick='eSetPath(\"..\");'> Up</span>\n";
}

// build directory display
$dirpaths = DirectoryList( $truepath );


// display a link for each directory
foreach ( $dirpaths as $dir ) {
	$dname = preg_replace( "/^.*\//", "", $dir );
	$str_directory_list .= "<span class='likealink' onclick='eSetPath(\"$dname\");'>$dname</span>\n";
}
$str_directory_list .= "</pre>\n";




// -----------------------------------------------------------------
// Main page table
// -----------------------------------------------------------------

$tblpages = new DpTable( "page_table", "w98 margined dptable" );

$tblpages->AddCaption( "^In Database", 2, $project->ProjectId() );
$tblpages->AddCaption( "^Pre Load Files", 3, $truepath );

$tblpages->AddColumn( "^Page<br/>name", "name");
$tblpages->AddColumn( ">In project", null, "eInDB" );
$tblpages->AddColumn( ">Load Image", "external_image", "epathlink" );
$tblpages->AddColumn( ">Load Text", "external_text", "epathlink" );
$tblpages->AddColumn( "^Encoding", "external_text", "encoding" );
$tblpages->AddColumn( chkall(), null, "textcheck" );
$tblpages->AddColumn("<projectid", "projectid", null, "hidden");
$tblpages->AddColumn("<imgpath", "external_image", null, "hidden");
$tblpages->AddColumn("<imgpath", "external_text", null, "hidden");

$pagerows = array();
$otherrows = array();

foreach($protopages as $protopg) {
	if(isset($protopg['is_in_db'])) {
		$pagerows[] = $protopg;
	}
	else if(isset($protopg['external_image']) && isset($protopg['external_text'])) {
		$pagerows[] = $protopg;
	}
	else {
		$otherrows[] = $protopg;
	}
}

$tblpages->SetRows( $pagerows );



// build other uploaded files display
$tblother = new DpTable( "tblother", "dptable" );
$tblother->AddColumn( "<Name", "name", null, "hidden" );
$tblother->AddColumn( "<File name", null, "eOther" );
$tblother->AddColumn( "<In<br>project", null, "eInProject");
$tblother->AddColumn( chkall2(), "name", "othercheck" );
$tblother->SetRows( $otherrows );


// setup up UI ----------------------------------

$delcaption     = _("Delete selected");
$loadcaption    = _("Load selected");
$uploadcaption  = _("Browse files to upload");

// ---------------------------------------------------
// page display
// ---------------------------------------------------

    $args = array("js_file"  => "./js/filemaster.js",
                  "css_file" => "./css/filemaster.css");
    $no_stats = 1;

	$title = ( $projectid && $project ? $project->NameOfWork() : _("New Project"));
    theme("FileMaster - " . $title, "header", $args);

	echo "<script type='text/javascript'>
		init();
		</script>\n";
//	if( $projectid && $project) {
		echo "<p>" . link_to_project( $projectid, "Return to project" ) . "</p>";
		echo "
  <div class='pagetitle'> {$project->TitleAuthor()} </div>
  <div id='divwork'>\n";
//	}

  echo "
    <div class='center' id='divworkform'>
	<form name='workform' id='workform' method='POST' action=''>

        <div id='divright' class='rfloat padded center w50'>
          <div id='dirs_and_files' class='center margined bordered padded' style='margin-left: 2em;'>
            <p>host = www.pgdpcanada.net, userid = dpscans, password = 2C4ever</p>
            <div id='divdirs' style='max-height: 20em;'>
                <h4 class='center w100'> Directory </h4>

              <h5 class='center clear'>$showpath</h5>

				<!-- the next two are to pass them along to js -->
              <input type='hidden' id='dpscans_path' name='dpscans_path'  value='$dpscans_path' />
              <input type='hidden' id='showpath' name='showpath'  value='$showpath' />
            $str_directory_list
            </div> <!-- divdirs -->

        </div> <!-- dirs_and_files -->\n";


    echo "
        <div id='divimages' class='margined center bordered padded' style='margin-left: 2em;'>
          <h4>Other Files</h4>
          <input type='submit' name='submit_load_others' value='Load selected'
            '/>
          <input type='submit' name='submit_delete_others' value='Delete selected'
            />\n";
          $tblother->EchoTableNumbered();
    echo "
          <input type='submit' name='submit_load_others2' value='Load selected'
          />
          <input type='submit' name='submit_delete_others2' value='Delete selected'
           />
        </div> <!-- divimages -->
  ";

  echo "
      </div>  <!-- divright -->\n";

  echo "
       <div id='divleft' class='half bordered rfloat padded'>
        <h3 class='center'>Project Pages</h3>
          <div id='divbuttons' class='w100'>
            <input type='submit' name='submit_delete' class='rfloat margined' value='$delcaption'
             />
            <input type='submit' name='submit_load' class='rfloat margined' value='$loadcaption'
            />
          </div> <!-- divbuttons -->\n";
          $tblpages->EchoTable();
  echo "
       <div id='divbuttons2' class='w100'>
            <input type='submit' name='submit_delete2' class='rfloat margined' value='$delcaption'
             />
            <input type='submit' name='submit_load2' class='rfloat margined' value='$loadcaption'
            />
          </div> <!-- divbuttons2 -->\n";

echo "
        </div> <!-- divleft -->\n";

echo "
    </form>

  </div> <!-- divwork -->
    </div>\n";

    theme("", "footer");
    exit;

function eFileName($row) {

}
function eInProject( $row) {
	global $project;
	if ( isset( $row["external_image"] ) ) {
		$fname = basename($row["external_image"]);
		$fpath = build_path($project->ProjectPath(), $fname);
	}
	else if ( isset( $row["external_text"] ) ) {
		$fname = basename($row["external_text"]);
		$fpath = build_path($project->ProjectPath(), $fname);
	}
	else if ( isset( $row["external_other"])) {
		$fname = basename($row["external_other"]);
		$fpath = build_path($project->ProjectPath(), $fname);
	}
	else {
		return "";
	}
	return file_exists($fpath) ? "Yes" : "No";
}

//function eMaybeImage($row) {
//	if ( isset( $row["external_image"] ) ) {
//			return basename( $row["external_image"] );
//	}
//	else if( isset( $row["external_text"] ) ) {
//		return basename( $row["external_text"] );
//	}
//	else if( isset( $row["external_other"])) {
//		return basename( $row["external_other"] );
//	}
//	else {
//		return "";
//	}
//}

function eOther($row) {
	if(isset($row["external_image"])) {
		return basename($row['external_image']);
	}
	if(isset($row["external_text"])) {
		return basename($row['external_text']);
	}
	if (isset($row["external_other"])) {
		return basename($row['external_other']);
	}
	return "";
}

function eInDb($row) {
	return isset($row['imagefile'])
		? "Yes" : "No";
}
function chkall() {
    return "^<input type='checkbox' id='chkall' name='chkall'
                    onclick='eCheckAll(event)'>"._("All");
}


function chkall2() {
    return "^<input type='checkbox' id='chkall2' name='chkall2'
                    onclick='eCheckAll2(event)'>"._("All");
}

function efile($row) {
    return $row;
}

function edelete($image) {
    return "<input type='checkbox' name='chk_delete[$image]' value='Delete' />\n";
}


function readme_data($path) {
	$rmpath = build_path($path, "readme.txt");
	if(file_exists($rmpath)) {
		return file_get_contents($rmpath);
	}

	$rmpath = build_path($path, "~readme.txt");
	return file_exists($rmpath)
		? $rmpath
		: "";
}

function DirectoryList($path) {
	$files = glob($path . "/*");
	$dirpaths = array();
	foreach($files as $fname) {
		if(is_dir($fname)) {
			$dirpaths[] = $fname;
		}
	}
	return $dirpaths;
}

function echo_upload_form() {
    echo "
        <form enctype='multipart/form-data' method='post' action=''
            name='upform' id='upform'>
        Select a zip file:
    <input type='file' name='projectzipfile' size='50'
            onchange='eFileSelect()'>
    <input type='button' value='Upload file' onclick='eUpClick()'
        name='upbutton' id='upbutton'>
    <span id='uploading'> Uploading....</span>
	</form>\n";
}


function yes_no($boolval) {
	$ret = $boolval;
	$ret .= (is_null($boolval) ? " N1 " : " Y1 ");
	$ret .= ($boolval ? " Y2 " : " N2 ");
	return $ret;
}


function encoding($path) {
	if(! file_exists($path)) {
		return "";
	}
	$text = file_get_contents($path);
	return is_utf8($text) ? "utf-8" : "other";
}

function textcheck($page) {
	global $protopages;
	$name = $page['name'];
	$key  = "pg_" . $name;
	if ( ! isset( $protopages[ $key ] ) ) {
		dump( $name );
		dump( $key );
		dump( $protopages );
		die();
	}
	$proto = $protopages[ $key ];

	return ( isset( $proto['external_image'] )
	         && isset( $proto['external_text'] ) )
		? "<input type='checkbox' name='chk_text[{$key}]'>\n"
		: "\n";
}

function othercheck($name) {
//	global $protopages;
//	$name = $row['name'];
	$key  = "pg_" . $name;
//	$proto = $protopages[ $key ];
//	$frompath = isset($proto['external_image']) ? $proto['external_image'] : $proto['external_text'];
	return "<input type='checkbox' name='chk_other[$key]'>";
}

function exttxtlen($page) {
	if(is_object($page)) {
		return "tbd";
	}
	else if(isset($page['external_text'])) {
		return $page['external_text'];
	}
	else {
		return "";
	}
}


function DeletePages($project, $chk_pages) {

}

// chk_pages has the heys, protopage has external_image, external_text
function LoadPageFiles($project, $chk_pages, $protopages ) {
	global $Context;
	// array to hold pages real and prospective
	/** @var $protopgs DpPage[] */
	/** @var $pgs array */
	/** @var $project DpProject */

	$projectid = $project->ProjectId();

	foreach(array_keys($chk_pages) as $key) {
//		dump($protopages);
//		die();
		$proto = $protopages[$key];
		if((! isset($proto['external_image']) || (! isset($proto['external_text'])))) {
			assert(false);
			dump($proto);
			continue;
		}
		$protoimagefile = $proto['external_image'];
		$prototextfile = $proto['external_text'];
		$pagename  = $proto['name'];
		// load image and text for this page

		$Context->AddPage($projectid, $pagename, $protoimagefile, $prototextfile);
	}
}

/*
function read_readme($readme, $path) {
	$ary = array();
	$tregex = "Title -\s+(.+)$";
	$aregex = "Personal Name:\s+(.*?)\d\d\d\d.*$";
	$ary["title"] = RegexMatch( $tregex, "uis", $readme );
	$ary["author"] = RegexMatch( $aregex, "uis", $readme );
	$spath = build_path($path, "scans");
	$tpath = build_path($path, "text");
	if(file_exists($spath) && file_exists($tpath)
	   && is_dir($spath) && is_dir($tpath)) {
		$ary["spath"] = $spath;
	}
	return $ary;
}
*/


/**
 * @param array $chk_delete
 * @param DpProject $project
 */
function do_chk_delete( $chk_delete, $project ) {
	foreach ( array_keys( $chk_delete ) as $filename ) {
		$project->DeleteProjectFile( $filename );
	}
}

function epathlink($path) {
	if(! file_exists($path)) {
		return "";
	}
	return basename($path);
}
