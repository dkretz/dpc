<?PHP
ini_set('display_errors', 1);
error_reporting(E_ALL);

// preferences arrive first via $userP, which is loaded in dpinit.
// If this is recursive from userprefs.php, it includes POST values,
// which are updates to make.
// So first apply all posts; 
// then refresh $userP;
// then build screen.
// insertdb is stored as a hidden field with value 'true', so it's always
// set whenever there are POST variables set

$relPath="./pinc/";
// dpinit grabs prefs and loads userP
include_once($relPath.'dpinit.php');
//include_once($relPath.'prefs_options.inc');
include_once($relPath.'tabs.inc');

$User->IsLoggedIn()
	or RedirectToLogin();

$selected_tab       = Arg("tab", 0);
$insertdb           = Arg("insertdb", 0);
$mkProfile          = Arg("mkProfile", 0);
$swProfile          = Arg("swProfile");    // they clicked to change profile
$c_profile          = Arg("c_profile");     // profile to switch to 
$restorec           = Arg("restorec");

$username           = $User->Username();
                    
$real_name          = Arg("real_name");
$email_updates      = Arg("email_updates");
$u_lang             = Arg("u_lang");
$u_intlang          = Arg("u_intlang");
$u_privacy          = Arg("u_privacy");
$u_align            = Arg("u_align");
$u_neigh            = Arg("u_neigh");
$cp_credit          = Arg("cp_credit");
$ip_credit          = Arg("ip_credit");
$tp_credit          = Arg("tp_credit");
$cp_credit          = Arg("cp_credit");
$pm_credit          = Arg("pm_credit");
$pp_credit          = Arg("pp_credit");
//$credit_name        = Arg("credit_name", $User->CreditNameSetting());
//$credit_other       = Arg("credit_other", $User->CreditOther());
$i_pmdefault        = Arg("i_pmdefault");
$auto_project_thread   = Arg("auto_project_thread");

if($insertdb) {
    switch($selected_tab) {
        case 1:
            $updatesql = save_proofreading_tab();
            break;
        case 2:
            save_pm_tab($i_pmdefault, $auto_project_thread);
            break;
        default:
            save_general_tab();
            break;
    }
    $User->FetchPreferences();

}

if ($swProfile) {
    // User clicked "Switch profile"
    if(! $c_profile) {
        die("requested switch to blank profile $c_profile failed.");
    }
    $User->SetProfile($c_profile);
    exit;
}


$event_id = 0;
$window_onload_event = 'set_credit_other()';


// restore session values from db
if ($restorec) {
    $User->FetchPreferences();
}


// Note that these indices are used in two if-else-clauses below
$tabs = array(0 => _('General'),
              1 => _('Proofreading'));

if ($User->IsProjectManager())
    $tabs[2] = _('Project managing');

// header, start of table, form, etc. common to all tabs
$header = _("Personal Preferences");
theme($header, "header");
echo_stylesheet_for_tabs();
if(isset($updatesql)) {
	html_comment( $updatesql );
}
echo "<center>";
$popHelpDir = "$code_url/faq/pophelp/prefs/set_";
include($relPath.'js_newpophelp.inc');

?>
<script language='javascript'>
function $(s) { return document.getElementById(s); }

function check_boxes(value) {
    var i, name;
    for (i = 1; i < arguments.length; i++) {
        name = arguments[i];
        $(name).checked = value;
    }
}

function set_credit_other() {
    var t = document.getElementById('credit_name');
    var o = document.getElementById('credit_other');
    if(t && o) { 
        o.disabled = (t.selectedIndex != 2);
    }
}
</script>

<?PHP
echo "<form id='frmpref' name='frmpref' action='' method='post'>
	  <input type='hidden' name='tab' value='$selected_tab' />
      <table class='w90' border='1' bordercolor='#111111' cellspacing='0' cellpadding='0'>
      <tr><td bgcolor='white' colspan='6' align='center'>
		  <h1>"._("Preferences Page for ").$User->Username()."</h1>
	  </td></tr>\n";

echo_tabs($tabs, $selected_tab);


// display one of the tabs

switch($selected_tab) {
    case 1:
        echo_proofreading_tab();
        break;
    /** @noinspection PhpMissingBreakStatementInspection */
    case 2:
        if($User->IsProjectManager()) {
            echo_pm_tab();
            break;
        }

    default:
        echo_general_tab();
        break;
}

echo "</table>
	  <input type='hidden' name='insertdb' value='true'>
      </form>
      <br></center>

<script language='javascript'>
window.onload = function() {
    set_credit_other();
}; 
</script>

";

theme("", "footer");

// End main code. Functions below.
// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

/*************** TO GENERATE TABS ***************/

// Produce tabs (display as an unordered list of links to non-CSS browsers)
/**
 * @param $tab_names array
 * @param $selected_tab int
 */
function echo_tabs($tab_names, $selected_tab) {
    echo "<tr><td colspan='6' align='left'>
            <div id='tabs'>
              <ul>\n";
    foreach (array_keys($tab_names) as $index) {
        if ($index == $selected_tab) {
            echo "<li id='current'>";
        } 
        else {
            echo "<li>";
        }
        $url = "?tab=$index";
        echo "
        <a href='$url'>{$tab_names[$index]}</a></li>\n";
    }
    echo "    </ul>
   </div>
   </td></tr>\n";
}

/*************** GENERAL TAB ***************/

function echo_general_tab() {
    global $User;
    global $i_stats, $u_lang;

    echo "<tr>\n";

    // real name
    show_preference(
        _('Name'), 'real_name', 'name',
        $User->RealName(),
        'textfield',
        array( '', '' ));

    // language
    show_preference(
        _('Language'), 'u_lang', 'lang',
        $User->InterfaceLanguage(),
        'dropdown',
        $u_lang);

    echo "</tr>
          <tr>\n";

	echo "<td></td><td></td><td></td>\n";
//    show_preference(
//        _('Interface Language'), 'u_intlang', 'intlang',
//        $User->InterfaceLanguage(),
//        'dropdown',
//        $u_intlang_options);

    show_preference(
        _('Email Updates'), 'email_updates', 'updates',
        $User->IsEmailUpdates(),
        'radio_group',
        array( 1 => _("Yes"), 0 => _("No") ));
    echo "</tr>\n";

//    echo "<tr>
//        <td></td>\n";
//    show_link_as_if_preference(
//        _('Password'),
//        'password',
//        url_for_change_password(),
//        _("Reset Password"));

//    show_preference(
//        _('Statistics Bar Alignment'), 'u_align', 'align',
//        $User->Align(),
//        'radio_group',
//        array( 1 => _("Left"), 0 => _("Right") ));
//    echo "</tr>\n";

    echo "<tr>\n";
    show_preference(
        _('Statistics'), 'u_privacy', 'privacy',
        $User->Privacy(),
        'dropdown',
        $i_stats);

    show_preference(
        _('Show Rank Neighbors'),
	    'u_neigh',
	    'neighbors',
        $User->NeighborRadius(),
        'dropdown',
        array('0'=>0,'2'=>2,'4'=>4,'6'=>6,'8'=>8,'10'=>10,'12'=>12,'14'=>14,'16'=>16,'18'=>18,'20'=>20));
    echo "</tr>\n";

    echo "<tr>\n";

	echo "<td></td><td></td><td></td>\n";
//    show_preference(
//        _('Credits Wanted'), NULL, 'creditswanted',
//        NULL,
//        'credits_wanted',
//        NULL);

    show_preference(
        _('Credit Name'), NULL, 'creditname',
        NULL,
        'credit_name',
        NULL);
    echo "</tr>\n";

    echo_bottom_button_row();
}

function save_general_tab() {
    global $User, $dpdb;
    global $real_name, $email_updates;
    global $u_align, $u_neigh, $u_lang, $u_intlang, $u_privacy;
    global $cp_credit, $ip_credit, $tp_credit, $pm_credit, $pp_credit;
    global $credit_name, $credit_other;

    $real_name      = $User->RealName();
    $email_updates  = $User->IsEmailUpdates();

    // set users values
    $sql = "
        UPDATE users 
        SET real_name       = '$real_name', 
            email_updates   = '$email_updates',
            u_align         = '$u_align',
            u_neigh         = '$u_neigh',
            u_lang          = '$u_lang' ,
            i_prefs         = '1',
            u_intlang       = '$u_intlang',
            u_privacy       = '$u_privacy'
        WHERE username      = '{$User->Username()}'";
    
    $dpdb->SqlExecute($sql);

    // Opt-out of credits when Content-Providing (deprecated), Image Preparing, 
    // Text Preparing, Project-Managing and/or Post-Processing.
    $User->SetBoolean("cp_anonymous", $cp_credit == "yes");
    $User->SetBoolean("ip_anonymous", $ip_credit == "yes");
    $User->SetBoolean("tp_anonymous", $tp_credit == "yes");
    $User->SetBoolean("pm_anonymous", $pm_credit == "yes");
    $User->SetBoolean("pp_anonymous", $pp_credit == "yes");
    $User->SetSetting("credit_name",  $credit_name);
    $User->SetSetting("credit_other", $credit_other);
}

/*************** PROOFREADING TAB ***************/

function echo_proofreading_tab() {

    // see if they already have 10 profiles, etc.
//    $pf_rows = $dpdb->SqlRows("
//        SELECT profilename, id FROM user_profiles
//        WHERE u_ref='$uid'
//        ORDER BY id");
//
//    $pf_num = count($pf_rows);

    echo "<tr>\n";
    td_label_long( 6, _('Profiles') );
    echo "</tr>\n";

    echo "<tr style='background-color: #e7efde;'>
        <td colspan='5'></td>\n";
//        <td class='right'>Default Profile</td>
//        <td bgcolor='#ffffff' colspan='1' align='center'>";
    // show all profiles
//    echo "<select name='c_profile' id='c_profile'>";
//    foreach($pf_rows as $row) {
//        $pf_Dex = $row['id'];
//        $pf_Val = $row['profilename'];
//        echo "<option value='$pf_Dex'";
//        if ($pf_Dex == $User->ProfileId()) {
//            echo " SELECTED";
//        }
//        echo ">$pf_Val</option>";
//    }
//    echo "</select>";
//    echo " <input type='submit' value='"._("Switch Profiles")."'
//                                    id='swProfile' name='swProfile'>&nbsp;";
//    echo "</td>";
//    td_pophelp( 'switch' );
    echo "</tr>\n";

    echo "<tr>\n";
	echo "<td></td><td></td><td></td>\n";
//    show_preference(
//        _('Screen Resolution'), 'i_res', 'screenres',
//        $User->ScreenResolution(),
//        'dropdown',
//        $i_r);

	echo "<td></td><td></td><td></td>\n";
	/*
    show_preference(
        _('Launch in New Window'), 'i_newwin', 'newwindow',
        $User->IsNewWindow(),
        'radio_group',
        array( 1 => _("Yes"), 0 => _("No") )
    );
	*/
    echo "</tr>\n";

    echo "<tr>\n";
	echo "<td></td><td></td><td></td>\n";
	/*
    show_preference(
        _('Interface Type'), 'i_type', 'facetype',
            $User->InterfaceIndex(),
            'radio_group',
            array( 0 => "Standard", 1 => "Enhanced", 2 => "Whistler<br>", 3 => "Blackcomb", 4 => "Ahmic", 5 => "Pennask")
        );
	*/
//    show_preference(
//        _('Show Toolbar'), 'i_toolbar', 'toolbar',
//        $User->IsToolbar(),
//        'radio_group',
//        array( 1 => _("Yes"), 0 => _("No") )
//    );
//    echo "</tr>\n";
//
//    echo "<tr>\n";
	echo "<td></td><td></td><td></td>\n";
	/*
    show_preference(
        _('Interface Layout'), 'i_layout', 'layout',
        $User->LayoutIndex(),
        'radio_group',
        array(
            1 => '<img src="tools/proofers/gfx/bt4.png" width="26" alt="'._("Vertical").'">',
            0 => '<img src="tools/proofers/gfx/bt5.png" width="26" alt="'._("Horizontal").'">'
        )
    );
	*/
//    show_preference(
//        _('Show Status Bar'), 'i_statusbar', 'statusbar',
//        $User->ShowStatusBar(),
//        'radio_group',
//        array( 1 => _("Yes"), 0 => _("No") )
//    );
    echo "</tr>\n";

    echo "<tr>\n";
	echo "<td></td> <td></td> <td></td> <td></td> <td></td> <td></td>";
//    td_label_long( 2,
//        "<img src='tools/proofers/gfx/bt4.png'>" . _("Vertical Interface Preferences") );
//    td_pophelp( 'vertprefs' );
//    td_label_long( 2,
//        "<img src='tools/proofers/gfx/bt5.png'>" . _("Horizontal Interface Preferences") );
//    td_pophelp( 'horzprefs' );
    echo "</tr>\n";

    echo "<tr>\n";
	echo "<td></td> <td></td> <td></td> <td></td> <td></td> <td></td>";
	/*
    show_preference(
        _('Font Face'), 'v_fntf', 'v_fontface',
        $User->VerticalFontFace(),
        'dropdown',
        $f_f
    );
    show_preference(
        _('Font Face'), 'h_fntf', 'h_fontface',
        $User->HorizontalFontFace(),
        'dropdown',
        $f_f
    );
	*/
    echo "</tr>\n";

    echo "<tr>\n";
	echo "<td></td> <td></td> <td></td> <td></td> <td></td> <td></td>";
	/*
    show_preference(
        _('Font Size'), 'v_fnts', 'v_fontsize',
        $User->VerticalFontSize(),
        'dropdown',
        $f_s
    );
    show_preference(
        _('Font Size'), 'h_fnts', 'h_fontsize',
        $User->HorizontalFontSize(),
        'dropdown',
        $f_s
    );
	*/
    echo "</tr>\n";

    echo "<tr>\n";
	echo "<td></td> <td></td> <td></td> <td></td> <td></td> <td></td>";
	/*
    show_preference(
        _('Image Zoom'), 'v_zoom', 'v_zoom',
        $User->VerticalZoom(),
        'textfield',
        array( 3, _("% of 1000 pixels") )
    );
    show_preference(
        _('Image Zoom'), 'h_zoom', 'h_zoom',
        $User->HorizontalZoom(),
        'textfield',
        array( 3, _("% of 1000 pixels") )
    );
	*/
    echo "</tr>\n";

    echo "<tr>\n";
	echo "<td></td> <td></td> <td></td> <td></td> <td></td> <td></td>";
	/*
    show_preference(
        _('Text Frame Size'), 'v_tframe', 'v_textsize',
        $User->VerticalTextFramePct(),
        'textfield',
        array( 3, _("% of browser width") )
    );
    show_preference(
        _('Text Frame Size'), 'h_tframe', 'h_textsize',
        $User->HorizontalTextFramePct(),
        'textfield',
        array( 3, _("% of browser height") )
    );
	*/
    echo "</tr>\n";

    echo "<tr>\n";
	echo "<td></td> <td></td> <td></td> <td></td> <td></td> <td></td>";
	/*
    show_preference(
        _('Scroll Text Frame'), 'v_tscroll', 'v_scroll',
        $User->IsVerticalTextScroll(),
        'radio_group',
        array( 1 => _("Yes"), 0 => _("No") )
    );
    show_preference(
        _('Scroll Text Frame'), 'h_tscroll', 'h_scroll',
        $User->IsHorizontalTextScroll(),
        'radio_group',
        array( 1 => _("Yes"), 0 => _("No") )
    );
	*/
    echo "</tr>\n";

    echo "<tr>\n";
	echo "<td></td> <td></td> <td></td> <td></td> <td></td> <td></td>";
	/*
    show_preference(
        _('Wrap Text'), 'v_twrap', 'v_wrap',
        $User->VerticalWrap(),
        'radio_group',
        array( 1 => _("Yes"), 0 => _("No") )
    );
    show_preference(
        _('Wrap Text'), 'h_twrap', 'h_wrap',
        $User->HorizontalWrap(),
        'radio_group',
        array( 1 => _("Yes"), 0 => _("No") )
    );
	*/
    echo "</tr>\n";

    echo "<tr>\n";
	echo "<td></td> <td></td> <td></td> <td></td> <td></td> <td></td>";
	/*
    show_preference(
        _('Number of Text Lines'), 'v_tlines', 'v_textlines',
        $User->VerticalTextLines(),
        'textfield',
        array( 3, "" )
    );
    show_preference(
        _('Number of Text Lines'), 'h_tlines', 'h_textlines',
        $User->HorizontalTextLines(),
        'textfield',
        array( 3, "" )
    );
	*/
    echo "</tr>\n";

    echo "<tr>\n";
	echo "<td></td> <td></td> <td></td> <td></td> <td></td> <td></td>";
	/*
    show_preference(
        _('Length of Text Lines'), 'v_tchars', 'v_textlength',
        $User->VerticalTextChars(),
        'textfield',
        array( 3, " "._("characters") )
    );
    show_preference(
        _('Length of Text Lines'), 'h_tchars', 'h_textlength',
        $User->HorizontalTextChars(),
        'textfield',
        array( 3, " "._("characters") )
    );
	*/
    echo "</tr>\n";

    // buttons
    echo "<tr><td bgcolor='#ffffff' colspan='6' align='center'>";
//    if ( $userP['prefschanged'] ) {
//        echo "<input type='submit' value='"._("Restore to Saved Preferences")
//            ."' name='restorec'> &nbsp;";
//    }
    echo "
  <input type='submit' value="._("'Save Preferences'")." name='change'> &nbsp;\n";

//    if ($pf_num < 10) {
//        echo "
//        <input type='submit' value="._("'Save as New Profile'")." name='mkProfile'> &nbsp;
//        <input type='submit' value="._("'Save as New Profile and Quit'")
//            ." name='mkProfileAndQuit'> &nbsp;\n";
//    }
    echo " </td></tr>\n";
}

function save_proofreading_tab() {
    global $User;
    global $dpdb;

    $username = $User->Username();
    $sql = "UPDATE user_profiles up
            JOIN users u ON u.u_profile = up.id
            SET i_type      = ".Arg('i_type').",
                i_layout    = ".Arg('i_layout').",
                i_newwin    = ".Arg('i_newwin').",
                v_fntf      = ".Arg('v_fntf').",
                v_fnts      = ".Arg('v_fnts').",
                v_zoom      = ".Arg('v_zoom').",
                v_tframe    = ".Arg('v_tframe').",
                v_tscroll   = ".Arg('v_tscroll').",
                v_twrap     = ".Arg('v_twrap').",
                v_tlines    = ".Arg('v_tlines').",
                v_tchars    = ".Arg('v_tchars').",
                h_fntf      = ".Arg('h_fntf').",
                h_fnts      = ".Arg('h_fnts').",
                h_zoom      = ".Arg('h_zoom').",
                h_tframe    = ".Arg('h_tframe').",
                h_tscroll   = ".Arg('h_tscroll').",
                h_twrap     = ".Arg('h_twrap').",
                h_tlines    = ".Arg('h_tlines').",
                h_tchars    = ".Arg('h_tchars')."
            WHERE u.username = '$username'";
    $dpdb->SqlExecute($sql);
	return $sql;
}

/*************** PM TAB ***************/

function echo_pm_tab() {
    global $User;
    global $i_pm;

    show_preference(
        _('Default PM Page'), 'i_pmdefault', 'pmdefault',
        $User->PMDefault(),
        'dropdown',
        $i_pm
    );

    $auto_project_thread = $User->BooleanSetting('auto_project_thread');
    show_preference(
        _('Automatically watch your project threads'), 'auto_project_thread', 'auto_thread',
        ($auto_project_thread ? 'yes' : 'no'),
        'dropdown',
        array( 'yes' => _('Yes'), 'no' => _('No') )
    );
    echo "</tr>\n";

    echo "<tr><td bgcolor='#ffffff' colspan='6' align='center'>";
    echo_bottom_button_row();
    echo "</td></tr>\n";
}

function save_pm_tab($i_pmdefault, $auto_project_thread) {
    global $User, $dpdb;

    $sql = "
        UPDATE users 
        SET i_pmdefault = '$i_pmdefault'
        WHERE username = '{$User->Username()}'";
    $dpdb->SqlExecute($sql);

    // remeber if the PM wants to be automatically signed up for email notifications of
    // replies made to their project threads

    $User->SetBoolean("auto_project_thread", $auto_project_thread == 'yes');
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function echo_bottom_button_row() {
    echo "
    <tr><td bgcolor='#ffffff' colspan='6' align='center'>
    <input type='submit' value="._("'Save Preferences'")." name='change'> &nbsp;
    <input type='submit' value="._("'Quit'")." name='quitnc'>
    </td></tr>\n";
}

// ---------------------------------------------------------

function show_preference( $label, $field_name, $pophelp_name,
                                        $current_value, $type, $extras ) {
    td_label( "$label:" );

    echo "<td bgcolor='#ffffff' align='left'>";
    switch($type) {
        case "textfield":
            show_textfield($field_name, $current_value, $extras );
            break;

        case "dropdown":
            show_dropdown($field_name, $current_value, $extras) ;
            break;

        case "radio_group":
            show_radio_group( $field_name, $current_value, $extras ) ;
            break;

//        case "credits_wanted":
//            show_credits_wanted() ;
//            break;

        case "credit_name":
//            show_credit_name() ;
            break;

        default:
			dump($type);
            assert(false);
    }
    echo "</td>";

    td_pophelp( $pophelp_name );
}

// ---------------------------------------------------------

/*
function show_credits_wanted() {
    global $User;

    $cp_credit_checked = $User->BooleanSetting('cp_anonymous') ? 'checked ' : '';
    $ip_credit_checked = $User->BooleanSetting('ip_anonymous') ? 'checked ' : '';
    $tp_credit_checked = $User->BooleanSetting('tp_anonymous') ? 'checked ' : '';
    $pm_credit_checked = $User->BooleanSetting('pm_anonymous') ? 'checked ' : '';
    $pp_credit_checked = $User->BooleanSetting('pp_anonymous') ? 'checked ' : '';

    echo "
    <input type='checkbox' name='cp_credit' id='cp_credit' value='yes' $cp_credit_checked/> CP
    <input type='checkbox' name='ip_credit' id='ip_credit' value='yes' $ip_credit_checked/> IP
    <input type='checkbox' name='tp_credit' id='tp_credit' value='yes' $tp_credit_checked/> TP
    <input type='checkbox' name='pp_credit' id='pp_credit' value='yes' $pp_credit_checked/> PP
    " 
    . ($User->IsProjectManager()
        ? "<input type='checkbox' name='pm_credit' id='pm_credit' value='yes' $pm_credit_checked/> PM" 
        : "") 
    ."<br />
    <a href='#' onClick='check_boxes(true, 'cp_credit', 
            'ip_credit', 'tp_credit', 'pm_credit', 'pp_credit');'>Check all</a>
     | 
    <a href='#' onClick='check_boxes(false, 'cp_credit',
            'ip_credit', 'tp_credit', 'pm_credit', 'pp_credit');'>Uncheck all</a>\n";
}
*/

// ---------------------------------------------------------

// Handles 'credit_name' and 'credit_other'.
/*
function show_credit_name() {
    global $User;

    $credname = $User->CreditNameSetting();
    show_credit_name_select($credname);
    echo " ";

    $credother  = h( $User->CreditOther());
    echo "<input type='text' id='credit_other' name='credit_other' 
                                        value='$credother' />\n";
}
*/

// The third argument should be a 'real' array.
// The labels will be displayed to the user,
// one of the values will be passed back from the browser as the selected value.
//
// The fifth (optional argument), $on_change, is used as a javascript event handler
// on the dropdown. It will be made into a function so quote marks should not
// be any problems.
// Example value: "alert('Hi'+\"!\");"
// Using this as the $on_change-argument will popup an alert displaying the string
// 'Hi!' (without quotes).
// The use of these event handlers are foremost to enable/disable certain preferences
// depending on the values set in other preferences.
//
// The event handler will also be run on page-load and in order to achieve this,
// something resembling a hack has been introduced. Always refer to the form
// as the variable f, and always use the variable t to refer to the dropdown.
// DO NOT USE this.form and this, respectively!!!

/*
function show_credit_name_select ($curval) {
    $values = array('real_name', 'username', 'other');
    $labels = array( _('Real Name'), _('Username'), _('Other:'));

    echo "<select name='credit_name' id='credit_name' onchange='set_credit_other()'>\n";
    for ($i = 0; $i < count($values); $i++) {
        $value = $values[$i];
        $label = $labels[$i];
        echo "<option value='$value'";
        if ($curval == $value) {
            echo " SELECTED"; 
        }
        echo ">" . h($label) . "</option>\n";
    }
    echo "</select>\n";
}
*/

// ---------------------------------------------------------

function show_dropdown($field_name, $current_value, $options) {
    echo "<select name='$field_name' id='$field_name'>";
    foreach ( $options as $option_value => $option_label ) {
        echo "<option value='$option_value'";
        if ($option_value == $current_value) { echo " SELECTED"; }
        echo ">$option_label</option>";
    }
    echo "</select>";
}

function show_radio_group( $field_name, $current_value, $options ) {
    foreach ( $options as $option_value => $option_label ) {
        echo "<input type='radio' name='$field_name' id='$field_name' value='$option_value'";
        if ( strtolower($option_value) == strtolower($current_value) )
        {
            echo " CHECKED";
        }
        echo ">$option_label&nbsp;&nbsp;";
    }
}

function show_textfield( $field_name, $current_value, $extras ) {
    list($size, $rest) = $extras;
    echo "<input type='text' name='$field_name' id='$field_name' 
                                value='$current_value' size='$size'>$rest";
}

// ---------------------------------------------------------

function show_link_as_if_preference(
    $label,
    $pophelp_name,
    $link_url,
    $link_text ) {
    td_label( "$label:" );
    echo "<td bgcolor='#ffffff' align='left'>";
    echo "<a href='$link_url'>$link_text</a>";
    echo "</td>";
    td_pophelp( $pophelp_name );
}

function show_blank()
{
    td_label( "&nbsp;" );
    echo "
        <td bgcolor='#ffffff' align='left'>&nbsp;</td>
        <td bgcolor='#ffffff' align='center'>&nbsp;</td>\n";
}

// ---------------------------------------------------------

function td_label( $label ) {
    global $theme;
    echo "<td bgcolor='".$theme['color_logobar_bg']."' align='right'>
          $label
          </td>\n";
}

function td_label_long( $colspan, $label ) {
    global $theme;
    echo "<td bgcolor='".$theme['color_logobar_bg']."' colspan='$colspan' align='center'>
          $label
          </td>\n";
}

function td_pophelp( $pophelp_name ) {
    echo "<td bgcolor='#ffffff' align='center'>
          &nbsp;<a href=\"JavaScript:newHelpWin('$pophelp_name');\">?</a>&nbsp;
          </td>\n";
}

// ---------------------------------------------------------

// vim: sw=4 ts=4 expandtab
?>

