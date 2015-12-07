<?php


/** 
    page initiation, termination, and transition
*/

/*
// http://stackoverflow.com/questions/3896591/what-is-the-equivalent-of-javascripts-decodeuricomponent-in-php
function utf8_uridecode($str) {
    $str = preg_replace( "/%u([0-9a-f]{3,4})/i", "&#x\\1;", urldecode($str));
    return html_entity_decode($str,null,'UTF-8');
}

function utf8_uriencode($str) {
    $encoded = '';
    $length = mb_strlen($str);
    for($i = 0; $i < $length; $i++){
        $encoded .= '%' . wordwrap(bin2hex(mb_substr($str, $i, 1)), 2, '%', true);
    }
    return $encoded;
}
*/


/**
 * @param $str
 * @return string
 */
function maybe_convert($str) {
    // $re = "/([Ã‡|Ã¦|Ã€Â]Ãƒ|Ã©|Â£|Å“|eÌ)/u";

	$re = "/[αειυàèìòùáéíóúäëïöüœæƒ£śćął“”‘’‛אבדע]/ui";
    $p1 = mb_convert_encoding($str, "Windows-1252", "utf-8");
//	$p1 = iconv("iso-8859-1", "utf-8", $str);
    if(preg_match( $re, $p1) && ! mb_strpos($p1, "�")) {
        return $p1;
    }
    return $str;
}

function _die($msg) {
    die(_($msg));
}

function html_head($title = "DP Canada") {
    return "<!DOCTYPE HTML>
<html>
<head>
<title>$title</title>
<meta charset='utf-8'>
<link type='text/css' rel='Stylesheet' href='/c/css/dp.css'>
</head>\n";
}

function html_foot() {
	return html_end();
}

function echo_header($title = "DP Canada") {
    echo html_head($title);
}

function html_end() {
return "
</body></html>";
}

function divert($url = "", $metacomment = "", $seconds = 1) {
    if(! $url) {
        $url = FromUrl();
    }

	$url = htmlspecialchars_decode($url);

    if(! headers_sent() && $metacomment == "") {
        header("Location: $url");
        exit();
    }
    echo "<meta http-equiv='refresh' content='$seconds;url=$url'>";
    exit();
}

function _metarefresh($url, $comment = "", $seconds = 1) {
    global $charset;
    echo "
<!DOCTYPE html>
<html>
    <head>
        <title>DP Canada Transition</title>
        <meta http-equiv='refresh' content='$seconds ;URL={$url}'>
        <meta http-equiv='Content-Type. content=.text/html; charset=$charset' />
    </head>
    <body>
        $comment
    </body>
</html>";
}

function UnauthorizedDeath($msg = null) {
    if(! $msg)
        $msg = _("Unauthorized access");
    die($msg);
}

function RedirectToLogin() {
    redirect_to_home();
	exit;
}


    // mbstring currently implements the following encoding detection
    // filters. If there is an invalid byte sequence for the following
    // encodings, encoding detection will fail. For ISO-8859-*,
    // mbstring always detects as ISO-8859-*. For UTF-16, UTF-32, UCS2
    // and UCS4, encoding detection will fail always. 

    // UTF-8, UTF-7, ASCII, EUC-JP,SJIS, eucJP-win, SJIS-win, 
    // JIS, ISO-2022-JP

/** 
    files and directories
*/

//function file_append_contents($path, $str) {
//    return file_put_contents($path, $str, FILE_APPEND);
//}

/**
 * @param $prefix string
 * @return string
 */
function temp_file_path($prefix) {
    return tempnam(sys_get_temp_dir(), $prefix);
}

function EnsureWriteableDirectory($path) {
    if(! file_exists($path)) {
        mkdir($path, 0777, true);
        file_exists($path) && is_dir($path)
            or die("mkdir failed for $path");
    }
}

function IsImageFile($path) {
    $info = getimagesize($path);
    return $info[0] > 0 && $info[1] > 0;
}

function build_path($path, $filename) {
    // trim off leading and trailing slashes
    if(mb_substr($filename, 0, 1) == '/') {
        $filename = mb_substr($filename, 1, 1000);
    }

    if(mb_substr($path, -1, 1) == '/') {
        $ret = $path . $filename;
    }
    else {
        $ret = $path . '/' . $filename;
    }
    return $ret;
}

function mkdir_recursive( $dir, $mode ) {
    if ( file_exists($dir) ) {
        if ( ! is_dir($dir) ) {
            die( "$dir exists, but isn't a directory." );
        }
        return;
    }
    mkdir_recursive( dirname($dir), $mode );
    mkdir( $dir, $mode )
        or die( "Unable to create $dir" );
}

function tempdir($dir = null, $prefix = null) {
    $template = "{$prefix}XXXXXX";
    if (($dir) && (is_dir($dir))) {
        $tmpdir = "--tmpdir=$dir";
    }
    else {
        $tmpdir = '--tmpdir=' . sys_get_temp_dir();
    }
    return exec("mktemp -d $tmpdir $template");
}

/** 
    NTML envelope
*/

function DefaultLocale() {
    global $User;
    return $User->InterfaceLanguage()
        ? $User->InterfaceLanguage()
        : "en";
}

function LanguageName($lang) {
	global $wclangs;
	return $wclangs[$lang];
}

function SimpleHeader($title = "") {
    return NotSoSimpleHeader(array("title" => $title));
}

function SimpleFooter() {
    return "\n</body></html>";
}

function FromUrl() {
    return $_SERVER['REQUEST_URI'];
}

function RefererUrl() {
    return $_SERVER['HTTP_REFERER'];
}

function ThisPageUrl() {
    return "http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
}
function ThisUrl() {
    return $_SERVER['PHP_SELF'];
}

function ThisFullUrl() {
    return "http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
}

function is_current_page($link) {
    return findstr($link, ThisUrl());
}

function UrlSelf() {
    return $_SERVER['PHP_SELF'];
}

function self_url() {
    return $_SERVER['PHP_SELF'];
}

function ReferringUrl() {
    return $_SERVER['REQUEST_URI'];
}

function reamp($str) {
    return preg_replace("/\&amp;/u", "&", $str);
}
function unamp($str) {
    return preg_replace("/\&/u", "&amp;", $str);
}

function mklink($url, $prompt = "") {
    if($prompt == "")
        $prompt = $url;
    return "<a href='{$url}'>{$prompt}</a>";
}

function pglink($pgnum) {
	$href = "#p" . right("000" . $pgnum, 3);
	return "<a href='$href'>$pgnum</a>";
}

function mkmailto($url, $prompt = null) {
    if(empty($prompt))
        $prompt = $url;
    return "<a href='mailto:{$url}'>{$prompt}</a>";
}

function NotSoSimpleHeader($arg) {

    global $css_url;
    global $site_name;

    $title = isset($arg['title']) 
                ? $arg['title'] 
                : $site_name;
    $css        = isset($arg['css']) ? $arg['css'] : "";
    $js         = isset($arg['js']) ? $arg['js'] : "";
    $onload     = isset($arg['onload']) ? $arg['onload'] : "";
    $jsurl      = isset($arg['jsurl']) ? $arg['jsurl'] : "";
    $cssurl     = isset($arg['cssurl']) ? $arg['cssurl'] : "";

return 
"<!DOCTYPE html>
<html>
<head>
<meta charset=utf-8 />
<title>$title</title>
".($jsurl != "" ? "<script src='$jsurl'></script>":"")."
".($cssurl != "" ? "<link type='text/css' rel='stylesheet' 
                                        href='$cssurl'>":"")."

<link type='text/css' rel='stylesheet' href='{$css_url}/dp.css'>
<script>
$js
</script>
<style type='text/css'>
$css
</style>
</head>
".($onload == "" ? "<body>" : "<body onload='$onload'>");
}

/**
 * strings
 * @param $str string
 * @return string
 */

function upper($str) {
    return mb_convert_case($str, MB_CASE_UPPER);
}
function uppercase($str) {
    return mb_convert_case($str, MB_CASE_UPPER);
}

function lower($str) {
    return mb_convert_case($str, MB_CASE_LOWER);
}
function lowercase($str) {
    return mb_convert_case($str, MB_CASE_LOWER);
}

function titlecase($str) {
    return mb_convert_case($str, MB_CASE_TITLE);
}

function left($str, $len) {
    return mb_substr($str, 0, $len);
}

function bleft($str, $len) {
    return substr($str, 0, $len);
}

function right($str, $len) {
    if($len >= mb_strlen($str))
        return $str;

    return mb_substr($str, strlen($str) - $len);
}

function startswith($str, $pfx) {
	return left($str, mb_strlen($pfx)) == $pfx;
}

function endswith($str, $sfx) {
	return right($str, mb_strlen($sfx)) == $sfx;
}

function TextRows($text) {
    return text_lines($text);
}
function text_lines($text) {
    return preg_split('/\r?\n/u', $text);
}

function empty_line($text) {
    return trim($text) == "";
}

/**
 * @param string $str
 * @param int $start
 * @param int $len
 * @return string
 * @throws Exception
 */
function mid($str, $start, $len = -1) {
    if($len < 0)
        return mb_substr($str, $start);
    try {
    return $len >= 0 
            ? mb_substr($str, $start, $len)
            : mb_substr($str, $start);
    }
    catch(Exception $e) {
        say("mid exception $start $len");
        throw $e;
    }
}

/**
 * @param string $str
 * @param int $start
 * @param int $len
 * @return string
 * @throws Exception
 */
function bmid($str, $start, $len = -1) {
    if($len < 0)
        return substr($str, $start);

    try {
    return $len >= 0 
            ? substr($str, $start, $len)
            : substr($str, $start);
    }
    catch(Exception $e) {
        say("bmid exception start=$start len=$len");
        throw $e;
    }
}

/**
 * @param string $str
 * @param int $pos1
 * @param int $pos2
 * @return string
 */
function bmiddle($str, $pos1, $pos2 = 0) {
    if($pos2 == 0)
        return bmid($str, $pos1);

    $len = $pos2 - $pos1;
    if($len > 0)
        return bmid($str, $pos1, $len);
    say("bmiddle fail: len $len pos1 $pos1 pos2 $pos2");
    assert(false);
    return "";
}

function middle($str, $pos1, $pos2 = 0) {
    if($pos2 == 0)
        return mid($str, $pos1);

    $len = $pos2 - $pos1;
    if($len > 0)
        return mid($str, $pos1, $len);
    say("middle fail: len $len pos1 $pos1 pos2 $pos2");
    assert(false);
    return "";
}

// convert tabs to single spaces
function dp_detab($text) {
    return preg_replace("/\t+/", " ", $text);
}

// remove control characters
// ref: http://stackoverflow.com/questions/1497885/remove-control-characters-from-php-string
function dp_decontrol($text) {
    return preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/',
            '', $text);

}

// boolean
function findstr($text, $str) {
    return preg_match("^".$str."^", $text);
}

function findistr($text, $str) {
    return preg_match("/".$str."/i", $text);
}

function dp_fix_newlines($text) {
    return preg_replace( '/\r(?!\n)/m', "\r\n", 
           preg_replace( '/(?<!\r)\n/m', "\r\n", $text));
}

function dp_trim($text) {
    // top:    blank lines at end of entire string
    // bottom: eol whitespace all lines
    return      preg_replace("/ +$/D", "", 
           preg_replace('/(\S) +([\r\n])/', '\1\2', $text));
}

// for making strings comparable (sorta)
function despace($text) {
    return preg_replace("/ +/gu", " ", $text);
}

function htmlsafe($value) {
  return htmlspecialchars($value, ENT_QUOTES);
}

function html_comment($str) {
    return 
"<!--
$str 
-->
";
}

function permit_path($path) {
    chmod($path, 0777);
}

/**
 * sql
 * @param string $str
 * @return string
 */


function SqlQuote($str) {
    return ($str == "") ? "NULL" : "'{$str}'";
}

function SqlInteger($num) {
    return is_null($num) ? "NULL" : intval($num);
}

function PrettySql( $sql ) {
    return str_replace( "\n", "<br />\n", $sql ) ;
}

function DieSql( $sql ) {
    die( PrettySql( $sql ) ) ;
}

function QuoteOrNull($code) {
    return empty($code) ? "NULL" : "'$code'" ;
}

function CheckSqlEcho() {
    global $dpdb;
    if(IsArg("sqlecho")) {
        $dpdb->SetEcho();
    }
}


/**
 * tracing and debugging
 * @param string $errmsg
 */

function ErrorBox($errmsg) {
    echo "
    <html>
    <head>
    <script'>
    <!--
        alert($errmsg);
        history.back();
    //-->
    </script>
    </head>
    <body></body>
    </html>\n";
}

function edump($val) {
    echo "\n<pre>\n";
    var_export($val);
    echo "</pre>\n";
}

function rdump($val) {
    echo "\n<pre>\n";
    print_r($val);
    echo "</pre>\n";
}

function dump($val) {
    echo "\n<pre>\n";
	var_dump($val);
//    var_dump(h($val));
    echo "</pre>\n";
}

function pdump($val) {
    return "\n<pre>
        ".print_r($val, true)."
    </pre>\n";
}

function strdump($val) {
    return pdump($val);
}

function ldump($val, $len = 0) {
    return $len <= 0
        ? pdump($val)
        : "\n<pre
        " . left(print_r($val, true), $len)."
        </pre\n";
}

function sqldump($str) {
	echo html_comment($str);
}

function trace( $msg ) {
    echo "<br />$msg<br />\n";
}

function error_box($errmsg) {
    return <<<EOT
    <html>
    <head>
    <script>
    <!--
        alert($errmsg);
        history.back();
    //-->
    </script>
    </head>
    <body></body>
    </html>
EOT;
}

function IsMe() {
	global $User;
	return $User->Username() == 'dkretz';
}


function StackDump() {
	$e = new Exception;
	var_dump($e->getTraceAsString());
}

function LogMsg($msg) {
    global $dpdb;
    global $User;
    $username = ($User == null  ? "(logging in)" : $User->Username() );
    $sql = "
        INSERT INTO log (username, eventtime, logtext)
        VALUES (?, UNIX_TIMESTAMP(), ?)
        ";
	$args = array(&$username, &$msg);
    $dpdb->SqlExecutePS($sql, $args);
}

function dump_memory_size() {
    dump("memory: ".memory_get_usage());
}

/**
 * UI, I/O
 * @param string $var
 */

function say($var) {
    echo "<br />$var<br />";
}

function pre($var) {
    echo "<pre>$var</pre>";
}

function coalesce($ary) {
    if(! is_array($ary)) {
        return $ary;
    }
    foreach($ary as $val) {
        if(!empty($val)) {
            return $val;
        }
    }
    return null;
}

function h( $str ) {
    return htmlspecialchars( $str, ENT_QUOTES, "UTF-8" ) ;
}

/**
 * regex
 * @param string $text
 * @param string $newtext
 * @param int $index
 * @param int $length
 * @return string
 */

function ReplaceText($text, $newtext, $index, $length) {
    return ($index > 0 
                ? left($text, $index) 
                : "")
            . $newtext
            . mb_substr($text, $index + $length);
}

function multimatch($regex, $flags, $text) {
    $regex = "~".$regex."~".$flags;
    preg_match_all($regex, $text, $matches, PREG_SET_ORDER);
    return $matches;
}

function ReplaceSubstr($str, $repl, $pos, $len) {
    return bleft($str, $pos).$repl.bmid($str, $pos+$len-1);
}

function CountReplaceRegex($ptn, $repl, $flags, &$text) {
    $ptn = "~".$ptn."~".$flags;
    $text = preg_replace($ptn, $repl, $text, -1, $n);
    return $n;
}

function ReplaceRegex($ptn, $repl, $flags, $text) {
    $ptn = "~".$ptn."~".$flags;
    return preg_replace($ptn, $repl, $text);
}

function CountReplaceRegexCallback($ptn, $func, $flags, &$text) {
	$ptn = "~".$ptn."~".$flags;
	$text = preg_replace_callback($ptn, $func, $text, -1, $n);
	return $n;
}

function ReplaceOneRegex($ptn, $repl, $flags, $text) {
    $ptn = "~".$ptn."~".$flags;
    return preg_replace($ptn, $repl, $text, 1);
}

function ReplaceMultiRegex($ptn, $repl, $flags, $text) {
    $ptn = "~".$ptn."~".$flags;
    return preg_replace($ptn, $repl, $text, -1);
}

function ReplaceLastRegex($ptn, $repl, $flags, $text) {
    $matcharray = LastRegexMatch($ptn, $flags, $text);
    if(! is_array($matcharray))
        return $text;
    list($match, $ipos) = $matcharray;

    $str = bleft($text, $ipos-1) 
        . ReplaceRegex($ptn, $repl, $flags, $match)
        . bmiddle($text, $ipos + strlen($match));
    return $str;
}

// returns offset from $start, or -1 for not found.
function RegexByteOffset($regex, $flags, $text, $start = 0) {
    $regex = "~".$regex."~".$flags;
    $n = preg_match(
            $regex, $text, $match, PREG_OFFSET_CAPTURE, $start);
    return $n > 0 ? $match[0][1]: -1;
}

function regex_offset($regex, $flags, $text, $start = 0) {
    $regex = "~".$regex."~".$flags;
    // say($regex);
    $n = preg_match(
            $regex, $text, $match, PREG_OFFSET_CAPTURE, $start);
    return $n > 0 ? $match : array();
}

function regex_offsets($regex, $flags, $text) {
    $regex = "~".$regex."~".$flags;
    preg_match_all($regex, $text, $matches, PREG_OFFSET_CAPTURE);
    return $matches;
}

function regex_offset_both($regex, $flags, $text, $start = 0) {
    $regex = "~".$regex."~".$flags;
    $n = preg_match(
            $regex, $text, $match, PREG_OFFSET_CAPTURE, $start);
    return $n > 0 ? $match[0] : null;
}

// returns the "match array" i.e. match string and offset
function FirstRegexMatch($regex, $flags, $text) {
    $regex = "~".$regex."~".$flags;
    $n = preg_match_all($regex, $text, $matches, PREG_OFFSET_CAPTURE);
    $match = $matches[0][0];
    return $n > 0 ? $match : null;
}

// returns the "match array" i.e. match string and offset
function LastRegexMatch($regex, $flags, $text) {
    $regex = "~".$regex."~".$flags;
    $n = preg_match_all($regex, $text, $matches, PREG_OFFSET_CAPTURE);
    return $n > 0 ? end($matches[0]) : null;
}

// find a regex, return array of matches (but no offsets)
// RegexMatches("(a.)(b.)(c.)", "ius", "aabbcc") returns array("aabbcc", "aa", "bb", "cc");
function RegexMatches($regex, $flags, $text) {
    $regex = "~".$regex."~".$flags;
    preg_match_all($regex, $text, $matches);
	$nsubs = count($matches);
	$ary = array();
	// matches[0] is full string matches
	// matches[1] is first substring matches
	for($i = 0; $i < $nsubs; $i++) {
		foreach ( $matches[$i] as $match ) {
			$ary[$i][] = $match;
		}
	}
	return $ary;
//    return $match[$index];
}

// Search for a regex.
// Returns a match and an offset
// Can search for a particular submatch in which case $index specifies which.
function RegexMatch($regex, $flags, $text, $index = 0, $offset = 0) {
    $regex = "~".$regex."~".$flags;
//    preg_match( $regex, $text, $matches, PREG_OFFSET_CAPTURE, $offset);
    // $matches is an array if there are submatches - else it's a string.
	$i = preg_match( $regex, $text, $matches, PREG_OFFSET_CAPTURE, $offset);
	if($i > 0) {
		return $matches[$index][$offset];
	}
	return null;
	    // if there is no match, $matches is null
//        return is_array( $matches ) ? $matches[ $index ] : $matches;
//    catch(Exception $e) {
//        say("RegexMatch exception - /$regex/$flags ($index) (offset) \n$text");
//        throw $e;
//    }
}

function RegexMatchArray($regex, $flags, $text) {
    return multimatch($regex, $flags, $text);
}

function RegexCount($regex, $flags, $text) {
    $regex = "~".$regex."~".$flags;
    return preg_match_all($regex, $text, $matches);
}

function RegexSplit($regex, $flags, $text) {
    $regex = "~".$regex."~".$flags;
    return preg_split($regex, $text);
}

function WordByteOffsets($word, &$text) {
    $edge1 = "(?<!\p{L})";
    $edge2 = "(?!\p{L})";
    $ptn = "~{$edge1}$word{$edge2}~u";
    preg_match_all($ptn, $text, $m, PREG_OFFSET_CAPTURE);
    return $m[0];
}

function RegexByteOffsets($word, $flags, &$text) {
    $ptn = "~$word~$flags";
    try {
        preg_match_all( $ptn, $text, $m, PREG_OFFSET_CAPTURE );
    }
    catch( Exception $e) {
        dump($ptn);
        throw($e);
    }
    return $m[0];
};

function set_timeout_seconds($sec) {
    set_time_limit($sec);
}

/**
 * date and time
 * @param string|null $val
 * @return bool|string
 */

function yy_m_d($val = null) {
    return y_m_d($val);
}

function y_m_d($val = null) {
    return empty($val)
        ? date('y-m-d')
        : date($val);
}

function yyyy_m_d($val = null) {
    return empty($val)
        ? date('Y-m-d')
        : date($val);
}

function start_of_month($val = null) {
    if(empty($val)) $val = time();
    $Ym1 = strftime("%Y-%m-1", $val);
    return strtotime($Ym1);
}

function end_of_month($val = null) {
    if(empty($val)) $val = time();
    $y = strftime("%Y", $val);
    $m = strftime("%m", $val);
    if($m == 12) {
        $y++;
        $m = 1;
    }
    else {
        $m++;
    }

    $Ym1 = sprintf("%s-%s-1", $y, $m);
    return start_of_month($Ym1) - 1; 
}

function pretty_date($ts) {
    if(! $ts)
        return "--" ;
    return strftime("%B %e, %Y", $ts);
}

function pretty_date_time($ts) {
    if(empty($ts))
        return "--" ;
    return strftime("%c", $ts) ;
}

function now() {
    return time();
}

function std_date_time($val = 0) {
    if($val == 0)
        return std_date_time(now());
    return strftime("%B %e, %Y at %H:%M", $val);
}

function std_date($val) {
    if($val == 0)
        return "";
    return strftime("%A, %B %e, %Y", $val);
}

function std_time($val) {
    if($val == 0)
        return "";
    return strftime("%X", $val);
}
function yy_mm_dd_hh_nn($val) {
    return strftime("%y-%m-%d %H:%M", $val);
}

function yy_mm_dd_hh_nn_ss($val) {
    return strftime("%y-%m-%d %H:%M:%S", $val);
}

function yyyymmddhhnn($val) {
    return strftime("%Y-%m-%d %H:%M:%S", $val);
}
function yymmdd() {
    return strftime('%y%m%d');
}

function Month_day($val) {
    return strftime("%b %e", $val);
}
function day_Month($val) {
    return strftime("%e %b", $val);
}

function yyyymmdd( $val = "" ) {
    return $val == ""
        ? strftime('%Y-%m-%d')
        : strftime('%Y-%m-%d', $val );
}

function std_ago($val) {
    if(minutes_ago($val) < 3) {
        return seconds_ago($val)." seconds";
    }
    if(hours_ago($val) < 3) {
        return minutes_ago($val)." minutes";
    }
    if(days_ago($val) < 3) {
        return hours_ago($val)." hours";
    }
    return days_ago($val)." days";
}

function weeks_ago($val) {
    return intval(days_ago($val) / 7);
}

function days_ago($val) {
    return intval(hours_ago($val) / 24);
}

function hours_ago($val) {
    return intval(minutes_ago($val) / 60);
}

function minutes_ago($val) {
    return intval(seconds_ago($val) / 60);
}

function seconds_ago($val) {
    return time() - $val;
}

function one_minute() {
    return 60;
}

function one_hour() {
    return 60 * one_minute();
}

function one_day() {
    return 24 * one_hour();
}

function one_week() {
    return 7 * one_day();
}

function one_minute_ago() {
    return time() - one_minute();
}

function one_hour_ago() {
    return time() - one_hour();
}

function one_day_ago() {
    return time() - one_day();
}

function one_week_ago() {
    return time() - one_week();
}

function TimeStampString() {
    return date('m-d-y H:i:s');
}

/** 
    timer
*/

function init_timer() {
    _timer(true);
}

function get_timer() {
    return number_format(_timer(), 3);
}

function say_timer() {
    say("<br>time: ".get_timer()."<br>");
}

function _timer($is_init = false) {
    static $_start;
    if($is_init) {
        $_start = microtime(true);
        return 0.0;
    }
    else if(! isset($_start)) {
        say("default timer init");
        _timer(true);
        return 0.0;
    }
    else {
        return microtime(true) - $_start;
    }
}

/**
 * file transfers
 * @param string $filename
 * @param string $str
 */

function send_string($filename, $str) {
    header('Content-Description: File Transfer');
    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename={$filename}");
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . strlen($str));
    echo $str;
    exit;
}

function send_file($path, $filename = null) {

    if(! file_exists($path)) {
        dump("request to send phantom file $path.");
        return;
    }

    if(! $filename) {
        $filename = basename($path);
    }

    header("Content-Description: File Transfer");
    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=$filename");
//    header("Content-Transfer-Encoding: binary");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: public");
    header("Content-Length: " . filesize($path));
//    ob_clean();
//    flush();
//	ignore_user_abort(true);
    readfile($path);
    exit;
}

/**
 * dependent paths
 * @param string $projectid
 * @return string
 */

function ProjectUploadPath($projectid) {
    global $transient_path;
    $path = build_path($transient_path, $projectid);
    return build_path($path, "loadfiles");
}

function ProjectWordcheckPath($projectid) {
    $path = build_path(ProjectPath($projectid), "wordcheck");
    EnsureWriteableDirectory($path);
    return $path;
}

function ProjectImageFilePath($projectid, $imagefile) {
    return build_path(ProjectPath($projectid), $imagefile);
}

/*
 * Filename suffix without the period
 */
function FileNameExtension($filename) {
    return pathinfo($filename, PATHINFO_EXTENSION);
}

// dirname exists in PHP
//function dirname($path) {
//}

// basename exists in PHP - returns whole filename incl. extension
//function dirname($path) {
//}

function extension($path) {
    return pathinfo($path, PATHINFO_EXTENSION);
}

function rootname($filename) {
    return pathinfo($filename, PATHINFO_FILENAME);
}

function ProjectImageFileSize( $projectid, $imagefile) {
	$path = ProjectImageFilePath($projectid, $imagefile);
	$ret = filesize($path);
	return $ret;
}
function SiteWordcheckPath() {
    global $wordcheck_dir;
    return $wordcheck_dir;
}

function ProjectExtraFilesPath($projectid) {
    $path = build_path(ProjectPath($projectid), "extrafiles");
    EnsureWriteableDirectory($path);
    return $path;
}

function ProjectUrl($projectid) {
	global $projects_url;
	$path = build_path($projects_url, $projectid);
	return $path;
}
function ProjectPath($projectid) {
	global $projects_dir;
	$path = build_path($projects_dir, $projectid);
	EnsureWriteableDirectory($path);
	return $path;
}
function ProjectArchivePath($projectid) {
    global $projects_archive_dir;
    $path = build_path($projects_archive_dir, $projectid);
    EnsureWriteableDirectory($path);
    return $path;
}

function ProjectSmoothDownloadUrl($projectid) {
	return build_path(ProjectUrl($projectid), $projectid . "_smooth_avail.zip");
}

function ProjectSmoothDownloadPath($projectid, $extension) {
    return build_path(ProjectPath($projectid), $projectid . "_smooth_avail.$extension");
}
function ProjectSmoothUploadPath($projectid, $username) {
    return build_path(ProjectPath($projectid), $projectid
                    . "_smooth_done_{$username}.zip");
}

function ProjectSmoothUploadFilename($projectid) {
	global $User;
	$username = $User->Username();
	return "{$projectid}_smooth_done_{$username}.zip";
}

function ProjectTextPath($projectid) {
	$path = build_path(ProjectPath($projectid), "text");
	EnsureWriteableDirectory($path);
	return $path;
}

function ProjectPagePath($projectid, $pagename) {
    $path = build_path(ProjectTextPath($projectid), $pagename);
    EnsureWriteableDirectory($path);
	return $path;
}

function ExportPageHeader($pagename, $proofers = null) {
	if(is_null($proofers)) {
		$proofers = array( "", "", "", "", "");
	}
	$str = implode(" \\ ", $proofers);
	$str = "-----File: $pagename ($str) ---";
	$str = str_pad($str, 75, "-", STR_PAD_RIGHT);
	return $str;
}

function PageTag($imagefile) {
	return "<page name='$imagefile' />";
}

function ProjectRoundsDownloadPath($projectid) {
    return build_path(ProjectPath($projectid), $projectid . ".zip");
}

function ProjectPPUploadPath($projectid) {
    return build_path(ProjectPath($projectid), $projectid . "_second.zip");
}
function ProjectPPVUploadPath($projectid) {
    return build_path(ProjectPath($projectid), $projectid . "_verified.zip");
}

function PageVersionPath($projectid, $pagecode, $version_number) {
    $p = ProjectPagePath($projectid, $pagecode);
	return build_path($p, $pagecode . "," . number_format($version_number));
}

function PageVersionText($projectid, $pagename, $version_number) {
	assert(file_exists(PageVersionPath($projectid, $pagename, $version_number)));
	return file_get_contents(PageVersionPath($projectid, $pagename, $version_number));
}

/*
function SetPageVersionText($projectid, $pagecode, $version_number, $text) {
	assert(! is_null($version_number));
	return file_put_contents(PageVersionPath($projectid, $pagecode, $version_number), trim_blank_lines($text));
}
*/

function norm($str) {
	$ptn = array("/\R/u",           // normalize newline
				 "/\t+/",           // any tabs to one space
				 "/[”‟““]/u",        // curly double-quotes to not
				 "/[‘‘’‛]/u");        // curly single-quotes to not
	$rpl = array("\n", " ", '"', "'");
	return trim_blank_lines(preg_replace($ptn, $rpl, $str));
}

/**
 * Args and other globals
 * @param string $name
 * @return array|null
 */

function FileArg($name) {
    return isset($_FILES[$name])
            ? array($_FILES[$name]['name'], $_FILES[$name]['tmp_name']): null;
}

function Arg($code, $default = "") {
    if( isset( $_SESSION[$code] ) )
        return $_SESSION[$code] ;
    if( isset( $_POST[$code] ) )
        return $_POST[$code] ;
    if( isset( $_GET[$code] ) )
        return $_GET[$code] ;
    return trim($default);
}

function Cookie($code, $default = "") {
    if( isset( $_COOKIE[$code] ) )
        return $_COOKIE[$code] ;
    return trim($default);
}

function SetCookieArg($code, $value) {
    setcookie($code, $value);
}

function CookieArg($code, $default = "") {
    return Cookie($code, $default);
}

function DeleteCookie($code) {
	unset($_COOKIE[$code]);
    setcookie($code, "", time()-3600);
}

function IsArg($arg, $default = false) {
    return isset($_REQUEST[$arg]) ? $_REQUEST[$arg] : $default;
}

// we only take positive integers
function ArgInt($arg, $default = 0) {
    return preg_match("/[^0-9]/", trim($arg))
        ? $default
        : $arg;
}

function ArgBoolean($arg, $default = false) {
    $value = Arg($arg);
    if(empty($value))
        return $default;

    if(empty($value))
        return false;
    switch($value) {
        case 1:
        case true:
        case 'yes':
        case 'y':
            return true;
        default:
            return false;
    }
}

function ArgRoundId($default = "") {
    return Arg("round_id", $default);
}

function ArgsLike($pfx) {
    $len = mb_strlen($pfx);
    $ary = array();
    foreach($_REQUEST as $key => $value) {
        $key = trim($key);
        if( left($key, $len) == $pfx) {
            $ary[$key] = trim($value);
        }
    }
    return $ary;
}

function ArgArray($name, $default = "") {
    $a = Arg($name);
    if(is_array($a)) {
        return $a;
    }
    if($a != "") {
        return array($a);
    }

    if($default == "") {
        return array();
    }

    return is_array($default)
        ? $default
        : array($default);
}

function ArgArrayFirst($aarg)
{
    $a = ArgArray($aarg);
    if(! is_array($a)) {
        return $a;
    }
    if(count($a) == 0) {
        return "";
    }
    foreach($a as $key => $val) {
        return $key;
    }
    return "";
}

function ArgProjectid($default = "") {
    return Arg("projectid", $default);
}

function ArgPageName($default = "") {
    return Arg("pagename", $default);
}

function ArgImageFile($default = "") {
    return Arg("imagefile", $default);
}

function ArgPage($default = "") {
    return Arg("page", $default);
}

function ArgLangCode($default = "") {
    return Arg("langcode", $default);
}

// trim whitepsace at end of string (including blank lines)
function trim_blank_lines($str) {
	return preg_replace("/[\pZ\pC]+$/u", "", $str);
}

/**
 * Unicode and encoding generally
 * @param string $str
 * @return bool
 */

function is_8859_1($str) {
    return mb_detect_encoding($str, 'ISO-8859-1') == 'ISO-8859-1';
}

function from_utf8($str) {
    return utf8_decode($str);
}

function utf8_to_utf8($str) {
    return iconv("UTF-8", "UTF-8//IGNORE", $str);
}

function to_utf8($str) {
    return utf8_encode($str);
}

function to_encoding($str, $encoding) {
    return iconv("UTF-8", $encoding."//IGNORE", $str);
}

function to_encoding_with_translit($str, $encoding) {
    return iconv("UTF-8", $encoding."//TRANSLIT", $str);
}

function is_charset($charset, $text) {
    return mb_check_encoding($text, $charset);
}


function is_utf8($string) {
    return ($string == Encoding::toUTF8($string));
}

$charsets = array(
    "UTF-8",
    "UTF-16",
    "ISO-8859-1",
    "ISO-8859-15",
    "Windows-1252",
    "Windows-1251",
);


class Encoding {

    protected static $win1252ToUtf8 = array(
        128 => "\xe2\x82\xac",

        130 => "\xe2\x80\x9a",
        131 => "\xc6\x92",
        132 => "\xe2\x80\x9e",
        133 => "\xe2\x80\xa6",
        134 => "\xe2\x80\xa0",
        135 => "\xe2\x80\xa1",
        136 => "\xcb\x86",
        137 => "\xe2\x80\xb0",
        138 => "\xc5\xa0",
        139 => "\xe2\x80\xb9",
        140 => "\xc5\x92",

        142 => "\xc5\xbd",


        145 => "\xe2\x80\x98",
        146 => "\xe2\x80\x99",
        147 => "\xe2\x80\x9c",
        148 => "\xe2\x80\x9d",
        149 => "\xe2\x80\xa2",
        150 => "\xe2\x80\x93",
        151 => "\xe2\x80\x94",
        152 => "\xcb\x9c",
        153 => "\xe2\x84\xa2",
        154 => "\xc5\xa1",
        155 => "\xe2\x80\xba",
        156 => "\xc5\x93",

        158 => "\xc5\xbe",
        159 => "\xc5\xb8"
    );

    protected static $brokenUtf8ToUtf8 = array(
        "\xc2\x80" => "\xe2\x82\xac",

        "\xc2\x82" => "\xe2\x80\x9a",
        "\xc2\x83" => "\xc6\x92",
        "\xc2\x84" => "\xe2\x80\x9e",
        "\xc2\x85" => "\xe2\x80\xa6",
        "\xc2\x86" => "\xe2\x80\xa0",
        "\xc2\x87" => "\xe2\x80\xa1",
        "\xc2\x88" => "\xcb\x86",
        "\xc2\x89" => "\xe2\x80\xb0",
        "\xc2\x8a" => "\xc5\xa0",
        "\xc2\x8b" => "\xe2\x80\xb9",
        "\xc2\x8c" => "\xc5\x92",

        "\xc2\x8e" => "\xc5\xbd",


        "\xc2\x91" => "\xe2\x80\x98",
        "\xc2\x92" => "\xe2\x80\x99",
        "\xc2\x93" => "\xe2\x80\x9c",
        "\xc2\x94" => "\xe2\x80\x9d",
        "\xc2\x95" => "\xe2\x80\xa2",
        "\xc2\x96" => "\xe2\x80\x93",
        "\xc2\x97" => "\xe2\x80\x94",
        "\xc2\x98" => "\xcb\x9c",
        "\xc2\x99" => "\xe2\x84\xa2",
        "\xc2\x9a" => "\xc5\xa1",
        "\xc2\x9b" => "\xe2\x80\xba",
        "\xc2\x9c" => "\xc5\x93",

        "\xc2\x9e" => "\xc5\xbe",
        "\xc2\x9f" => "\xc5\xb8"
    );

    protected static $utf8ToWin1252 = array(
        "\xe2\x82\xac" => "\x80",

        "\xe2\x80\x9a" => "\x82",
        "\xc6\x92"     => "\x83",
        "\xe2\x80\x9e" => "\x84",
        "\xe2\x80\xa6" => "\x85",
        "\xe2\x80\xa0" => "\x86",
        "\xe2\x80\xa1" => "\x87",
        "\xcb\x86"     => "\x88",
        "\xe2\x80\xb0" => "\x89",
        "\xc5\xa0"     => "\x8a",
        "\xe2\x80\xb9" => "\x8b",
        "\xc5\x92"     => "\x8c",

        "\xc5\xbd"     => "\x8e",


        "\xe2\x80\x98" => "\x91",
        "\xe2\x80\x99" => "\x92",
        "\xe2\x80\x9c" => "\x93",
        "\xe2\x80\x9d" => "\x94",
        "\xe2\x80\xa2" => "\x95",
        "\xe2\x80\x93" => "\x96",
        "\xe2\x80\x94" => "\x97",
        "\xcb\x9c"     => "\x98",
        "\xe2\x84\xa2" => "\x99",
        "\xc5\xa1"     => "\x9a",
        "\xe2\x80\xba" => "\x9b",
        "\xc5\x93"     => "\x9c",

        "\xc5\xbe"     => "\x9e",
        "\xc5\xb8"     => "\x9f"
    );

    static function toUTF8($text){
        /**
         * Function Encoding::toUTF8
         *
         * This function leaves UTF8 characters, while converting most non-UTF8 to UTF8.
         *
         * It assumes the encoding of the original string is Windows-1252 or ISO 8859-1.
         *
         * It may fail to convert characters if they fall into one of these scenarios:
         *
         * 1) when any of these characters:   ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞß
         *    are followed by any of these:  ("group B")
         *                                    ¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶•¸¹º»¼½¾¿
         * For example:   %ABREPRESENT%C9%BB. «REPRESENTÉ»
         * The "«" (%AB) character will be converted, but the "É" followed by "»" (%C9%BB)
         * is also a valid unicode character, and will be left unchanged.
         *
         * 2) when any of these: àáâãäåæçèéêëìíîï  are followed by TWO chars from group B,
         * 3) when any of these: ðñòó  are followed by THREE chars from group B.
         *
         * @name toUTF8
         * @param string $text  Any string.
         * @return string  The same string, UTF8 encoded
         *
         */

        if(is_array($text)) {
            /** @var array $text  */
            foreach($text as $k => $v)
            {
                $text[$k] = self::toUTF8($v);
            }
            return $text;
        }
        else if(is_string($text)) {

            $max = strlen($text);
            $buf = "";
            for($i = 0; $i < $max; $i++){
                $c1 = $text{$i};
                if($c1>="\xc0"){ //Should be converted to UTF8, if it's not UTF8 already
                    $c2 = $i+1 >= $max? "\x00" : $text{$i+1};
                    $c3 = $i+2 >= $max? "\x00" : $text{$i+2};
                    $c4 = $i+3 >= $max? "\x00" : $text{$i+3};
                    if($c1 >= "\xc0" & $c1 <= "\xdf"){ //looks like 2 bytes UTF8
                        if($c2 >= "\x80" && $c2 <= "\xbf"){ //yeah, almost sure it's UTF8 already
                            $buf .= $c1 . $c2;
                            $i++;
                        }
                        else { //not valid UTF8.  Convert it.
                            $cc1 = (chr(ord($c1) / 64) | "\xc0");
                            $cc2 = ($c1 & "\x3f") | "\x80";
                            $buf .= $cc1 . $cc2;
                        }
                    }
                    else if($c1 >= "\xe0" & $c1 <= "\xef"){ //looks like 3 bytes UTF8
                        if($c2 >= "\x80" && $c2 <= "\xbf" && $c3 >= "\x80" && $c3 <= "\xbf"){ //yeah, almost sure it's UTF8 already
                            $buf .= $c1 . $c2 . $c3;
                            $i = $i + 2;
                        }
                        else { //not valid UTF8.  Convert it.
                            $cc1 = (chr(ord($c1) / 64) | "\xc0");
                            $cc2 = ($c1 & "\x3f") | "\x80";
                            $buf .= $cc1 . $cc2;
                        }
                    }
                    else if($c1 >= "\xf0" & $c1 <= "\xf7"){ //looks like 4 bytes UTF8
                        if($c2 >= "\x80" && $c2 <= "\xbf" && $c3 >= "\x80" && $c3 <= "\xbf" 
                            && $c4 >= "\x80" && $c4 <= "\xbf"){ // likely it's UTF8 already
                            $buf .= $c1 . $c2 . $c3;
                            $i = $i + 2;
                        }
                        else { //not valid UTF8.  Convert it.
                            $cc1 = (chr(ord($c1) / 64) | "\xc0");
                            $cc2 = ($c1 & "\x3f") | "\x80";
                            $buf .= $cc1 . $cc2;
                        }
                    }
                    else { //doesn't look like UTF8, but should be converted
                        $cc1 = (chr(ord($c1) / 64) | "\xc0");
                        $cc2 = (($c1 & "\x3f") | "\x80");
                        $buf .= $cc1 . $cc2;
                    }
                }
                else if(($c1 & "\xc0") == "\x80"){ // needs conversion
                    if(isset(self::$win1252ToUtf8[ord($c1)])) { // Windows-1252 special cases
                        $buf .= self::$win1252ToUtf8[ord($c1)];
                    }
                    else {
                        $cc1 = (chr(ord($c1) / 64) | "\xc0");
                        $cc2 = (($c1 & "\x3f") | "\x80");
                        $buf .= $cc1 . $cc2;
                    }
                }
                else { // it doesn't need convesion
                    $buf .= $c1;
                }
            }
            return $buf;
        }
        else {
            return $text;
        }
    }

    static function toWin1252($text) {
        if(is_array($text)) {
            foreach($text as $k => $v) {
                $text[$k] = self::toWin1252($v);
            }
            return $text;
        }
        else if(is_string($text)) {
            return utf8_decode(str_replace(array_keys(self::$utf8ToWin1252), array_values(self::$utf8ToWin1252), self::toUTF8($text)));
        }
        else {
            return $text;
        }
    }

    static function toISO8859($text) {
        return self::toWin1252($text);
    }

    static function toLatin1($text) {
        return self::toWin1252($text);
    }

    static function fixUTF8($text){
        if(is_array($text)) {
            foreach($text as $k => $v) {
                $text[$k] = self::fixUTF8($v);
            }
            return $text;
        }

        $last = "";
        while($last <> $text){
            $last = $text;
            $text = self::toUTF8(utf8_decode(str_replace(array_keys(self::$utf8ToWin1252), array_values(self::$utf8ToWin1252), $text)));
        }
        $text = self::toUTF8(utf8_decode(str_replace(array_keys(self::$utf8ToWin1252), array_values(self::$utf8ToWin1252), $text)));
        return $text;
    }

    static function UTF8FixWin1252Chars($text){
        // If you received an UTF-8 string that was converted from Windows-1252 as it was ISO8859-1
        // (ignoring Windows-1252 chars from 80 to 9F) use this function to fix it.
        // See: http://en.wikipedia.org/wiki/Windows-1252

        return str_replace(array_keys(self::$brokenUtf8ToUtf8), array_values(self::$brokenUtf8ToUtf8), $text);
    }

    static function removeBOM($str=""){
        if(substr($str, 0,3) == pack("CCC",0xef,0xbb,0xbf)) {
            $str=substr($str, 3);
        }
        return $str;
    }
}

/**
 * to deprecate
 * @param array $arr
 * @param string $key
 * @param string $default
 * @return string
 */

function array_get( $arr, $key, $default ) {
    return isset($arr[$key])
    ? $arr[$key]
    : $default;
}


function text_to_words($text) {
    $ptn = "~".UWR."~iu";
    preg_match_all($ptn, $text, $m);
    return $m[0];
}

function dkretz() {
	global $User;
	return $User->Username() == 'dkretz';
}



define( 'NONCE_SECRET', "XA'T+^Q84;#d`f8^");
/**
 *
 * A tiny Nonce generator with variable time-outs.
 *
 * No database required.
 * Each Nonce has its own Salt.
 *
 */
class NonceUtil {


	/**
	 * Generate a Nonce.
	 *
	 * The generated string contains three parts, seperated by a comma.
	 * The first part is the individual salt. The seconds part is the
	 * time until the nonce is valid. The third part is a hash of the
	 * salt, the time, and a secret value.
	 *
	 * @param $secret string required String with at least 10 characters. The
	 * same value must be passed to check().
	 *
	 * @param $timeoutSeconds int the time in seconds until the nonce
	 * becomes invalid.
	 *
	 * @return string the generated Nonce.
	 *
	 */
	public static function generate($secret, $timeoutSeconds = 180) {
		if (is_string($secret) == false || strlen($secret) < 10) {
			throw new InvalidArgumentException("missing valid secret");
		}
		$salt = self::generateSalt();
		$time = time();
		$maxTime = $time + $timeoutSeconds;
		$nonce = $salt . "," . $maxTime . "," . sha1( $salt . $secret . $maxTime );
		self::save_nonce($nonce, $maxTime);
		return $nonce;
	}

	private static function save_nonce($nonce, $maxTime) {
		global $dpdb;
		$dpdb->SqlExecute("
			DELETE FROM nonces WHERE maxTime < UNIX_TIMESTAMP()");
		$dpdb->SqlExecute("
			DELETE FROM nonces WHERE nonce = '$nonce'");
		$dpdb->SqlExecute("
			INSERT INTO nonces ( nonce, maxTime ) VALUES ( '$nonce', $maxTime )");
	}


	/**
	 * Check a previously generated Nonce.
	 *
	 * @param $secret string the secret string passed to generate().
	 *
	 * @param $nonce string
	 *
	 * @return bool whether the Nonce is valid.
	 */
	public static function check($secret, $nonce) {
		global $dpdb;
		if (is_string($nonce) == false) {
			return false;
		}
		$a = explode(',', $nonce);
		if (count($a) != 3) {
			return false;
		}
		$salt = $a[0];
		$maxTime = intval($a[1]);
		$hash = $a[2];
		$back = sha1( $salt . $secret . $maxTime );
		if ($back != $hash) {
			return false;
		}
		if (time() > $maxTime) {
			return false;
		}

		if(! $dpdb->SqlExists(
				"SELECT 1 FROM nonces WHERE nonce = '$nonce'")) {
			return false;
		}
		$dpdb->SqlExecute("
			DELETE FROM nonces WHERE nonce = '$nonce'");

		return true;
	}


	private static function generateSalt() {
		$length = 10;
		$chars='1234567890qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM';
		$ll = strlen($chars)-1;
		$o = '';
		while (strlen($o) < $length) {
			$o .= $chars[ rand(0, $ll) ];
		}
		return $o;
	}
}

