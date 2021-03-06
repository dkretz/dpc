<?
$relPath='../pinc/';
include_once($relPath.'dpinit.php');
include_once($relPath.'theme.inc');
include_once($relPath.'prefs_options.inc');


$no_stats = 1;
theme(_('Custom Font Comparison'), 'header');

// if they're not logged in use arial

$tfont = 'Arial';

// determine user's current proofreading font, if any

if ($userP['i_layout' ] == 1) {
        $tfonti = $userP['v_fntf'];
        $tfont = $f_f[$tfonti];
}
else if (count($userP) > 0 && $userP['i_layout'] == 0) {
        $tfonti = $userP['h_fntf'];
        $tfont = $f_f[$tfonti];
}

$cfont = $tfont;


// get font variable from url, if any

if (isset($_GET['compare']) ) {
        $tfont = $_GET['compare'];
}


// use this font, unless it's DPCM2

if ($tfont != 'DPCustomMono2') {
	echo "<font face='$tfont'>";
} 
else {
	echo "<font face='Times New Roman, Times, serif'>";
}

if ($cfont != 'DPCustomMono2') {
    	$DPCM = 0;
} else {
	$DPCM = 1;
}



// echo text



$exp_text = "<h1><font face='DPCustomMono2'>DPCustomMono2</font>";

if ($tfont != BROWSER_DEFAULT_STR && $tfont != 'DPCustomMono2' && $tfont != "Monospaced") {
    $exp_text .= " vs. $tfont";
}


$exp_text .=
	"</h1>
        <p>"
        ._("DPCustomMono2 is a font adapted by DP's own big_bill, based on the
        suggestions and ideas of many experienced proofreaders, that helps
        proofreaders find mistakes.  You can change the font that you use for
        proofreading in your ")
        ."<a href='$code_url/userprefs.php'>"
        ._("preferences")."</a>. "
        ._("Here are some samples that compare DPCustomMono2 to other fonts.
        For information on installing and using the font, read the ")
        . "<a href='$forums_url/viewtopic.php?p=31521#31521'>"
        . _("DPWiki post.")
        ."</a></P>";

echo $exp_text;

$exp_text ="
        <p>
        <font face='DPCustomMono2, $tfont'>";

if ($DPCM) {
    $exp_text .= _("You currently have DPCustomMono2 selected as your default
    proofreading font.")." ";

} 

$exp_text .= 
     _("If you already have the font installed, you will see this paragraph in
     the DPCustomMono2 typeface.  If this paragraph's font doesn't look
     radically different to that of the paragraph above, you can download
     DPCustomMono2 from <a href='DPCustomMono2.ttf'> here </a> (right click the
     link, and choose Save Target As...).  After you have installed the font
     please refresh this page to make sure DPCustomMono2 is installed correctly.");

if ($DPCM) {
    $exp_text .= _("If DPCustomMono2 is displayed correctly in this paragraph,
    then please browse through the gallery of font comparisons below to remind
    yourself why it's so useful. ");

} 


if ($tfont == BROWSER_DEFAULT_STR || $tfont == "Monospaced") {
    $exp_text .= _("Since you have the non-specific font type of $tfont
    selected, we don't have any specific comparison images to show you; but we
    encourage you to browse through the gallery of comparisons to specific
    fonts from the links below, to see them juxtaposed with DPCustomMono2.");
}

$exp_text .= "</font></p>";

echo $exp_text;


$first = 1;

foreach ($f_f as $otherfont) {
    if($otherfont != $tfont && $otherfont != BROWSER_DEFAULT_STR 
                    && $otherfont != 'DPCustomMono2' && $otherfont != "Monospaced") {
        if (! $first) {
                echo " | ";
        } 
        else {
                $first = 0;
        }
        echo "<font face='$otherfont'><a
        href='font_sample.php?compare=$otherfont'>$otherfont</a></font>";
    }
}


echo " | <a href='images/Original.gif'>"._("View original image")."</a></P><br><br>\n";


if ($tfont != BROWSER_DEFAULT_STR && $tfont != 'DPCustomMono2' && $tfont != "Monospaced") {
    echo "<hr style='width: 546; text-align: left;'> <p>"
        . _("On this page, the top font is <b>$tfont</b>, and the bottom example is  ")
        ."<b>DPCustomMono2</b>.</p> <p><img border='0' src='images/"
        .$tfont."_A.gif'></p> <p><img border='0' src='images/DPCustomMono2_A.gif' width='588' height='264'></p>
        <hr style='width: 546; text-align: left;'> <p><img border='0' src='images/"
        .$tfont."_B.gif'></p> <p><img border='0' src='images/DPCustomMono2_B.gif' width='632' height='212'></p>
        <hr style='width: 546; text-align: left;'> <p><img border='0' src='images/"
        .$tfont."_C.gif'></p> <p><img border='0' src='images/DPCustomMono2_C.gif' width='624' height='190'></p>\n";
}

theme("", "footer");
?>
