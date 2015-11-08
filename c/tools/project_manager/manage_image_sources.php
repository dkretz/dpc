<?PHP

ini_set('display_errors', true);
error_reporting(E_ALL);

$relPath='../../pinc/';
include_once($relPath.'dpinit.php');
//include_once($relPath.'maybe_mail.inc');

// $action is show_sources, add_source, edit_source, update_oneshot

$action                 = Arg("action", "show_sources");
$source                 = Arg("source");
$edit                   = IsArg("edit");
$enable                 = IsArg("enable");
$disable                = IsArg("disable");
$approve                = IsArg("approve");
$save_edits             = IsArg("save_edits"); 
$code_name              = Arg("code_name"); 
$display_name           = Arg("display_name"); 
$full_name              = Arg("full_name"); 
$credit                 = Arg("credit"); 
$url                    = Arg("url"); 
$ok_keep_images         = Arg("ok_keep_images"); 
$ok_show_images         = Arg("ok_show_images"); 
$info_page_visibility   = Arg("info_page_visibility"); 
$public_comment         = Arg("public_comment"); 
$internal_comment       = Arg("internal_comment");
$is_active              = Arg("is_active");


$theme_args['css_data'] = "
    table.source {
        width:75%; border-collapse:collapse;
        margin-left: auto; margin-right: auto; 
    }
    table.source td {
        border:1px solid black; padding:5px; 
    }
    td.enabled {
        background-color: #9f9; 
    }
    td.disabled {
        background-color: #ddd; 
    }
    td.pending {
        background-color: #ff8; 
    }
    td.pa {
        width:30%; font-weight:bold; 
    }";

// $page_url = "$code_url/tools/project_manager/manage_image_sources.php?".rand(1000,9999);



// if ( !in_array($action,array('show_sources','add_source','edit_source','update_oneshot')) )
    // die("Bad 'action': $action");


$can_edit = $User->IsImageSourcesManager() || $User->IsAdmin();
// $can_edit = user_is_image_sources_manager();

switch($action) {
    case 'show_sources':
        if ( ! $can_edit) {
            redirect_to_home();
            exit;
        }

        theme(_('List Image Sources'),'header', $theme_args);

        show_is_toolbar();

        $codes = $dpdb->SqlValues("
                SELECT code_name FROM image_sources 
                ORDER BY display_name");

        echo "<br />";
        foreach($codes as $code) {
            $imgsource = new ImageSource($code);
            $imgsource->show_summary();
            echo "<hr style='margin: 1em auto 1em auto; width:50%;text-align:center;' />";
        }
        break;


    case 'edit_source':
        $imgsource = new ImageSource($source);
        theme(sprintf(_("Editing %s"), $imgsource->display_name()), 'header', $theme_args);
        show_is_toolbar();
        $imgsource->show_edit_form();
        break;


    case 'add_source':
        $title = $can_edit ? _('Add a new image source') : _('Propose a new image source');
        theme($title, 'header', $theme_args);
        show_is_toolbar();
        $blank = new ImageSource("");
        $blank->show_edit_form();
        break;


    case 'update_oneshot':
        global $User;

        if ($edit) {
            divert("$pageurl&action=edit_source&source=$source");
            exit;
        }
        if ($enable) {
            $imgsource = new ImageSource($source);
            $imgsource->enable();
            divert("$pageurl&action=show_sources#$source");
            exit;
        }

        if ($disable) {
            $imgsource = new ImageSource($source);
            $imgsource->disable();
            divert("$pageurl&action=show_sources#$source");
            exit;
        }

        if ($approve) {
            $imgsource = new ImageSource($source);
            $imgsource->approve();
            divert("$pageurl&action=show_sources#$source");
            exit;
        }

        if ($save_edits) {
            # This handles both edits to existing sources, and the creation of new sources
            $imgsource = new ImageSource;

            $errmsgs = '';

            $new_code_name = trim($code_name);

            if ($new_code_name == "")
                $errmsgs .= _("A value for Image Source ID is required. Please enter one. ");

            $new = ! $dpdb->SqlExists("
                SELECT 1 FROM image_sources
                WHERE code_name = '$new_code_name'");

            if (! $new)
                $imgsource = new ImageSource($code_name);

            if ( !$new && !$edit) {
                $errmsgs .= sprintf(_('An image source with this ID already exists. If you
                wish to edit the details of an existing source, please contact %s.
                Otherwise, choose a different ID for this source. <br />'),$db_requests_email_addr);
            }

            $imgsource->save_from_post();
            if ($can_edit) {
                divert("?action=show_sources#$source");
                exit;
            }

            theme('','header');
            if ($new)
                $imgsource->log_request_for_approval($User->Username());
            echo _("Your proposal has been successfully recorded. You will be
                notified by email once it has been approved.");
        }
        break;
}


theme('','footer');

// ----------------------------------------------------------------------------

class ImageSource
{
    private $_row;
    public $code_name = "";
    public $display_name = "";
    public $full_name = "";
    public $is_active = "";
    public $credit = "";
    public $url = "";
    public $ok_keep_images = "";
    public $ok_show_images = "";
    public $info_page_visibility = "";
    public $public_comment = "";
    public $internal_comment = "";

    function __construct($code_name = "") {
        global $dpdb;

        if( $code_name != "" ) {
            $this->_row = $dpdb->SqlOneRow("
                SELECT * FROM image_sources
                WHERE code_name = '$code_name'");
        }
        else {
            $this->_row = array(
                      "code_name" => "",
                      "display_name" => "",
                      "full_name" => "",
                      "info_page_visibility" => "",
                      "is_active" => "",
                      "url" => "",
                      "credit" => "",
                      "ok_keep_images" => "",
                      "ok_show_images" => "",
                      "public_comment" => "",
                      "internal_comment" => "");

        }
    }

    function code_name() {
        return $this->_row["code_name"];
    }
    function display_name() {
        return $this->_row["display_name"];
    }
    function full_name() {
        return $this->_row["full_name"];
    }
    function url() {
        return $this->_row["url"];
    }
    function credit() {
        return $this->_row["credit"];
    }
    function public_comment() {
        return $this->_row["public_comment"];
    }
    function internal_comment() {
        return $this->_row["internal_comment"];
    }

    function show_summary() {
      echo "
      <table class='source' id='tblsummary'>
      <form action='?action=update_oneshot&amp;source={$this->code_name()}' method='post'></form>
      <tr><td class='pa'>Image Source ID</td><td class='pb'>{$this->code_name()}</td></tr>
      <tr><td class='pa'>Display Name</td><td class='pb'>{$this->display_name()}</td></tr>
      <tr><td class='pa'>Full Name</td><td class='pb'>{$this->full_name()}</td></tr>
      <tr><td class='pa'>Status</td><td class='enabled pb'>{$this->_get_status_cell()}</td></tr>
      <tr><td class='pa'>Web site</td><td class='pb'>{$this->url()}</td></tr>
      <tr><td class='pa'>Credits Line</td><td class='pb'>{$this->credit()}</td></tr>
      <tr><td class='pa'>Permissions</td><td>{$this->_get_permissions_cell()}</td></tr> 
      <tr><td class='pa'>Comment (public)</td><td class='pb'>{$this->public_comment()}</td></tr>
      <tr><td class='pa'>Notes (internal)</td><td class='pb'>{$this->internal_comment()}</td></tr>
      <tr><td class='center' colspan='2'>
            <input type='submit' value='Edit' name='edit'> 
            <input type='submit' value='Disable' name='disable'> </td> </tr> 
      </table>\n";

        // global $page_url;
        // $sid = $this->code_name;
        // echo "<a name='$this->code_name' id='$this->code_name'></a>
            // <table class='source'><form method='post'
            // action='?action=update_oneshot&amp;source=$sid'>\n";

        // $this->_show_summary_row(_('Image Source ID'), $this->code_name);
        // $this->_show_summary_row(_('Display Name'),$this->display_name);
        // $this->_show_summary_row(_('Full Name'),$this->full_name);
        // echo "<tr><td class='pa'>" . _("Status") . "</td>" .
                // $this->_get_status_cell(' pb') . "</tr>";
        // $this->_show_summary_row(_('Web site'),make_link($this->url),false);
        // $this->_show_summary_row(_('Credits Line'),$this->credit);
        // echo "<tr><td class='pa'>" . _("Permissions") . "</td>" .
          //        $this->_get_permissions_cell(
                     // $this->ok_keep_images,
                     // $this->ok_show_images,
                     // $this->info_page_visibility
                 // ) . "</tr>";
        // $this->_show_summary_row(_('Comment (public)'),$this->public_comment);
        // $this->_show_summary_row(_('Notes (internal)'),$this->internal_comment);

        // echo "<tr><td colspan='2' style='text-align:center;'>";
            // $this->show_buttons();
        // echo "</td> </tr> </form> </table>\n\n";
    }


    function show_buttons() {
        echo "<input type='submit' name='edit' value='"._('Edit')."' />\n";
        switch ($this->is_active) {
            case('-1'):
                echo "<input type='submit' name='approve' value='"._('Approve')."' />\n";
                break;
            case('0'):
                echo "<input type='submit' name='enable' value='"._('Enable')."' />\n";
                break;
            case('1'):
                echo "<input type='submit' name='disable' value='"._('Disable')."' />\n";
                break;
        }
    }

    function show_edit_form() {
        // global $page_url;
        echo "
        <table class='source' id='tbledit'><form method='POST' action='?action=update_oneshot'>\n";

        if($this->code_name == "") {
            $this->_show_edit_row('code_name', _('Image source ID'), false, 10);
        }
        else {
            echo "
        <input type='hidden' name='editing' value='true' />
        <input type='hidden' name='code_name' value='{$this->code_name()}' />
        <tr><td class='pa'>". _('Display name') . "</td>
            <td class='pb'>{$this->code_name()}</td></tr>\n";
        }
        $this->_show_edit_row('display_name', false, 30);
        $this->_show_edit_row('full_name', _('Full name'));
        $this->_show_edit_row('url',_('Web site'));
        $this->_show_edit_row('credit', _('Credits line'), true);
        $this->_show_edit_permissions_row();
        $this->_show_edit_row('public_comment', _('Comment (public)'), true);
        $this->_show_edit_row('internal_comment', _('Notes (internal)'), true);

        echo "<tr><td colspan='2' style='text-align:center;'>
            <input type='submit' name='save_edits' value='"._('Save')."' />
            </td> </tr> </form> </table>\n\n";
    }

    function _show_edit_row($field, $label, $textarea = false, $maxlength = null) {

        $value = $this->code_name == ""
            ? ($field == "") ? '' : $field
            : $this->$field;

        $value = h($value);

        if ($textarea) {
            $editing = "<textarea cols='60' rows='6' name='$field'>$value</textarea>";
        }
        else {
            $maxlength_attr = is_null($maxlength) ? '' : "maxlength='$maxlength'";
            $editing = "<input type='text' name='$field' size='60' value='$value' $maxlength_attr />";
        }
        echo "  <tr>" .
            "<td class='pa'>$label</td>" .
            "<td class='pb'>$editing</td>" .
            "</tr>\n";
    }

    function _show_edit_permissions_row() {
        $cols = array(
            array('field' => 'ok_keep_images', 'label' => _('Images may be stored'), 'allow_unknown' => true),
            array('field' => 'ok_show_images', 'label' => _('Images may be published'), 'allow_unknown' => true),

            );

        $editing = '';

        foreach ($cols as $col) {

            $field = $col['field'];
            $existing_value = $this->code_name == ""
                ? $field == "" ? '-1' : $field
                : $this->$field;

            $editing .= "$col[label] <select name='$col[field]'>";
            foreach (array('1' => 'Yes','0' => 'No','-1' => 'Unknown') as $val => $opt) {
                if (! (!$col['allow_unknown'] && $opt == 'Unknown') ) {
                $editing .= "<option value='$val' " .
                    ($existing_value == $val ? 'selected' :'') .
                    ">$opt</option>";
                }
            }
            $editing .= "</select><br />";
        }

        // info page visibility is more complicated
        //  0 = Image Source Managers and SAs
        //  1 = also any PM
        //  2 = also any logged-in user
        //  3 = anyone

            $field = 'info_page_visibility';
            $existing_value = $this->code_name == ""
                ? $field == "" ? '2' : $field
                : $this->$field;

            $editing .= "Visibility on Info Page <select name='$field'>";
            foreach (array('0' => 'IS Managers Only','1' => 'Also PMs','2' => 'All DP Users','3' => 'Publicly Visible') as $val => $opt) {
                
                $editing .= "<option value='$val' " .
                    ($existing_value == $val ? 'selected' :'') .
                    ">$opt</option>";
            }
            $editing .= "</select><br />";


        $this->_show_summary_row(_('Permissions'),$editing,false);
    }

    function save_from_post() {
        global $errmsgs, $can_edit, $new, $theme_args;
        $std_fields = array('display_name',
                            'full_name',
                            'credit',
                            'ok_keep_images',
                            'ok_show_images',
                            'info_page_visibility',
                            'public_comment',
                            'internal_comment');
        $std_fields_sql = "";
        foreach ($std_fields as $field) {
            $this->$field = $field;
            $std_fields_sql .= "$field = '{$this->$field}',\n";
        }

        // If the url has no scheme, prepend http://
        $this->url = mb_strpos($this->url, '://') ? $this->url : 'http://'.$this->url;
        $this->code_name = upper($this->code_name);
        if ($new) {
            // If the user is an image sources manager, then the new source should
            // default to disabled. If not, the source should default to pending approval.
            $this->is_active = $can_edit ? '0' : '-1';
            // new sources shouldn't be shown on
            // the public version of the info page until they are approved.
             $this->info_page_visibility = '1' ;
        }

        if ($errmsgs) {
            theme('', 'header', $theme_args);
            echo "<p style='font-weight: bold; color: red;'>" . $errmsgs . "</p>";
            $this->show_edit_form();
            theme('', 'footer');
            exit;
        }

        $sql = "
            REPLACE INTO image_sources
            SET code_name            = ?,
                url                  = ?,
                is_active            = ?,
                full_name            = ?,
                credit               = ?,
                ok_keep_images       = ?,
                ok_show_images       = ?,
                info_page_visibility = ?,
                public_comment       = ?,
                internal_comment     = ?";

        $args = array(&$this->code_name,
                      &$this->url,
                      &$this->is_active,
                      &$this->full_name,
                      &$this->credit,
                      &$this->ok_keep_images,
                      &$this->ok_show_images,
                      &$this->info_page_visibility,
                      &$this->public_comment,
                      &$this->internal_comment);

        dump($sql);
        dump($args);
        die();
//        $n = $dpdb->SqlExecutePS($sql, $args);
//        if($dpdb->ErrorMessage() != "") {
//            die($dpdb->ErrorMessage());
//        }
//        assert($n == 1);
    }

    function enable() {
        $this->_set_field('is_active', 1);
    }

    function disable() {
        $this->_set_field('is_active', 0);
    }

    function approve() {
        global $dpdb, $User, $site_url;
        $this->_set_field('is_active', 1);
        $this->_set_field('info_page_visibility', 1);

        $row = $dpdb->SqlOneRow("
            SELECT users.username, users.email
            FROM usersettings, users
            WHERE usersettings.setting = 'is_approval_notify'
                AND usersettings.value = '{$this->code_name()}'
                AND usersettings.username = '{$User->Username()}");

        $dpdb->SqlExecute("
                DELETE FROM usersettings
                WHERE setting = 'is_approval_notify'
                    AND usersettings.value = '{$this->code_name()}'");

        $username = $row["username"];
        $email = $row["email"];

        $subject = sprintf(_('DP: Image source %s has been approved!'), $this->display_name);

        $body = "Hello $username,\n\n" .
            "This is a message from the Distributed Proofreaders Canada website.\n\n".
            "The image source that you proposed, $this->display_name, has been\n".
            "approved by {$User->Username()}. You can select it, and apply it to projects, from\n".
            "your project manager's page.\n\nThank you!\nDistributed Proofreaders Canada\n$site_url";

        maybe_mail($email, $subject, $body, null);
    }

    function _set_field($field, $value) {
        global $dpdb;
        $dpdb->SqlExecute("
            UPDATE image_sources SET $field = '$value'
            WHERE code_name = '{$this->code_name}'");
        $this->$field = $value;
    }


    function _show_summary_row($label, $value) {
        echo "
        <tr><td class='pa'>$label</td><td class='pb'>$value</td></tr>\n";
    }

    function _get_status_cell($class = '') {
        $status = $this->is_active;
        $open = $middle = "";
        switch ($status) {
          case(1):
              $middle = _('Enabled');
              $open = "<td class='enabled{$class}'>";
              break;
          case(0):
              $middle = _('Disabled');
              $open = "<td class='disabled{$class}'>";
              break;
          case(-1):
              $middle = _('Pending approval');
              $open = "<td class='pending{$class}'>";
              break;
        }

        return $open . $middle . '</td>';
    }

    function _get_permissions_cell($class = '') {
         $can_keep      = $this->ok_keep_images;
         $can_publish   = $this->ok_show_images;
         $show_to       = $this->info_page_visibility;
        $cell           = "<td class='$class'>";

        if ($can_keep != '-1') {
            $cell .= sprintf("Images from this provider <b>%s</b> be stored.<br />",
               ( $can_keep ? _('may') : _('may not') ));
        }
        else
            $cell .= _("It is <b>unknown</b> whether images from this source may be stored. <br />");

        if ($can_publish != '-1') {
            $cell .= sprintf("Images from this provider <b>%s</b> be published.<br />",
               ( $can_publish ? _('may') : _('may not') ));
        }
        else
            $cell .= _("It is <b>unknown</b> whether images from this source may be published.<br />");

        switch ($show_to) {
            case '0':
                $to_whom = _("Image Managers Only");
                break;
            case '1':
                $to_whom = _("Project Managers");
                break;
            case '2':
                $to_whom = _("Any DP User");
                break;
            default:
                $to_whom = _("All Users and Visitors");
                break;
        }

        $cell .= sprintf("Information about this source is shown to <b>%s</b>.<br />",
                   $to_whom);


       $cell .= '</td>';

       return $cell;
    }

    function log_request_for_approval($requestor_username) {
        global $general_help_email_addr, $image_sources_manager_addr, $code_url, $site_url;
        global $dpdb;

        $dpdb->SqlExecute("
            INSERT INTO usersettings
            SET username = '$requestor_username',
                setting = 'is_approval_notify',
                value = '$this->code_name'");

        $subject = _('DP: New image source proposed')." : ".$this->display_name;

        $body = "Hello,\n\nYou are receiving this email because\n".
        "you are listed as an image sources manager at the Distributed\n".
        "Proofreaders Canada site. If this is an error, please contact <$general_help_email_addr>.\n\n".
        "$requestor_username has proposed that $this->display_name be added\n".
        "to the list of image sources. To edit or approve this image source,\n".
        "visit\n    $code_url/tools/project_manager/manage_image_sources.php?action=show_sources#$this->code_name".
        "\n\nThank you!\nDistributed Proofreaders Canada\n$site_url";

        maybe_mail($image_sources_manager_addr,$subject,$body,null);
    }

}

// ----------------------------------------------------------------------------

function make_link($url) {
    $start = substr($url,0,3);
    if ($start == 'htt') {
        return "<a href='$url'>$url</a>";
    }
    else {
        return "<a href='http://$url'>$url</a>";
    }
}

function show_is_toolbar() {
    global $action;

    $pages = array(
        'add_source' => _('Add New Source'),
        'show_sources' => _('List All Image Sources')
    );
    echo "<p style='text-align: center; margin: 5px 0 5px 0;'>";
    foreach ($pages as $new_action => $label) {
        echo ($action == $new_action) ? "<b>" : "<a href='?action=$new_action'>";
        echo $label;
        echo ($action == $new_action) ? "</b>" : "</a>";

        if ( $label != end($pages) )
            echo " | ";
    }
    echo "</p>";
}

    // vim: sw=4 ts=4 expandtab

