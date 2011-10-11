m<?php 
/*
Plugin Name: PlainSight Notes
Plugin URI: http://chnm.gmu.edu/hiddeninplainsight/
Description: A plugin allowing note fields for users to save text to be retrieved elsewhere.
Version: 0.9.7
Author: Adapted by Adam Turner from the work of the ScholarPress folks, being Jeremy Boggs, Dave Lester, Zac Gordon, and Sean Takats 
Author URI: http://adamturner.org/
License: GPL2
*/

/*
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// Plugin Version
global $plainsightnotes_version;
$plainsightnotes_version= "0.9.7";
global $psn_db_version;
$psn_db_version = "1.0";

register_activation_hook( __FILE__, 'plainsightnotes_install' );

// PlainSight Notes Path
$plainsightnotes_path = ABSPATH . PLUGINDIR . DIRECTORY_SEPARATOR . 'plainsight-notes/';

// Include necessary files
include_once 'plainsightnotes-public.php';
include_once 'plainsightnotes-notes.php';
include_once 'psnotes-shortcodes.php';
require_once( $plainsightnotes_path . 'lib/boones-sortable-columns.php' );

if ( isset($_GET['activate']) && $_GET['activate'] == 'true' ) {
	add_action('init', 'plainsightnotes_install');
}

// Insert sinks into the plugin hook list
add_action('admin_init', 'psn_admin_init');
add_action('admin_menu', 'psns_admin_menu');

// Filter the notes pages
add_filter('the_content', 'notes_page', 10);

// Page Delimiters
define('PSN_NOTES_PAGE', '<psnotes />');

/* ---------- Misc functions ---------- */
// Adapted from PHP.net: http://us.php.net/manual/en/function.nl2br.php#73479
function plainsightnotes_nls2p($str) {
  return str_replace('<p></p>', '', '<p>'
        . preg_replace('#([\r\n]\s*?[\r\n]){2,}#', '</p>$0<p>', $str)
        . '</p>');
}

/* ---------- Options functions ---------- */
// Admin options
function psn_get_admin_options() {
	$plainsightnotesOptions = get_option('PlainSightNotesAdminOptions');
	if ( !empty($plainsightnotesOptions) ) {
		foreach ( $plainsightnotesOptions as $key => $option )
			$psnAdminOptions[$key] = $option;
	}
	return $psnAdminOptions;
}
function psn_set_admin_options($instructor_firstname, $instructor_lastname, $instructor_email)
{
		$psnAdminOptions = array('instructor_firstname' => $instructor_firstname,
		'instructor_lastname' => $instructor_lastname,
		'instructor_email' => $instructor_email);
	
	$psn_admin_options = 'PlainSightNotesAdminOptions';
	
	if (get_option($psn_admin_options) ) {
		update_option($psn_admin_options, $psnAdminOptions);
	} else {
		$deprecated=' ';
		$autoload='no';
		add_option($psn_admin_options, $psnAdminOptions, $deprecated, $autoload);
	}
}




/**
 * The PS Notes settings functions
 *
 *	Collect PS Notes settings values with: $psn_settings = get_option('psn_settings');
 * and then $psn_settings['variable_name']
 *
 * Current variable names:
 * 	noteform_width (the width of the Note input textarea)
 * 	noteform_titletype (whether hidden or text)
 *
 * Many thanks to Bob Chatman's tutorial at 
 * http://blog.gneu.org/2010/09/intro-to-the-wordpress-settings-api/
 *
 * @since 0.9.6
 * 
 * @todo Clean up (bring everything up to its own line)
 * @todo Probably move to its own file and include_once here
 */
class PSNSettings {

	function init() {
		// Create array of PSNotes setting: psn_settings
		register_setting(
			'psn_settings_group', 
			'psn_settings', 
			array('PSNSettings', 'validate_fn'));
		
		// Create note form settings section: note_form_group
		add_settings_section(
			'note_form_group', 
			'Note Form Settings', 
			array('PSNSettings', 'note_section_overview_fn'), 
			'note_form_settings');
		
		// Call noteform width field within note_form_group
		add_settings_field(
			'note_form_width_id', 
			'Note Form Width', 
			array('PSNSettings', 'note_form_width_control_fn'), 
			'note_form_settings', 
			'note_form_group');
		
		// Call noteform titletype checkbox within note_form_group
		add_settings_field(
			'note_form_titletype_id', 
			'Note title field display', 
			array('PSNSettings', 'note_form_titletype_control_fn'), 
			'note_form_settings', 
			'note_form_group');
		
		// Call noteform notetitle field within note_form_group
		add_settings_field(
			'note_form_notetitle_id', 
			'Default note title', 
			array('PSNSettings', 'note_form_notetitle_control_fn'), 
			'note_form_settings', 
			'note_form_group');
		
		// Call noteform character limit field within note_form group
		add_settings_field(
			'note_form_charlimit_id',
			'Note character limit',
			array('PSNSettings', 'note_form_charlimit_control_fn'),
			'note_form_settings',
			'note_form_group');
		
		// Call noteform allow repeats yes/no field w/in note_form group
		add_settings_field(
			'note_form_repeats_id',
			'Allow multiple posts per note',
			array('PSNSettings', 'note_form_repeats_control_fn'),
			'note_form_settings',
			'note_form_group');
		
		// Call noteform no-repeat-posts message w/in note_form group
		add_settings_field(
			'note_form_norepeats_msg',
			'Message: already submitted',
			array('PSNSettings', 'note_form_norepeats_msn_fn'),
			'note_form_settings',
			'note_form_group');
		
		/*
		Fields for: add_settings_field()
			'unique ID',
			'Name for display',
			array('PSNSettings, 'callback_function_to_get_element'),
			'Last field of add_settings_section (probably note_form_settings)',
			'group_it_belongs_to (probably note_form_group');
		*/
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_style( 'thickbox' );
	}
	
	function admin_menus() {
		if (!function_exists('current_user_can') || !current_user_can('manage_options')) {
			return;
		}
		if (function_exists('add_options_page')) {
			add_options_page(
				'PSNotes Settings', 
				'PS Notes Settings', 
				'manage_options',
				'ps_notes_settings', 
				array('PSNSettings', 'psnotes_settings_page_fn'));
		}
	}

	function psnotes_settings_page_fn() {
		$psn_settings = get_option('psn_settings');
		?>
		<div class="wrap">
			<?php screen_icon("options-general"); ?>
			<h2>PS Notes Settings</h2>
			<form action="options.php" method="post">
				
				<?php settings_fields('psn_settings_group'); ?>
				<?php do_settings_sections('note_form_settings'); ?>
				
				<p class="submit">
					<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
				</p>
			</form>
			<h4>Future options:</h4>
			<p>If you have any you'd like to add to the list, please let me know</p>
			<ol>
				<li>Don't de-register settings upon plugin deactivation</li>
				<li>Date/time format (or perhaps just hook into WP date/time option)</li>
				<li>Height of note form box, in rows</li>
			</ol>
		</div> 
	<?php 
	}
	
	/* The form section called with add_settings_section() */
	function note_section_overview_fn() {
	?>
		<p>The default settings for displaying the PS Notes input textarea and controlling note collection.</p>
		<p><a href="#TB_inline?height=500&width=800&inlineId=note_preview" class="thickbox">Preview</a> note form with current settings. (Not including live page styles.)</p>
		<div id="note_preview" style="display:none;">
				<?php 
					$psn_settings = get_option('psn_settings');
					psn_notes_editform( 'notebefore=&noteafter=' );
				?>
		</div>
	<?php 
	}

	/* The form elements called with add_settings_field() */
	function note_form_width_control_fn() {
		$psn_settings = get_option('psn_settings');
		?>	
		<input id="noteform_width" 
				 name="psn_settings[noteform_width]" 
				 class="regular-text" 
				 type="text" 
				 value="<?php echo $psn_settings['noteform_width']; ?>" /> 
		<span class="description">Width of note form measured in columns</span>
	<?php 
	}
	
	function note_form_titletype_control_fn() {
		$psn_settings = get_option('psn_settings');
		$options = array("Text", "Hidden");
		
		foreach($options as $option) {
			$checked = ($psn_settings['noteform_titletype'] == strtolower($option)) ? ' checked="checked" ' : '';
			?>
			<label>
				<input id="noteform_titletype"
						 name="psn_settings[noteform_titletype]" 
						 class="code" 
						 value="<?php echo $option; ?>" 
						 type="radio" 
						 <?php echo $checked; ?> />
			<?php echo $option; ?>
			</label><br />
		<?php 
		} // endforeach
	}
	
	function note_form_notetitle_control_fn() {
		$psn_settings = get_option('psn_settings');		
		?>	
		<input id="noteform_notetitle" 
				 name="psn_settings[noteform_notetitle]" 
				 class="regular-text" 
				 type="text" 
				 <?php if ( $psn_settings['noteform_titletype'] != 'hidden' ) { echo 'readonly="readonly"'; } ?> 
				 value="<?php echo $psn_settings['noteform_notetitle']; ?>" />
		<span class="description">Title notes will be given with when the field is hidden from users
		<br />Variables you can use:</span><br />
		<span>
		<code>%%SOURCE_PAGE_TITLE%%</code> Page title of page the note was submitted from<br />
		<code>%%USER_NAME%%</code> User's display name<br />
		<code>%%FIRST%%</code> = User's first name<br />
		<code>%%LAST%%</code> User's last name<br />
		<code>%%DATE%%</code> Date note was submitted<br />
		</span>
	<?php	
	}
	
	function note_form_repeats_control_fn() {
		$psn_settings = get_option('psn_settings'); 
		?>
		<input id="noteform_repeats" 
				 name="psn_settings[noteform_repeats]" 
				 type="checkbox" 
				 class="code" 
				 value="1" 
				 <?php checked( $psn_settings['noteform_repeats'], 1 ); ?> />
	<?php 
	}
	
	function note_form_charlimit_control_fn() {
		$psn_settings = get_option('psn_settings');
		?>
		<input id="noteform_charlimit" 
				 name="psn_settings[noteform_charlimit]" 
				 class="regular-text" 
				 type="text"  
				 value="<?php echo $psn_settings['noteform_charlimit']; ?>" />
		<span class="description">To remove character limit on notes enter value of 0.</span>
	<?php 
	}
	
	function note_form_norepeats_msn_fn() {
		$psn_settings = get_option('psn_settings');
		?>
		<input id="noteform_norepeats_msg" 
				 name="psn_settings[noteform_norepeats_msg]" 
				 class="regular-text"
				 type="text" 
				 <?php if ( $psn_settings['noteform_repeats'] == 1 ) { echo 'readonly="readonly"'; } ?>
				 value="<?php echo $psn_settings['noteform_norepeats_msg']; ?>" />
		<span class="description">Message to display in place of note forms where user already submitted a note.<br />
		Some formatting allowed using BBCode format:</span><br />
		<span>
			<code>[b]some text[/b]</code> becomes <strong>some text</strong><br />
			<code>[i]some text[/i]</code> becomes <em>some text</em><br />
			<code>[s]some text[/s]</code> becomes <del>some text</del>
		</span>
	<?php 
	}
	
	/* Validate submitted form data, then return squeaky-clean settings */
	function validate_fn($input) {
		$psn_settings = get_option('psn_settings');
	
		// validate noteform columns as an integer
		if (sprintf("%.0f", $input['noteform_width']) == $input['noteform_width']) {
			$psn_settings['noteform_width'] = $input['noteform_width'];
		} else {
			add_settings_error('psn_settings', 'settings_updated', __('<em>Note form width:</em> Only integers are accepted. Please use a number.'));
		}
		// validate noteform titletype as either 'text' or 'hidden'
		if ($input['noteform_titletype'] == 'Text' || $input['noteform_titletype'] == 'Hidden') { 
			$psn_settings['noteform_titletype'] = strtolower($input['noteform_titletype']);
		} else {
			add_settings_error('psn_settings', 'settings_updated', __('<em>Note form title display:</em> Only 2 options, Text or Hidden, are accepted. Adieu.'));
		}
		// validate noteform character limit as an integer
		if (sprintf("%.0f", $input['noteform_charlimit']) == $input['noteform_charlimit']) {
			$psn_settings['noteform_charlimit'] = $input['noteform_charlimit'];
		} else {
			add_settings_error('psn_settings', 'settings_updated', __('<em>Note form character limit:</em> Only integers are accepted. Please use a number.'));
		}
		
		// validate noteform repeats checkbox
		$psn_settings['noteform_repeats'] = $input['noteform_repeats'];
		
		// strip html and php tags from input and output noteform data
		$psn_settings['noteform_notetitle'] = strip_tags($input['noteform_notetitle']);
		$psn_settings['noteform_norepeats_msg'] = strip_tags($input['noteform_norepeats_msg']);
		
		// And finally, spit out the validated data
		return $psn_settings;
	}
} // end PSNSettings class

// Hook settings into the admin init and menu processes
add_action('admin_init', array('PSNSettings', 'init'));
add_action('admin_menu', array('PSNSettings', 'admin_menus'));

// Define default option settings
register_activation_hook(__FILE__, 'add_psnsettings_defaults_fn');
function add_psnsettings_defaults_fn() {
    $arr = array(
    	"noteform_width" => "80", 
    	"noteform_titletype" => "text", 
    	"noteform_notetitle" => "A Note", 
    	"noteform_charlimit" => "2500",
    	"noteform_norepeats_msg" => "You have already completed this.",
    	"noteform_repeats" => 1);
    update_option('psn_settings', $arr);
}

/* Things to add

Run a de-register settings function on plugin deactivation; 
and a user option to NOT de-register settings upon deactivation

*/
// End PS Notes settings functions






/** 
 * Add multisite compliance; thanks to: http://shibashake.com/wordpress-theme/write-a-plugin-for-wordpress-multi-site
 * @since WP3
 */
function plainsightnotes_install() {
	global $wpdb;
	
	if (function_exists('is_multisite') && is_multisite()) {
		// check to see if it is a network activation - if so, run the activation funciton for each blog id
		if (isset($_GET['networkwide']) && ($_GET['networkwide'] == 1)) {
			$old_blog = $wpdb->blogid;
			// Get all blod ids
			$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs"));
			foreach ($blogids as $blog_id) {
				switch_to_blog($blog_id);
				_plainsightnotes_install();
			}
			switch_to_blog($old_blog);
			return;
		}
	}
	_plainsightnotes_install();
}

// Install the notes plugin
function _plainsightnotes_install() {
	global $wpdb, $user_level, $plainsightnotes_version;
	global $psn_db_version;
	
	// Check user level
	get_currentuserinfo();
	if ( !current_user_can('delete_others_posts') ) {
		return;
	}
	
	// table names
	$notes_table_name = $wpdb->prefix . "notes";
	$timelines_table_name = $wpdb->prefix . "timelines";

	// First-Run-Only parameters: Check if notes table exists:
	if( $wpdb->get_var("SHOW TABLES LIKE '$notes_table_name'") != $notes_table_name ) {
		// Since it doesn't exist, create the table
		$sql = "CREATE TABLE " . $notes_table_name . " (
		 	 `noteID` INT(11) NOT NULL AUTO_INCREMENT,
		 	 `notes_title` TEXT NOT NULL, 
		 	 `notes_authorID` INT NOT NULL,
		 	 `notes_date` TEXT NOT NULL,
		 	 `notes_content` TEXT NOT NULL,
		 	 `notes_parentPostID` INT(11) NOT NULL,
		 	 PRIMARY KEY  (`noteID`)
		 	 )";

	    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	    dbDelta($sql);
	    
	    add_option("psn_db_version", $psn_db_version);

		// Check for updates to the database?	    
	    $installed_ver = get_option( "psn_db_version" );

   		if( $installed_ver != $psn_db_version ) {
			$sql = "CREATE TABLE " . $notes_table_name . " (
		 		`noteID` INT(11) NOT NULL AUTO_INCREMENT,
		 	 	`notes_title` TEXT NOT NULL, 
		 	 	`notes_authorID` INT NOT NULL,
			 	`notes_date` TEXT NOT NULL,
			 	`notes_content` TEXT NOT NULL,
			 	`notes_parentPostID` INT(11) NOT NULL,
		 		PRIMARY KEY  (`noteID`)
		 	 	)";
		
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
	
			update_option( "psn_db_version", $psn_db_version );
  		}
	}
	
	// Check if timelines table exists:
	if ( $wpdb->get_var("SHOW TABLES LIKE '$timelines_table_name'") != $timelines_table_name ) {
		// Since it doesn't exist, create the table
		$sql = "CREATE TABLE " . $timelines_table_name . " (
			`timelineID` INT(11) NOT NULL AUTO_INCREMENT,
			`timelines_authorID` INT NOT NULL,
			`timelines_authorLast` TEXT NOT NULL,
			`timelines_authorFirst` TEXT NOT NULL,
			`timelines_date` TEXT NOT NULL,
			`timelines_yearSelected` TEXT NOT NULL,
			`timelines_parentPostID` INT(11) NOT NULL,
			PRIMARY KEY (`timelineID`)
			)";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
	}
		
	// Add notes info to the options table.
	$plainsightnotesOptions = get_option('PlainSightNotesAdminOptions');
	
	if( empty($plainsightnotesOptions) ) {
		psn_set_admin_options(null, null, null);
	}
	
	/// POPULATE DB WITH NOTES PAGE IF NOT ALREADY CREATED
	$now = time();
	$now_gmt = time();
	$parent_id = 1; // Uncategorized default
	$post_modified = $now;
	$post_modified_gmt = $now_gmt;
	
	if ( !$wpdb->get_row("SELECT * FROM $wpdb->posts WHERE post_title='Notes'", OBJECT) )
	{
		$notes_title = "Notes";
		$notes_content = "<div id=\"psnotes\"><psnotes /></div>";
		$notes_excerpt = "";
		$notes_status = "publish";
		$notes_name = "notes";
		
		wp_insert_post(array(
			'post_author'		=> '1',
			'post_date'		=> $post_dt,
			'post_date_gmt'		=> $post_dt,
			'post_modified'		=> $post_modified_gmt,
			'post_modified_gmt'	=> $post_modified_gmt,
			'post_title'		=> $notes_title,
			'post_content'		=> $notes_content,
			'post_excerpt'		=> $notes_excerpt,
			'post_status'		=> $notes_status,
			'post_name'		=> $notes_name,
			'post_type' => 'page')
		);
	}
}

/* Run plainsightnotes_install() function on all new multisite blogs created, if active for network */
function new_blog($blog_id) {
	global $wpdb;

	if ( is_plugin_active_for_network('plainsight-notes/plainsightnotes-admin.php') ) {
		//$old_blog = $wpdb->blogid;
		switch_to_blog($blog_id);
			plainsightnotes_install();
		restore_current_blog();
	}
}
add_action('wpmu_new_blog', 'new_blog');

// Add management pages to the administration panel; sink function for 'admin_menu' hook
function psns_admin_menu()
{
	$psnManage = add_menu_page('PS Notes','PS Notes','delete_others_posts','plainsightnotes','psn_manage');
	$notes = add_submenu_page('plainsightnotes','PS Notes | All Notes', 'All Notes', 'delete_others_posts', 'notes', 'psn_notes_manage');
	$timeline = add_submenu_page('plainsightnotes', 'PS Notes | Timeline', 'Timeline', 'delete_others_posts', 'timeline', 'psn_timeline_manage');
	$help = add_submenu_page('plainsightnotes', 'PS Notes | Help', 'Help', 'delete_others_posts', 'help', 'psn_help');
	$plainsightnotesPages = array($psnManage, $notes, $timeline, $help);
	
	foreach( $plainsightnotesPages as $page ) {
		add_action('admin_print_styles-' . $page, 'psn_admin_stuff');
	}
}

/* Set up stylesheets and scripts */
function psn_admin_init() {
	wp_register_style( 'psnadminstyle', plugins_url('/lib/css/psnadmin.css', __FILE__) );
}

add_action('wp_print_styles', 'psn_public_style');
function psn_public_style() {
	wp_register_style( 'plainsightstyle', plugins_url('/lib/css/plainsightnotes.css', __FILE__) );
	wp_enqueue_style( 'plainsightstyle' );
}

add_action('init', 'psn_public_script');
function psn_public_script() {
	wp_register_script( 'plainsightscript', plugins_url('/lib/js/plainsightnotes.js', __FILE__), array('jquery'), '', true );
	wp_enqueue_script( 'plainsightscript' );
}

// admin styles & scripts
function psn_admin_stuff() {
	wp_enqueue_style( 'psnadminstyle' );
	wp_enqueue_script( 'plainsightscript' );
	wp_enqueue_script( 'dashboard' );
}

// The management splash page
function psn_manage() {
	global $current_user;
	get_currentuserinfo();
?>
	<div class="wrap">
		<div id="icon-index" class="icon32"><br /></div>
		<h2>PlainSight Notes</h2>
		<div id="dashboard-widgets-wrap">
			<div id="dashboard-widgets" class="metabox-holder">
				<div class="postbox-container">
					<div id="normal-sortables" class="meta-box-sortables ui-sortable">		
						<div id="dashboard_recent_notes" class="postbox">
							<div class="handlediv" title="Click to toggle"><br /></div>
							<h3 class="handle"><span>Recent Notes</span></h3>
							<div class="inside">
								<div id="the-comment-list" class="list:comment">
									<?php psn_recent_notes( 3 ); ?>
									<p class="textright"><a href="?page=notes" class="button">View All</a></p>
								</div><?php // Closes #the-comment-list ?>
							</div> <?php // Closes .inside ?>
						</div><?php // Closes .postbox #dashboard_recent_notes ?>
						<div id="dashboard_recent_notes_by_auth" class="postbox">
							<div class="handlediv" title="Click to toggle"><br /></div>
							<h3 class="handle"><span>Recent Notes by <?php echo $current_user->display_name; ?> (That's you!)</span></h3>
							<div class="inside">
								<div id="the-recent-list" class="list:comment">
									<?php psn_notes_by_author_id( 'author_id=' . $current_user->ID . '&howmany=3' ); ?>
									<p class="textright"><a href="?page=notes" class="button">View All</a></p>
								</div><?php // Closes #the-recent-list ?>
							</div> <?php // Closes .inside ?>
						</div><?php // Closes .postbox #dashboard_recent_notes_by_auth ?>
					</div><?php // Closes #normal-sortables .meta-box-sortables ?>
				</div> <?php // Closes .postbox-container ?>
				<div class="postbox-container">
					<div id="side-sortables" class="meta-box-sortables ui-sortable">	
						<div id="update_settings" class="postbox">
							<div class="handlediv" title="Click to toggle"><br /></div>
							<h3 class="handle"><span>PlainSight Notes Settings</span></h3>
							<div class="inside">
								<div id="the-comment-list" class="list:comment">
									<p class="textright"><a href="<?php bloginfo( 'url' );?>/wp-admin/options-general.php?page=ps_notes_settings" class="button">Edit Settings</a></p>
								</div><?php // Closes #the-comment-list ?>
							</div> <?php // Closes .inside ?>
						</div><?php // Closes .postbox #dashboard_recent_timeline ?>
						<?php if ( is_plugin_active('scholarpress-courseware/spcourseware-admin.php') ) { 
						?>
							<div id="dashboard_courseinfo" class="postbox">
								<div class="handlediv" title="Click to toggle"><br /></div>
								<h3 class="handle"><span>Course Information</span></h3>
								<div class="inside">
									<div id="the-timeline-list" class="list:comment">
										<?php courseinfo_printfull(); ?>
										<p class="textright"><a href="?page=courseinfo" class="button">Edit Course Information</a></p>
										<p class="textright"><small>Fetched from the <a href="?page=scholarpress-courseware" title="SP Courseware Admin">ScholarPress Courseware</a> plugin.</small></p>
									</div><?php // Closes #the-timeline-list ?>
								</div> <?php // Closes .inside ?>
							</div><?php // Closes .postbox #dashboard_courseinfo ?>
						<?php
						}
						?>
						<div id="dashboard_recent_timeline" class="postbox">
							<div class="handlediv" title="Click to toggle"><br /></div>
							<h3 class="handle"><span>Recent Timeline Selections</span></h3>
							<div class="inside">
								<div id="the-comment-list" class="list:comment">
									<p>Nothing here for now.</p>
								</div><?php // Closes #the-comment-list ?>
							</div> <?php // Closes .inside ?>
						</div><?php // Closes .postbox #dashboard_recent_timeline ?>
					</div><?php // Closes #side-sortables .meta-box-sortables ?>
				</div> <?php // Closes .postbox-container ?>
			</div> <?php // Closes #dashboard-widgets .metabox-holder ?>
			<div class="clear">
			</div>
		</div><!-- dashboard-widgets-wrap -->
	</div><!-- wrap -->		
<?php
}

// Handles the notes management page and form
function psn_notes_manage( $args ) {
	global $wpdb;
	
	$updateaction = !empty($_REQUEST['updateaction']) ? $_REQUEST['updateaction'] : '';
	$noteID = !empty($_REQUEST['noteID']) ? $_REQUEST['noteID'] : '';
	
	if ( isset($_REQUEST['action']) ) : 
		
		if ( $_REQUEST['action'] == 'delete_note' ) {
			
			if ( ! is_admin() ) {
				$nonce = $_REQUEST['_wpnonce'];
				if( ! wp_verify_nonce( $nonce, 'psnotes-note-delete' ) ) die( 'Are you sure you want to do this?' );
			} else {
				check_admin_referer( 'psnotes-note-delete' );
			}
			
			$noteID = intval($_GET['noteID']);
			if (empty($noteID)) {
				?><div class="error"><p><strong>Failure:</strong> No notes ID given. Successfully emptied the nothing.</p></div><?php
			} else {
				$wpdb->query("DELETE FROM " . $wpdb->prefix . "notes WHERE noteID = '" . $noteID . "'");
				$sql = "SELECT noteID FROM " . $wpdb->prefix . "notes WHERE noteID = '" . $noteID . "'";
				$check = $wpdb->get_results($sql);
				if ( empty($check) || empty($check[0]->noteID) )
				{
					?><div class="updated"><p>Note ID <?php echo $noteID; ?> deleted successfully.</p></div><?php
				}
				else
				{
					?><div class="error"><p><strong>Failure:</strong></p></div><?php
				}
			}
		} // end delete_note block
	endif;
	
	if ( $updateaction == 'update_note' ) {
		
		if ( ! is_admin() ) {
			$nonce = $_REQUEST['_wpnonce'];
			if( ! wp_verify_nonce( $nonce, 'psnotes-note-submit' ) ) die( 'Are you sure you want to do this?' );
		} else {
			check_admin_referer( 'psnotes-note-submit' );
		}
		
		$title = !empty($_REQUEST['note_title']) ? $_REQUEST['note_title'] : '';
		$authorID = !empty($_REQUEST['note_authorID']) ? $_REQUEST['note_authorID'] : '';
		$content = !empty($_REQUEST['note_content']) ? $_REQUEST['note_content'] : '';
		$parentpostID = !empty($_REQUEST['note_ppostID']) ? $_REQUEST['note_ppostID'] : '';
		
		if ( empty($noteID) ) {
			?><div class="error"><p><strong>Failure:</strong> No note ID provided. I can't save what doesn't exist.</p></div><?php
		} else {
			$sql = "UPDATE " . $wpdb->prefix . "notes SET notes_title = '" . $title . "', notes_authorID = '" . $authorID . "', notes_content = '" . $content . "', notes_parentPostID = '" . $parentpostID . "' WHERE noteID = '" . $noteID . "'";
			$wpdb->get_results($sql);
			$sql = "SELECT noteID FROM " . $wpdb->prefix . "notes WHERE notes_title = '" . $title . "' and notes_authorID = '" . $authorID . "' and notes_content = '" . $content . "' and notes_parentPostID = '" . $parentpostID . "' LIMIT 1";
			$check = $wpdb->get_results($sql);
			if ( empty($check) || empty($check[0]->noteID) ) {
				?><div class="error"><p><strong>Failure:</strong> I couldn't update your entry. Perhaps try again?</p></div><?php
			} else {
				?><div class="updated"><p>Note <?php echo $noteID; ?> updated successfully.</p></div><?php
			}
		}
	} // end update_note block
	
	elseif ( $updateaction == 'add_note' ) {
		
		if ( ! is_admin() ) {
			$nonce = $_REQUEST['_wpnonce'];
			if( ! wp_verify_nonce( $nonce, 'psnotes-note-submit' ) ) die( 'Are you sure you want to do this?' );
		} else {
			check_admin_referer( 'psnotes-note-submit' );
		}
		
		$title = !empty($_REQUEST['note_title']) ? $_REQUEST['note_title'] : '';
		$authorID = !empty($_REQUEST['note_authorID']) ? $_REQUEST['note_authorID'] : '';
		$date = !empty($_REQUEST['note_date']) ? $_REQUEST['note_date'] : '';
		$content = !empty($_REQUEST['note_content']) ? $_REQUEST['note_content'] : '';
		$parentpostID = !empty($_REQUEST['note_ppostID']) ? $_REQUEST['note_ppostID'] : '';
		
		$sql = "INSERT INTO " . $wpdb->prefix . "notes SET notes_title = '" . $title . "', notes_authorID = '" . $authorID . "', notes_date = '" . $date . "', notes_content = '" . $content . "', notes_parentPostID = '" . $parentpostID . "'";
		$wpdb->get_results($sql);
		$sql = "SELECT noteID FROM " . $wpdb->prefix . "notes WHERE notes_title = '" . $title . "' and notes_authorID = '" . $authorID . "' and notes_date = '" . $date . "' and notes_content = '" . $content . "' and notes_parentPostID = '" . $parentpostID . "'";
		$check = $wpdb->get_results($sql);
		if ( empty($check) || empty($check[0]->noteID) )
		{
			?><div class="error"><p><strong>Failure:</strong> Oh hum, nothing happened. Try again? </p></div><?php
		}
		else
		{
			?><div class="updated"><p>Super! Note saved.</p></div><?php
		}
	} // end add_note block
	?>

	<div class="wrap">
	<?php
	if ( $_REQUEST['action'] == 'edit_note' ) { ?>
		<h2><?php _e('Edit Note'); ?></h2>
		<?php
		if ( empty($noteID) ) {
			echo "<div class=\"error\"><p>I didn't get an entry identifier from the request. Stopping here.</p></div>";
		} else {			
			psn_notes_editform('mode=update_note&noteID=' . $noteID . '');
		}
	} else {
		if ( is_admin() ) {
			psn_notes_displaylist();
		} else { 
			psn_notes_editform($args);
		}
	}
	?>
	</div>
<?php 
}

// Displays the list of notes entries
function psn_notes_displaylist() {
	global $wpdb;
	
	/**
	 * Define array of column data (in case it isn't obvious, 'name' 
	 * must be a database column name)
	 */
	$cols = array(
		array(
			'name'       => 'noteID',
			'title'      => 'ID',
			'css_class'  => 'note-id',
			'is_default' => true,
			'default_order' => 'desc'
		),
		array(
			'name'      => 'notes_date',
			'title'     => 'Date Added',
			'css_class' => 'date-added',
			'default_order' => 'desc'
		),
		array(
			'name'       => 'notes_title',
			'title'      => 'Note Title',
			'css_class'  => 'note-title'
		),
		array(
			'name'       => 'display_name',
			'title'      => 'Creator',
			'is_sortable' => false,
			'css_class'  => 'creator'
		),
		array(
			'name'        => 'notes_authorID',
			'title'       => 'Author ID',
			'css_class'   => 'author-id'
		),
		array(
			'name'       => 'notes_parentPostID',
			'title'      => 'Source Page ID',
			'css_class'  => 'source-page-id'
		),
		array(
			'name'       => 'parenttitle',
			'title'      => 'Source Page Title',
			'is_sortable' => false,
			'css_class'  => 'source-page-title'
		),
		array(
			'name'       => 'edit',
			'title'      => 'Edit',
			'is_sortable' => false,
			'css_class'  => 'edit'
		),
		array(
			'name'       => 'delete',
			'title'      => 'Delete',
			'is_sortable' => false,
			'css_class'  => 'delete'
		)
	);
	
	// Create sorting object based on $psncols array data
	$sortable = new BBG_CPT_Sort( $cols );
	
	// Establish some query info
	$query_args = array(
		'post_type' => '',
		'orderby'   => $sortable->get_orderby,
		'order'     => $sortable->get_order
	);
	
	// Run query
	// was: 	$notes = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . "notes ORDER BY " . $orderby . " " . $order . "" ) );
	$notes = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . "notes ORDER BY " . $query_args['orderby'] . " " . $query_args['order'] . "" ) );
	
	// Start the All Notes page ?>
	<div id="icon-edit" class="icon32 icon32-posts-post">
		<br />
	</div>
	<h2><?php _e('All Notes'); ?><!-- <a href="" class="add-new-h2">Add New</a> --></h2>
	<br />
	
	<?php // Create table markup
	if ( ! empty($notes) ) : ?>
		<table class="wp-list-table widefat fixed posts" cellspacing="0">
			<thead>
				<tr>
				<?php // Boone's Sortable Columns has a Loop syntax similar to WP's Loop
				if ( $sortable->have_columns() ) {
					while ( $sortable->have_columns() ) { 
						$sortable->the_column(); 
						$sortable->the_column_th();
					}
				} ?>
				</tr>
			</thead>
			<tbody>
				<?php // Here's we do what we normally do with the DB data
				foreach ( $notes as $note ) {
					$authorID = $note->notes_authorID ? $note->notes_authorID : 'No ID';
					$user_info = get_userdata($authorID);
					$parentID = $note->notes_parentPostID ? $note->notes_parentPostID : 'Admin';
					$nonce = wp_create_nonce( 'psnotes-note-delete' ); ?>
					<tr>
						<td class="note-id"><?php echo $note->noteID ? $note->noteID : 'No ID'; ?></td>
						<td class="date-added"><?php echo $note->notes_date ? $note->notes_date : 'No Date'; ?></td>				
						<td class="note-title"><?php echo $note->notes_title ? $note->notes_title : 'Untitled'; ?></td>
						<td class="creator"><?php echo $user_info->display_name ? $user_info->display_name : 'Anonymous'; ?></td>
						<td class="author-id"><?php echo $authorID; ?></td>
						<td class="source-page-id"><?php echo '<a href="' . get_permalink($parentID) . '" title="Posted from: ' . get_the_title($parentID) . '">' . $parentID . '</a>'; ?></td>
						<td class="source-page-title"><?php echo '<a href="' . get_permalink($parentID) . '" title="Posted from: ' . get_the_title($parentID) . '">' . get_the_title($parentID) . '</a>'; ?></td>
						<td class="edit"><a href="admin.php?page=<?php echo $_GET['page']; ?>&amp;action=edit_note&amp;noteID=<?php echo $note->noteID;?>" class="edit"><?php echo __('Edit'); ?></a></td>
						<td class="delete"><a href="admin.php?page=<?php echo $_GET['page']; ?>&amp;action=delete_note&amp;noteID=<?php echo $note->noteID;?>&amp;_wpnonce=<?php echo $nonce; ?>" class="delete" onclick="return confirm('Are you sure you want to delete this entry for ever and ever?')"><?php echo __('Delete'); ?></a></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	<?php else : ?>		
		<p><?php _e("There are no notes in existence yet."); ?></p>
	<?php endif;
}

// Displays the add/edit form
function psn_notes_editform( $args ) {
	global $wpdb, $current_user;
	$data = false;
	$psn_settings = get_option('psn_settings');
	
	// Set default values
	$defaults = array (
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
	);
	// Parse incoming $args into an array and merge with $defaults
	$args = wp_parse_args( $args, $defaults );
	// Declare each $args item as individual variable
	extract( $args, EXTR_SKIP );
	
	global $current_user;
	get_currentuserinfo();
	
	if ( $noteID !== false )
	{
		if ( intval($noteID) != $noteID )
		{
			echo "<div class=\"error\">Not a valid ID!</div>";
			return;
		}
		else
		{
			$data = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "notes WHERE noteID = '" . $noteID . " LIMIT 1'");
			if ( empty($data) )
			{
				echo "<div class=\"error\"><p>Sorry. I could not find a note with that ID.</p></div>";
				return;
			}
			$data = $data[0];
		}	
	}	
	psn_get_admin_options();
	
	$parentp_id = get_the_ID();
	$author_id = get_current_user_id();
	$notedata = psn_get_notes_by_meta( $parentp_id, $author_id, $num );

	// Filtering to use variables in the noteform_notetitle value
	if ( empty($data) ) { 
		$name = $current_user->display_name;
		$source_title = get_the_title($parentp_id);
		$first = $current_user->first_name;
		$last = $current_user->last_name;
		$date = date_i18n('F j, Y');	
	
		$replace_these	= array('%%USER_NAME%%', '%%SOURCE_PAGE_TITLE%%', '%%FIRST%%', '%%LAST%%', '%%DATE%%');
		$with_these		= array($name, $source_title, $first, $last, $date);
		$hidden_title = str_replace($replace_these, $with_these, $notetitle);
	}
	// Filtering to allow rudimentary BBCode formatting in some messages defined in Settings
	$bbcode_in = array('[b]', '[/b]', '[i]', '[/i]', '[s]', '[/s]');
	$html_out = array('<strong>', '</strong>', '<em>', '</em>', '<del>', '</del>');
	$norepeats_msg = str_replace($bbcode_in, $html_out, $norepeats_msg);
	
	/* Checks to see if user has already submitted a 'note' for this page; displays note form if not */
	if ( $allowrepeats != 1 && !empty( $notedata ) ) :
		echo '<div class="updated"><p>' . $norepeats_msg .'</p></div>';							
	else : /* Note not submitted by this user yet, so display the form */ ?>
		<!-- Beginning of note adding form -->
		<?php echo $notebefore; ?>
			<?php wp_nonce_field( 'psnotes-note-submit' ); ?>
			<input type="hidden" name="updateaction" value="<?php echo $mode?>" />
			<input type="hidden" name="noteID" value="<?php echo $noteID?>" />
			<input type="hidden" name="note_ppostID" value="<?php if ( !empty($data) ) {echo htmlspecialchars($data->notes_parentPostID);} else {the_ID();} ?>" />
			<div id="titlediv">
				<input type="hidden" id="date" name="note_date" value="<?php if ( !empty($data) ) { echo htmlspecialchars($data->notes_date); } else { echo date_i18n('F j, Y'); } ?>" />			
				<input type="hidden" name="note_authorID" value="<?php if ( !empty($data) ) { echo htmlspecialchars($data->notes_authorID);} else {echo $current_user->ID;} ?>" />
				<?php if ( $titletype == 'text' ) { ?>
					<label for="note_title"><?php _e('Title'); ?></label>
					<input type="<?php echo $titletype; ?>" id="title" name="note_title" class="input" size="<?php echo $titlesize; ?>" value="<?php if ( !empty($data) ) echo htmlspecialchars($data->notes_title); ?>" />				
				<?php } elseif ( $titletype == 'hidden' ) { ?>
					<input type="<?php echo $titletype; ?>" id="title" name="note_title" class="input" size="<?php echo $titlesize; ?>" value="<?php if ( !empty($data) ) { echo htmlspecialchars($data->notes_title); } else { echo $hidden_title; } ?>" />
				<?php } ?>
			</div><!-- #titlediv -->
			<div class="postbox" id="notefield">
				<div class="inside">
					<textarea name="note_content" id="note-textarea" class="mceEditor input maxlength" rows="<?php echo $textarea_rows; ?>" cols="<?php echo $textarea_cols; ?>"><?php if ( !empty($data) ) echo htmlspecialchars($data->notes_content); ?></textarea>
					<?php if ( $char_limit > 0 ) { ?>
						<span class="remaining-char"><span id="psnscript-chars"><?php echo $char_limit; ?></span> character limit.</span>
					<?php } ?>
				</div> <?php // closes inside ?>
			</div> <?php // closes postbox ?>
			<?php if ( $showsubmit == true ) { ?>
				<p class="submit"><input type="submit" name="save" class="button-primary" value="Save Note" /></p>
			<?php } ?>
		<?php echo $noteafter; ?>
		<!-- End of note adding form -->	
	<?php endif;
}

// Handles the timeline management page and form
function psn_timeline_manage( $args ) {
?>
	<div class="wrap">
		<?php psn_the_timeline( $args ); ?>
	</div>
<?php 
}

// Process psn_the_timeline_form (in the same manner as the psn_notes_manage function)
function psn_the_timeline( $args ) {
	global $wpdb;

	$timelineupdateaction = !empty($_REQUEST['timelineupdateaction']) ? $_REQUEST['timelineupdateaction'] : '';
	$timelineID = !empty($_REQUEST['timelineID']) ? $_REQUEST['timelineID'] : '';
	
	if ( isset($_REQUEST['action']) ):
		if ( $_REQUEST['action'] == 'delete_chosen_year' ) 
		{
			$timelineID = intval($_GET['timelineID']);
			if (empty($timelineID))
			{
				?><div class="error"><p><strong>Failure:</strong> No timeline ID given. Successfully purged the void.</p></div><?php
			}
			else
			{
				$wpdb->query("DELETE FROM " . $wpdb->prefix . "timelines WHERE timelineID = '" . $timelineID . "'");
				$sql = "SELECT timelineID FROM " . $wpdb->prefix . "timelines WHERE timelineID = '" . $timelineID . "'";
				$check = $wpdb->get_results($sql);
				if ( empty($check) || empty($check[0]->timelineID) )
				{
					?><div class="updated"><p>Note Entry <?php echo $timelineID; ?> deleted successfully.</p></div><?php
				}
				else
				{
					?><div class="error"><p><strong>Failure:</strong></p></div><?php
				}
			}
		} // end delete_note block
	endif;
	
	if ( $timelineupdateaction == 'choose_year' )
	{
		$yearauthorID = !empty($_REQUEST['timeline_authorID']) ? $_REQUEST['timeline_authorID'] : '';
		$yearauthorLast = !empty($_REQUEST['timeline_authorLast']) ? $_REQUEST['timeline_authorLast'] : '';
		$yearauthorFirst = !empty($_REQUEST['timeline_authorFirst']) ? $_REQUEST['timeline_authorFirst'] : '';
		$yeardate = !empty($_REQUEST['timeline_date']) ? $_REQUEST['timeline_date'] : '';
		$yearselected = !empty($_REQUEST['timeline_yearSelected']) ? $_REQUEST['timeline_yearSelected'] : '';
		$yearparentpostID = !empty($_REQUEST['timeline_ppostID']) ? $_REQUEST['timeline_ppostID'] : '';
		
		$sql = "INSERT INTO " . $wpdb->prefix . "timelines SET timelines_authorID = '" . $yearauthorID . "', timelines_authorLast = '" . $yearauthorLast . "', timelines_authorFirst = '" . $yearauthorFirst . "', timelines_date = '" . $yeardate . "', timelines_yearSelected = '" . $yearselected . "', timelines_parentPostID = '" . $yearparentpostID . "'";
		$wpdb->get_results($sql);
		$sql = "SELECT timelineID FROM " . $wpdb->prefix . "timelines WHERE timelines_authorID = '" . $yearauthorID . "' and timelines_authorLast = '" . $yearauthorLast . "' and timelines_authorFirst = '" . $yearauthorFirst . "' and timelines_date = '" . $yeardate . "' and timelines_yearSelected = '" . $yearselected . "' and timelines_parentPostID = '" . $yearparentpostID . "'";
		$check = $wpdb->get_results($sql);
		if ( empty($check) || empty($check[0]->timelineID) )
		{
			?><div class="error"><p><strong>Failure:</strong> Oh hum, nothing happened. Try again? </p></div><?php
		}
		else
		{
			?><div class="updated"><p>Year saved.</p></div><?php
		}
	} // end add_note block
	?>
	
	<?php // now call up the actual forms and tabular data ?>
	
	<?php
	if ( is_admin() ) {
	?>
		<div class="wrap">
			<h2><?php _e('Review Timeline Results'); ?></h2>
				<?php psn_display_timeline_results(); ?>
			<h2><?php _e('Timeline'); ?></h2>
				<?php psn_the_timeline_form( $args ); ?>
		</div>
	<?php
	} else { 
		psn_the_timeline_form( $args );
	}
}

/*
 * Lets have some descriptions...
 *
 * @since 0.9.0
 */
function psn_the_timeline_form( $args ) {
	global $wpdb, $current_user;

	// Set default values
	$defaults = array (
		'timelinebefore' => '<form name="timelineform" id="timelineform" class="wrap" method="post" action="">',
		'timelineafter'  => '</form>',
		'showsubmit' 	 => true,
		'mode' 			 => 'choose_year',
		'timelineID' 	 => false,
		'timelinetype' 	 => 'radio',
		'yr1' 			 => '1650 &#8211; 1700',
		'yr2' 			 => '1700 &#8211; 1750',
		'yr3' 			 => '1750 &#8211; 1800',
		'yr4' 			 => '1800 &#8211; 1850',
		'yr5' 			 => '1850 &#8211; 1900',
		'yr6' 			 => '1900 &#8211; 1950',
		'yr7' 			 => '1950 &#8211; Today',
	);

	// Parse incoming $args into an array and merge with $defaults
	$args = wp_parse_args( $args, $defaults );

	// Declare each $args item as individual variable
	extract( $args, EXTR_SKIP );

	$timelinedata = false;
	get_currentuserinfo();
	
	if ( $timelineID !== false )
	{
		if ( intval($timelineID) != $timelineID )
		{
			echo "<div class=\"error\">Not a valid ID!</div>";
			return;
		}
		else
		{
			$data = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "timelines WHERE timelineID = '" . $timelineID . " LIMIT 1'");
			if ( empty($data) )
			{
				echo "<div class=\"error\"><p>Sorry. I could not find a note with that ID.</p></div>";
				return;
			}
			$timelinedata = $timelinedata[0];
		}	
	}
	
	psn_get_admin_options();
	
	?>
	<!-- Beginning of Note Adding Page -->
		<?php echo $timelinebefore; ?>
			<input type="hidden" name="timelineupdateaction" value="<?php echo $mode?>" />	
		    <div class="timeline-meta">
				<input type="hidden" name="timelineID" value="<?php echo $timelineID?>" />
				<input type="hidden" name="timeline_ppostID" value="<?php if ( !empty($timelinedata) ) {echo htmlspecialchars($timelinedata->timelines_parentPostID);} else {the_ID();} ?>" />
				<input type="hidden" id="timeline-date" name="timeline_date" value="<?php if ( !empty($timelinedata) ) { echo htmlspecialchars($timelinedata->timelines_date); } else { echo date('l, F j, Y'); } ?>" />
		    </div>
			<div class="timeline-author">
				<input type="hidden" name="timeline_authorFirst" value="<?php if ( !empty($timelinedata) ) {echo htmlspecialchars($timelinedata->timelines_authorFirst);} else {echo $current_user->user_firstname;} ?>" />
				<input type="hidden" name="timeline_authorLast" value="<?php if ( !empty($timelinedata) ) {echo htmlspecialchars($timelinedata->timelines_authorLast);} else {echo $current_user->user_lastname;} ?>" />
				<input type="hidden" name="timeline_authorID" value="<?php if ( !empty($timelinedata) ) { echo htmlspecialchars($timelinedata->timelines_authorID);} else {echo $current_user->ID;} ?>" />
			</div>
				<div class="timeline" id="timeline-<?php echo $timelinetype; ?>">
					<label for="<?php _e($yr1); ?>"><input id="yr1" name="timeline_yearSelected" type="radio" value="<?php _e($yr1); ?>"/><?php _e($yr1); ?></label>
					<label for="<?php _e($yr2); ?>"><input id="yr2" name="timeline_yearSelected" type="radio" value="<?php _e($yr2); ?>"/><?php _e($yr2); ?></label>
					<label for="<?php _e($yr3); ?>"><input id="yr3" name="timeline_yearSelected" type="radio" value="<?php _e($yr3); ?>"/><?php _e($yr3); ?></label>
					<label for="<?php _e($yr4); ?>"><input id="yr4" name="timeline_yearSelected" type="radio" value="<?php _e($yr4); ?>"/><?php _e($yr4); ?></label>
					<label for="<?php _e($yr5); ?>"><input id="yr5" name="timeline_yearSelected" type="radio" value="<?php _e($yr5); ?>"/><?php _e($yr5); ?></label>
					<label for="<?php _e($yr6); ?>"><input id="yr6" name="timeline_yearSelected" type="radio" value="<?php _e($yr6); ?>"/><?php _e($yr6); ?></label>
					<label for="<?php _e($yr7); ?>"><input id="yr7" name="timeline_yearSelected" type="radio" value="<?php _e($yr7); ?>"/><?php _e($yr7); ?></label>
				</div>
			<?php if ( $showsubmit == true ) { ?>
				<p class="submit">
					<input type="submit" name="save" class="button-primary" value="Save Year" />
				</p>
			<?php } ?>
		<?php echo $timelineafter; ?>
	<?php
}

// This function displays all of the timeline selections made
function psn_display_timeline_results() 
{
	global $wpdb;
	
	$years = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "timelines ORDER BY timelines_authorLast" );
	
	if ( !empty($years) )
	{
		?>
			<table width="100%" cellpadding="3" cellspacing="3" class="widefat post">
			<thead>
			<tr>
				<th scope="col"><?php _e('ID') ?></th>
				<th scope="col"><?php _e('Date') ?></th>
				<th scope="col"><?php _e('Year Selected') ?></th>
				<th scope="col"><?php _e('User') ?></th>
				<th scope="col"><?php _e('User ID') ?></th>
				<th scope="col"><?php _e('ParentPost ID') ?></th>
				<th scope="col"><?php _e('Delete') ?></th>
			</tr>
			</thead>
		<?php
		$class = '';
		echo '<tbody>';
		
		foreach ( $years as $year )
		{
			$class = ($class == 'alternate') ? '' : 'alternate';
			?>
			<tr class="<?php echo $class; ?>">
				<td><?php echo $year->timelineID ? $year->timelineID : 'No ID'; ?></td>
				<td><?php echo $year->timelines_date ? $year->timelines_date : 'No Date'; ?></td>
				<td><?php echo $year->timelines_yearSelected ? $year->timelines_yearSelected : 'No Selection'; ?></td>
				<td><?php echo $year->timelines_authorFirst.' '.$year->timelines_authorLast ? $year->timelines_authorFirst.' '.$year->timelines_authorLast : 'Anonymous'; ?></td>
				<td><?php echo $year->timelines_authorID ? $year->timelines_authorID : 'No ID'; ?></td>
				<td><?php echo $year->timelines_parentPostID ? $year->timelines_parentPostID : 'Admin'; ?></td>
				<td><a href="admin.php?page=<?php echo $_GET['page']; ?>&amp;action=delete_chosen_year&amp;timelineID=<?php echo $year->timelineID;?>" class="delete" onclick="return confirm('Are you sure you want to delete this entry for ever and ever?')"><?php echo __('Delete'); ?></a></td>
			</tr>
			<?php
		}
		?>
		</table>
		<?php
	}
	else
	{
		?>
		<p><?php _e("Nobody has selected a year yet.") ?></p>
		<?php	
	}
}

function psn_help() { ?>

	<div class="wrap"> 
		<div id="icon-tools" class="icon32"><br /></div> 
		<h2>Help</h2>

		<h2>General</h2>
		<div class="error"><p><strong>Please note:</strong> Currently, only 1 Note form can be used on a given Page or Post &ndash; it is likely that more than one will cause multiple entries to be saved to the database with each single submission. This will hopefully be fixed in the future.</p></div>
		<div class="updated"><p><strong>New feature</strong>: You can now set default values (such as whether to display the note title on the form) on the <a href="<?php bloginfo( 'url' ); ?>/wp-admin/options-general.php?page=ps_notes_settings">PSN Settings Page</a>.</p></div>
		
		<h2>Help with PlainSight Notes Shortcodes</h2>
		<p>Shortcodes are a way to bring added functionality to WordPress Pages and Posts. They can be used in Posts, Pages, or in text widgets to display the Notes input form, or to output the content of submitted notes.</p>
		<p>More information is available in the WordPress codex on <a href="http://codex.wordpress.org/Shortcode">shortcodes</a> and on the <a href="http://codex.wordpress.org/Shortcode_API">shortcode API</a>.</p>
		<p>Shortcodes can be used in any Post, Page, or Text Widget content area by following the correct syntax. The default formats and customization options for displaying PlainSight Notes elements are outlined below &ndash; they are organized into <em>User Input</em> and <em>Display</em> sections; <em>User Input</em> being the form used to submit Notes, and <em>Display</em> being the ways that the Notes are then shown on the public site.)</p>
		<hr />
		<h3><em>User Input</em></h3>
		<p>There is only one shortcode for the Notes form, but it can be customized in a variety of different ways using attributes.</p>
		<h4>Syntax for the Notes form</h4>
		<ol>
			<li><p>Add the following in the content textarea of any WordPress Post, Page, or Text Widget wherever you want the <strong>default</strong> Notes input form to be displayed.</p> 
				<textarea class="code" readonly="readonly" cols="70" rows="2">[note_form]</textarea>
			</li>
			<li><p><strong>By default</strong> this shortcode will output output the Notes form with a text field for a title, a textarea for the note content, and a submit button, and with the following parameters:</p>
				<ul>
					<li><code>'notebefore' =&gt; '&lt;form name="notesform" id="notesform" class="wrap" method="post" action=""&gt;',</code></li>
					<li><code>'noteafter' =&gt; '&lt;/form&gt;',</code></li>
					<li><code>'titletype' =&gt; 'text',</code></li>
					<li><code>'notetitle' =&gt; 'Note',</code></li>
					<li><code>'titlesize' =&gt; 45,</code></li>
					<li><code>'showsubmit' =&gt; true,</code></li>
					<li><code>'mode' =&gt; 'add_note',</code></li>
					<li><code>'noteID' =&gt; false,</code></li>
					<li><code>'allowrepeats' =&gt; true,</code></li>
					<li><code>'char_limit' =&gt; 2500,</code></li>
					<li><code>'textarea_rows' =&gt; 10,</code></li>
					<li><code>'textarea_cols' =&gt; 80,</code></li>
				</ul>
			</li>
		</ol>
		<h4>Parameters (How to Customize)</h4>
		<ul>
			<li><p><strong>notebefore</strong></p>
				<p> &bull; (<em>string</em>) This governs the &lt;form&gt; opening tag and its attributes. <em><strong>Break Warning</strong>: Do not alter this unless you are familiar with working with forms and have a reason to.</em> That said, you can use this to blend this form with anther, or to change the form id or class, but must declare the <em>entire</em> statement:</p>
				<p><code>[note_form notebefore="&lt;form name="notesform" id="notesform" class="wrap" method="post" action=""&gt;"]</code></p></li>
			<li><p><strong>noteafter</strong></p>
			<p> &bull; (<em>string</em>) Governs the &lt;/form&gt; closing tag. This needs to agree with <code>notebefore</code>.</p></li>	
			<li><p><strong>titletype</strong></p>
			<p> &bull; (<em>string</em>) This defines the input <code>type</code> of the title form field. The default is "text", but this can be changed to "hidden" to hide the title form field and force a pre-set default title. (Defaults to "Note" see parameter for <code>notetitle</code> to see about customizing default title.) For example:</p>
			<p><code>[note_form titletype="hidden"]</code></p></li>		
			<li><p><strong>notetitle</strong></p>
			<p> &bull; (<em>string</em>) This defines the default title <strong>if</strong> <code>titletype</code> has been set to "hidden". This can be any string of plain text contained within double quotes, but cannot itself contain double quotes, (single quotes should be okay). For example:</p>
			<p>Okay: <code>[note_form titletype="hidden" notetitle="My New Title"]</code></p>
			<p>Not Okay: <code>[note_form titletype="hidden" notetitle="My "New" Title"]</code></p></li>
			<li><p><strong>titlesize</strong></p>
			<p> &bull; (<em>integer</em>) Defines the size (width) of the title input field, must be a whole integer. For example:</p>
			<p><code>[note_form titlesize="25"]</code></p></li>
			<li><p><strong>showsubmit</strong></p>
			<p> &bull; (<em>string</em>) Shows (true) or hides (false) the submit button at the end of the form. Not recommended to hide unless integrating with another form (see <code>notebefore</code> parameter). <strong>Requires a true [default] (1) or false (2) value.</strong> For example, to <em>hide</em> the submit button:</p>
			<p><code>[note_form showsubmit="0"]</code></p></li>
			<li><p><strong>mode</strong></p>
			<p> &bull; (<em>string</em>) Determines the way the database should handle the note. <em><strong>Break Warning</strong>: Not recommended to alter this.</em>.</p></li>
			<li><p><strong>noteID</strong></p>
			<p> &bull; (<em>string</em>) Determines the way the database should handle the note. <em><strong>Break Warning</strong>: Not recommended to alter this.</em>.</p></li>
			<li><p><strong>allowrepeats</strong></p>
			<p> &bull; (<em>string</em>) Defines whether a user should be allowed to submit multiple notes for a given Note form (from a given page) or if s/he should be only allowed to submit 1 Note per instance of the form. This might be useful if using the Notes plugin for users to input answers to a question on a quiz or poll where they should only have 1 chance to answer. <strong>Requires a true [default] (1) or false (2) value.</strong> For example, to disallow repeat submissions from a given form, use:</p>
			<p><code>[note_form allowrepeats="0"]</code></p></li>
			<li><p><strong>char_limit</strong></p>
			<p> &bull; (<em>integer</em>) This works with custom JavaScript to output the character limit, if in use. <strong>[Pending]</strong></p></li>
			<li><p><strong>textarea_rows</strong></p>
			<p> &bull; (<em>integer</em>) This determines the height of the Notes content textarea. Requires a positive whole integer, such as:</p>
			<p><code>[note_form textarea_rows="70"]</code></p></li>
			<li><p><strong>textarea_cols</strong></p>
			<p> &bull; (<em>integer</em>) This determines the width of the Notes content textarea. Requires a positive whole integer, such as:</p>
			<p><code>[note_form textarea_cols="120"]</code></p></li>
		</ul>
		<h4>Some Examples</h4>
		<ol>
			<li><p>If you need a form where the user shouldn't need to enter a title, but with a title of your own choosing, copy and paste the following shortcode and replace My New Title, with your own custom title:</p><textarea class="code" readonly="readonly" cols="70" rows="2">[note_form titletype="hidden" notetitle="My New Title"]</textarea></li>
			<li><p>To display a form where the user can choose his/her own title but cannot submit more than 1 note per Post/Page, copy and paste the following shortcode:</p><textarea class="code" readonly="readonly" cols="70" rows="2">[note_form allowrepeats="0"]</textarea></li>
			<li><p>To output a form where the user cannot enter a title of his/her own and can only submit once, copy and paste the following shortcode (see Example 1 if you also want to customize the title to something other than "Note"):</p><textarea class="code" readonly="readonly" cols="70" rows="2">[note_form titletype="hidden" allowrepeats="0"]</textarea></li>
		</ol>
		
		<hr />
		<h3><em>Display</em></h3>
		<div class="updated"><p>These shortcodes <em>are</em> currently working. The customization how-to is coming soon.</p></div>
		<p>There are a number of different ways Notes can be displayed on the public site, including:</p>
		<ol>
			<li>A single, specific note identified by its unique ID (see the first column of the <a href="<?php bloginfo('url'); ?>/wp-admin/admin.php?page=notes">All Notes</a> table).</li>
			<li>A series of the most recent notes (defaults to 4, but can be changed) posted by all users of the site.</li>
			<li>A series of all notes created by a given user (author), posted website-wide.</li>
			<li>A series of all notes posted from a given WP Page/Post, created by any user.</li>
			<li>A series of all notes posted from a given WP Page/Post <strong>and</strong> created by a given user (so only those notes created by user <em>n</em> and submitted from page id <em>x</em>.)</li>
		</ol>

		<h4>Single Note by ID</h4>
			<p><strong>[Pending]</strong> Use the shortcode: <code>[ ]</code></p>
			<h5>Defaults</h5>
		
		<hr />
		<h4>Recent Notes</h4>
			<p>Use the shortcode: <code>[recent_notes]</code></p>
			<h5>Defaults</h5>
			<ul>
				<li><code>'howmany' =&gt; 4</code></li>
				<li><code>'orderby' =&gt; 'noteID'</code></li>
				<li><code>'order' =&gt; 'ASC'</code></li>
				<li><code>'wrap_class' =&gt; 'note'</code></li>
				<li><code>'title_wrap' =&gt; 'h4'</code></li>
				<li><code>'length' =&gt; 'full'</code></li>
			</ul>
		
		<hr />
		<h4>Notes by User</h4>
			<p>Use the shortcode: <code>[note_by_author author_id="1"]</code><br />The author_id must be the desired user's ID (see the <a href="<?php bloginfo('url'); ?>/wp-admin/admin.php?page=notes">All Notes</a> table).</p>
			<h5>Defaults</h5>
			<ul>
				<li><code>'author_id' =&gt; (int) $author_id</code></li>
				<li><code>'howmany' =&gt; $howmany</code></li>
				<li><code>'wrap_class' =&gt; 'note'</code></li>
				<li><code>'title_wrap' =&gt; 'h4'</code></li>
				<li><code>'length' =&gt; 'full'</code></li>
				<li><code>'date_first' =&gt; true</code></li>
				<li><code>'show_avatar' =&gt; true</code></li>
			</ul>
			
		<hr />
		<h4>Notes from Page/Post</h4>
			<p>Use the shortcode: <code>[note_by_parent parentp_id="1"]</code><br />The parentp_id must be the desired Notes's parent postID (see the <a href="<?php bloginfo('url'); ?>/wp-admin/admin.php?page=notes">All Notes</a> table).</p>
			<h5>Defaults</h5>
			<ul>
				<li><code>'parentp_id' =&gt; $parentp_id</code></li>	
				<li><code>'howmany' =&gt; $howmany</code></li>
				<li><code>'wrap_class' =&gt; 'note'</code></li>
				<li><code>'title_wrap' =&gt; 'h4'</code></li>
				<li><code>'length' =&gt; 'full'</code></li>
			</ul>
		
		<hr />
		<h4>Notes by User &amp; from Page/Post</h4>
			<p>Use the shortcode: <code>[note_by_meta author_id="1" parentp_id="1"]</code><br />The author_id must be the desired user's ID <em>and</em> the parentp_id must be the desired Notes's parent postID (see the <a href="<?php bloginfo('url'); ?>/wp-admin/admin.php?page=notes">All Notes</a> table).</p>
			<h5>Defaults</h5>
			<ul>
				<li><code>'parentp_id' =&gt; (int) $parentp_id</code></li>
				<li><code>'author_id' =&gt; (int) $author_id</code></li>
				<li><code>'howmany' =&gt; (int) $howmany</code></li>
				<li><code>'wrap_class' =&gt; 'note'</code></li>
				<li><code>'title_wrap' =&gt; 'h4'</code></li>
				<li><code>'length' =&gt; 'full'</code></li>
			</ul>
		
		<hr />
		<h4>Hide selected Content until user completes Note</h4>
			<p>Use the start and end shortcodes (<strong>note that you need both</strong>) <code>[psn_content_vis]</code>Content<code>[/psn_content_vis]</code> <em>where</em> Content is whatever WP Page content you want to hide until the user completes the note on the same page.</p>
			<p><strong>Note:</strong> By default only works if used on the same page the Note Form is displayed on or submitted from. (If using a shortcode to display the form, for example, whatever page has the [note_form] shortcode could also use this shortcode.) To check if someone has posted a Note from a *different* page, need to use the parentp_id attribute and specify the desired page ID. This also works for checking against a user other than the current user.</p>
			<p>For example, to display the content inside the <code>[psn_content_vis]</code> codes only when the Note on page ID 397 has been submitted, you would use:<br /><code>[psn_content_vis parentp_id="397"]The content[/psn_content_vis]</code></p>
			<h5>Defaults</h5>
			<ul>
				<li><code>'parentp_id' =&gt; ''</code></li>
				<li><code>'author_id' =&gt; ''</code></li>
				<li><code>'message' =&gt; 'Complete Note to continue.'</code></li>
			</ul>
	</div><?php // close .wrap ?>
<?php	
}