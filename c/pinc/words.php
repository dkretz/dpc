<?php

function is_project_words_file($projectid, $code, $langcode) {
    $path = project_words_file_path($projectid, $code, $langcode);
    return is_file($path);
}

function is_project_good_words_file($projectid, $langcode) {
    return is_project_words_file($projectid, 'good', $langcode);
}

function is_project_bad_words_file($projectid, $langcode) {
    return is_project_words_file($projectid, 'bad', $langcode);
}

// every real words file is a serialized array
function make_empty_words_file($path) {
    file_put_contents($path, serialize(array()));
}

// initialize project good words file with site good words
// create a site file if none
function copy_site_good_words_file($projectid, $langcode) {
    $from = site_good_words_file_path($langcode);
    if(! file_exists($from)) {
        make_empty_words_file($from);
    }
    $to = project_words_file_path($projectid, "good", $langcode);
    copy($from, $to);
}

// initialize project bad words file with site bad words
// create a site file if none
function copy_site_bad_words_file($projectid, $langcode) {
    $from = site_bad_words_file_path($langcode);
    if(! file_exists($from)) {
        make_empty_words_file($from);
    }
    $to = project_words_file_path($projectid, "bad", $langcode);
    copy($from, $to);
}

function project_good_words_array($projectid, $langcode) {
    if(! is_project_good_words_file($projectid, $langcode)) {
        copy_site_good_words_file($projectid, $langcode) ;
    }
    return _read_project_words_array($projectid, 'good', $langcode);
}

function project_bad_words_array($projectid, $langcode) {
    if(! is_project_bad_words_file($projectid, $langcode)) {
        copy_site_bad_words_file($projectid, $langcode) ;
    }
    return _read_project_words_array($projectid, 'bad', $langcode);
}

//function suspect_words_array($projectid, $langcode) {
//    return _read_project_words_array($projectid, 'suspect', $langcode);
//}

function project_suggested_words_array($projectid, $langcode) {
    return _read_project_words_array($projectid, 'suggested', $langcode);
}

function words_file_name($code, $langcode) {
    return "{$langcode}.{$code}.txt";
}

// words files contain serialized arrays
// so unserializing the contents returns an array
function _read_file_array($path) {
    if(! is_file($path)) {
	    make_empty_words_file( $path );
	    return array();
    }

	$ret = @unserialize(file_get_contents($path));
		// if unreadable
	if($ret === false || ! is_array($ret)) {
		make_empty_words_file($path);
		return array();
	}

    // trim left, extract leading characters to first whitespace
    $ret = preg_replace("~^\s*(\S+).*?$~um", "$1", $ret);
    // remove empty words (i.e. just a return)
    $ret = array_diff($ret, array("", "\r"));
    return $ret;
}

// site word files contain an array of default words
function _read_site_words_array($code, $langcode) {
    $path = site_words_file_path($code, $langcode);
    if(! file_exists($path)) {
        make_empty_words_file($path);
        return array();
    }
    return _read_file_array($path);
}

function site_good_words_array($langcode) {
    return _read_site_words_array("good", $langcode);
}

function site_bad_words_array($langcode) {
    return _read_site_words_array("bad", $langcode);
}

function site_good_words_file_path($langcode) {
    return site_words_file_path('good', $langcode);
}

function site_bad_words_file_path($langcode) {
    return site_words_file_path('bad', $langcode);
}

function site_words_file_path($code, $langcode) {
    global $wordcheck_dir;
    return build_path($wordcheck_dir,
                    words_file_name($code, $langcode));
}

function project_words_file_path($projectid, $code, $langcode) {
    return build_path(ProjectWordcheckPath($projectid),
                    words_file_name($code, $langcode));
}

function _read_project_words_array($projectid, $code, $langcode) {
    $path = project_words_file_path($projectid, $code, $langcode);
    if(! is_file($path)) {
        make_empty_words_file($path);
        return array();
    }
    return _read_file_array($path);
}

function _write_file_array($path, $ary) {
    file_put_contents($path, serialize($ary));
}

function _write_project_words_array($projectid, $code, $langcode, $warray) {
    $warray = preg_replace("~^\s*?(.*?\S)\s*?$~um", "$1", $warray);
    $anew   = to_unique_array($warray);

    $anew = array_diff($anew, array(""));

    $path = project_words_file_path($projectid, $code, $langcode);
    _write_file_array($path, $anew);
}

function _write_site_words_array($code, $langcode, $warray) {
    $warray = preg_replace("~^\s*?(.*?\S)\s*?$~um", "$1", $warray);
    $anew   = to_unique_array($warray);

    $anew = array_diff($anew, array(""));

    $path = site_words_file_path($code, $langcode);
    _write_file_array($path, $anew);
}

//function merge_project_words_array($projectid, $code, $langcode, $warray)
//{
//    $a      = _read_project_words_array($projectid, $code, $langcode);
//    $anew   = to_unique_array(array_merge($warray, $a));
//
//    if(isset($anew['']))
//        unset($anew['']);
//
//    _write_project_words_array($projectid, $code, $langcode, $anew);
//}

// given a word list in a string, create a unique array of words.
// Note that capitalization counts.
function list_to_unique_array($word_list) {
    $ary = text_lines($word_list);
    return to_unique_array($ary);
}

function to_unique_array($ary) {
	assert(is_array($ary));
    sort($ary);
    return array_unique($ary);
}

// words and counts within a text
// to avoid hassle with numbers as words, don't make word the key
function word_count_array($text, $word_array) {
    // $ptn = "/".NON_WORD_CHAR."+/u";
    $textwords = array_count_values(text_to_words($text));
    $a = array();
    foreach($word_array as $word) {
        if(isset($textwords[$word])) {
            $a[$word] = $textwords[$word];
        }
        else {
            // $a[$word] = 0;
            // not any apparently
        }
    }
    return $a;
}


class DpPageLines {
    private $_text;
    private $_lines;
    function __construct($text) {
        $this->_text = $text;
    }

    function Lines() {
        if(! isset($this->_lines)) {
            $this->_lines = text_lines($this->_text);
        }
        return $this->_lines;
    }

    function LinePositionForOffset($offset) {
        $cumlen = 0;
        for($i = 0; $i < count($this->Lines()); $i++) {
        // foreach($this->Lines() as $l) {
            $l = $this->Line($i);
            $nextlen = $cumlen + mb_strlen($l) + 1;
            if($offset <= $nextlen) {
                return array($i+1, $offset - $cumlen);
            }
            $cumlen = $nextlen;
        }
        return null;
    }

    function Line($index) {
        $_lines = $this->Lines();
        return $_lines[$index];
    }

    function Length() {
        return count($this->Lines());
    }
}

function WordRegexArray($word, $flags, $text) {
    $ptn = "~" . $word . "~" . $flags;
    preg_match_all($ptn, $text, $match, PREG_PATTERN_ORDER);
    return array_unique($match[0]);
}

// accept an array of regexes with a common flag expression
// return array(array(word, line, offset))
function RegexWordLinePositions($awords, $flags, $text) {
    if(! is_array($awords)) {
        return array();
    }
    $lines = new DpPageLines($text);

    $a = array();
    foreach($awords as $word) {
        $ptn = "~" . $word . "~" . $flags;
        preg_match_all($ptn, $text, $match,
                            PREG_OFFSET_CAPTURE);
        $m = $match[0];
        for($i = 0; $i < count($m); $i++) {
            list($wd, $pos) = $m[$i];
            $os = $lines->LinePositionForOffset($pos);
            $a[] =  array("word" => $wd,
                          "lnum" => $os[0],
                          "lpos" => $os[1]);
        }
    }
    return $a;
}

