
/**
 *
 */
var formcontext;
var tblcontext;

var _active_context_index = -1;
var _active_ctl;


var _contexts;

function $(id) {
    return document.getElementById(id);
}

function addEvent(obj, evType, fn) {
    if(obj && obj.addEventListener) {
        obj.addEventListener(evType, fn, false);
    }
    if(obj && obj.attachEvent) {
        obj.attachEvent("on" + evType, fn);
    }
}

function set_scroll_pct(ctl, pct) {
    if(pct <= 0) {
        return;
    }

    var torange = ctl.scrollHeight - ctl.clientHeight;
    if(torange <= 0) {
        return;
    }

    ctl.scrollTop = torange * pct / 100;
}

// load event for wordcontext page
// we have projectid, language, and which list type.
// need to pull list and select first word and context.
function eContextInit() {
    formcontext = $("formcontext");

    addEvent(formcontext.btnremove,  "click", eRemoveWordClick);
    addEvent(formcontext.btngood,    "click", eGoodWordClick);
    addEvent(formcontext.btnbad,     "click", eBadWordClick);
    addEvent(formcontext.btnrefresh, "click", eRefreshClick);
    //addEvent(formcontext. btnreplace, "click", eReplaceWordClick);

    formcontext.btnremove.style.display =
        ['good', 'bad', 'suggested']
            .indexOf($("mode").value) >= 0
        ? "inline-block" 
        : "none";
    formcontext.btngood.style.display =
        ['bad', 'suggested', 'adhoc', 'flagged', 'regex']
            .indexOf($("mode").value) >= 0
        ? "inline-block"
        : "none";
    formcontext.btnbad.style.display = 
        ['good', 'suggested', 'adhoc', 'flagged', 'regex']
            .indexOf($("mode").value) >= 0
        ? "inline-block"
        : "none";
    //formcontext.btnreplace.style.display = "inline-block";
    switch($("mode").value) {
        default:
            $('command-section').style.height = "25%";
            $('divtblcontext').style.height = "75%";
            break;

        case "regex":
        case 'adhoc':
            $('command-section').style.height = "50%";
            $('divtblcontext').style.height = "50%";
            break;
    }

    sync_context();
}

function eGoodWordClick(e) {
    var i = formcontext.tblcontext.selectedIndex;
    if(i >= 0) {
        switch($("mode").value) {
            case "good":
                break;
            case "bad":
                awcBadToGoodWord(i);
                break;
            //case "suggested":
            //    awcSuggestedToGoodWord(i);
            //    break;
            case "flagged":
            case "adhoc":
                awcAddGoodWord(i);
                break;
        }
        formcontext.tblcontext.remove(i);
        formcontext.tblcontext.selectedIndex = i;
        sync_context();
    }
}

function eRefreshClick(e) {
    sync_context();
}

// user clicks a list word to choose a context set
// the option element for the word has value w_ + the word
function eTblContextChange(e) {
    //_active_context_index = -1;
    sync_context();
    return true;
}

function eBadWordClick(e) {
    var i = formcontext.tblcontext.selectedIndex;
    if(i >= 0) {
        switch($("mode").value) {
            case "good":
                awcGoodToBadWord(i);
                break;
            case "bad":
                break;
            case "suggested":
                awcSuggestedToBadWord(i);
                break;
            case "flagged":
            case "adhoc":
                awcAddBadWord(i);
                break;
        }
        formcontext.tblcontext.remove(i);
        formcontext.tblcontext.selectedIndex = i;
        sync_context();
    }
}

function eRemoveWordClick() {
    var i = formcontext.tblcontext.selectedIndex;
    if(i < 0) {
        return;
    }
    switch($("mode").value) {
        case "good":
            awcRemoveGoodWord(i);
            break;
        case "bad":
            awcRemoveBadWord(i);
            break;
        case "suggested":
            awcRemoveSuggestedWord(i);
            break;
        case "flagged":     // Remove meaningless
            return;
            break;
        case "adhoc":
            break;
    }
    formcontext.tblcontext.remove(i);
    formcontext.tblcontext.selectedIndex = i;
    sync_context();
}

function eContextImgLoad() {
    var context_image_scroll_pct = 
        (active_context() && (active_context().lineindex >= 0))
            ? 100 * active_context().lineindex 
                                / active_context().linecount 
            : -1;
    set_scroll_pct($('div_context_image'), context_image_scroll_pct);
}

// consequence of word table item onclick
function eSetContextWordIndex(index) {
    if(index == _active_context_index) {
        return;
    }
    // if a current item is active, un-hilite it
    if(_active_context_index >= 0) {
        $('divctxt_' + _active_context_index.toString()).style.backgroundColor = "#EEEEEE";
    }

    _active_context_index = index;
    $('divctxt_' + _active_context_index.toString()).style.backgroundColor = "white";

    $('imgcontext').src =  active_context().imageurl.replace(/\s/g, "").replace(/&amp;/g, "&");
    eSetImageScroll();
}


function eSetImageScroll() {
    var context_image_scroll_pct =
        (active_context() && (active_context().lineindex > 0))
            ? 100 * active_context().lineindex
        / active_context().linecount
            : -1;
    set_scroll_pct($('div_context_image'), context_image_scroll_pct);
}

/*
function SetAllCheck(val) {
    var i;
    var row;
    var tbl = pf$('tblcontext');
    for (i = 0; i < tbl.rows.length; i++) {
        row = tbl.rows[i];
        c = row.getElementsByTagName('input')[0];
        if(c) {
            c.checked = val;
        }
    }
}

function eCheckAll() {
    SetAllCheck(true);
}

function eUncheckAll() {
    SetAllCheck(false);
}
*/

// json response handler for wordcontext
// fills top box with contexts from json
// response has array of pagename, lineindex, linecount, context
function ajxDisplayWordContextList(rsp) {
    var i;
    var str = "";
 
    if(! $("div_context_list")) {
        return;
    }
    _contexts = rsp.contextinfo.contexts;
    if(_contexts.length < 1) {
        return;
    }
    for(i = 0; i < _contexts.length; i++) {
        var ctxt = _contexts[i];
        var id = i.toString();
        str = str + "<div class='ctxt' id='divctxt_" + id + "'"
        + " onclick='eSetContextWordIndex(" + id + ")'>"
        + "<div class='ctxt-left'>"
        + "page " + ctxt.pagename
        + "<br>line " + ctxt.lineindex.toString()
        + " of " + ctxt.linecount.toString()
        + "</div>"
        + "<div class='ctxt-right'>"
        + ctxt.context + "</div>  <!-- ctxt-right -->"
        + "</div>\n";
    }
    $("div_context_list").innerHTML = str;
    $("div_context_list").scrollTop = 0;

    // now make the first context active
    eSetContextWordIndex(0);
}

// json response handler for wccontext
// builds the word list for context selection
function ajxDisplayContextWords(rsp) {
    var i;
    var str = "<tbody>\n";
    if(_active_ctl) {
        _active_ctl = null;
    }
    for (i = 0; i < rsp.length; i++) {
        var wrd = rsp[i][0];
        var n   = rsp[i][1];
        str = str + "<tr><td id='w_" + wrd + "' class='likealink'"
                + " style='background-color: white;'>"
                + wrd + "</td><td>" + n + "</td></tr>\n";
    }
    str += "</tbody>\n";
    formcontext.tblcontext.innerHTML = str;
}

// json response handler for regexcontext
// fills top box with contexts from json
// response has array of pagename, lineindex, linecount, context
function ajxDisplayRegexContextList(rsp) {
    var i;
    var str = "";
 
    if(! $("div_context_list")) {
        return;
    }
    _contexts = rsp.contextinfo.contexts;
    if(_contexts.length < 1) {
        return;
    }
    // DisplayRegexList(_rsp.word, _contexts.length);
    for(i = 0; i < _contexts.length; i++) {
        var ctxt = _contexts[i];
        var id = i.toString();
        str = str + "<div class='ctxt' id='divctxt_" + id + "'"
        + " onclick='eSetContextWordIndex(" + id + ")'>"
        + "<div class='ctxt-left'>"
        + "page " + ctxt.pagename
        + "<br>line " + ctxt.lineindex.toString()
        + " of " + ctxt.linecount.toString()
        + "</div>"
        + "<div class='ctxt-right'>"
        + ctxt.context + "</div>  <!-- ctxt-right -->"
        + "</div>\n";
    }
    $("div_context_list").innerHTML = str;
    $("div_context_list").scrollTop = 0;
    eSetContextWordIndex(0);
}

function sync_context() {
    var w = "";
    var t = $("tblcontext");
    if(! t) {
        return;
    }
    if($('mode').value == "regex") {

    }
    else {
        if (t.value.length <= 2) {
            return;
        }

        var w = t.value.substring(2);
    }
    qry = {};
    qry['querycode'] = 'wordcontext';
    qry['projectid'] = $("projectid").value;
    qry['word']      = w;
    writeAjax(qry);
}

function active_context() {
    return _contexts.length > 0
        ? _contexts[_active_context_index]
        : null;
}

// select another language
function eLangPick() {
    requestContext();
}

// select another wordlist type
function eListPick() {
    requestContext();
}

function requestContext() {
    awcContext();
}

// -----------------------------------------------------------------
// ajax calls
// -----------------------------------------------------------------

function awcAddGoodWord(i) {
    var qry = {};

    // hide now-defunct contexts
    $("div_context_list").style.visibility = "hidden";

    qry['querycode']    = "addgoodword";
    qry['projectid']    = formcontext.projectid.value;
    qry['langcode']     = formcontext.langcode.value;
    qry['word']         = formcontext.tblcontext.options[i]
                            .value.substring(2);
    //writeAjax(qry);
    myAjax(qry);
}

function awcAddBadWord(i) {
    var qry = {};
    qry['querycode']    = "addbadword";
    qry['projectid']    = formcontext.projectid.value;
    qry['langcode']     = formcontext.langcode.value;
    qry['word']         = formcontext.tblcontext.options[i]
                            .value.substring(2);
    writeAjax(qry);
}

function awcRemoveGoodWord(i) {
    var qry = {};
    qry['querycode']    = "removegoodword";
    qry['projectid']    = formcontext.projectid.value;
    qry['langcode']     = formcontext.langcode.value;
    qry['word']         = formcontext.tblcontext.options[i]
                            .value.substring(2);
    writeAjax(qry);
}

function awcRemoveBadWord(i) {
    var qry = {};
    qry['querycode']    = "removebadword";
    qry['projectid']    = formcontext.projectid.value;
    qry['langcode']     = formcontext.langcode.value;
    qry['word']         = formcontext.tblcontext.options[i]
                            .value.substring(2);
    writeAjax(qry);
}

function awcRemoveSuggestedWord(i) {
    var qry = {};
    qry['querycode']    = "removesuggestedword";
    qry['projectid']    = formcontext.projectid.value;
    qry['langcode']     = formcontext.langcode.value;
    qry['word']         = formcontext.tblcontext.options[i]
                            .value.substring(2);
    writeAjax(qry);
}

function awcGoodToBadWord(i) {
    var qry = {};
    qry['querycode']    = "goodtobadword";
    qry['projectid']    = formcontext.projectid.value;
    qry['langcode']     = formcontext.langcode.value;
    qry['word']         = formcontext.tblcontext.options[i]
                            .value.substring(2);
    writeAjax(qry);
}

function awcBadToGoodWord(i) {
    var qry = {};
    qry['querycode']    = "badtogoodword";
    qry['projectid']    = formcontext.projectid.value;
    qry['langcode']     = formcontext.langcode.value;
    qry['word']         = formcontext.tblcontext.options[i]
                            .value.substring(2);
    writeAjax(qry);
}

function awcSuggestedToBadWord(i) {
    var qry = {};
    qry['querycode']    = "suggestedtobadword";
    qry['projectid']    = formcontext.projectid.value;
    qry['langcode']     = formcontext.langcode.value;
    qry['word']         = formcontext.tblcontext.options[i]
                            .value.substring(2);
    writeAjax(qry);
}

function awcSuggestedToGoodWord(i) {
    var qry = {};
    qry['querycode']    = "suggestetogoodword";
    qry['projectid']    = formcontext.projectid.value;
    qry['langcode']     = formcontext.langcode.value;
    qry['word']         = formcontext.tblcontext.options[i]
                            .value.substring(2);
    writeAjax(qry);
}


function awcContext() {
    var qry = {};
    qry['querycode']    = "wccontext";
    qry['projectid']    = formcontext.projectid.value;
    qry['langcode']     = formcontext.langcode.value;
    qry['mode']         = $("mode").value;
    writeAjax(qry);

}

// -----------------------------------------------------------------
// ajax callback
// -----------------------------------------------------------------

// handler for responses returning from web service
function eWCMonitor(msg) {
    _rsp = JSON.parse(msg);
    console.log('response arrived for ' + _rsp.querycode );
    switch (_rsp.querycode) {

        case 'wccontext':
            ajxDisplayContextWords(_rsp.wordarray);
            break;

        case 'wordcontext':
            ajxDisplayWordContextList(_rsp);
            break;

        case 'regexcontext':
            ajxDisplayRegexContextList(_rsp);
            break;

        case 'addgoodword':
        case 'addbadword':
        case 'removegoodword':
        case 'removebadword':
        case 'removesuggestedword':
        case 'badtogoodword':
        case 'goodtobadword':
        case 'suggestedtobadword':
        case 'suggestedtogoodword':
            // all these send stuff off to wc.php to be disposed of and only get ACK back

            $("div_context_list").style.visibility = "visible";
            break;

        default:
            window.alert("unknown querycode: " + msg);
            break;
    }
}

// -----------------------------------------------------------------
// ajax functions
// -----------------------------------------------------------------

var _ajax;
//var _ajaxActionFunc;

function myAjax(args) {
    var _ajax;
    if(window.XMLHttpRequest) {
        _ajax = new XMLHttpRequest();
    }
    else {
        _ajax = new ActiveXObject("Microsoft.XMLHTTP");
    }
    //_ajaxActionFunc = eWCMonitor;
    _ajax.onreadystatechange = function() {
        var msg;
        var errstr, jsonrsp;
        if(_ajax.responseText == "") {
            return;
        }
        console.log("readAjax: " + _ajax.responseText.substring( 0, 200));
        if(_ajax.readyState == 4 && _ajax.status == 200) {
            msg = _ajax.responseText;
            errstr = "";
            try {
                errstr = "err decodeURI msg: ";
                msg = decodeURIComponent(msg);
                errstr = "err parse msg: ";
                //jsonrsp = JSON.parse(msg);
                JSON.parse(msg);
                errstr = "";
                //console.log("readAjax success: " + msg.substring(0, 200));
            }
            catch(err) {
                //console.log("readAjax FAILED: " + errstr + msg);
                window.alert(errstr + msg);
                return;
            }

            eWCMonitor(msg);
            //if(_ajaxActionFunc) {
            //    _ajaxActionFunc(msg);
            //}
        }
    };
    function write(a_args) {
        var jq = JSON.stringify(a_args);
        console.log("ajax.write: " + jq.substring( 0, 200));
        jq = "jsonqry=" + encodeURIComponent(jq);
        _ajax.open("POST", AJAX_URL, true);
        _ajax.setRequestHeader("Content-type",
            "application/x-www-form-urlencoded");
        _ajax.send(jq);
    }
    write(args);
}
function initAjax() {
    if(_ajax) {
        return;
    }
    if(window.XMLHttpRequest) {
        _ajax = new XMLHttpRequest();
    }
    else {
        _ajax = new ActiveXObject("Microsoft.XMLHTTP");
    }
    //_ajaxActionFunc = eWCMonitor;
    _ajax.onreadystatechange = readAjax;
}

// NOTE: DP-IT has different code, apparently to deal with queue overload
function writeAjax(a_args) {
    // php end will rawurldecode this to recover it
    //var jq = 'jsonqry=' + encodeURIComponent(JSON.stringify(a_args));
    var jq = JSON.stringify(a_args);
    console.log("writeAjax: " + jq.substring( 0, 200));
    jq = "jsonqry=" + encodeURIComponent(jq);
    initAjax();
    _ajax.open("POST", AJAX_URL, true);
    _ajax.setRequestHeader("Content-type",
                        "application/x-www-form-urlencoded");
    _ajax.send(jq);
}

// NOTE: DP-IT has different code, apparently to deal with queue overload
function readAjax() {
    var msg;
    var errstr, jsonrsp;
    if(_ajax.responseText == "") {
        return;
    }
    console.log("readAjax: " + _ajax.responseText.substring( 0, 200));
    if(_ajax.readyState == 4 && _ajax.status == 200) {
        msg = _ajax.responseText;
        errstr = "";
        try {
            errstr = "err decodeURI msg: ";
            msg = decodeURIComponent(msg);
            errstr = "err parse msg: ";
            //jsonrsp = JSON.parse(msg);
            JSON.parse(msg);
            errstr = "";
            //console.log("readAjax success: " + msg.substring(0, 200));
        }
        catch(err) {
            //console.log("readAjax FAILED: " + errstr + msg);
            window.alert(errstr + msg);
            return;
        }

        eWCMonitor(msg);
        //if(_ajaxActionFunc) {
        //    _ajaxActionFunc(msg);
        //}
    }
}

