<?php

/*
function RolePicker($name = "pkrole",
                    $class = "picker",
                    $dflt = "",
                    $onchange = "",
                    $ismulti = false) {
    global $dpdb;
    $sql = "SELECT description FROM roles
            ORDER BY description";
    $rows = $dpdb->SqlValues($sql);
    return picker($rows, $name, $dflt, $class, $onchange);
}

function RoleUserPicker($role,
                        $name = "pkuser",
                        $class = "picker",
                        $onchange = "",
                        $dispfunc = null,
                        $ismulti = false) {
    global $dpdb;
    $sql = "SELECT username FROM user_roles
            WHERE rolename = '$role'
            ORDER BY username";
    $rows = $dpdb->SqlValues($sql);
    return picker($rows, $name, $dflt, $class, $onchange);
}
*/


function LanguagePicker( $name = "pklangcode", $default = "",
$class = "", $onchange = "", $dispfunc = null, $ismulti = false) {
    $langs = ActiveLanguagesData();

    // $dflt2 = $default;
    // foreach($langs as $key => $data) {
        // $args[$data["code2"]] = $data["name"];
        // if($key == $default) {
            // $dflt2 = $data["code2"];
        // }
    // }

    return picker($langs, $name, $default, $class, 
                            $onchange, $ismulti, $dispfunc); 
}

function WordlistPicker($name="pkwordlist",
                        $dflt = "",
                        $class = "",
                        $onchange="") {
    $args = array( "flagged"    => _("Wordcheck words to flag"),
                   "suggested"  => _("Wordcheck suggested words"),
                   "good"       => _("Project good word list"),
                   "bad"        => _("Project bad word list"),
                   "allalpha"   => _("Project words (alphabetic)"),
                   "allcount"   => _("Project words (frequency)"),
                   "adhoc"      => _("Provide your own list"),
                   "regex"      => _("Find / replace"));
    return picker($args, $name, $dflt, $class, $onchange);
}

function multipicker($valarray,
                     $ctlname,
                     $selectedkey = "",
                     $class = "",
                     $onchange = "") {
    return picker($valarray, $ctlname, $selectedkey, $class, $onchange, true);
}

function picker($valarray,
                $ctlname,
                $selectedkey = "", 
                $class = "",
                $onchange = "",
                $mult = false,
				$dispfunc = null) {
    $ret = "<select name=\"{$ctlname}\" id=\"{$ctlname}\" "
            . ($selectedkey ? " title=\"{$selectedkey}\"" : "")
            . ($class ? " class=\"{$class}\"" : "")
            . ($onchange ? " onchange=\"{$onchange}\"" : "")
            . ($mult ? " multiple=\"multiple\" size=\"8\"" : "")
            .">\n";

    $ret .= ($selectedkey == "" 
        ? "
        <option value=\"\" selected='selected'></option>" : "");

    foreach($valarray as $key => $value) {
        $optstr = ("
        <option value=\"$key\""
            . ($key == $selectedkey 
                    ? " selected='selected'" 
                    : "") .">"
            . ($dispfunc != ""
                ? call_user_func($dispfunc, $key, $value)
                : $value)."</option>");
        $ret .= $optstr;
    }
    $ret .= "
    </select>\n";
    return $ret;
}

    function ActiveLanguagesData() {
        static $_data;
        global $wclangs;
//        global $_languages_array_2;
//        global $_active_languages_array_2;

        if(! isset($_data)) {
            $_data = array();
            foreach($wclangs as $key => $name) {
                $_data[$key] = $name;
            }
            asort($_data);
        }
        return $_data;
    }

