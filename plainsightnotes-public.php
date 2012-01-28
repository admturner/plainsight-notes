<?php 
/**
 * These functions govern the live display of plainsight-notes data
 *
 * These functions process the queries to display formatted content
 * on the public site. Most of them use queries run in 
 * /plainsightnotes-notes. This page also includes functions to prepare
 * and call shortcodes.
 *
 * @package WordPress
 * @subpackage PlainSight-Notes
 *
 * @since 0.9.5
 */

// Page Delimiters 
define('PSN_NOTES_PAGE', '<psnotes />');


/**
 * Create the Note excerpt
 *
 * @since 1.8
 */
function psn_get_the_note_excerpt( $data = null, $length = 25 ) {
	$text = $data->notes_content;
	$words = explode(' ', $text, $length+1);
	if (count($words)> $length) {
		array_pop($words);
		$text = implode(' ', $words);
		$text = $text . ' [...] <a href="' . get_bloginfo( 'url' ) . '/notes#note-' . $data->noteID . '" title="Keep reading...">More &rarr;</a>';
	} else {
		$text = implode(' ', $words);
	}
	
	return wptexturize($text);
}

/**
 * The standard HTML output for all Notes
 * 
 * @todo Add an "excerpt == true" case
 *
 * @since 1.8
 */
function psn_the_note_output( $data = null, $wrap_class = 'note', $title_wrap = 'h1', $excerpt = false, $avatar = true ) {
	$user_info = get_userdata($data->notes_authorID);
	?>
	<article id="note-<?php echo $data->noteID; ?>" class="<?php echo esc_attr( $wrap_class ); ?>">
		<header class="note-meta">
			<div class="note-author vcard">
				<?php 
				if ( $avatar == true ) 
					echo get_avatar($data->notes_authorID, 50);
				echo '<' . $title_wrap . ' class="note-title">' . wptexturize($data->notes_title) . '</' . $title_wrap . '>';
				if ( $excerpt == false ) 
					echo '<h2 class="note-sub"><span class="fn">' . wptexturize($user_info->display_name) . '</span> commenting on <a href="' . esc_url( get_permalink( $data->notes_parentPostID ) ) . '" title="' . esc_attr( get_the_title( $data->notes_parentPostID ) ) . '">' . _(wptexturize(get_the_title( $data->notes_parentPostID )) ) . '</a> <span class="says">said:</span></h2>';
				?>
			</div><!-- .note-author .vcard -->
		</header>
		<div class="note-content">
			<?php if ( $excerpt == false ) {
				echo wpautop(wptexturize($data->notes_content));
			} else {
				echo '<p>' . psn_get_the_note_excerpt( $data, 25 ) . '</p>';
			} ?>
		</div><!-- .note-content -->
		<footer class="note-meta">
			<h5>Posted on <span class="pub-date"><?php echo $data->notes_date; ?></span></h5>
		</footer>
	</article><!-- #note-<?php echo $data->noteID; ?> -->
	<?php 
}

/**
 * Display single Note based on given ID.
 *
 * @uses psn_get_note_by_id();
 * @uses psn_the_note_output( $data, $wrap_class, $title_wrap, $excerpt );
 *
 * @param int $noteID Note ID from notes table
 * 
 * @since 1.8
 */
function psn_the_note( $args ) { 
	$defaults = array(
		'noteID' => (int) $noteID,
		'wrap_class' => 'note',
		'title_wrap' => 'h1',
		'length' => 'full',
		'date_first' => true,
		'avatar' => true
	);
	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );
	
	$note = psn_get_note_by_id( $noteID );
	$status = $note->note_status;
	if ( $status == 'published' || $status == 'archived' ) {
		psn_the_note_output( $note, $wrap_class, $title_wrap, $excerpt, $avatar );
	}
}

/**
 * Display the most recent notes. Defaults to 4.
 *
 * @uses psn_get_recent_notes();
 * @uses psn_the_note_output( $data, $wrap_class, $title_wrap, $excerpt ); @since 1.8
 * 
 * @since 0.9.0
 */
function psn_recent_notes( $args ) {
	$defaults = array(
		'howmany' => 4,
		'orderby' => 'noteID',
		'order' => 'ASC',
		'wrap_class' => 'note',
		'title_wrap' => 'h1',
		'excerpt' => false,
		'include_pages' => array(),
		'avatar' => true
	);
	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );
	
	$notes = psn_get_recent_notes( $orderby, $order, $howmany, $include_pages );
	
	if ( !empty($notes) ) {
		foreach ( $notes as $note ) {
			$status = $note->note_status;
			if ( $status == 'published' || $status == 'archived' ) { 
				psn_the_note_output( $note, $wrap_class, $title_wrap, $excerpt, $avatar );
			}
		} // endforeach
	} else {
		_e('<p>No recently posted notes.</p>');
	}
}

/**
 * Display all notes by a given author. 
 *
 * Notes are displayed in reverse chronological order 
 * (with most recent at the top).
 *
 * @uses psn_get_notes_by_author_id();
 * @uses psn_the_note_output( $data, $wrap_class, $title_wrap, $excerpt ); @since 1.8
 *
 * @since 0.9.0
 */
function psn_notes_by_author_id( $args ) {
	$defaults = array(
		'author_id' => (int) $author_id,
		'exclude_author' => (int) $exclude_author,
		'howmany' => $howmany,
		'wrap_class' => 'note',
		'title_wrap' => 'h1',
		'excerpt' => false,
		'orderby' => $orderby,
		'avatar' => true
	);
	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );
	
	$notes = psn_get_notes_by_author_id( $author_id, $howmany, $exclude_author, $orderby );	
	
	if ( !empty($notes) ) {
		foreach ( $notes as $note ) {
			$status = $note->note_status;
			if ( $status == 'published' || $status == 'archived' ) {
				psn_the_note_output( $note, $wrap_class, $title_wrap, $excerpt, $avatar );
			}
		} // endforeach
	} else {
		$user_info = get_userdata( $author_id );
		_e('<p>' . $user_info->display_name . 'hasn\'t posted any notes yet.</p>');
	}
}

/**
 * Display all notes posted from the given WordPress post/page.
 *
 * @uses psn_get_notes_by_parent_post_id()
 * @uses psn_the_note_output( $data, $wrap_class, $title_wrap, $excerpt ); @since 1.8
 * 
 * @since 0.9.0
 */
function psn_notes_by_parent_post_id( $args ) {
	$defaults = array(
		'parentp_id' => $parentp_id,	
		'howmany' => $howmany,
		'exclude_author' => (int) $exclude_author,
		'wrap_class' => 'note',
		'title_wrap' => 'h1',
		'excerpt' => false,
		'avatar' => true
	);
	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );
	
	$notes = psn_get_notes_by_parent_post_id( $parentp_id, $howmany, $exclude_author );
	
	if ( !empty($notes) ) {
		foreach ( $notes as $note ) {
			$status = $note->note_status;
			if ( $status == 'published' || $status == 'archived' ) {
				psn_the_note_output( $note, $wrap_class, $title_wrap, $excerpt, $avatar );
			}
		} // endforeach
	} else {
		_e('<p>No notes have been posted from <a href="' . esc_url(get_permalink($parentp_id)) . '" title="' . esc_attr(get_the_title($parentp_id)) . '">' . wptexturize(get_the_title($parentp_id)) . '</a> yet.</p>');
	}
}

/**
 * Display all notes posted from a given WordPress post/page and
 * only by a specified author.
 * 
 * @uses psn_get_notes_by_meta()
 * @uses psn_the_note_output( $data, $wrap_class, $title_wrap, $excerpt );
 * 
 * @since 1.8
 */
function psn_notes_by_meta( $args ) {
	global $current_user;
	get_currentuserinfo();
	
	$defaults = array(
		'parentp_id' => (int) $parentp_id,
		'author_id' => '',
		'howmany' => (int) $howmany,
		'wrap_class' => 'note',
		'title_wrap' => 'h1',
		'excerpt' => false,
		'avatar' => true
	);
	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );
	
	if ( empty($author_id) ) {
		$author_id = get_current_user_id();
	}
	
	$notes = psn_get_notes_by_meta( $parentp_id, $author_id, $howmany, $orderby );
	
	if ( !empty($notes) ) {
		foreach ( $notes as $note ) {
			$status = $note->note_status;
			if ( $status == 'published' || $status == 'archived' ) {
				psn_the_note_output( $note, $wrap_class, $title_wrap, $excerpt, $avatar );
			}
		} // endforeach
	} else {
		$user_info = get_userdata( $author_id );
		_e('<p>No notes have been posted from <a href="' . get_permalink($parentp_id) . '" title="' . get_the_title($parentp_id) . '">' . get_the_title($parentp_id) . '</a> by '. $user_info->display_name .'.</p>');
	}
}
?>