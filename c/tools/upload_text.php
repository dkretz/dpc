<?PHP
/*

    Upload types:   
    PP work completed        _second                    advance to PPV             _pp_username
    PP work not completed    _first_in_prog_            no change                  _pp_username
    smooth available         _smooth_avail              set deadline               _smooth
    smooth work              _smooth_done_username      no change                  _smooth_username

      upload_action           filename
    post_1          _second
    return_1        _first_in_prog_{$username}";
    return_2        _second_in_prog_username 
    smooth_avail    _smooth_avail
    smooth_done     _smooth_done_username

*/


ini_set("display_errors", 1);
error_reporting(E_ALL);

$relPath="../pinc/";
include_once($relPath.'dpinit.php');

$projectid      = Arg('projectid', Arg('project'));
$upload_action  = Arg('upload_action');
$postcomments   = Arg('postcomments');
$submit_upload  = IsArg('submit_upload');  

$project        = new DpProject($projectid);
$nameofwork     = $project->Title();
$username       = $User->Username();

if(isset($_FILES) && isset($_FILES["dpupload"])) {
    $isuploadfile       = true ;
    $upfiles            = $_FILES["dpupload"];
    $uploadfilename     = $upfiles["name"];
    $uploadtmpfilename  = $upfiles["tmp_name"];
    $uploadfilesize     = $upfiles["size"];
}
else {
    $isuploadfile = false;
}

// if files have been uploaded, process them
// mangle the postcomments

// make reasonably sure script does not timeout on large file uploads
// $path_to_file = "$projects_dir/$projectid";

// do some checks. File must exist (except if we are returning to PP 
// or PPV available.
// if we have a file, we need its name to end in .zip, and we need
// it to have non zero size.  and there must be only one file.

if ($isuploadfile) {       // we have a file now. do some more checks.

//	if(right($uploadfilename, 4) != ".zip") {
//		echo _("Invalid Filename (not .zip)");
//		divert($back_url);
//	}
//	if($uploadfilesize == 0) {
//		echo _("File $uploadfilename is empty");
//		divert($back_url);
//	}

	switch($upload_action) {
        case "pp_temp":
        case "pp_complete":
            $back_url   = url_for_my_projects();
            if(extension($uploadfilename) != "zip") {
                echo _("Invalid Filename (not .zip)");
                divert($back_url);
                exit;
            }
            $tofilepath = $project->PPUploadPath();
            $log_comment = "Uploaded file for $upload_action";
            break;

        case "ppv_complete":
            $back_url       = "$code_url/tools/ppv.php";
            if(extension($uploadfilename) != "zip") {
                echo _("Invalid Filename (not .zip)");
                divert($back_url);
                exit;
            }
            $tofilepath = $project->PPVUploadPath();
	        $log_comment = "Uploaded file for $upload_action";
            break;

        case "ppv_temp":
            $back_url = "$code_url/tools/ppv.php";
            if(extension($uploadfilename) != "zip") {
                echo _("Invalid Filename (not .zip)");
                divert($back_url);
                exit;
            }
            $tofilepath = $project->PPVUploadPath();
	        $log_comment = "Uploaded file for $upload_action";
            break;

        case "smooth_avail":
            $back_url = url_for_project($projectid);
            $tofilepath = $project->SmoothDownloadPath("zip");
	        $log_comment = "Uploaded zipped files for smooth reading";
            break;

        case "smooth_done":
            $tofilepath = $project->SmoothUploadPath();
            $back_url = url_for_project($projectid);
	        $log_comment = "Uploaded smoothed files";
            $project->LogSmoothDone();
            break;

        default:
            die("Invalid value for upload_action: $upload_action");
    }

    rename($uploadtmpfilename, $tofilepath);
    chmod($tofilepath, 0777);
    
    // we've put the file in the right place.
    // now let's deal with the postcomments.
    // we construct the bit that's going to be added on to the existing postcomments.
    // if we're returning to available, and the user hasn't loaded a file, and not
    // entered any comments, we don't bother.
    // Otherwise, we add a divider, time stamp, user name, and the name of the file
	$tofilename = basename($tofilepath);
    $postcomments = "\n----------\n".date("Y-m-d H:i")
        .  ($isuploadfile ? "Uploaded $tofilename" : "")
        . "\n$log_comment
        $postcomments\n";

	$sql = "
			UPDATE projects
			SET  postcomments = CONCAT(IFNULL(postcomments, ''), ?)
			WHERE projectid = '$projectid'";

	$args = array(&$postcomments);
	$dpdb->SqlExecutePS($sql, $args);

    divert($back_url);
    exit;
}

// Present the upload page.
$backto = "";
switch($upload_action) {
    case 'pp_complete':
    case 'pp_temp':
        $title = _("Upload Post-Processed Project<br/>(complete or not)");
        $backto = "<div class='lfloat'>"
                    . link_to_my_projects("Back to My Projects")
                    . "</div>\n";
        break;

    case 'ppv_complete':
        $title = _("Upload Completed Verified Project (to be posted)");
        break;

    case 'ppv_temp':
        $title = _("Upload incompletely Verified Project (for others to work on)");
        break;

    case 'smooth_avail':
        $title = _("Upload zipped Project files for Smooth Reading");
        break;

    case 'smooth_done':
        $title = _("Upload project you have Smooth Read");
        break;

    default:
        echo "Don't know how to handle upload_action='$upload_action'<br>\n";
        die();
}


theme($title, "header");

$admonition = ($upload_action == "smooth_avail")
	? "Instructions for Smooth Readers"
	: "Annoted text or or other notes (need not be zipped.)";

echo "
<div class='w800 center'>
{$backto}
  <h1 class='center'>$title</h1>
  <h2 class='center'>$nameofwork</h2>
    <form action='' method='post' enctype='multipart/form-data'>
      <input type='hidden' name='project' value='$projectid' />
      <input type='hidden' name='upload_action' value='$upload_action' />
      <input type='hidden' name='MAX_FILE_SIZE' value='300000000' />
    <div class='w75'>
    <input name='dpupload' id='dpupload' type='file' accept='zip' />
    <input name='submit_upload' id='submit_upload' type='submit' value='Submit'/>
    </div>
        " . _("
        <p>(After you click Upload, the browser may be slow getting to the next
        page, while it is uploading the file.)</p>") . "
";

    echo "
    <hr>
    <h4>$caption</h4>
        <div>
            <textarea class='b111'  name='postcomments' cols='50' rows='16'></textarea>
        </div>
      </form>
    </div>";

theme("", "footer");
exit;


// vim: sw=4 ts=4 expandtab



