<?php
$relPath="./../../pinc/";
include_once($relPath.'dpinit.php');
include_once($relPath.'theme.inc');
include_once($relPath.'site_news.inc');

// require_login();
$User->IsLoggedIn()
    or die("Please log in.");

$User->IsSiteManager() || $User->IsSiteNewsEditor()
    or die("You are not authorized to use this form.");

$news_page_id   = Arg('news_page_id');
$item_id        = Arg("item_id");
$action         = Arg('action');
$content        = Arg('content');

if ($news_page_id) {
    if (isset($news_pages[$news_page_id])) {
        $news_subject = get_news_subject($news_page_id);
        $title = sprintf(_('News Desk for %s'), $news_subject );
        theme($title, "header");
        echo "<a href='sitenews.php'>"._("Site News Central")."</a>";
        echo "<h1 align='center'>$title</h1>";
        handle_any_requested_db_updates( $news_page_id, $item_id, $action, $content);
        show_item_editor( $news_page_id, $item_id, $action );
        show_all_news_items_for_page( $news_page_id );
    }
    else {
        echo _("Error").": <b>".$news_page_id."</b> "
            ._("Unknown news_page_id specified, exiting.");
    }
}
else {

    theme(_("Site News Central"), "header");

    echo "<h1>"._("Site News Central")."</h1>";
    echo "<ul>";
    echo "\n";
    foreach ( $news_pages as $news_page_id => $news_subject ) {
        echo "<li>";

        $news_subject = get_news_subject($news_page_id);
        $link = "<a href='sitenews.php?news_page_id=$news_page_id'>$news_subject</a>";
        echo sprintf( _("Edit Site News for %s"), $link );
        echo "\n";

        $date_changed = get_news_page_last_modified_date( $news_page_id );
        if ( !is_null($date_changed) ) {
            $last_modified = strftime(_("%A, %B %e, %Y"), $date_changed);
            echo _("Last modified:")." ".$last_modified;
        }
        echo "\n";
    }
    echo "</ul>";
}
theme("", "footer");

// Everything else is just function declarations.

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function handle_any_requested_db_updates( $news_page_id, $item_id, $action, $content ) {
    global $dpdb;
	echo "<!-- $news_page_id  $item_id  $action  $content --> \n";
    switch($action) {

	    case 'add':
		    // Save a new site news item
		    // $content = strip_tags($content, $allowed_tags);
		    // $date_posted = time();
		    if ( ! $dpdb->SqlExists( "
				SELECT 1 FROM news_items WHERE news_page_id = '$news_page_id'" )
		    ) {
			    $sql = "
					INSERT INTO news_items
						(ordering, news_page_id, status, date_posted, content)
					SELECT 1,
                       ?,
                       'current',
                       CURRENT_TIMESTAMP(),
                       ?
			   ";
		    } else {
		    $sql = "
                INSERT INTO news_items
                    (ordering, news_page_id, status, date_posted, content)
                SELECT MAX(ni.ordering) + 1,
                       ni.news_page_id,
                       'current',
                       CURRENT_TIMESTAMP(),
                       ?
                FROM news_items AS ni
                WHERE ni.news_page_id = ?";
			}
            $args = array(&$news_page_id, &$content );
            $dpdb->SqlExecutePS($sql, $args);

            // by default, new items go at the top
            // $dpdb->SqlExecute("
                // UPDATE news_items SET ordering = id 
                // WHERE id = LAST_INSERT_ID()");
            // news_change_made($news_page_id);
            break;

        case 'delete':
            // Delete a specific site news item
            $dpdb->SqlExecute("
                DELETE FROM news_items 
                WHERE id=$item_id");
            break;

        case 'display':
            // Display a specific site news item
            $dpdb->SqlExecute("
                UPDATE news_items SET status = 'current' 
                WHERE id=$item_id");
            news_change_made($news_page_id);
            break;

        case 'hide':
            // Hide a specific site news item
            $dpdb->SqlExecute("
                UPDATE news_items SET status = 'recent' 
                WHERE id=$item_id");
            news_change_made($news_page_id);
            break;

        case 'archive':
            // Archive a specific site news item
            $dpdb->SqlExecute("
                UPDATE news_items SET status = 'archived' 
                WHERE id=$item_id");
            break;

        case 'unarchive':
            // Unarchive a specific site news item
            $dpdb->SqlExecute("
                UPDATE news_items SET status = 'recent' 
                WHERE id=$item_id");
            break;

        case 'moveup':
            // Move a specific site news item higher in the display list
            move_news_item ($news_page_id, $item_id, 'up');
            news_change_made($news_page_id);
            break;

        case 'movedown':
            // Move a specific site news item lower in the display list
            move_news_item ($news_page_id, $item_id, 'down');
            news_change_made($news_page_id);
            break;

        case 'edit_update':
            // Save an update to a specific site news item
            // $content = strip_tags($_POST['content'], $allowed_tags);
            $sql = "
                UPDATE news_items SET content = ?
                WHERE id = $item_id";
            $args = array(&$content);
            $dpdb->SqlExecutePS($sql, $args);
            $status = $dpdb->SqlOneValue("
                SELECT status FROM news_items 
                WHERE id=$item_id");
            $visible_change_made = ($status == 'current');
            if ($visible_change_made) {
                news_change_made($news_page_id);
            }
            break;
    }
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function show_item_editor( $news_page_id, $item_id, $action )
// Show a form:
// -- to edit the text of an existing item (if requested), or
// -- to compose a new item (otherwise).
{
    global $dpdb;
    if ($action == "edit") {
        $initial_content = $dpdb->SqlOneValue("
            SELECT content FROM news_items 
            WHERE id=$item_id");
        $action_to_request = "edit_update";
        $submit_button_label = _("Save News Item");
    }
    else {
        $item_id = "";
        $initial_content = "";
        $action_to_request = "add";
        $submit_button_label = _("Add News Item");
    }

    echo "
    <form class='center' action='sitenews.php?news_page_id=$news_page_id&amp;action=$action_to_request' method='post'>
    <textarea name='content' cols='80' rows='8'>" . htmlspecialchars($initial_content, ENT_QUOTES) . "</textarea>
    <br />
    <input type='submit' class='center' value='$submit_button_label' name='submit'>
    <input type='hidden' name='item_id' value='$item_id'>
    </form>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function show_all_news_items_for_page( $news_page_id ) {
    global $dpdb;
    // three categories:
    // 1) current  (currently displayed on page every time)
    // 2) recent   (displayed on "Recent News", and one shown as Random)
    // 3) archived (not visible to users at all, saved for later use or historical interest)

    $categories = array(
        array(
            'status'   => 'current',
            'title'    => _('Sticky News Items'),
            'blurb'    => _("All of these items are shown every time the page is loaded. Most important and recent news items go here, where they are guaranteed to be displayed."),
            'order_by' => 'ordering DESC',
            'actions'  => array(
                'hide'     => _('Make Random'),
                'archive'  => _('Archive Item'),
                'moveup'   => _('Move Up'),
                'movedown' => _('Move Down'),
            ),
        ),

        array(
            'status'   => 'recent',
            'title'    => _('Random/Recent News Items'),
            'blurb'    => _("This is the pool of available random news items for this page. Every time the page is loaded, a randomly selected one of these items is displayed."),
            'order_by' => 'ordering DESC',
            'actions'  => array(
                'display' => _('Make Sticky'),
                'archive' => _('Archive Item'),
            ),
        ),

        array(
            'status'   => 'archived',
            'title'    => _('Archived News Items'),
            'blurb'    => _("Items here are not visible anywhere, and can be safely stored here until they become current again."),
            'order_by' => 'id DESC',
            'actions'  => array(
                'unarchive' => _('Unarchive Item'),
            ),
        ),
    );

    foreach ( $categories as $category ) {
        $status = $category['status'];

        $rows = $dpdb->SqlRows("
            SELECT * FROM news_items
            WHERE news_page_id = '$news_page_id' 
                AND status = '$status'
            ORDER BY {$category['order_by']} ");

        if (count($rows) == 0)
            continue;

        echo "<hr align-'center' width='75%' size='5'>\n";
        echo "{$category['title']}";
        if ($status == 'current') {
            $date_changed = get_news_page_last_modified_date( $news_page_id );
            $last_modified = strftime(_("%A, %B %e, %Y"), $date_changed);
            echo "&nbsp;&nbsp; ("._("Last modified:")." ".$last_modified.")";
        }
        echo $category['blurb'];
        echo "\n";

        $actions = $category['actions'] +
            array(
                'edit'     => _('Edit'),
                'delete'   => _('Delete'),
            );

        foreach($rows as $news_item) {
        echo "<hr align-'center' width='75%' size='5'>\n";
            $date_posted = strftime(_("%A, %B %e, %Y"),$news_item['date_posted']);
            foreach ( $actions as $action => $label ) {
                $url = "sitenews.php"
                    ."?news_page_id=$news_page_id"
                    ."&amp;item_id={$news_item['id']}"
                    ."&amp;action=$action";
                echo "[<a href='$url'>$label</a>]\n";
            }
            echo " &mdash; ($date_posted)";
            echo "\n";
            echo $news_item['content'];
            echo "\n";
        }
    }
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function news_change_made ($news_page_id) {
    global $dpdb;
    $date_changed = time();
    $dpdb->SqlExecute("
            REPLACE INTO news_pages
            SET news_page_id = '$news_page_id', 
                t_last_change = $date_changed");
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function move_news_item ($news_page_id, $id_of_item_to_move, $direction) {
    global $dpdb;

    $rows = $dpdb->SqlRows("
        SELECT * FROM news_items
        WHERE news_page_id = '$news_page_id' 
            AND status = 'current'
        ORDER BY ordering");

    $i = 1 ;
    foreach($rows as $news_item) {
        $curr_id = $news_item['id'];
        $dpdb->SqlExecute("
            UPDATE news_items 
            SET ordering = $i 
            WHERE id = $curr_id");
        if (intval($curr_id) == intval($id_of_item_to_move)) {
            $old_pos = $i;
        }
        $i++;
    }

    if (isset($old_pos)) {
        if ($direction == 'up') {
            $dpdb->SqlExecute("
                UPDATE news_items SET ordering = $old_pos
                WHERE news_page_id = '$news_page_id'
                    AND status = 'current' 
                    AND ordering = ($old_pos + 1)");
            $dpdb->SqlExecute("
                UPDATE news_items 
                SET ordering = $old_pos + 1 
                WHERE id = $id_of_item_to_move");
        } else {
            $dpdb->SqlExecute("
                UPDATE news_items SET ordering = $old_pos
                WHERE news_page_id = '$news_page_id' 
                    AND status = 'current'
                    AND ordering = ($old_pos - 1)");
            $dpdb->SqlExecute("
                UPDATE news_items SET ordering = $old_pos - 1 
                WHERE id = $id_of_item_to_move");
        }
    }
}

// vim: sw=4 ts=4 expandtab
