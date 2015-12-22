<?PHP

ini_set('display_errors', 1);
error_reporting(E_ALL);

/*
 *
 *  may be invoked by:
 *      1. image_sources.php to edit a source (whose code is in src[])
 *         ($src_name != "")
 *      2. image_sources.php to create a new source (in which case submit_create_source is a POST variable)
 *      3. itself to process data (either a new source or a revised source) and submit_image_source is set)
 */


$relPath = "../../pinc/";
include_once $relPath . 'dpinit.php';
include_once $relPath . 'DpForm.class.php';

$code_name          = ArgArrayFirst("src");
if(! $code_name) {
    $code_name = Arg("form_code_name");
}
if(! $code_name) {
    $code_name = Arg("code_name");
}
$is_edit            = ($code_name != "");
$is_create          = IsArg("submit_create_source");
$is_submit          = IsArg("submit_image_source");

$display_name       = Arg("display_name");
$full_name          = Arg("full_name");
$url                = Arg("url");
$credit             = Arg("credit");
$internal_comment   = Arg("internal_comment");
$public_comment     = Arg("public_comment");

if($is_submit) {
    assert($code_name != "");
    $code_exists = $dpdb->SqlExists("
        SELECT 1 FROM image_sources
        WHERE code_name = '$code_name'");
    if($code_exists) {
        if (ApplyEdit($code_name, $display_name, $full_name, $url, $credit, $public_comment, $internal_comment)) {
            divert(url_for_image_sources());
            exit;
        } else {
            $is_edit = true;
        }
    }
    else {
        if (ApplyCreate($code_name, $display_name, $full_name, $url, $credit, $public_comment, $internal_comment)) {
            divert(url_for_image_sources());
            exit;
        } else {
            $is_create = true;
        }
    }
}
if($is_edit) {
    $header_text = _("Edit Image Source");
}
else if($is_create) {
    $header_text =  _("Add Image Source");
}
else {
    dump($_REQUEST);
    divert(url_for_image_sources());
    exit;
}

$no_stats = 1;
theme($header_text, "header");
echo "<h1 class='center'>{$header_text}</h1>\n";

// if coming from list, will have $src[] array with
// src_code to be edited in the first element


if($is_create) {
    $code_name =
    $display_name =
    $full_name =
    $url =
    $credit =
    $public_comment =
    $internal_comment = "";
}
else if($is_edit) {
    assert($code_name != "");
    $sql = "
        SELECT code_name,
               display_name,
               full_name,
               url,
               credit,
               internal_comment,
               public_comment
        FROM image_sources
        WHERE code_name = '$code_name'";
    $rec = $dpdb->SqlOneRow($sql);

    $code_name          = $rec["code_name"];
    $display_name       = $rec["display_name"];
    $full_name          = $rec["full_name"];
    $url                = $rec["url"];
    $credit             = $rec["credit"];
    $public_comment     = $rec["public_comment"];
    $internal_comment   = $rec["internal_comment"];
}

$form = new DpForm("tblsource");

if($is_create) {
    $form->AddRow("code_name", "Code Name", $code_name, "text");
}
else {
    $form->AddRow("code_name", "Code Name", $code_name, "display");
}
$form->AddRow("display_name", "Display Name", $display_name, "text");
$form->AddRow("full_name", "Full Name", $full_name, "text");
$form->AddRow("url", "URL", $url, "text");
$form->AddRow("credit", "Credit", $credit, "textarea");
$form->AddRow("public_comment", "Public Comment", $public_comment, "textarea");
$form->AddRow("internal_comment", "Internal Comment", $internal_comment, "textarea");
    echo "<form name='frmsource' id='frmsource' method='POST'>\n";
    if($code_name != "") {
        echo "<input type='hidden' name='form_code_name' id='form_code_name' value='$code_name'>\n";
    }
    $form->EchoForm();
    echo "</form>\n";

theme("", "footer");
exit;

function ApplyCreate($code_name, $display_name, $full_name, $url, $credit, $public_comment, $internal_comment) {
    global $dpdb;
    if(! $code_name) {
        assert(false);
        return false;
    }
    $sql = "SELECT 1 FROM image_sources
            WHERE code_name ='$code_name'";
    $is_code = $dpdb->SqlExists($sql);
    if($is_code) {
        return false;
    }
    $sql = "INSERT INTO image_sources
                  (code_name, display_name, full_name, url, credit, public_comment, internal_comment, is_active)
                VALUES
                  (?, ?, ?, ?, ?, ?, ?, 1)";
    $args = array(&$code_name, &$display_name, &$full_name, &$url, &$credit, &$public_comment, &$internal_comment);
    return $dpdb->SqlExecutePS($sql, $args);
}

/**
 * @param string $code_name
 * @param string $display_name
 * @param string $full_name
 * @param string $url
 * @param string $credit
 * @param string $public_comment
 * @param string $internal_comment
 * @param bool $is_create
 * @return bool|int
 */
function ApplyEdit(
        $code_name, $display_name, $full_name, $url, $credit, $public_comment, $internal_comment) {
    global $dpdb;
    if(! $code_name) {
        assert(false);
        return false;
    }
    $sql = "SELECT 1 FROM image_sources
            WHERE code_name ='$code_name'";
    $is_code = $dpdb->SqlExists($sql);
    if(! $is_code ) {
        return false;
    }
    $sql = "UPDATE image_sources
                SET display_name = ?,
                full_name = ?,
                url = ?,
                credit = ?,
                public_comment = ?,
                internal_comment = ?
                WHERE code_name = '$code_name'";
    $args = array(&$display_name, &$full_name, &$url, &$credit, &$public_comment, &$internal_comment);
//    dump($sql);
//    dump($args);
    return $dpdb->SqlExecutePS($sql, $args);
}
