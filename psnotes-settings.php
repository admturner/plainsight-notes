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
 * @todo Clean up
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
			'Note form width', 
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
		
		// Call noteform send/don't send email confirmation w/in note_form group
		add_settings_field(
			'note_form_should_email',
			'Email copy of published note to',
			array('PSNSettings', 'note_form_should_email_fn'),
			'note_form_settings',
			'note_form_group');
		
		// Call noteform email message within note_form group
		add_settings_field(
			'note_form_email_msg',
			'Email message',
			array('PSNSettings', 'note_form_email_msg_fn'),
			'note_form_settings',
			'note_form_group');
		
		// Call noteform email recipients within note_form group
		add_settings_field(
			'note_form_email_recipients',
			'Additional email recipients',
			array('PSNSettings', 'note_form_email_recipients_fn'),
			'note_form_settings',
			'note_form_group');
		
		add_settings_field(
			'note_form_reset_options',
			'Restore defaults on reactivation?',
			array('PSNSettings', 'note_form_reset_options_fn'),
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
	
	function note_form_should_email_fn() {
		$psn_settings = get_option('psn_settings');
		$options = array( 'nobody', 'user only', 'user and additional', 'additional only' );
		
		foreach ( $options as $option ) : 
			$checked = ($psn_settings['noteform_should_email'] == strtolower($option)) ? ' checked="checked" ' : '';
			?>
			<label>
				<input id="noteform_should_email_<?php echo $option; ?>"
						 name="psn_settings[noteform_should_email]"
						 class="code"
						 value="<?php echo $option; ?>"
						 type="radio"
						 <?php echo $checked; ?> />
				<?php echo ucfirst( $option ); ?>
			</label><br />
		<?php 
		endforeach;
	}
	
	function note_form_email_msg_fn() {
		$psn_settings = get_option('psn_settings');
		
		if ( $psn_settings['noteform_should_email'] != 'nobody' ) {
			?>
<textarea name="psn_settings[noteform_email_msg]" id="noteform_email_msg" class="text" cols="80" rows="10"><?php echo wp_kses_stripslashes($psn_settings['noteform_email_msg']); ?></textarea><br />
			<span class="description">Message body to precede Note metadata (author, date, etc.) and content in email.</span>
		<?php
		} else {
			echo '<span class="description">Email not being sent, so message not needed.</span>';
		}
	}
	
	function note_form_email_recipients_fn() {
		$psn_settings = get_option('psn_settings');
		
		if ( $psn_settings['noteform_should_email'] != 'nobody' ) {
			?>
			<span class="description">If you would like to send the results of any quiz to an additional user(s), like a teacher, select from the menu(s) below.</span><br />
			<label for="psn_settings[recipient_one_email]"><?php _e("Additional recipient one") ?></label>
			<select name="psn_settings[recipient_one_email]">
				<option>Choose a user</option>
				<?php $blogusers = get_users_of_blog();
				foreach ( $blogusers as $user) : ?>
					<option value="<?php echo $user->user_email; ?>"<?php if ( $psn_settings['recipient_one_email'] == $user->user_email) echo ' selected="selected"'; ?>><?php echo $user->user_login . ' - '. $user->display_name . ' (' . $user->user_email . ')'; ?></option>
				<?php endforeach; ?>
			</select>
			<br />
			<label for="psn_settings[recipient_two_email]"><?php _e("Additional recipient two") ?></label>
			<select name="psn_settings[recipient_two_email]">
				<option>Choose a user</option>
				<?php $blogusers = get_users_of_blog();
				foreach ( $blogusers as $user) : ?>
					<option value="<?php echo $user->user_email; ?>"<?php if ( $psn_settings['recipient_two_email'] == $user->user_email) echo ' selected="selected"'; ?>><?php echo $user->user_login . ' - '. $user->display_name . ' (' . $user->user_email . ')'; ?></option>
				<?php endforeach; ?>
			</select>		
		<?php
		} else {
			echo '<span class="description">Email not being sent, so additional recipients not needed.</span>';
		}
	}
	
	function note_form_reset_options_fn() {
		$psn_settings = get_option('psn_settings');
		?>
		<input id="noteform_reset" 
				 name="psn_settings[noteform_reset]" 
				 type="checkbox" 
				 class="code" 
				 value="1" 
				 <?php checked( $psn_settings['noteform_reset'], 1 ); ?> />
		<span class="description"><span class="error">Warning:</span> If checked, restores default settings upon plugin deactivation/reactivation.</span>
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
			add_settings_error('psn_settings', 'settings_updated', __('<em>Note form title display:</em> Only 2 options, Text or Hidden, are accepted. Please try again.'));
		}

		// validate noteform should_email as part of array( 'nobody', 'user and additional', 'user only', 'additional only' )
		$opts = array( 'nobody', 'user and additional', 'user only', 'additional only' );
		if ( in_array( $input['noteform_should_email'], $opts ) ) {
			$psn_settings['noteform_should_email'] = strtolower($input['noteform_should_email']);
		} else {
			add_settings_error('psn_settings', 'settings_updated', __('<em>Note form email option:</em> Needs to be one of the four given options. Please try again.'));
		}

		// validate noteform character limit as an integer
		if (sprintf("%.0f", $input['noteform_charlimit']) == $input['noteform_charlimit']) {
			$psn_settings['noteform_charlimit'] = $input['noteform_charlimit'];
		} else {
			add_settings_error('psn_settings', 'settings_updated', __('<em>Note form character limit:</em> Only integers are accepted. Please use a number.'));
		}

		// validate noteform repeats checkbox and reset on reactivation checkbox
		$psn_settings['noteform_repeats'] = $input['noteform_repeats'];
		$psn_settings['noteform_reset'] = $input['noteform_reset'];

		// strip html and php tags from input and output noteform data
		$psn_settings['noteform_notetitle'] = strip_tags($input['noteform_notetitle']);
		$psn_settings['noteform_norepeats_msg'] = strip_tags($input['noteform_norepeats_msg']);
		$psn_settings['noteform_email_msg'] = strip_tags($input['noteform_email_msg']);

		// pass along email recipients' email addresses
		$psn_settings['recipient_one_email'] = $input['recipient_one_email'];
		$psn_settings['recipient_two_email'] = $input['recipient_two_email'];
		
		// And finally, spit out the validated data
		return $psn_settings;
	}
	
	function add_psnsettings_defaults_fn() {
		$psn_settings = get_option('psn_settings');
		if ( $psn_settings['noteform_reset'] != 0 ) {
			$defaults = array(
					'noteform_width' => '80',
					'noteform_titletype' =>	'text',
					'noteform_notetitle' => 'A Note',
					'noteform_repeats' => 1,
					'noteform_charlimit' => '2500',
					'noteform_norepeats_msg' => 'You have already completed this.',
					'noteform_should_email' => 'nobody',
					'noteform_email_msg' => 'This message to the user will precede the Note in the email body.',
					'noteform_reset' => 0,
					'recipient_one_email' => '',
					'recipient_two_email' => ''
				);
			update_option( 'psn_settings', $defaults );
		}
	}
} // end PSNSettings class

// Hook settings into the admin init and menu processes
add_action('admin_init', array('PSNSettings', 'init'));
add_action('admin_menu', array('PSNSettings', 'admin_menus'));
?>