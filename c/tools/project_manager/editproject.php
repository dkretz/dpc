<?php
ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath = "./../../pinc/";
include_once($relPath.'dpinit.php');
include_once($relPath.'comment_inclusions.inc');
include_once('edit_common.inc');
include_once($relPath.'project_edit.inc');
include_once($relPath.'project_events.inc');

$projectid      = Arg("projectid");
//$saveAndQuit    = IsArg("saveAndQuit");
$saveAndProject = IsArg("saveAndProject");
$isdelete       = IsArg("Delete");

$project        = new DpProject($projectid);
$project->UserMayManage()
    or die("Not authorized to manage project.");

if($isdelete) {
    $project->DeleteProject();
    divert(url_for_project_manager());
    exit;
}
$page_title = _("Edit a Project");

$pih = new ProjectInfoHolder($projectid);
//if ( $saveAndQuit || $saveAndProject )
if( $saveAndProject ) {

    $errors = $pih->set_from_post();
    if (! $errors) {
        $pih->save_to_db();

//        if( $saveAndQuit) {
//            divert("projectmgr.php");
//            exit;
//        }
        if( $saveAndProject) {
            divert(url_for_project($projectid));
//            divert("$code_url/project.php?projectid=$pih->projectid");
            exit;
        }
    }
    theme($page_title, "header");

    echo "<br><h2 align='center'>$page_title</h2>\n";

    if ($errors != '') {
        echo "
        <p class='center bold'>$errors</p>\n";
    }

    $pih->show_form($project);
}
else {
    $fatal_error = $pih->set_from_db($projectid);

    theme($page_title, "header");
    echo "<br><h2 align='center'>$page_title</h2>\n";

    if ($fatal_error != '') {
        $fatal_error = _('site error') . ': ' . $fatal_error;
        echo "<br><h3>$fatal_error</h3>\n";
        theme('', 'footer');
        exit;
    }

    $pih->show_form($project);
}
theme("", "footer");

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

class ProjectInfoHolder
{
    public $projectid        = '';
    public $nameofwork       = '';
    public $authorsname      = '';
    public $projectmgr       = '';
    public $pper             = '';
    public $ppverifier       = '';
    public $languagecode     = '';
    public $scannercredit    = '';
    public $comments         = '';
    public $clearance        = '';
    public $postednum        = null;
    public $genre            = '';
    public $difficulty       = '';
    public $image_source     = '';
    public $image_link       = '';
    public $image_preparer   = '';
    public $text_preparer    = '';
    public $extra_credits    = '';
    public $isposted         = '';

    public function __construct($projectid) {
        global $User;
        $projectmgr = $User->Username();

        $this->projectid = $projectid;
        $this->projectmgr = $projectmgr;
        $this->difficulty       = ( $projectmgr == "BEGIN" ? "Beginner" : "Average" );
        $this->image_source     = '_internal';
        $this->image_preparer   = $projectmgr;
        $this->text_preparer    = $projectmgr;
    }

    // -------------------------------------------------------------------------

    public function set_from_db($projectid) {

        $project = new DpProject($projectid);
        if(! $project->Exists()) {
            dump($project);
            return _("projectid $projectid does not exist.");
        }

        if(! $project->UserMayManage()) {
            die( _("you are not allowed to edit this project").": '$projectid'");
        }

        $this->nameofwork       = $project->NameOfWork();
        $this->authorsname      = $project->AuthorsName();
	    $this->projectmgr       = $project->ProjectManager();
        $this->pper             = $project->PPer();
        $this->ppverifier       = $project->PPVer();
        $this->languagecode     = $project->LanguageCode();
        $this->scannercredit    = $project->ScannerCredit();
        $this->comments         = $project->Comments();
        $this->clearance        = $project->Clearance();
        $this->postednum        = $project->PostedNumber();
        $this->genre            = $project->Genre();
        $this->difficulty       = $project->Difficulty();
        $this->image_source     = $project->ImageSource();
        $this->image_link       = $project->ImageLink();
        $this->image_preparer   = $project->ImagePreparer();
        $this->text_preparer    = $project->TextPreparer();
        $this->extra_credits    = $project->ExtraCredits();
        return "";
    }

    // -------------------------------------------------------------------------

    public function set_from_post() {
        $errors = '';

        if( $this->projectid == '') {
            $errors .= "Project ID is required..<br>"; 
        }
        $this->nameofwork = Arg('nameofwork');
        if ( $this->nameofwork == '' ) {
            $errors .= "Name of work is required.<br>"; 
        }

        $this->authorsname = Arg('authorsname');
        if ( $this->authorsname == '' ) { $errors .= "Author is required.<br>"; }

        $languagecode = Arg('pri_language');
        if ( $languagecode == '' ) { $errors .= "Language is required.<br>"; }

//        $sec_language = Arg('sec_language');

        $this->languagecode = $languagecode;
//        $this->language = (
//            $sec_language != ''
//            ? "$language with $sec_language"
//            : $language );
	    $projectmgr = Arg('projectmgr');
	    if ( $this->projectmgr == '' ) { $errors .= "Project Manager is required.<br>"; }

	    $this->projectmgr = $projectmgr;
        $this->genre = Arg('genre');
        if ( $this->genre == '' ) { $errors .= "Genre is required.<br>"; }

        $this->image_source = Arg('image_source');
        if ($this->image_source == '') {
            $errors .= "Image Source is required. If the one you want isn't in list, you can propose to add it.<br>";
            $this->image_source = '_internal';
        }

        $this->pper = Arg('pper');
        if($this->pper != "") {
            /** @var DpContext $Context */
            if(! DpContext::UserExists($this->pper)) {
                $errors .= "PPer must be an existing user - check case
                and spelling of username.<br>"; 
            }
        }
        $this->ppverifier = Arg('ppverifier');
        if ($this->ppverifier != '') {
            /** @var DpContext $Context */
            if(! DpContext::UserExists($this->ppverifier)) {
                $errors .= "PPVer must be an existing user - check case
                and spelling of username.<br>"; 
            }
        }

        $this->image_preparer = Arg('image_preparer');
        if ($this->image_preparer != '') {
            if(! DpContext::UserExists($this->image_preparer)) {
                $errors .= "Image Preparer must be an existing user - check
                case and spelling of username.<br>";
            }
        }

        $this->text_preparer = Arg('text_preparer');
        if ($this->text_preparer != '') {
            if(! DpContext::UserExists($this->text_preparer)) {
                $errors .= "Text Preparer must be an existing user - check case
                and spelling of username.<br>";
            }
        }

        $this->postednum = Arg('postednum');
        if ( $this->isposted ) {
            // We are in the process of marking this project as posted.
            if ( $this->postednum == 0 ) {
                $errors .= "Posted Number is required.<br>";
            }
        }

        $this->image_link       = Arg('image_link');
        $this->scannercredit    = Arg('scannercredit');
        $this->comments         = Arg('comments');
        $this->clearance        = Arg('clearance');
        $this->difficulty       = Arg('difficulty');
        // $this->up_projectid     = Arg('up_projectid');
        // $this->original_marc_array_encd = Arg('rec');
        $this->extra_credits    = Arg('extra_credits');

        return $errors;
    }

    // -------------------------------------------------------------------------

    public function save_to_db() {
        global $dpdb;

        $postednum_str = $this->postednum == ""
            ? NULL
            : "$this->postednum";

        $sql = "
                UPDATE projects 
                SET t_last_edit    = UNIX_TIMESTAMP(),
                    nameofwork     = ?,
                    authorsname    = ?,
                    username	   = ?,
                    genre          = ?,
                    language       = ?,
                    difficulty     = ?,
                    clearance      = ?,
                    comments       = ?,
                    image_source   = ?,
                    image_link     = ?,
                    scannercredit  = ?,
                    postproofer    = ?,
                    ppverifier     = ?,
                    postednum      = ?,
                    image_preparer = ?,
                    text_preparer  = ?,
                    extra_credits  = ?
                WHERE projectid='{$this->projectid}'";

        $args = array( &$this->nameofwork, &$this->authorsname, &$this->projectmgr,
                    &$this->genre, &$this->languagecode, &$this->difficulty,
                    &$this->clearance, &$this->comments, &$this->image_source,
                    &$this->image_link,
                    &$this->scannercredit, &$this->pper, &$this->ppverifier,
                    &$postednum_str, &$this->image_preparer, &$this->text_preparer,
                    &$this->extra_credits);

        $dpdb->SqlExecutePS($sql, $args);
    }

// TODO not scannercredit below!


    // =========================================================================

    public function show_form($project) {
        global $User;
        /** @var DpProject $project */

        echo "<form method='post' enctype='multipart/form-data' action=''>\n";

        if($this->isposted) {
            echo "<input type='hidden' name='isposted' value='1'>";
        }
        if (!empty($this->projectid)) {
            echo "<input type='hidden' name='projectid' value='$this->projectid'>";
        }

        echo "<br>";
        echo "<table cellspacing='0' cellpadding='5' border='1' width='90%'
        bordercolor='#000000' style='border-collapse:collapse'>";

        if (!empty($this->projectid)) {
            $this->row( _("Project ID"), 'just_echo', $this->projectid );
        }
        $this->row( _("Name of Work"),                'text_field',          $this->nameofwork,      'nameofwork' );
        $this->row( _("Author's Name"),               'text_field',          $this->authorsname,     'authorsname' );
	    $this->row( _("Project Manager"),             'DP_user_field',       $this->projectmgr,   'projectmgr', "" );
        $this->row( _("Language"),                    'language_list',       $this->languagecode     );
        $this->row( _("Genre"),                       'genre_list',          $this->genre            );
        $this->row( _("Difficulty Level"),            'difficulty_list',     $this->difficulty);
        $this->row( _("Post Processor"),      'DP_user_field',       $this->pper,    'pper' );
        $this->row( _("PP Verifier"),  'DP_user_field', $this->ppverifier, 'ppverifier');
        $this->row( _("Images Source"),       'image_source_list',   $this->image_source     );
        $this->row( _("URL for Source Images"),       'text_field',          $this->image_link);
        $this->row( _("Image Preparer"),              'DP_user_field',       $this->image_preparer,  'image_preparer', _("DP user who scanned or harvested the images."));
        $this->row( _("Text Preparer"),               'DP_user_field',       $this->text_preparer,   'text_preparer', _("DP user who prepared the text files.") );
        $this->row( _("Extra Credits (to be included in list of names)"),   
                                               'extra_credits_field', $this->extra_credits);
        if ($this->scannercredit != '') {
            $this->row( _("Scanner Credit (deprecated)"), 'text_field',      $this->scannercredit,   'scannercredit' );
        }
        $this->row( _("Clearance Information"),       'text_field',          $this->clearance,       'clearance' );
        $this->row( _("Posted Number"),               'text_field',          $this->postednum,       'postednum' );
        $this->row( _("Project Comments"),            'proj_comments_field', $this->comments         );

        // <input type='submit' name='saveAndQuit' value='"._("Save and Quit")."'>
        $quiturl = url_for_project($this->projectid);
        echo "
        <tr><td class='CCC center' colspan='2'>
        <input type='submit' name='saveAndProject' value='"._("Save and Go To Project")."'>
        <input type='button' value='"._("Quit Without Saving")."' 
                onclick='javascript:location.href=\"{$quiturl}\";'>\n";
        if($project->Phase() == "PREP" || $User->IsSiteManager()) {
            echo "<input type='submit' name='Delete' value='"._("Delete Project")."'>\n";
        }
        echo "
        </td></tr>
        </table>
        </form>\n";
    }

    // -------------------------------------------------------------------------


    // -------------------------------------------------------------------------

    public function row( $label, $show_func, $field_value, $field_name = NULL, $explan='' ) {
        echo "<tr>";
        echo   "<td bgcolor='#CCCCCC'>";
        echo     "<b>$label</b>";
        echo   "</td>";
        echo   "<td>";
        $show_func( $field_value, $field_name );
        echo   "  ";
        echo   $explan;
        echo   "</td>";
        echo "</tr>";
        echo "\n";
    }

    // =========================================================================
}

function language_list($language) {
    global $lang_codes;
    if (strpos($language, "with") > 0) {
        $pri_language = trim(substr($language, 0, strpos($language, "with")));
        $sec_language = trim(substr($language, (strpos($language, "with")+5)));
    }
    else {
        $pri_language = $language;
        $sec_language = '';
    }

    if(isset($lang_codes[$pri_language])) {
        $langcode = $pri_language;
        // $langname = $lang_codes[$pri_language];
    }
    else {
        $langcode = array_search($pri_language, $lang_codes);
        // $langname = $pri_language;
    }

    echo "
    <select name='pri_language'>
    <option value=''>Primary Language</option>\n";

    foreach($lang_codes as $code => $name) {
        // for ($i = 0; $i<count($array_list); $i++) {
        echo "<option value='$code'";

        if ($langcode == $code) {
            echo " SELECTED";
        }
        echo ">$name</option>\n";
    }
    echo "</select>\n";

    if(isset($lang_codes[$sec_language])) {
        $langcode = $sec_language;
        // $langname = $lang_codes[$sec_language];
    }
    else {
        $langcode = array_search($sec_language, $lang_codes);
        // $langname = $sec_language;
    }
    echo "
    &nbsp;&nbsp;
    <select name='sec_language'>
    <option value=''>Secondary Language</option>\n";

    foreach($lang_codes as $code => $name) {
        echo "<option value='$code'";

        if ($langcode == $code) {
            echo " SELECTED";
        }
        echo ">$name</option>";
        echo "\n";
    }
    echo "</select>\n";
}

// vim: sw=4 ts=4 expandtab
