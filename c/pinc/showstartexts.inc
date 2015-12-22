<?PHP

function showstartexts($limit, $metal) {
    global $code_url;

    $nmetal = metal_count($metal);
    $lmetal = lower($metal);

    if($metal == "Bronze") {
        $desc = _("$nmetal Now Proofreading.");
        $text = _("These books are currently being proofed and formatted by our volunteers.
        Sign up, log in, and start helping!!!!"); }
    else if($metal == "Silver") {
        $desc = _("$nmetal In Progress.");
        $text = _("These books have been proofed and for through our site but have
        not yet posted - currently going through final proofreading and
        assembly.");
    }
    else {
        $desc = _("$nmetal Complete.");
        $text = _("These books have been processed through our site and posted
        to the Faded Page archive.  They may also have been posted to Project
        Gutenberg Canada and/or Project Gutenberg US.");
    }

    echo "
    <img class='star' src='$code_url/graphics/{$lmetal}_star.jpg'
    alt='$metal Star'>
    <h4 class='lfloat'>{$desc}</h4>
    <p class='clear lfloat'>$text</p>
    <div class='clear'>\n";

    list_projects( $metal, $limit );

    echo "</div>\n";


    if ($nmetal > $limit) {
        echo _("<p class='center'>
        -- <a href='list_etexts.php?metal=$metal'>See more...</a></p>\n");
    }

}

// List the specified projects,
// giving brief information about each.
function list_projects( $metal, $limit = "20") {
    global $dpdb;

    $where = metal_where($metal);
    $rows = $dpdb->SqlRows("
        SELECT *,
            nameofwork title,
            authorsname author,
            DATE_FORMAT(DATE(FROM_UNIXTIME(phase_change_date)), '%b %D, %Y') moddate
        FROM projects
        $where
        ORDER BY phase_change_date DESC
        LIMIT $limit");

    $counter = 0;
    foreach($rows as $project) {
        $counter++;
        $title = $project['nameofwork'];
        $author = $project['authorsname'];
        $language = $project['language'];
        $n_pages = $project["n_pages"];
        $moddate = $project["moddate"];
        $postednum = $project['postednum'];

        echo "<p>$counter) \"$title\" $author ($language)<br>
            " . _("$n_pages pages; ") . "$moddate\n";
        // Download info
        if ($postednum ) {
            echo link_to_fadedpage_catalog($postednum);
        }
        echo "</p>\n";
    }
}

function metal_where($metal) {
    $bronze_where = 'WHERE phase IN ("P1", "P2", "P3", "F1", "F2")';
    $silver_where = 'WHERE phase IN ("PP", "PPV")';
    $gold_where   = 'WHERE phase = "POSTED"';
    switch($metal) {
        case "Gold":
            return $gold_where;

        case "Silver":
            return $silver_where;

        case "Bronze":
            return $bronze_where;

        default:
            return null;
    }
}

function metal_count($metal) {
    global $dpdb;
    $where = metal_where($metal);
    return $dpdb->SqlOneValue("
        SELECT COUNT(1) FROM projects
        $where");
}
