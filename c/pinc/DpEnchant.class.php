<?php

/*
\p{Ll}  G_UNICODE_LOWERCASE_LETTER
\p{Lt}  G_UNICODE_TITLECASE_LETTER
\p{Lu}  G_UNICODE_UPPERCASE_LETTER
\p{Lo}  G_UNICODE_OTHER_LETTER
\p{Lm}  G_UNICODE_MODIFIER_LETTER
  
     L& = Ll, Lu, Lt
     L  = L&, Lo, Lm
  
\p{Mc}  G_UNICODE_COMBINING_MARK
\p{Me}  G_UNICODE_ENCLOSING_MARK
\p{Mn}  G_UNICODE_NON_SPACING_MARK
  
     M = Mc, Me, Mn
  
\p{Nd}  G_UNICODE_DECIMAL_NUMBER
\p{Nl}  G_UNICODE_LETTER_NUMBER
\p{No}  G_UNICODE_OTHER_NUMBER

     N = Nd, Nl, No

\p{Pc}  G_UNICODE_CONNECT_PUNCTUATION

----------------------------------

Suffix:
' (&amp;)
\p{Pd}  G_UNICODE_DASH_PUNCTUATION

regex: "[\p{L}\p{M}\p{Pc}]+['\p{Pc}]*"

Dictionaries are in /usr/share/myspell
Project word files (temp) go to /d/enchant
Good, Bad, etc. files go to {project}/wordcheck
cli - enchant -d xx filename or < filename
*/


// define("UNICODE_WORD_REGEX", 
// "[\p{L}\p{M}\p{N}\p{Pc}][\p{L}\p{M}\p{N}\p{Pc}\p{Pd}']*[\p{L}\p{M}\p{N}\p{Pc}\p{Pd}]");
define("UNICODE_WORD_NUMBER_REGEX", 
     "[\p{L}\p{M}\p{N}\p{Pc}]"        // letter or digit or connector
    ."[\p{L}\p{M}\p{N}\p{Pc}\p{Pd}']+"); // ... or dash-punctuation

define("UNICODE_WORD_REGEX", 
"[\p{L}\p{M}\p{N}\p{Pc}][\p{L}\p{M}\p{N}\p{Pc}\p{Pd}']*[\p{L}\p{M}\p{N}\p{Pc}\p{Pd}]");

//     "[\p{L}\p{M}\p{Pc}][\p{L}\p{M}\p{Pc}\p{Pd}']+");
define('PREG_CLASS_UNICODE_WORD_BOUNDARY', '\x{0}-\x{2F}\x{3A}-\x{40}\x{5B}-\x{60}\x{7B}-\x{A9}\x{AB}-\x{B1}\x{B4}'
                                           . '\x{B6}-\x{B8}\x{BB}\x{BF}\x{D7}\x{F7}\x{2C2}-\x{2C5}\x{2D2}-\x{2DF}'
                                           . '\x{2E5}-\x{2EB}\x{2ED}\x{2EF}-\x{2FF}\x{375}\x{37E}-\x{385}\x{387}\x{3F6}'
                                           . '\x{482}\x{55A}-\x{55F}\x{589}-\x{58A}\x{5BE}\x{5C0}\x{5C3}\x{5C6}'
                                           . '\x{5F3}-\x{60F}\x{61B}-\x{61F}\x{66A}-\x{66D}\x{6D4}\x{6DD}\x{6E9}'
                                           . '\x{6FD}-\x{6FE}\x{700}-\x{70F}\x{7F6}-\x{7F9}\x{830}-\x{83E}'
                                           . '\x{964}-\x{965}\x{970}\x{9F2}-\x{9F3}\x{9FA}-\x{9FB}\x{AF1}\x{B70}'
                                           . '\x{BF3}-\x{BFA}\x{C7F}\x{CF1}-\x{CF2}\x{D79}\x{DF4}\x{E3F}\x{E4F}'
                                           . '\x{E5A}-\x{E5B}\x{F01}-\x{F17}\x{F1A}-\x{F1F}\x{F34}\x{F36}\x{F38}'
                                           . '\x{F3A}-\x{F3D}\x{F85}\x{FBE}-\x{FC5}\x{FC7}-\x{FD8}\x{104A}-\x{104F}'
                                           . '\x{109E}-\x{109F}\x{10FB}\x{1360}-\x{1368}\x{1390}-\x{1399}\x{1400}'
                                           . '\x{166D}-\x{166E}\x{1680}\x{169B}-\x{169C}\x{16EB}-\x{16ED}'
                                           . '\x{1735}-\x{1736}\x{17B4}-\x{17B5}\x{17D4}-\x{17D6}\x{17D8}-\x{17DB}'
                                           . '\x{1800}-\x{180A}\x{180E}\x{1940}-\x{1945}\x{19DE}-\x{19FF}'
                                           . '\x{1A1E}-\x{1A1F}\x{1AA0}-\x{1AA6}\x{1AA8}-\x{1AAD}\x{1B5A}-\x{1B6A}'
                                           . '\x{1B74}-\x{1B7C}\x{1C3B}-\x{1C3F}\x{1C7E}-\x{1C7F}\x{1CD3}\x{1FBD}'
                                           . '\x{1FBF}-\x{1FC1}\x{1FCD}-\x{1FCF}\x{1FDD}-\x{1FDF}\x{1FED}-\x{1FEF}'
                                           . '\x{1FFD}-\x{206F}\x{207A}-\x{207E}\x{208A}-\x{208E}\x{20A0}-\x{20B8}'
                                           . '\x{2100}-\x{2101}\x{2103}-\x{2106}\x{2108}-\x{2109}\x{2114}'
                                           . '\x{2116}-\x{2118}\x{211E}-\x{2123}\x{2125}\x{2127}\x{2129}\x{212E}'
                                           . '\x{213A}-\x{213B}\x{2140}-\x{2144}\x{214A}-\x{214D}\x{214F}'
                                           . '\x{2190}-\x{244A}\x{249C}-\x{24E9}\x{2500}-\x{2775}\x{2794}-\x{2B59}'
                                           . '\x{2CE5}-\x{2CEA}\x{2CF9}-\x{2CFC}\x{2CFE}-\x{2CFF}\x{2E00}-\x{2E2E}'
                                           . '\x{2E30}-\x{3004}\x{3008}-\x{3020}\x{3030}\x{3036}-\x{3037}'
                                           . '\x{303D}-\x{303F}\x{309B}-\x{309C}\x{30A0}\x{30FB}\x{3190}-\x{3191}'
                                           . '\x{3196}-\x{319F}\x{31C0}-\x{31E3}\x{3200}-\x{321E}\x{322A}-\x{3250}'
                                           . '\x{3260}-\x{327F}\x{328A}-\x{32B0}\x{32C0}-\x{33FF}\x{4DC0}-\x{4DFF}'
                                           . '\x{A490}-\x{A4C6}\x{A4FE}-\x{A4FF}\x{A60D}-\x{A60F}\x{A673}\x{A67E}'
                                           . '\x{A6F2}-\x{A716}\x{A720}-\x{A721}\x{A789}-\x{A78A}\x{A828}-\x{A82B}'
                                           . '\x{A836}-\x{A839}\x{A874}-\x{A877}\x{A8CE}-\x{A8CF}\x{A8F8}-\x{A8FA}'
                                           . '\x{A92E}-\x{A92F}\x{A95F}\x{A9C1}-\x{A9CD}\x{A9DE}-\x{A9DF}'
                                           . '\x{AA5C}-\x{AA5F}\x{AA77}-\x{AA79}\x{AADE}-\x{AADF}\x{ABEB}'
                                           . '\x{E000}-\x{F8FF}\x{FB29}\x{FD3E}-\x{FD3F}\x{FDFC}-\x{FDFD}'
                                           . '\x{FE10}-\x{FE19}\x{FE30}-\x{FE6B}\x{FEFF}-\x{FF0F}\x{FF1A}-\x{FF20}'
                                           . '\x{FF3B}-\x{FF40}\x{FF5B}-\x{FF65}\x{FFE0}-\x{FFFD}');

// define("UNICODE_WORD_DELIMITER", "[^\p{L}\p{M}\p{N}\p{Pc}\-{Pd}]");
define("UNICODE_WORD_DELIMITER", "[^\p{L}\p{M}\p{Pc}\-{Pd}]");
define("UWR", UNICODE_WORD_REGEX);
define("UWD", UNICODE_WORD_DELIMITER);

class DpEnchantedWords  {
    // fully composed word => array( line ... )
    private $_words;
    private $_text;
    private $_raw;
    private $_words_lines;
    private $_cmd;

    /*
        /var/transient/enchant/
    */

    public function __construct($langcode, $text, $projectid = "") {
        $this->_text = $text;

        // /var/transient/enchant/
        $wcpath = $this->transient_directory();

        $txtpath = build_path($wcpath, "{$projectid}.{$langcode}.enchant.txt");
        $txtpath = tempnam($wcpath, $txtpath);

	    if($langcode == "en") {
		    $langcode = "en_US";
	    }

        file_put_contents($txtpath, $text);
//	    die($txtpath);
        chmod($txtpath, 0777);
        $this->_cmd = "enchant -L -l -d $langcode $txtpath";

        $enchanted = shell_exec($this->_cmd);

        unlink($txtpath);

        $this->_raw = $enchanted;

        // dump no-words
        // dump numbers
        $enchanted = ReplaceRegex("^[\d\s\p{Pd}]*$", "", "um", $enchanted);


        // convert it to array and store items in _words_lines
        $tl = array_diff(text_lines($enchanted), array(""));


        $this->_words_lines = array();
        foreach($tl as $t) {
            list($l, $w) =  RegexSplit(" ", "u", $t);
            if(RegexCount("[\d\-\_]", "u", $w) == 0
                                    && mb_strlen($w) > 0) {
                $this->_words_lines[$w][] = $l;
            }
        }

        $this->_words = array_unique(array_diff($tl, array("")));
    }

    public function Input() {
        return $this->_text;
    }
    public function Command() {
        return $this->_cmd;
    }

    private function transient_directory() {
        global $transient_root;
        $dirpath = build_path($transient_root, "enchant");
        if(! is_dir($dirpath)) {
            mkdir($dirpath, 0775, true);
            is_dir($dirpath)
                or die ("mkdir failed for enchant transient path '$dirpath'");
        }
        return $dirpath;
    }


    public function WordsArray() {
        $a = ReplaceRegex("\d+\s+(.*?)$", "$1", "u", $this->_words);
//        natsort(array_values($a));
        return array_unique($a);
    }


    // transform word => array(lines) to array(word, line)
    public function WordLineArray() {
        $a = array();
        foreach($this->_words_lines as $word => $lines) {
            foreach($lines as $line) {
                $a[] = array($word, $line);
            }
        }
        return $a;
    }

    // transform word => array(lines) to array( word, array(lines))
    public function WordLinesArray() {
        $a = array();
        foreach($this->_words_lines as $word => $lines) {
            $a[] = array($word, $lines);
        }
        return $a;
    }

    function TextLines() {
        return text_lines($this->_text);
    }
}
?>
