<?php 
/**
 * Functions to create and manage WP Shortcodes
 *
 * These functions create the ability to call PlainsightNotes
 * functions as shortcodes.
 *
 * @package WordPress
 * @subpackage PlainSight-Notes
 * @since 0.9.0
 */

// Include necessary files
include_once 'plainsightnotes-public.php';
include_once 'plainsightnotes-admin.php';

// SHORTCODES FOR USER INPUT
/**
 * Shortcode for main Notes form for user input
 *
 * Basic shortcode format is: [note_form]
 * Add attributes with the format: [note_form showsubmit="false" titletype="hidden"]
 *
 * @uses psn_notes_manage()
 * @since 0.9.0
 */
function shortcode_notes_form( $args ) {
	$psn_settings = get_option('psn_settings');
	
	extract(shortcode_atts(array(
		'notebefore' => '<form name="notesform" id="psnotesform" class="wrap" method="post" action="">',
		'noteafter' => '</form>',
		'titletype' => $psn_settings['noteform_titletype'],
		'notetitle' => $psn_settings['noteform_notetitle'],
		'titlesize' => 45,
		'showsubmit' => true,
		'mode' => 'add_note',
		'noteID' => false,
		'allowrepeats' => $psn_settings['noteform_repeats'],
		'norepeats_msg' => $psn_settings['noteform_norepeats_msg'],
		'char_limit' => (int) $psn_settings['noteform_charlimit'],
		'textarea_rows' => 10,
		'textarea_cols' => (int) $psn_settings['noteform_width'],
     ), $args));
   	
	ob_start();
		psn_notes_manage( $args );
		$output_string = ob_get_contents();
	ob_end_clean();

	return $output_string;
}
add_shortcode( 'note_form', 'shortcode_notes_form' );


// SHORTCODES FOR OUTPUT / DISPLAY OF NOTES
/**
 * Shortcode for displaying single Note by ID
 *
 * Basic shortcode format is: [the_note noteid="1"]
 * Add attributes with the format: [the_note noteid="1" wrap_class="note" show_avatar="0"]
 *
 * @uses psn_the_note()
 * @since 0.9.0
 * @todo NOT WORKING, likely problem with noteID arg
 */
function shortcode_psn_the_note( $args ) {
	extract(shortcode_atts(array(
		'noteID' => (int) $noteID,
		'wrap_class' => 'note',
		'title_wrap' => 'h4',
		'length' => 'full',
		'date_first' => true,
		'show_avatar' => true,
     ), $args));
	
	ob_start();
		psn_the_note( $args );
		$output_string = ob_get_contents();
	ob_end_clean();
	
	return $output_string;
}
add_shortcode( 'the_note', 'shortcode_psn_the_note' );


/**
 * Shortcode to display recent notes. Defaults to 4.
 *
 * Syntax is [recent_notes]. Can be customized with attributes such
 * as order, number displayed, and wrapper div class. Such as:
 * [recent_notes howmany="2" orber="DESC" wrap_class="wrapper"]
 *
 * @since 0.9.0
 * @uses psn_recent_notes()
 */
function shortcode_psn_recent_notes( $args ) {
	extract(shortcode_atts(array(
		'howmany' => 4,
		'orderby' => 'noteID',
		'order' => 'ASC',
		'wrap_class' => 'note',
		'title_wrap' => 'h4',
		'length' => 'full',
	), $args));
	
	ob_start();
		psn_recent_notes( $args );
		$output_string = ob_get_contents();
	ob_end_clean();
	
	return $output_string;
}
add_shortcode( 'recent_notes', 'shortcode_psn_recent_notes' );


/**
 * Shortcode to display Notes by given author
 *
 * Display all notes by a given author. Notes are displayed in
 * reverse chronological order (with most recent at the top). Syntax
 * is [note_by_author author_id="1"] Customize attributes such as: 
 * [note_by_author author_id="1" howmany="3" show_avatar="0"]
 *
 * @since 0.9.0
 * @uses psn_notes_by_author_id()
 */
function shortcode_notes_by_author( $args ) {
	extract(shortcode_atts(array(
		'author_id' => (int) $author_id,
		'howmany' => $howmany,
		'wrap_class' => 'note',
		'title_wrap' => 'h4',
		'length' => 'full',
		'date_first' => true,
		'show_avatar' => true
	), $args));
	
	ob_start();
		psn_notes_by_author_id( $args );
		$output_string = ob_get_contents();
	ob_end_clean();
	
	return $output_string;
}
add_shortcode( 'note_by_author', 'shortcode_notes_by_author' );


/**
 * Shortcode to display Notes from given page/post
 *
 * Default syntax is: [note_by_parent parentp_id="1"] Where
 * parentp_id is the ID of the WP page/post from which the note
 * was submitted. Customize display with attributes such as: 
 * [note_by_parent parentp_id="1" howmany="4" wrap_class="wrapper"]
 * 
 * @since 0.9.0
 * @uses psn_notes_by_parent_post_id()
 */
function shortcode_psn_notes_by_parent( $args ) {
	extract(shortcode_atts(array(
		'parentp_id' => $parentp_id,	
		'howmany' => $howmany,
		'wrap_class' => 'note',
		'title_wrap' => 'h4',
		'length' => 'full',
	), $args));
	
	ob_start();
		psn_notes_by_parent_post_id( $args );
		$output_string = ob_get_contents();
	ob_end_clean();
	
	return $output_string;
}
add_shortcode( 'note_by_parent', 'shortcode_psn_notes_by_parent' );


/**
 * Shortcode to display selected Notes by both author and origin
 * 
 * Display all notes posted from a given WordPress post/page and
 * only by a specified author; default syntax is: 
 * [note_by_meta parentp_id="1" author_id="1"]
 *
 * @since 0.9.0
 * @uses psn_notes_by_meta()
 */
function shortcode_psn_notes_by_meta( $args ) {
	extract(shortcode_atts(array(
		'parentp_id' => (int) $parentp_id,
		'author_id' => (int) $author_id,
		'howmany' => (int) $howmany,
		'wrap_class' => 'note',
		'title_wrap' => 'h4',
		'length' => 'full',
	), $args));
	
	ob_start();
		psn_notes_by_meta ( $args );
		$output_string = ob_get_contents();
	ob_end_clean();
	
	return $output_string;
}
add_shortcode( 'note_by_meta', 'shortcode_psn_notes_by_meta' );

/**
 * Allow showing/hide content based on note submission
 *
 * A shortchode function that provides a wrapper to hide the enclosed
 * content depending on status of the Note on the page. Hides the 
 * enclosed content until a Note is submitted. By default, only works 
 * if used on the SAME page the Note Form is displayed on or submitted 
 * from (if using a shortcode to display the form, for example, whatever 
 * page has the [note_form] shortcode could also use this shortcode.)
 * To check if someone has posted a Note from a *different* page, need to 
 * use the parentp_id attribute and specify the desired page ID. This also
 * works for checking against a user other than the current user.
 *
 * For example, to display the content inside the [psn_content_vis] codes
 * only when the Note on page ID 397 has been submitted, you would use:
 * [psn_content_vis parentp_id="397"]The content[/psn_content_vis]
 *
 * @since 0.9.1
 * @uses psn_get_notes_by_meta()
 */
function shortcode_psn_content_vis( $args, $content = null ) {
	extract(shortcode_atts(array(
		'parentp_id' => '',
		'author_id' => '',
		'message' => 'Complete Note to continue.',
	), $args));
	
	global $wpdb, $current_user;
	get_currentuserinfo();
	
	if ( ! current_user_can( 'delete_users' ) ) {
		if ( empty($parentp_id) ) {
			$parentp_id = get_the_ID();
		}
		if ( empty($author_id) ) {
			$author_id = get_current_user_id();
		}
		$note_data = psn_get_notes_by_meta( $parentp_id, $author_id );
		/* Checks to see if user has already submitted a 'note' for this page; displays note form if not */
		if ( !empty( $note_data ) ) {
			$the_content = wpautop( $content, $br ); 
			return do_shortcode($the_content);
		} else { 
			return '<p class="error">' . $message . '</p>';
		}
	} else {
		// In that case its an Admin and we don't need to hide anything
		$the_content = wpautop( $content, $br ); 
		return do_shortcode($the_content);
	}
}
add_shortcode( 'psn_content_vis', 'shortcode_psn_content_vis');
?>