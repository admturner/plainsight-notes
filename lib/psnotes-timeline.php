<?php 
/**
 * [Deprecated] Handles the timeline management page and form
 *
 * @deprecated
 */

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
?>