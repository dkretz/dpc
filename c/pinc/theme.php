<?PHP

class DpTheme {

    public function __construct() {

    }

    public function EchoHtmlHead($title = "DP Canada Template") {
        echo "<!DOCTYPE HTML>
        <html>
        <head>
            <meta charset='utf-8'>
            <link rel='shortcut icon' href='http://www.pgdpcanada.net/c/favicon.ico'>
            <script type='text/javascript' src='/c/js/dp.js'></script>
            <title>$title</title>
            <link type='text/css' rel='Stylesheet' href='/c/css/dp.css'>
        </head>";
    }

    public function EchoHeader() {
        global $Context;

        $this->EchoHtmlHead();
        $nproj = number_format($Context->PostedCount());
        echo "
        <body >
        <div class='w100'>
            <div id='logobox'>
                <div id='logoleft'>
                    <a href='http://www.pgdpcanada.net/c/default.php'>
                        <img width='336' height='68' alt='Distributed Proofreaders' src='http://www.pgdpcanada.net/c/graphics/dpclogo.png'>
                    </a>
                </div>
                <div id='logoright' class='w50 lfloat middle'>
                    <span class=' w100 center'> $nproj titles preserved for the world!</span>
                </div>
            </div>
        </div>\n";
    }

    public function EchoNavbar() {
    $register = _("Register");
    $signin  = _("Sign In");
    $regurl  = url_for_registration();

        echo "
<div id='navbox'>
    <div id='navleft' class='nav lfloat'>
      <a class='nav' href='http://www.pgdpcanada.net/c/default.php'>DPC</a>
    · <a class='nav' href='http://www.pgdpcanada.net/c/activity_hub.php'>Activity Hub</a>
    · <a class='nav' href='http://www.pgdpcanada.net/c/search.php'>Project Search</a>
    · <a class='nav' href='http://www.pgdpcanada.net/c/tools/proofers/my_projects.php'>My Projects</a>
    · <a class='nav' href='http://www.pgdpcanada.net/c/userprefs.php'>My Preferences</a>
    · <a class='nav' href='http://www.pgdpcanada.net/forumdpc/ucp.php?i=pm&amp;folder=inbox'>My Inbox</a>
    · <a class='nav' href='http://www.pgdpcanada.net/forumdpc'>Forums</a>
    · <a class='nav' href='http://www.pgdpcanada.net/wiki/index.php'>Wiki</a>
    · <a class='nav' href='http://www.pgdpcanada.net/c/tools/logout.php'>Log out (dkretz)</a>
    </div>
    <div id='navright' class='nav rfloat'>
    <form class='nomargin' id='frmlogin' name='frmlogin' method='post'>
      ID <input class='nav' type='text' name='userNM' size='10' tabindex='1' maxlength='50'> "
            . _("Password:") . " <input class='nav' type='password' name='userPW' size='10' tabindex='2' maxlength='50'>
      <input class='nav' type='submit' name='submit_login' id='submit_login' value='$signin'>
      <a class='nav' href='$regurl'>$register</a>
    </form>
    </div>
</div>\n";

    }

    private function timer_string() {
        global $starttime;
        $mtime = explode(" ", microtime());
        $endtime = $mtime[1] + $mtime[0];
        $totaltime = ($endtime - $starttime);
        return left($totaltime, 5);
    }

    public function EchoFooter() {
        global $code_version;

        $strtime = $this->timer_string();
        $copyright = _("Copyright Distributed Proofreaders Canada");
        $build     = _(" (Page Build Time: {$strtime} ");
        $version   = _(" Version $code_version");


        echo "<div id='divfooter' class='w100 em80 center white noserif redback'>
            $copyright
            $build
            $version
            </div> <!-- divfooter -->\n";
//            . _("Copyright Distributed Proofreaders Canada")
//            . _(" (Page Build Time: {$strtime} ")
//            . _(" Version $code_version")
//            . "</div> <!-- divfooter -->\n";
//        if($User->Username() == 'dkretz') {
//            echo implode("<br>\n", timer_array());
//        }
    }

    public function echo_quick_links() {
        function echo_quick_links() {
            global $User;

            if (! $User->IsLoggedIn())
                return;

            $items = array();

            if ($User->IsProjectManager() || $User->IsSiteManager()) {
                $items[] = link_to_project_manager("PM");
            }

            foreach(array("P1", "P2", "P3", "F1", "F2") as $phs) {
                if($User->MayWorkInRound($phs)) {
                    $items[] = link_to_round($phs);
                }
            }
            if($User->MayWorkInRound("PP")) {
                $items[] = link_to_pp();
            }
            if($User->MayWorkInRound("PPV")) {
                $items[] = link_to_ppv();
            }
            $items[] = link_to_smooth_reading("SR");

            $divider        = "\n · ";
            echo implode($divider, $items);
        }
        global $User;

        if (! $User->IsLoggedIn())
            return;

        $items = array();

        if ($User->IsProjectManager() || $User->IsSiteManager()) {
            $items[] = link_to_project_manager("PM");
        }

        foreach(array("P1", "P2", "P3", "F1", "F2") as $phs) {
            if($User->MayWorkInRound($phs)) {
                $items[] = link_to_round($phs);
            }
        }
        if($User->MayWorkInRound("PP")) {
            $items[] = link_to_pp();
        }
        if($User->MayWorkInRound("PPV")) {
            $items[] = link_to_ppv();
        }
        $items[] = link_to_smooth_reading("SR");

        $divider        = "\n · ";
        echo implode($divider, $items);
    }
}
