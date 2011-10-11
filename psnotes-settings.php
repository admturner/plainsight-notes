<?php 
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
 * @since 0.9.7
 * 
 * @todo Clean up (bring everything up to its own line)
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
		<!-- <p><a href="#TB_inline?height=500&width=800&inlineId=note_preview" class="thickbox">Preview</a> note form with current settings. (Not including live page styles.)</p>
		<div id="note_preview" style="display:none;">
				<?php 
					
					// $psn_settings = get_option('psn_settings');
					// psn_notes_editform( 'notebefore=&noteafter=' );
				?>
		</div> -->
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
?>