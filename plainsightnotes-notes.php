<?php 
/**
 * Run database queries to retrieve content.
 *
 * These functions run queries to retrieve content from the
 * database for display on the public site.
 *
 * @package WordPress
 * @subpackage PlainSight-Notes
 * @subpackage Notes
 * @since 0.9.0
 */

// First do some housekeeping.
include_once 'plainsightnotes-public.php';

define('PSN_NOTES_PAGE', '<psnotes />');

add_filter('the_content', 'notes_page', 10);

/**
 * Retrieve all notes in the "notes" table, ordered by ID, with
 * most recent entry on top.
 *
 * @since 0.9.0
 * @uses $wpdb
 */
function psn_get_notes()
{
    global $wpdb;
    $notes_table_name = $wpdb->prefix . "notes";
	
	$results = $wpdb->get_results("SELECT noteID FROM " . $notes_table_name . " ORDER BY noteID DESC", OBJECT);
	return $results;
}

/**
 * Retrieve single notes from the "notes" table by ID.
 *
 * @since 0.9.0
 * @uses $wpdb
 */
function psn_get_note_by_id( $id ) {
	global $wpdb;
	$id = (int) $id;
	
	$notes_table_name = $wpdb->prefix . "notes";
	$sql = "SELECT * from " . $notes_table_name . " WHERE noteID=" . $id;
	
	return $wpdb->get_row($sql, OBJECT);
}

/**
 * Retrieve all notes created by a given user.
 *
 * @since 0.9.0
 * @uses $wpdb
 */
function psn_get_notes_by_author_id( $author_id, $howmany, $exclude_author, $orderby ) {
	global $wpdb;
	$author_id = (int) $author_id;
	$howmany = (int) $howmany;
	$exclude_author = (int) $exclude_author;
	$orderby = 'noteID';
	// Set the limit
	if ( $howmany ) {
		$limit = "LIMIT $howmany";
	}
	$notes_table_name = $wpdb->prefix . "notes";
	$sql = "SELECT * from " . $notes_table_name . " WHERE notes_authorID=" . $author_id . " AND notes_authorID<>" . $exclude_author . " ORDER BY $orderby DESC $limit";
	
	return $wpdb->get_results($sql);
}

/**
 * Retrieve the most recent notes. Defaults to the 6 most recent and
 * sorts them in descending order (most recent on top).
 *
 * @since 0.9.0
 * @uses $wpdb
 */
function psn_get_recent_notes( $orderby = 'noteID', $order = 'ASC', $howmany = 6 ) {
    global $wpdb;
	$howmany = (int) $howmany;
	
	// Set the limit
	if ( $howmany ) {
		$limit = "LIMIT $howmany";
	}
	$notes_table_name = $wpdb->prefix . "notes";
	$sql = "SELECT * FROM " . $notes_table_name . " ORDER BY $orderby $order $limit";
	$results = $wpdb->get_results($sql);
	
	return $results;
}

/**
 * Retrieve all notes posted from given WordPress post/page ID.
 *
 * @since 0.9.0
 * @uses $wpdb
 */
function psn_get_notes_by_parent_post_id( $parentp_id, $howmany, $exclude_author ) {
	global $wpdb;	
	$parentp_id = (int) $parentp_id;
	$howmany = (int) $howmany;
	$exclude_author = (int) $exclude_author;

	if ( $howmany ) {
		$limit = "LIMIT $howmany";
	}

	$notes_table_name = $wpdb->prefix . "notes";
	$sql = "SELECT * FROM " . $notes_table_name . " WHERE notes_parentPostID=" . $parentp_id . " AND notes_authorID<>" . $exclude_author . " ORDER BY noteID DESC $limit";
	
	return $wpdb->get_results($sql);
}

/**
 * Retrieve notes specifically posted from given WordPress post/page 
 * ID and created by given author.
 *
 * @since 0.9.0
 * @uses $wpdb
 */
function psn_get_notes_by_meta( $parentp_id, $author_id, $num ) {
	global $wpdb;
	$parentp_id = (int) $parentp_id;
	$author_id = (int) $author_id;
	$num = (int) $num;
	
	if ( $num ) {
		$limit = "LIMIT $num";
	}
	$notes_table_name = $wpdb->prefix . "notes";
	$sql = "SELECT * FROM " . $notes_table_name . " WHERE notes_parentPostID=" . $parentp_id . " AND notes_authorID=" . $author_id . " ORDER BY noteID ASC $limit";
	
	return $wpdb->get_results($sql);
}

/**
 * Retrieve timeline selection posted from given WordPress post/page
 * ID by specific author.
 *
 * @since 0.9.0
 * @uses $wpdb
 */
function psn_get_timeline_by_meta( $parentp_id, $author_id, $howmany ) {
	global $wpdb;
	
	// Set the limit
	if ( $howmany ) {
		$limit = "LIMIT $howmany";
	}
	$timeline_table_name = $wpdb->prefix . "timelines";
	$sql = "SELECT * FROM " . $timeline_table_name . " WHERE timelines_parentPostID=" . $parentp_id . " AND timelines_authorID=" . $author_id . " ORDER BY timelineID ASC $limit";
	
	return $wpdb->get_results($sql);
}

/**
 * Displays all note entries on the Notes page.
 *
 * @since 0.9.0
 * @uses psn_the_note($id)
 */
function notes_page($data) {
	$start = strpos($data, PSN_NOTES_PAGE);
	if ( $start !== false ) {
		ob_start();
    	$entries = psn_get_notes();
		if ( count($entries) > 0 ) {
			echo '<div id="notes-page-content">';
			foreach ( $entries as $entry ) {
				psn_the_note( $entry->noteID );
			} 
			echo "</div>";
		} else {
			_e('<p>There are no notes at this time. Perhaps you could check back later?</p>');
		}
		$contents = ob_get_contents();
		ob_end_clean();
		$data = substr_replace($data, $contents, $start, strlen(PSN_NOTES_PAGE));
	}
	return $data;	
}