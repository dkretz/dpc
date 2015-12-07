<?php
/**
 * Created by JetBrains PhpStorm.
 * User: don
 * Date: 3/29/13
 * Time: 7:25 PM
 */

$proof_url = "http://www.pgdpcanada.net/c/tools/proofers/";

function redirect_to_url( $url, $metacomment = "" ) {
    divert( $url, $metacomment );
    exit;
}

function link_to_new_url($url, $msg) {
    return link_to_url($url, $msg, true);
}

function link_to_url( $url, $prompt, $is_new_tab = false ) {
    $prompt = left($prompt, 1) == "\\" ? mid($prompt, 2) : _($prompt);
    $prompt = _($prompt);
    return $is_new_tab
        ? "<a href='$url' target='_blank'>".h(_($prompt))."</a>\n"
        : "<a href='$url'>".h(_($prompt))."</a>\n";
}

function red_link_to_url( $url, $prompt, $is_new_tab = false ) {
	$prompt = left($prompt, 1) == "\\" ? mid($prompt, 2) : _($prompt);
	$prompt = _($prompt);
	return $is_new_tab
		? "<a class='red' href='$url' target='_blank'>".h(_($prompt))."</a>\n"
		: "<a class='red' href='$url'>".h(_($prompt))."</a>\n";
}

// -- site

function url_for_logout() {
    return "http://www.pgdpcanada.net/c/tools/logout.php";
}

function link_to_logout($prompt = "Log out") {
    return link_to_url(url_for_logout(), $prompt);
}

function url_for_site() {
    return "http://www.pgdpcanada.net/c/default.php";
}

function link_to_site($prompt = "Home") {
    return link_to_url(url_for_site(), $prompt);
}

function redirect_to_home() {
    global $site_url;
    redirect_to_url($site_url);
}

function url_for_help() {
    return "http://www.pgdpcanada.net/wiki/index.php/FAQ_Central";
}

function link_to_help($prompt = "Help") {
    return link_to_url(url_for_help(), $prompt);
}

function url_for_proofing_guidelines() {
    return "http://www.pgdpcanada.net/wiki/index.php/FAQ_Proofreading_Guidelines";
}

function link_to_proofing_guidelines($prompt = "Proofing Guidelines") {
    return link_to_url(url_for_proofing_guidelines(), $prompt);
}

function url_for_formatting_guidelines() {
    return "http://www.pgdpcanada.net/wiki/index.php/FAQ_Formatting_Guidelines";
}

function link_to_formatting_guidelines($prompt = "Formatting Guidelines") {
    return link_to_url(url_for_formatting_guidelines(), $prompt);
}


// -- phase

function url_for_phase($phase) {
    return "http://www.pgdpcanada.net/c/tools/{$phase}.php";
}

function link_to_phase($phase, $prompt = "") {
    if($prompt == "") {
        $prompt = $phase;
    }
    return link_to_url(url_for_phase($phase), $prompt);
}

// -- proof next

function redirect_no_page_available($projectid) {
    redirect_to_project($projectid, "No more pages available");
}

//function redirect_to_proof_next_page($projectid) {
//    redirect_to_url(url_for_proof_next_page($projectid));
//}

//function url_for_proof_next_page($projectid) {
//    global $proof_url;
//    return $proof_url . "proof.php?projectid=$projectid";
//}

function redirect_to_proof_next($projectid) {
    redirect_to_url(url_for_proof_next($projectid));
}

function link_to_proof_next($projectid, $prompt) {
    return link_to_url(
        url_for_proof_next($projectid), $prompt);
}

function link_to_proof_frame_next($projectid, $prompt) {
    return link_to_url(
        url_for_proof_frame_next($projectid), $prompt);
}

function  url_for_proof_frame_next($projectid) {
    global $proof_url;
    return $proof_url . "proof_frame.php?projectid=$projectid";
}

function url_for_proof_next($projectid) {
    global $proof_url;
    return $proof_url . "proof.php?projectid=$projectid";
}
function link_to_css($filename) {
    global $css_url;
    return "<link rel='stylesheet' href='{$css_url}/{$filename}'>\n";
}

function favicon() {
    global $code_url;
    return "<link rel='shortcut icon' href='$code_url/favicon.ico'>\n";
}

function link_to_js($filename) {
    global $js_url;
    return "<script src='{$js_url}/{$filename}' charset='UTF-8'></script>\n";
}


// -- registration

function url_for_registration() {
    global $registration_url;
    return $registration_url;
}

function link_to_registration($text = "") {
    if(! $text)
        $text = _("Register");
    return link_to_url(url_for_registration(), $text);
}

function url_for_change_password() {
    global $change_password_url;
    return $change_password_url;
}

function link_to_change_password($text = "") {
    if(! $text)
        $text = _("Change password");
    return link_to_url(url_for_change_password(), $text);
}



// -- processtext

function url_for_proof_processpage() {
    global $code_url;
    return $code_url . "/proof/processpage.php";
}

function url_for_processpage() {
    global $proof_url;
    return $proof_url . "processpage.php";
}

//function url_for_processtext() {
//    global $proof_url;
//    return $proof_url . "processtext.php";
//}
//function url_for_iprocesstext() {
//    global $proof_url;
//    return $proof_url . "iprocesstext.php";
//}


// -- project


function url_for_project($projectid) {
    global $code_url;
    return "$code_url/project.php?projectid={$projectid}";
}

function link_to_project($projectid, $prompt = "", $newpage = false) {
    return link_to_url(url_for_project($projectid), $prompt, $newpage);
}

function url_for_project_level($projectid, $level = 3) {
    global $code_url;
    return "$code_url/project.php"
                ."?projectid={$projectid}"
                ."&amp;detail_level=$level";
}

function link_to_project_level($projectid, $level = 3, $prompt = "To Project", $newpage = 0) {
    return link_to_url(url_for_project_level($projectid, $level), $prompt, $newpage);
}

function redirect_to_project($projectid, $metacomment = "") {
    redirect_to_url(url_for_project($projectid), $metacomment);
}

function redirect_to_error_page($msg) {
    redirect_to_url(url_for_error_page($msg), $msg);
}

function url_for_error_page($msg) {
    global $code_url;
    return $code_url . "/error_page.php"
            . "?error_message=" . urlencode($msg);
}

/*
// -- differ

function url_for_differ($projectid) {
    global $proof_url;
    return $proof_url . "differ.php?projectid={$projectid}";
}

function link_to_differ($projectid, $prompt) {
    return link_to_url(url_for_differ($projectid), $prompt);
}
*/

// -- diffs

function url_for_my_diffs($roundid = "P1") {
    global $code_url;
    return "$code_url/user_pages.php?roundid=$roundid";
}

function link_to_my_diffs($roundid = "P1", $prompt = "My diffs", $newtab = true) {
    return link_to_url( url_for_my_diffs($roundid), $prompt, $newtab);
}

// -- edit project

function url_for_edit_project($projectid) {
    global $pm_url;
    return "{$pm_url}/editproject.php"
        ."?projectid={$projectid}";
}

function link_to_edit_project($projectid, $prompt, $newtab = false) {
    return link_to_url( url_for_edit_project($projectid), $prompt, $newtab);
}

// -- round page


function link_to_round($roundid, $prompt = "") {
    if($prompt == "")
        $prompt = "{$roundid}";
    return "<a href='".url_for_round($roundid)."'"
    ." title='".h(_("Proofreading for Round $roundid"))."'>"
    .h(_($prompt))."</a>";
}

function url_for_round($roundid) {
    global $proof_url;
    return $proof_url . "round.php?roundid=$roundid";
}

function link_to_pp($prompt = "PP") {
    return link_to_url(url_for_pp(), $prompt);
}

function url_for_pp() {
    global $code_url;
    return "$code_url/tools/pp.php";
}
function link_to_ppv($prompt = "PPV") {
    return link_to_url(url_for_ppv(), $prompt);
}

function url_for_ppv() {
    global $code_url;
    return "$code_url/tools/ppv.php";
}

function url_for_upload_ppv_return($projectid) {
    return url_for_upload_page("ppv_temp", $projectid);
}
function link_to_upload_pp($projectid, $prompt) {
    return link_to_url(url_for_upload_pp($projectid), $prompt);
}
function url_for_upload_pp($projectid) {
    return url_for_upload_page("pp_temp", $projectid);
}
function url_for_upload_ppv_complete($projectid) {
    return url_for_upload_page("ppv_complete", $projectid);
}

function url_for_upload_page($upload_action, $projectid) {
    global $code_url;
    return "$code_url/tools/upload_text.php"
            ."?projectid={$projectid}"
           ."&amp;upload_action=$upload_action";

}

// -- create project

function link_to_create_project($prompt = "Create project") {
    return link_to_url(url_for_create_project(), $prompt);
}

function url_for_create_project() {
    global $pm_url;
    return "{$pm_url}/createproject.php";
}

// -- proof frame

function url_for_proof_frame($projectid, $pagename) {
    global $proof_url;

    return $proof_url . "proof_frame.php"
        ."?projectid={$projectid}"
        . ($pagename ? "&amp;pagename={$pagename}" : "");
}

// -- proof page

function redirect_to_proof_page($projectid, $pagename) {
    redirect_to_url(url_for_proof_page($projectid, $pagename));
}

function link_to_proof_page($projectid, $pagename, $prompt, $newtab=false) {
    return link_to_url(
        url_for_proof_page($projectid, $pagename), $prompt, $newtab);
}

function url_for_proof_page($projectid, $pagename) {
    global $proof_url;
    return
        $proof_url . "proof.php?projectid=$projectid"
        ."&amp;pagename=$pagename";
}

// -- smooth reading

function url_for_smooth_reading() {
    global $code_url;
    return "$code_url/tools/post_proofers/smooth_reading.php";
}

function link_to_smooth_reading($prompt, $isnewpage = false) {
    return link_to_url(url_for_smooth_reading(), $prompt, $isnewpage);
}


// -- upload text to smooth
function link_to_upload_text_to_smooth($projectid, $prompt = "Upload") {
    return link_to_url(url_for_upload_text_to_smooth($projectid), $prompt);
}
function url_for_upload_text_to_smooth($projectid) {
	return url_for_upload_page("smooth_avail", $projectid);
}

function link_to_smoothed_upload($projectid, $prompt = "Upload") {
    return link_to_url(url_for_smoothed_upload($projectid), $prompt);
}

function url_for_smoothed_upload( $projectid ) {
    return url_for_upload_page("smooth_done", $projectid);
}

function link_to_smooth_download($projectid, $prompt = "Download") {
    return link_to_url(url_for_smooth_download($projectid), $prompt);
}

function url_for_smooth_download($projectid) {
    global $projects_url;
    return "$projects_url/$projectid/{$projectid}_smooth_avail.zip";
}

function url_for_image_index($projectid) {
    global $code_url;
    return "$code_url/tools/proofers/images_index.php"
        . "?projectid=$projectid";
}

function link_to_image_index($projectid, $prompt = "View images online", $is_new_tab = false) {
    return link_to_url(url_for_image_index($projectid), $prompt, $is_new_tab);
}

function link_to_project_text($projectid, $phase = 'F2', $prompt = "", $isnew = false) {
    return link_to_url(url_for_project_text($projectid, $phase), $prompt, $isnew);
}

function url_for_project_text($projectid, $phase = "F2") {
    global $code_url;
    return "$code_url/project_text.php"
    . "?projectid=$projectid"
    . "&amp;phase=$phase";
}
/*
function link_to_upload_smoothed_text($projectid, $prompt = "Upload smooth reading notes") {
	return link_to_url(url_for_upload_smoothed_text($projectid), $prompt);
}

function url_for_upload_smoothed_text($projectid) {
	return url_for_upload_page("smooth_done", $projectid);
//    global $code_url;
//    return "$code_url/tools/upload_text.php"
//    ."?projectid=$projectid"
//    ."&amp;upload_action=smooth_done";
}
*/


function url_for_download_text($projectid, $pagename, $phase) {
    global $code_url;
    return "$code_url/tools/project_manager/downloadproofed.php"
                ."?projectid=$projectid"
                ."&amp;pagename=$pagename"
                ."&amp;roundid=$phase";
}


function link_to_download_text($projectid, $pagename, $phase, $prompt="View text", $isnew = false) {
    return link_to_url(url_for_download_text($projectid, $pagename, $phase), $prompt, $isnew);
}

function url_for_download_images($projectid) {
    global $code_url;
    return "$code_url/tools/download_images.php"
                    ."?projectid=$projectid"
                    ."&amp;dummy={$projectid}images.zip";
}
function link_to_download_images($projectid, $prompt="Download images", $isnew = false) {
    return link_to_url(url_for_download_images($projectid), $prompt, $isnew);
}

// -- diff

//function url_for_version_diff($projectid, $pagename, $version) {
//	global $pm_url;
//	return "$pm_url/diff.php"
//	       ."?projectid={$projectid}"
//	       . "&amp;pagename={$pagename}"
//	       . "&amp;version={$version}";
//}

//function link_to_version_diff($projectid, $pagename, $version, $prompt = "Diff", $isnew = false) {
//	return link_to_url(url_for_version_diff($projectid, $pagename, $version), $prompt, $isnew);
//}

function url_for_diff($projectid, $pagename, $phase, $mode = "1") {
    global $pm_url;
    return "$pm_url/diff.php"
            ."?projectid={$projectid}"
            . "&amp;pagename={$pagename}"
            . "&amp;phase={$phase}"
	        . "&amp;mode={$mode}";
}

function link_to_diff($projectid, $pagename, $phase, $prompt = "Diff", $mode = "1", $isnew = false) {
    return link_to_url(url_for_diff($projectid, $pagename, $phase, $mode), $prompt, $isnew);
}

// -- wiki

function  url_for_wiki() {
    global $wiki_url;
    return $wiki_url;
}

function link_to_wiki($prompt = "Wiki") {
    return link_to_url(url_for_wiki(), $prompt);
}

function url_for_forums() {
    global $forums_url;
    return $forums_url;
}

function link_to_forums($prompt = "Forums") {
    return link_to_url(url_for_forums(), $prompt);
}

function url_for_a_forum($forumid) {
    global $forums_url;
    return build_path($forums_url, "viewforum.php?f={$forumid}");
}

function link_to_a_forum($forumid, $prompt = "") {
    return link_to_url(url_for_a_forum($forumid), $prompt);
}

// -- project thread

function link_to_project_thread($postid, $prompt) {
    return link_to_url(url_for_project_thread($postid), $prompt);
}

function url_for_project_thread($postid) {
    global $forums_url;
    return build_path($forums_url,
        "viewtopic.php?t={$postid}");
}

//-- inbox

function link_to_inbox($prompt = "Inbox") {
    return link_to_url(url_for_inbox(), $prompt);
}

function url_for_inbox() {
    global $forums_url;
    return build_path($forums_url,
        "ucp.php?i=pm"
            ."&amp;folder=inbox");
}

// -- private message

function url_for_pm($username) {
    return url_for_private_message($username);
}
function url_for_private_message($username) {
    global $forums_url;
    $user_id = forum_user_id_for_username($username);

    return build_path($forums_url,
        "ucp.php?i=pm"
            ."&amp;mode=compose&amp;u={$user_id}");
}

function red_link_to_pm($username, $prompt = null, $is_new_tab = false) {
	if($prompt == null) {
		$prompt = $username;
	}
	return red_link_to_url(url_for_private_message($username), $prompt, $is_new_tab);


}
function link_to_pm($username, $prompt = null, $is_new_tab = false) {
    if($prompt == null) {
        $prompt = $username;
    }
    return link_to_private_message($username, $prompt, $is_new_tab);
}

function link_to_private_message($username, $prompt = null, $is_new_tab = false) {
    if(! $prompt) $prompt = $username;
    return link_to_url( url_for_private_message($username), $prompt, $is_new_tab);
}

// -- activity hub

function link_to_activity_hub($prompt = "Activity Hub") {
    return link_to_url(url_for_activity_hub(), $prompt);
}

function url_for_activity_hub() {
    global $code_url;
    return "$code_url/activity_hub.php";
}

function url_for_image_sources() {
    global $pm_url;
    return "$pm_url/image_sources.php";
}

function url_for_image_source_edit() {
    global $pm_url;
    return "$pm_url/image_source_edit.php";
}


function redirect_to_activity_hub() {
    redirect_to_url(url_for_activity_hub());
}

function url_for_search() {
    global $code_url;
    return "$code_url/search.php";
}

function link_to_search($prompt = "ProjectSearch") {
    return link_to_url(url_for_search(), $prompt);
}


function redirect_to_project_manager() {
    redirect_to_url(url_for_project_manager());
}

function link_to_project_manager($prompt = "Manage projects") {
    return link_to_url(url_for_project_manager(), $prompt);
}

function url_for_project_manager() {
    global $pm_url;
    return "$pm_url/projectmgr.php";
}

// -- clone project

function link_to_clone_project($projectid, $prompt) {
    return link_to_url(url_for_clone_project($projectid), $prompt);
}

function url_for_clone_project($projectid) {
    global $pm_url;
    return "$pm_url/editproject.php"
        ."?projectid={$projectid}"
        ."&amp;action=clone";
}

// -- project holds

function link_to_project_holds($projectid, $prompt) {
    return link_to_url(url_for_project_holds($projectid), $prompt);
}

function url_for_project_holds($projectid) {
    global $code_url;
    return "$code_url/holdmaster.php"
        ."?projectid={$projectid}";
}

// -- project files

function link_to_project_files($projectid, $prompt) {
    return link_to_url(url_for_project_files($projectid), $prompt);
}

function url_for_project_files($projectid) {
    global $code_url;
    return "$code_url/filemaster.php"
        ."?projectid={$projectid}";
}

// -- my projects

function url_for_my_projects() {
    global $code_url;
    return "$code_url/tools/proofers/my_projects.php";
}

function link_to_my_projects($prompt = "My Projects", $is_new_tab = false) {
    return link_to_url( url_for_my_projects(), $prompt, $is_new_tab);
}

// -- my preferences

function url_for_preferences() {
    global $code_url;
    return "$code_url/userprefs.php";
}

function link_to_preferences($prompt = "My Preferences", $is_new_tab = false) {
    return link_to_url( url_for_preferences(), $prompt, $is_new_tab);
}

// -- page detail

function url_for_page_detail($projectid) {
    global $code_url;
    return "$code_url/tools/project_manager/page_detail.php"
        ."?projectid={$projectid}";
}

function link_to_page_detail( $projectid, $prompt, $is_new_tab = false) {
    return link_to_url(
        url_for_page_detail($projectid), $prompt, $is_new_tab);
}

function url_for_page_detail_mine($projectid) {
    global $code_url;
    return "$code_url/tools/project_manager/page_detail.php"
        ."?projectid={$projectid}"
        ."&amp;select_by_user=1";
}

function link_to_page_detail_mine($projectid, $prompt, $is_new_tab = false) {
    return link_to_url(
        url_for_page_detail_mine($projectid), $prompt, $is_new_tab);
}

// -------------------------------------------------------------------
// -- view image - image + navigation
function url_for_view_image($projectid, $pagename) {
	global $code_url;
//	return url_for_page_image($projectid, $pagename);
    return "$code_url/tools/project_manager/displayimage.php"
        ."?projectid={$projectid}"
        ."&amp;pagename={$pagename}";
}
function link_to_view_image($projectid, $pagename, $msg = "", $is_new_tab = false) {
    if($msg == "")
        $msg = $pagename;
    return link_to_url(
        url_for_view_image($projectid, $pagename), $msg, $is_new_tab);
}

function link_to_page_image($projid, $pgname, $msg = "", $is_new_tab = true) {
    if($msg == "")
        $msg = $pgname;
    return link_to_url(
        url_for_page_image($projid, $pgname), $msg, $is_new_tab);
}

function url_for_page_image($projectid, $pagename) {
    global $code_url;
    return "$code_url/imgsrv.php"
        ."?projectid=$projectid"
        ."&amp;pagename=$pagename";
}

function link_to_version_text($projectid, $pagename, $version, $prompt = "", $is_new_tab = false) {
	if($prompt == "")
		$prompt = $pagename;
	return link_to_url(url_for_version_text($projectid, $pagename, $version), $prompt, $is_new_tab);
}

function url_for_version_text($projectid, $pagename, $version = 0) {
	global $code_url;
	return "$code_url/textsrv.php"
	       ."?projectid=$projectid"
	       ."&amp;pagename=$pagename"
	       ."&amp;version=$version";
}
function link_to_page_text($projectid, $pagename, $roundid = "PREP",
                                $prompt = "", $is_new_tab = false) {
    if($prompt == "")
        $prompt = $pagename;
    return link_to_url(
        url_for_page_text($projectid, $pagename, $roundid), $prompt, $is_new_tab);
}
function url_for_page_text($projectid, $pagename, $roundid = "PREP") {
    global $code_url;
    return "$code_url/textsrv.php"
        ."?projectid=$projectid"
        ."&amp;pagename=$pagename"
        ."&amp;roundid=$roundid";
}

function url_for_page_log($projectid, $pagename) {
    global $pm_url;
    return "$pm_url/page_log.php"
        ."?projectid=$projectid"
        ."&amp;pagename=$pagename";
}

function link_to_page_log($projectid, $pagename, $prompt = "Log") {
    return link_to_url(url_for_page_log($projectid, $pagename), $prompt);
}

function link_to_modify_page($action, $projectid, $pagename, $prompt = "") {
    return link_to_url( url_for_modify_page($action, $projectid, $pagename), $prompt);
}

function url_for_modify_page($action, $projectid, $pagename) {
    global $code_url;
    return "$code_url/pagesrv.php"
                ."?action=$action"
                ."&amp;projectid=$projectid"
                ."&amp;pagename=$pagename";
}

function link_to_fix($projectid, $pagename, $prompt = "Fix") {
    return link_to_url(url_for_fix($projectid, $pagename), $prompt) ;
}

function url_for_fix($projectid, $pagename) {
    global $code_url;
    return "$code_url/tools/project_manager/handle_bad_page.php"
        ."?projectid=$projectid"
        ."&amp;pagename=$pagename";
}

function url_for_project_trace($projectid = "") {
    global $code_url;
    return "$code_url/tools/trace.php"
        . ($projectid == "" ? "" :  "?projectid=$projectid");
}

function link_to_project_trace($projectid = "", $prompt = "trace") {
    return link_to_url(url_for_project_trace($projectid), $prompt);
}

// -- fadedpage

function url_for_fadedpage_catalog( $postednum ) {
    return "http://www.fadedpage.com/showbook.php?pid={$postednum}";
}

function link_to_fadedpage_catalog( $postednum, $prompt="FadedPage" ) {
    return link_to_url(url_for_fadedpage_catalog($postednum), $prompt);
}
function url_for_fadedpage() {
    return "http://www.fadedpage.com";
}
function link_to_fadedpage( $prompt = "FadedPage" ) {
    return link_to_url(url_for_fadedpage(), $prompt);
}

/**
 *   Members
 * @param string $prompt
 * @return string
 */


function link_to_member_list($prompt = "") {
    return link_to_url(url_for_member_list(), $prompt);
}

function url_for_member_list() {
    global $code_url;
    return "$code_url/stats/members/member_list.php";
}


function link_to_member_stats($username, $roundid = "all", $prompt = "") {
    return link_to_url(url_for_member_stats($username, $roundid), $prompt);
}

function url_for_member_stats($username, $roundid) {
    global $code_url;
    return 
	"$code_url/stats/members/member_stats.php"
                        . "?username=$username"
			. ($roundid ? "&amp;roundid=$roundid" : "");
}

function url_for_team_stats($tid, $roundid = "") {
    global $code_url;
    return $roundid
        ? "$code_url/stats/teams/tdetail.php?tid=$tid&amp;roundid=$roundid"
        : "$code_url/stats/teams/tdetail.php?tid=$tid";
}

function url_for_team_list() {
    global $stats_url;
    return $stats_url . "/teams/teamlist.php";
}
function url_for_team($tid) {
    global $code_url;
    return "$code_url/stats/teams/tdetail.php?tid=$tid";
}

function link_to_team($tid, $prompt, $is_new_tab = false, $red = false) {
    return $red
            ? red_link_to_url(url_for_team($tid), $prompt, $is_new_tab)
            : link_to_url(url_for_team($tid), $prompt, $is_new_tab);

}

function url_for_join_team($tid) {
    global $stats_url;
    return $stats_url . "/teams/jointeam.php?tid=$tid";
}

function url_for_quit_team($tid) {
    global $stats_url;
    return $stats_url . "/teams/quitteam.php?tid=$tid";
}

function link_to_quit_team($tid) {
    return red_link_to_url(url_for_quit_team($tid), "Quit");
}

//"<a href='../teams/jointeam.php?tid={$id}'>$quit</a>\n";
function link_to_join_team($tid) {
    return link_to_url(url_for_join_team($tid), "Join");

}
function link_to_team_stats($tid, $roundid, $prompt = "") {
    if($prompt == "") {
        $prompt = $roundid;
    }
    return link_to_url(url_for_team_stats($tid, $roundid), $prompt);
}

function link_to_user_roles($username, $prompt = null) {
	if(! $prompt) $prompt = $username;
	return link_to_url(url_for_user_roles($username), $prompt);
}

function url_for_user_roles($username) {
	global $code_url;
	return build_path($code_url, "tools/site_admin/user_roles.php"
								."?username=$username"
	                            ."&amp;qrysubmituser=1");
}

function link_to_round_stats($round, $prompt = "") {
    if(! $prompt) $prompt = $round;
    return link_to_url(url_for_round_stats($round), $prompt);
}

function url_for_round_stats($round) {
    global $code_url;
    return "$code_url/stats/proof_stats.php?roundid=$round";
}

function link_to_round_charts($round, $prompt = "") {
    if(! $prompt) $prompt = $round;
    return link_to_url(url_for_round_charts($round), $prompt);
}

function url_for_round_charts($round) {
    global $code_url;
    return "$code_url/stats/pages_proofed_graphs.php?roundid=$round";
}

function link_to_misc_stats($round, $prompt = "") {
    if(! $prompt) $prompt = $round;
    return link_to_url(url_for_misc_stats($round), $prompt);
}

function url_for_misc_stats($round) {
    global $code_url;
    return "$code_url/stats/misc_stats1.php?roundid=$round";
}

function url_for_create_team() {
    global $code_url;
    return "$code_url/stats/teams/new_team.php";
}

function link_to_create_team($prompt = "Create a new team") {
    return link_to_url(url_for_create_team(), $prompt);
}

function url_for_metal_list($metal) {
    global $code_url;
    return "$code_url/list_etexts.php?{$metal}";
}

function link_to_metal_list($metal, $prompt = "", $isnew=false) {
    if(!$prompt) $prompt = $metal;
    return link_to_url(url_for_metal_list($metal), $prompt, $isnew );
}

// -- wordcontext

function url_for_word_context($projectid, $mode, $options = "") {
    global $wc_url;
    return "$wc_url/wordcontext.php"
        ."?projectid={$projectid}"
        ."&amp;mode={$mode}"
        . (($options) ? "&amp;$options" : "");
}

function url_for_ad_hoc_word_context($projectid) {
    return url_for_word_context($projectid, "adhoc");
}

function url_for_flagged_word_context($projectid) {
    return url_for_word_context($projectid, "aspell");
}



// -- wordcheck flags (word context)

function link_to_wordcheck_flags(
    $projectid, $prompt, $is_new_tab = false) {
    return link_to_url(
        url_for_wordcheck_flags($projectid), $prompt, $is_new_tab);
}

function url_for_wordcheck_flags($projectid) {
    return url_for_word_context($projectid, "flagged");
}

// -- wordcheck stats

function link_to_wordcheck_stats($projectid, $prompt) {
    return link_to_url( url_for_wordcheck_stats($projectid), $prompt);
}

function url_for_wordcheck_stats($projectid) {
    global $wc_url;
    return "$wc_url/wordcheck_stats.php?projectid=$projectid";
}

// -- ad hoc words

function link_to_regex_words($projectid, $prompt) {
    return link_to_url(url_for_regex_words($projectid), $prompt);
}

function url_for_regex_words($projectid) {
    return url_for_word_context($projectid, "regex");
}

// -- ad hoc words

function link_to_adhoc_words($projectid, $prompt) {
    return link_to_url(url_for_adhoc_words($projectid), $prompt);
}

function url_for_adhoc_words($projectid) {
    return url_for_word_context($projectid, "adhoc");
}

// -- wdiff

function link_to_wdiff_results($projectid, $langcode, $prompt) {
    return link_to_url(
        url_for_wdiff_results($projectid, $langcode), $prompt);
}

function url_for_wdiff_results($projectid, $langcode) {
    global $wc_url;
    return "$wc_url/wdiff.php?projectid=$projectid"
    ."&amp;langcode=$langcode";
}

// -- hyphenated words

function link_to_hyphenated_words($projectid, $prompt) {
    return link_to_url(
        url_for_hyphenated_words($projectid), $prompt);
}

function url_for_hyphenated_words($projectid) {
    return url_for_word_context($projectid, "hyphenated");
}

// -- good words

function link_to_good_words(
    $projectid, $prompt, $is_new_tab = false) {
    return link_to_url(url_for_good_words($projectid), $prompt,
        $is_new_tab);
}

function url_for_good_words($projectid) {
    return url_for_word_context($projectid, "good");
}

// -- suggested words

function link_to_suggested_words(
    $projectid, $prompt, $is_new_tab = false) {
    return link_to_url(
        url_for_suggested_words($projectid), $prompt, $is_new_tab);
}

function url_for_suggested_words($projectid) {
    return url_for_word_context($projectid, "suggested");
}

// -- suspect words

function link_to_suspect_words($projectid, $prompt, $is_new_tab = false) {
    return link_to_url(url_for_suspect_words($projectid), $prompt,
        $is_new_tab);
}

function url_for_suspect_words($projectid) {
    return url_for_word_context($projectid, "suspect");
}

function link_to_refresh_suspect_words($projectid, $prompt,
                                         $is_new_tab = false, $options) {
    return link_to_url(url_for_refresh_suspect_words($projectid, $options),
        $prompt, $is_new_tab);
}

function url_for_refresh_suspect_words($projectid, $options) {
    return url_for_word_context($projectid, "suspect", $options);
}

// -- bad words

function link_to_bad_words($projectid, $prompt, $is_new_tab = false) {
    return link_to_url(url_for_bad_words($projectid), $prompt,
        $is_new_tab);
}

function url_for_bad_words($projectid) {
    return url_for_word_context($projectid, "bad");
}

function link_to_project_words($projectid, $prompt="Words", $newtab = false) {
    return link_to_url( url_for_project_words($projectid), $prompt, $newtab);
}

function url_for_project_words($projectid) {
    global $wc_url;
    return "$wc_url/project_words.php"
    ."?projectid={$projectid}";
}

function link_to_notify($username, $projectid, $prompt = "notify", $setclear = "set") {
    return link_to_url(url_for_notify($username, $projectid, $setclear), $prompt);
//    return link_to_url(url_for_notify($username, $projectid, $setclear));
}

function url_for_notify($username, $projectid, $setclear = "set") {
    global $code_url;
    return "$code_url/tools/proofers/posted_notice.php"
        ."?projectid=$projectid"
        ."&amp;username=$username"
        ."&amp;setclear=$setclear";
}
