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
 * @subpackage Public
 * @since 0.9.5
 */

// Page Delimiters 
define('PSN_NOTES_PAGE', '<psnotes />');

/**
 * Display full note entry for given note ID.
 *
 * @since 0.9.5
 * @uses psn_get_note_by_id()
 *
 * @param int $noteID Note ID from notes table
 *
 * @todo Add else{} if ($length=='small')
 * @todo Filter output of content to auto pee
 */
function psn_the_note( $args ) { 
	global $wpdb;
	
	$defaults = array(
		'noteID' => (int) $noteID,
		'wrap_class' => 'note',
		'title_wrap' => 'h4',
		'length' => 'full',
		'date_first' => true,
		'show_avatar' => true,
	);
	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );
	
	$table_name = $wpdb->prefix . "notes";
	$note = psn_get_note_by_id( $noteID );
	$user_info = get_userdata($note->notes_authorID);
?>
		<div class="<?php echo $wrap_class; ?>">
			<?php if($date_first==true): ?>
				<div class="note-meta">
					<?php if ( $show_avatar == true ) { echo get_avatar($note->notes_authorID, 50); } ?>
					<p><cite class="note-author"><?php echo $user_info->display_name; ?></cite> commenting on <a href="<?php echo get_permalink($note->notes_parentPostID); ?>" title="<?php _e(get_the_title($note->notes_parentPostID)); ?>"><?php _e(get_the_title($note->notes_parentPostID)); ?></a>  <abbr class="value"><?php echo $note->notes_date; ?></abbr></p>
					<?php echo '<' . $title_wrap . ' class="note-title">' . $note->notes_title . '</' . $title_wrap . '>'; ?>
				</div>
				<div class="note-content">
					<p><?php echo nl2br($note->notes_content); ?></p>
				</div>
			<?php else : ?>
				<div class="note-content">
					<p><?php echo nl2br($note->notes_content); ?></p>
				</div>
				<div class="note-meta">
					<?php if ( $show_avatar == true ) { echo get_avatar($note->notes_authorID, 50); } ?>
					<p><cite class="note-author"><?php echo $user_info->display_name; ?></cite> commenting on <a href="<?php echo get_permalink($note->notes_parentPostID); ?>" title="<?php _e(get_the_title($note->notes_parentPostID)); ?>"><?php _e(get_the_title($note->notes_parentPostID)); ?></a>  <abbr class="value"><?php echo $note->notes_date; ?></abbr></p>
					<?php echo '<' . $title_wrap . ' class="note-title">' . $note->notes_title . '</' . $title_wrap . '>'; ?>
				</div>
			<?php endif; ?>
		</div><!-- .<?php echo $wrap_class; ?> -->
<?php 
}

/**
 * Display all notes by a given author. Notes are displayed in
 * reverse chronological order (with most recent at the top).
 *
 * @since 0.9.0
 * @uses psn_get_notes_by_author_id()
 *
 * @todo Add else{} if ($length == 'small').
 * @todo Filter output of content to auto pee.

 */
function psn_notes_by_author_id( $args ) {
	global $wpdb;
	$defaults = array(
		'author_id' => (int) $author_id,
		'exclude_author' => (int) $exclude_author,
		'howmany' => $howmany,
		'wrap_class' => 'note',
		'title_wrap' => 'h4',
		'length' => 'full',
		'date_first' => true,
		'show_avatar' => true,
	);
	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );
	
	$table_name = $wpdb->prefix . "notes";
	$entries = psn_get_notes_by_author_id( $author_id, $howmany, $exclude_author );	
	
	if ( !empty($entries) ) {
		foreach ( $entries as $entry ) {
			//print_r($entry);
			$user_info = get_userdata($entry->notes_authorID);
		?>
			<?php if ( !is_admin() ) : ?>
				<div class="<?php echo $wrap_class; ?>">
					<div class="note-meta">
						<?php if ( $show_avatar == true ) { echo get_avatar($entry->notes_authorID, 50); } ?>
						<?php echo '<' . $title_wrap . ' class="note-title">' . $entry->notes_title . '</' . $title_wrap . '>'; ?>
						<h6 class="meta"><cite class="note-author"><?php echo $user_info->display_name; ?></cite> commenting on <a href="<?php echo get_permalink($entry->notes_parentPostID); ?>" title="<?php _e(get_the_title($entry->notes_parentPostID)); ?>"><?php _e(get_the_title($entry->notes_parentPostID)); ?></a>  <abbr class="value"><?php echo $entry->notes_date; ?></abbr></h6>
					</div>
					<div class="note-content">
						<p><?php echo nl2br($entry->notes_content); ?></p>
					</div>
				</div><!-- .<?php echo $wrap_class; ?> -->
			<?php else : ?>
				<div class="<?php echo $wrap_class; ?>">
				<div class="note-wrap">
					<h4 class="note-meta">
						<?php if ( $show_avatar == true ) { echo get_avatar($note->notes_authorID, 50); } ?>
						From <cite class="comment-author"><?php echo $user_info->display_name; ?></cite> on  <a href="<?php echo get_permalink($entry->notes_parentPostID); ?>" title="<?php _e(get_the_title($entry->notes_parentPostID)); ?>"><?php _e(get_the_title($entry->notes_parentPostID)); ?></a>
					</h4>
					<blockquote><p><strong><?php _e($entry->notes_title); ?></strong></p></blockquote> 
					<p><small><?php _e($entry->notes_date); ?></small></p>
				</div>
			</div><!-- .<?php echo $wrap_class; ?> -->
			<?php endif; ?>
		<?php 
		}
	} else {
		_e("<p>This person hasn't created any notes yet.</p>");
	}
}
		
/**
 * Display the most recent notes. Defaults to 4.
 *
 * @since 0.9.0
 * @uses psn_get_recent_notes()
 */
function psn_recent_notes( $args ) {
	global $wpdb;
	$defaults = array(
		'howmany' => 4,
		'orderby' => 'noteID',
		'order' => 'ASC',
		'wrap_class' => 'note',
		'title_wrap' => 'h4',
		'length' => 'full',	
	);
	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );

	$entries = psn_get_recent_notes( $orderby, $order, $howmany );

	if ( !empty($entries) ) {
		foreach ( $entries as $entry ) {
			//print_r($entry);
			$user_info = get_userdata($entry->notes_authorID);
		?>
			<?php if ( !is_admin() ) : ?>
				<div class="<?php echo $wrap_class; ?>">
					<div class="note-meta">
						<?php echo get_avatar($entry->notes_authorID, 50); ?>
						<p><cite class="note-author"><?php echo $user_info->display_name; ?></cite> commenting on <a href="<?php echo get_permalink($entry->notes_parentPostID); ?>" title="<?php _e(get_the_title($entry->notes_parentPostID)); ?>"><?php _e(get_the_title($entry->notes_parentPostID)); ?></a>  <abbr class="value"><?php echo $entry->notes_date; ?></abbr></p>
						<?php echo '<' . $title_wrap . ' class="note-title">' . $entry->notes_title . '</' . $title_wrap . '>'; ?>
					</div>
					<div class="note-content">
						<p><?php echo nl2br($entry->notes_content); ?></p>
					</div>
				</div><!-- .<?php echo $wrap_class; ?> -->
			<?php else : ?>
				<div class="<?php echo $wrap_class; ?>">
				<div class="note-wrap">
					<h4 class="note-meta">
						<?php echo get_avatar($entry->notes_authorID, 50); ?>
						From <cite class="comment-author"><?php echo $user_info->display_name; ?></cite> on  <a href="<?php echo get_permalink($entry->notes_parentPostID); ?>" title="<?php _e(get_the_title($entry->notes_parentPostID)); ?>"><?php _e(get_the_title($entry->notes_parentPostID)); ?></a>
					</h4>
					<blockquote><p><strong><?php _e($entry->notes_title); ?></strong></p></blockquote> 
					<p><small><?php _e($entry->notes_date); ?></small></p>
				</div>
			</div><!-- .<?php echo $wrap_class; ?> -->
			<?php endif; ?>
		<?php 
		}
	} else {
		_e('<p>There are no recently posted notes. Perhaps check back later?</p>');
	}
}

/**
 * Display all notes posted from the given WordPress post/page.
 *
 * @since 0.9.0
 * @uses psn_get_notes_by_parent_post_id()
 */
function psn_notes_by_parent_post_id( $args ) {
	global $wpdb;
	$defaults = array(
		'parentp_id' => $parentp_id,	
		'howmany' => $howmany,
		'exclude_author' => (int) $exclude_author,
		'wrap_class' => 'note',
		'title_wrap' => 'h4',
		'excerpt' => false,
	);
	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );
	
	$table_name = $wpdb->prefix . "notes";
	$entries = psn_get_notes_by_parent_post_id( $parentp_id, $howmany, $exclude_author );
	
	if ( !empty($entries) ) {
		foreach ( $entries as $entry ) {
			//print_r($entry);
			$user_info = get_userdata($entry->notes_authorID);
		?>
			<div class="<?php echo $wrap_class; ?>">
				<div class="note-meta">
					<?php echo get_avatar($entry->notes_authorID, 50); ?>
					<?php echo '<' . $title_wrap . ' class="note-title">' . $entry->notes_title . '</' . $title_wrap . '>'; ?>
					<h6 class="meta"><cite class="note-author"><?php echo $user_info->display_name; ?></cite> commenting on <a href="<?php echo get_permalink($entry->notes_parentPostID); ?>" title="<?php _e(get_the_title($entry->notes_parentPostID)); ?>"><?php _e(get_the_title($entry->notes_parentPostID)); ?></a>  <abbr class="value"><?php echo $entry->notes_date; ?></abbr></h6>
				</div>
				<?php 
				if ( $excerpt == false ) { ?>
					<div class="note-content">
						<p><?php echo nl2br($entry->notes_content); ?></p>
					</div>
				<?php } else { ?>
					<div class="note-content">
						<p>
							<?php
							$text = $entry->notes_content;
							$words = explode(' ', $text, 26);
							if (count($words)> 25) {
								array_pop($words);
								$text = implode(' ', $words);
								$text = $text . '[...]';
							} else {
								$text = implode(' ', $words);
							}
							echo $text;
						?>
						</p>
					</div>
				<?php } ?>
			</div><!-- .<?php echo $wrap_class; ?> -->
		<?php 
		}
	} else {
		_e('<p>No notes have been posted from <a href="' . get_permalink($parentp_id) . '" title="' . get_the_title($parentp_id) . '">' . get_the_title($parentp_id) . '</a> yet.</p>');
	}
}

/**
 * Display all notes posted from a given WordPress post/page and
 * only by a specified author.
 *
 * @since 0.9.0
 * @uses psn_get_notes_by_meta()
 *
 * @param ...
 */
function psn_notes_by_meta( $args ) {
	global $wpdb;
	$defaults = array(
		'parentp_id' => (int) $parentp_id,
		'author_id' => (int) $author_id,
		'howmany' => (int) $howmany,
		'wrap_class' => 'note',
		'title_wrap' => 'h4',
		'length' => 'full',
	);
	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );
	
	$table_name = $wpdb->prefix . "notes";
	$entries = psn_get_notes_by_meta( $parentp_id, $author_id, $howmany );
	
	if ( !empty($entries) ) {
		foreach ( $entries as $entry ) {
			//print_r($entry);
			$user_info = get_userdata($entry->notes_authorID);
		?>
			<div class="<?php echo $wrap_class; ?>">
				<div class="note-meta">
					<?php echo get_avatar($entry->notes_authorID, 50); ?>
					<p><cite class="note-author"><?php echo $user_info->display_name; ?></cite> commenting on <a href="<?php echo get_permalink($entry->notes_parentPostID); ?>" title="<?php _e(get_the_title($entry->notes_parentPostID)); ?>"><?php _e(get_the_title($entry->notes_parentPostID)); ?></a>  <abbr class="value"><?php echo $entry->notes_date; ?></abbr></p>
					<?php echo '<' . $title_wrap . ' class="note-title">' . $entry->notes_title . '</' . $title_wrap . '>'; ?>
				</div>
				<div class="note-content">
					<p><?php echo nl2br($entry->notes_content); ?></p>
				</div>
			</div><!-- .<?php echo $wrap_class; ?> -->
		<?php 
		}
	} else {
		$user_info = get_userdata( $author_id );
		_e('<p>No notes have been posted from <a href="' . get_permalink($parentp_id) . '" title="' . get_the_title($parentp_id) . '">' . get_the_title($parentp_id) . '</a> by '. $user_info->first_name .'.</p>');
	}
}

/**
 * CHANGE THIS TO LIST NOTES (linked title only)
 *
 * @since 0.9.0
 * @uses
 *
 * @todo CHANGE THIS TO LIST NOTES (linked title only).
 */
function psn_list_notes( $noteID, $title = "h6" ) { 
   $result = psn_get_note_by_id($noteID);
	?>
	<li class="note-title-link">
		<?php echo nl2br($result->notes_title); ?>
		<span class="fn"><?php echo $author; ?></span>
	</li> 
<?php
}






/**
 * Display timeline year selected on given WordPress page/post ID by
 * given author. Can display either full timeline with selected year
 * highlighted or only the selected year.
 *
 * @since 0.9.0
 * @uses psn_get_timeline_by_meta()
 */
function psn_timeline_by_meta( $args ) {
	global $wpdb;
	
	$defaults = array(
		'parentp_id' => (int) $parentp_id,
		'author_id'  => (int) $author_id,
		'howmany' 	 => (int) $howmany,
		'display'	 => 'full_timeline', // Alternate is year_only
		'answer'	 => (int) $answer,
		'yr1'		 => '1650-1700',
		'yr2'		 => '1700-1750',
		'yr3'		 => '1750-1800',
		'yr4'		 => '1800-1850',
		'yr5'		 => '1850-1900',
		'yr6'		 => '1900-1950',
		'yr7'		 => '1950-today',
	);

	$args = wp_parse_args( $args, $defaults );

	extract( $args, EXTR_SKIP );
	
	$table_name = $wpdb->prefix . "timelines";
	$years = psn_get_timeline_by_meta( $parentp_id, $author_id, $howmany );
	
	if ( !empty($years) ) {
		foreach ( $years as $year ) {
			//print_r($entry);
			$chosen = sanitize_title($year->timelines_yearSelected);
		?>
			<div class="timeline-year">
				
				<?php if ( $display == 'full_timeline' ) : ?>
					<p><span class="chosen">Your guess.</span> <span class="answer">The answer.</span></p>
					<ul class="timeline">
						<li<?php if ( $chosen == $yr1 ) _e(' id="chosen-year"'); if ( $answer == 1 ) _e(' class="correct-year"'); ?>><?php _e($yr1); ?><?php if ( $chosen == $yr1 && $answer == 1 ) echo ' <span class="yr7">Correct!</span>'; ?></li>
						<li<?php if ( $chosen == $yr2 ) _e(' id="chosen-year"'); if ( $answer == 2 ) _e(' class="correct-year"'); ?>><?php _e($yr2); ?><?php if ( $chosen == $yr2 && $answer == 2 ) echo ' <span class="yr7">Correct!</span>'; ?></li>
						<li<?php if ( $chosen == $yr3 ) _e(' id="chosen-year"'); if ( $answer == 3 ) _e(' class="correct-year"'); ?>><?php _e($yr3); ?><?php if ( $chosen == $yr3 && $answer == 3 ) echo ' <span class="yr7">Correct!</span>'; ?></li>
						<li<?php if ( $chosen == $yr4 ) _e(' id="chosen-year"'); if ( $answer == 4 ) _e(' class="correct-year"'); ?>><?php _e($yr4); ?><?php if ( $chosen == $yr4 && $answer == 4 ) echo ' <span class="yr7">Correct!</span>'; ?></li>
						<li<?php if ( $chosen == $yr5 ) _e(' id="chosen-year"'); if ( $answer == 5 ) _e(' class="correct-year"'); ?>><?php _e($yr5); ?><?php if ( $chosen == $yr5 && $answer == 5 ) echo ' <span class="yr7">Correct!</span>'; ?></li>
						<li<?php if ( $chosen == $yr6 ) _e(' id="chosen-year"'); if ( $answer == 6 ) _e(' class="correct-year"'); ?>><?php _e($yr6); ?><?php if ( $chosen == $yr6 && $answer == 6 ) echo ' <span class="yr7">Correct!</span>'; ?></li>
						<li<?php if ( $chosen == $yr7 ) _e(' id="chosen-year"'); if ( $answer == 7 ) _e(' class="correct-year"'); ?>><?php _e($yr7); ?><?php if ( $chosen == $yr7 && $answer == 7 ) echo ' <span class="yr7">Correct!</span>'; ?></li>
					</ul>

				<?php elseif ( $display == 'year_only' ) : ?>
					<div class="year-selected">
						<p><?php _e($chosen); ?></p>
					</div>
				<?php endif; ?>
				
			</div><!-- .timeline-year -->
		<?php 
		}
	} else {
		$user_info = get_userdata( $author_id );
		_e('<p>No years have been selected from <a href="' . get_permalink($parentp_id) . '" title="' . get_the_title($parentp_id) . '">' . get_the_title($parentp_id) . '</a> by '. $user_info->first_name .'.</p>');
	}
}
?>