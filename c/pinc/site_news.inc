<?PHP

global $Context;

$news_pages = array(
    'PREP'    => _("Project Prep"),
    'PP'      => _("Post Processing"),
    'PPV'     => _("PP Verification"),
    'FRONT'   => _('Front Page'),
    'FAQ'     => _('FAQ Central'),
    'HUB'     => _('Activity Hub'),
    'STATS'   => _('Stats Central'),
    'SR'      => _('Smooth Reading'),
    'GLOBAL'  => _('Global Items'),
);

/** @var Round $round */
if(! isset($Context)) {
	$Context = new DpContext();
}
foreach($Context->Rounds() as $round) {
	$news_pages[$round->RoundId()] = $round->Caption();
}
//foreach ( $Stage_for_id_ as $stage_id => $stage ) {
//    $news_pages[$stage_id] = $stage->name;
//}

function get_news_subject( $news_page_id ) {
    global $news_pages;
    return $news_pages[$news_page_id];
}

// -----------------------------------------------------------------------------

function get_news_page_last_modified_date( $news_page_id ) {
    global $dpdb;

    return $news_page_id == 'GLOBAL'
		? $dpdb->SqlOneValue("
			SELECT t_last_change FROM news_pages
			WHERE news_page_id IS NULL")
	    : $dpdb->SqlOneValue("
			SELECT t_last_change FROM news_pages
			WHERE news_page_id = '$news_page_id'");
}

// -----------------------------------------------------------------------------

// Show the news block for the given page, consisting of:
// -- a header,
// -- all the 'current' news items,
// -- a randomly-chosen 'recent' news item,
// where the news items are designated for the given page,
// or for every page.
function show_news_for_page( $news_page_id ) {
    global $dpdb;
    global $User;
    global $code_url;

    $news_subject = get_news_subject($news_page_id);

    // -------------------------------------------

    // Get the set of 'current' news items 
    // defined for the given page.
    $rows = $dpdb->SqlRows("
        SELECT date_posted, content FROM news_items 
        WHERE status = 'current' 
            AND (news_page_id = '$news_page_id' 
                OR news_page_id IS NULL)
        ORDER BY ORDERING DESC");

    // Get a randomly selected news item from the set of 
    // 'recent' news items defined for the given page.
//    $random = $dpdb->SqlOneValue("
//        SELECT content FROM news_items
//        WHERE status = 'recent'
//            AND (news_page_id = '$news_page_id'
//                OR news_page_id IS NULL)
//        ORDER BY RAND() LIMIT 1");

    // -------------------------------------------

    if (count($rows) > 0) {
        // We have at least one news item to show,
        // so display the news header.

        $header_title = _("$news_subject News");

        // Show header-title as an image, if available.
        global $dyn_dir, $dyn_url;
        $header_image_file = "$dyn_dir/news_header_images/$news_page_id.jpg";
        $header_image_url  = "$dyn_url/news_header_images/$news_page_id.jpg";
        $date_changed = get_news_page_last_modified_date( $news_page_id );
        $formatted_date = strftime(_("%A, %B %e, %Y"), $date_changed);
        $echo_date = sprintf( _("last changed %s."), $formatted_date );

        if ( file_exists($header_image_file) ) {
            echo "
            <h1><img src='$header_image_url' alt=''></h1>\n";
        }
        else {
            echo "
            <div id='div_ph2' class='ph2'>$header_title
            <p class='right em50'>$echo_date</p>
            </div>  <!-- div_ph2 -->\n";
        }

        foreach($rows as $news_item) {
            echo 
            "<div class='article left'>
             ".$news_item['content']."
             <hr/>
             </div>  <!-- div_article -->\n";
        }
    }

    // -------------------------------------------
    // Output the 'random' news item, if any.

//    if ($random != "") {
//
//        echo "<h4>", _("Random News Item"), ":</h4>
//        <div id='div_article' class='article'>
//        $random
//        </div>  <!-- div_article -->\n";
//
//        $url = "$code_url/pastnews.php?news_page_id=$news_page_id";
//        $linktext = _("See All Random News Items");
//        echo "(<a href='$url'>$linktext</a>)";
//    }

    // -------------------------------------------
    // Give site admin and news editors a link to add/edit/show/hide/delete
    // and otherwise manage news items for this page.

    if ( $User->IsSiteManager() or $User->HasRole("Editor")) {
        $url = "$code_url/tools/site_admin/sitenews.php?news_page_id=$news_page_id";
        $linktext = sprintf( _("Update News for %s"), $news_subject );
        echo _("News Editor"), ": <a href='$url'>$linktext</a>";
        echo "\n";
    }
}

// vim: sw=4 ts=4 expandtab
