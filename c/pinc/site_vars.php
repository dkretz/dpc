<?PHP
// Variables (constants?) whose values are specific
// to the local installation of the DP code.

// During site configuration, identifiers delimited by double angle-brackets
// are replaced by the corresponding values in SETUP/configuration.sh.

$code_version = "0.9.2.1";
$reldate = "24-Jul-2015";

define("DPC_PATH", "/home/pgdpcanada/public_html/");
define("DPC_URL", "http://www.pgdpcanada.net");
define("TEMP_DIR", build_path(DPC_PATH, "d/temp"));
define("TEMP_URL", build_path(DPC_URL, "d/temp"));

$site_url = DPC_URL;
$logo_url = DPC_URL . "c/graphics/dpclogo.png";
$ajax_url = DPC_URL . "/c/wc.php";

$code_url = DPC_URL . '/c';
$stats_url = DPC_URL . '/c/stats';
$pm_url   = $code_url . "/tools/project_manager";
$wc_url   = DPC_URL . "/c/wc";

$code_path = $code_dir = DPC_PATH . 'c/';
$proof_dir = $code_dir . "tools/proofers/";
$pm_dir = $code_dir . "tools/project_manager/";

$include_dir = DPC_PATH . "c/include/";

$js_url = $code_url . "/js";
$css_url = $code_url . "/css";

$projects_dir = DPC_PATH . 'projects';
$projects_url = $site_url . '/projects';

// /home/pgdpcanada/public_html/archive
$projects_archive_dir = DPC_PATH . 'archive';
$projects_archive_url = $site_url . '/archive';

$dyn_dir = DPC_PATH . 'd';
$dyn_url = DPC_URL . '/d';

//$dynstats_dir = "$dyn_dir/stats";
//$dynstats_url = "$dyn_url/stats";

$transient_root = $dyn_dir;
//$transient_url = $dyn_url;

$site_log_path      = $transient_root . "/log/dpc.log";
$wordcheck_dir = $transient_root . "/wordcheck";

//$dyn_locales_dir = "$dyn_dir/locale";

//$xmlfeeds_dir = "$dyn_dir/xmlfeeds";

//$jpgraph_dir = DPC_PATH . 'jpgraph';

$wiki_url = DPC_URL . '/wiki/index.php';

//$wikihiero_dir = DPC_PATH . 'wikihiero';
//$wikihiero_url = DPC_URL . '/wikihiero';

$site_name = "Distributed Proofreaders of Canada";
$site_abbreviation = "DPC";

// for phpbb3 to use
$phpbb_root_path = '/home/pgdpcanada/public_html/forumdpc/';
$phpbb_database_name = "newDPCForum";
$forumdb        = "newDPCForum";
$forumpfx       = "new_";
$forum_users_table = "new_users";

$sftp_path      = "/var/sftp";
$dpscans_path   = "/var/sftp/dpscans";

$forums_dir = DPC_PATH . 'forumdpc';
$forums_url = DPC_URL . "/forumdpc";
$registration_url = "{$forums_url}/ucp.php?mode=register";
$forum_login_url = "{$forums_url}/ucp.php?mode=login";
$change_password_url = "$forums_url/ucp.php?i=profile&mode=reg_details";

$team_avatars_dir = DPC_PATH . "c/users/teams/avatar";
$team_avatars_url = "/c/users/teams/avatar";

$team_icons_dir   = DPC_PATH . "c/users/teams/icon";
$team_icons_url   = "/c/users/teams/icon";

$general_forum_idx                = '5';
$beginners_site_forum_idx         = '2';
$beginners_proofing_forum_idx     = '3';
$waiting_projects_forum_idx       = '15';
$projects_forum_idx               = '16';
$pp_projects_forum_idx            = '17';
$posted_projects_forum_idx        = '19';
$content_providing_forum_idx      = '10';
$post_processing_forum_idx        = '13';
$teams_forum_idx                  = '21';


$general_forum_url                = "$forums_url/viewforum.php?f=$general_forum_idx";
$waiting_projects_forum_url       = "$forums_url/viewforum.php?f=$waiting_projects_forum_idx";
$projects_forum_url               = "$forums_url/viewforum.php?f=$projects_forum_idx";
$pp_projects_forum_url            = "$forums_url/viewforum.php?f=$pp_projects_forum_idx";
$posted_projects_forum_url        = "$forums_url/viewforum.php?f=$posted_projects_forum_idx";
$post_processing_forum_url        = "$forums_url/viewforum.php?f=$post_processing_forum_idx";
$content_providing_forum_url   	  = "$forums_url/viewforum.php?f=$content_providing_forum_idx";
$beginners_site_forum_url         = "$forums_url/viewforum.php?f=$beginners_site_forum_idx";
$beginners_proofing_forum_url     = "$forums_url/viewforum.php?f=$beginners_proofing_forum_idx";
$teams_forum_url                  = "$forums_url/viewforum.php?f=$teams_forum_idx";


$uploads_dir = '/home/dpscans';
$uploads_host = 'pgdpcanada.net';
$uploads_account = 'dpscans';
$uploads_password = '2C4ever';

// -----------------------------------------------------------------------------

$hunspell_path = '/usr/bin/aspell';
$hunspell_temp_dir = DPC_PATH . 'd/sp_check';

$xgettext_executable = '/usr/bin/xgettext';
$system_locales_dir = '/usr/share/locale';

// -----------------------------------------------------------------------------

$no_reply_email_addr = 'no-reply@pgdpcanada.net';
$general_help_email_addr = 'dphelp@pgdpcanada.net';
$site_manager_email_addr = $general_help_email_addr;
$auto_email_addr = $general_help_email_addr;
$db_requests_email_addr = 'db-requests@pgdpcanada.net';
$promotion_requests_email_addr = 'dp-promote@pgdpcanada.net';
$ppv_reporting_email_addr = 'ppv-reports@pgdpcanada.net';
$image_sources_manager_addr = 'ism@pgdpcanada.net';

// -----------------------------------------------------------------------------

$completed_books_offset = 2185;
$testing = FALSE;
$use_php_sessions = TRUE;
$cookie_encryption_key = 'A_LONG_STRING_OF_GIBBERISH2';
$maintenance = 0;
$site_supports_metadata = FALSE;
$site_supports_corrections_after_posting = FALSE;
//$auto_post_to_project_topic = FALSE;
$external_catalog_locator = 'z3950.loc.gov:7090/Voyager';
$charset = 'utf-8';

$jpgraph_FF='2';
$jpgraph_FS='9002';

$writeBIGtable = FALSE;
$readBIGtable = FALSE;

// -----------------------------------------------------------------------------

// If the gettext extension is compiled into PHP, then the function named '_'
// (an alias for 'gettext') will be defined.
// If it's not defined (e.g., on dproofreaders.sourceforge.net),
// define it to simply return its argument.
if (! function_exists('_') )
{
    function _($str) { return $str; }
}
