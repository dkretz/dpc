<?PHP
// dancer.php
// dance the project through the phases
// <div><input type='submit' name='smt_phase' value='from phase'/></div>

ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once($relPath.'dpinit.php');
include_once($relPath.'theme.php');

// -----------------------------------------------------------------------------
// collect GETs, POSTs, etc.
// -----------------------------------------------------------------------------

$User->IsLoggedIn()
    or redirect_to_home();

$projectid = ArgProjectId();
$projectid != ""
    or die("No Project ID.");
$project  = new DpProject($projectid);
$project->Exists()
    or die("No such project: $projectid");
$sql = "
    SELECT pv.projectid, pv.pagename, pv.version
    FROM page_last_versions pv
    WHERE pv.projectid = ?
    ORDER BY pagename";
$args = array(&$projectid);
$pgrows = $dpdb->SqlRowsPs($sql, $args);
$npages    = count($pgrows);

if($npages < 1) {
    die("No page texts available for this project.");
}

//$roundid    = ArgRound();
// if not specified, use last version

//foreach($pgrows as $pgrow) {
//    $rows = ($roundid == "")
//        ? $project->LastPageVersions()
//        : $project->RoundPageVersions($roundid);
//}

$projecturl = $project->ProjectUrl();
$options = array();
$divs    = array();
$selected = "selected='SELECTED'";

foreach($pgrows as $pgrow) {
    $pagename = $pgrow["pagename"];
    $version = (integer) $pgrow["version"];
    $text = PageVersionText($projectid, $pagename, $version);
    $options[]  = "<option id='pg$pagename' value='{$pagename}' $selected>$pagename</option>";
    $divs[]     = "<pre class='concat' id='div{$pagename}'><div class='pgnum' id='pg{$pagename}' name='#$pagename'>$pagename</div>$text</pre>
                    <!-- $pagename -->\n";
    $selected = "";
}

$pagename1 = $pgrows[0]["pagename"];
$imageurl1 = url_for_page_image($projectid, $pagename1);

$pagedivs = implode("\n", $divs);

$_theme = new DpTheme();
$_theme->EchoHtmlHead();

echo "

<body>

<style type='text/css'>
body { height: 100%; }
#selpage { position: absolute; height: 100%;}
#divtext { position: absolute; left: 5%; width: 50%; height: 100%; padding: 0 2em; }
.concat {  margin: 0; padding: 0 .2em; border: 1px solid pink; font-family: monospace; font-size: 14px; }
#divimage { position: absolute; right: 0; width: 45%; height: 100%; padding: 0 1em; }
div.pgnum { width: 2em; text-align: right; position: absolute; left: -2em; width: 4.5em; font-size: .7em; color: #8080FF; }
b, i { color: blue; }
sc { color: red; font-variant: small-caps; }
</style>

<script type='text/javascript'>

function addEvent(obj, evType, fn) {
    if(obj && obj.addEventListener) {
        obj.addEventListener(evType, fn, false);
    }
    else if(obj && obj.attachEvent) {
        obj.attachEvent('on' + evType, fn);
        obj.setCapture();
    }
}
function pgclick(e){
    var n;
    if(! e) e = window.event;
    var t = e.target;

    for(var i  = 0; i < 2; i++) {
        switch(t.tagName) {
            case 'OPTION':
                eSelect(t);
                return true;
            case 'PRE':
                eDivClick(t.id);
                return true;
        }
        t = t.parentNode;
    }
}



/*
function eAnchor(id) {
    var selvalue = id.replace(/pg/, '');
    var pgname = id;
    // display the page image
    $('imgpage').src = image_url(pgname);
    // reposition the page list
    selpage.value = selvalue;
}
*/

function eSelect(option) {
    var pgname = option.value;
    imgpage.src = image_url(pgname);
    $('div' + pgname).scrollIntoView();
}

// example value: 'div001'
function eDivClick(id) {
    var pgname = id.replace(/div/, '');
    imgpage.src = image_url(pgname);
    // reposition the page list
    selpage.value = pgname;
}

function image_url(pgname) {
    return $('imageurl').value
        + '?projectid=' + projectid.value
        + '&pagename=' + pgname;
}

addEvent(document, 'click', pgclick);
</script>

<!--      begin page --------------->
<form name='frmpager' id='frmpager' method='POST'>
<input type='hidden' name='imageurl' id='imageurl' value='http://www.pgdpcanada.net/c/imgsrv.php'>
<input type='hidden' name='projectid' id='projectid' value='$projectid'>'
<input type='hidden' name='projecturl' id='projecturl' value='$projecturl'>'

<select id='selpage' name='selpage' size='$npages'>
    " . implode("\n", $options) . "
</select>

<div id='divtext'>
    $pagedivs
</div>  <!-- divtext -->

<div id='divimage'>
        <img name='imgpage' id='imgpage' src='$imageurl1' width='100%'>
</div>  <!-- divimage -->
</form>\n";

//$_theme->EchoFooter();







// -----------------------------------------------------------------------------
// Execution logic
// -----------------------------------------------------------------------------


// -----------------------------------------------------------------------------
// Data acquisition
// -----------------------------------------------------------------------------



// -----------------------------------------------------------------------------
// Display
// -----------------------------------------------------------------------------


echo "</body></html>";
exit;


