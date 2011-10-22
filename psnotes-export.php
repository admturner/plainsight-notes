<?php
/**
 * PS Notes Export Administration API
 * 
 * Takes GET data from admin management function in 
 * plainsightnotes-admin.php and then generates a CSV file for
 * export, after cleaning up some of the content. Adapted from 
 * Amol Nirmala Waman's "Navayan CSV Export" plugin.  
 * 
 * @package WordPress
 * @subpackage PlainSight-Notes
 * @since 0.9.8
 */

function escapeCSV( $str ) {
	// if , or " exist then "$str"
	if ( strpos($str, ',') !== false || strpos($str, '"') !== false ) {
		$str = str_replace('"', '""', $str);
		$str =  '"'. $str .'"';
	}
	// replace \r\n (new line) with " "
	$str = str_replace('\r\n', ' ', $str);
		
	return $str . "\t";
}

function psn_generate_csv( $args = array() ) {
	global $wpdb;
	
	$defaults = array( 
		'psncontent' => 'all', 
		'author' => false, 
		'status' => false
	);
	$args = wp_parse_args( $args, $defaults );
	
	$psncontent_type = isset($_GET['psncontent']) ? $_GET['psncontent'] : '';
	
	if ( $psncontent_type ) {
		
		$field = '';
		$getfield = '';
		$notes_table_name = $wpdb->prefix . "notes";
		
		$result = $wpdb->get_results("SELECT * FROM $notes_table_name");
		
		$r1 = mysql_query("SELECT * FROM ".$notes_table_name);
		$fields_num = mysql_num_fields($r1);
		
		for ( $i=0; $i<$fields_num; $i++ ) {
			$field = mysql_fetch_field($r1);
			$field = (object) $field;
			$getfield .= $field -> name . "\t";
		}
		
		$getfield .= 'Author' . "\t";
		$getfield .= 'Note posted from' . "\t";
		
		$sub = substr_replace($getfield, '', -1);
		$fields = $sub; // get fields name
		$each_field = explode("\t", $sub);
		
		// get fields values without last comma
		foreach ( $result as $row ) {
			for ( $s = 0; $s < $fields_num; $s++ ) {
				if ( $s == 0 ) {
					$fields .= "\n";
					$fields = ucfirst( str_replace( 'notes_', '', $fields ) );
				}
				$fields .= escapeCSV( $row -> $each_field[$s] );
			}
			$user_info = get_userdata($row->notes_authorID);
			$fields .= escapeCSV( $user_info->display_name );
			$fields .= escapeCSV( get_the_title($row->notes_parentPostID) );
			
			$fields = substr_replace($fields, '', -1);
		}
		
		$sitename = sanitize_key( get_bloginfo( 'name' ) );
		if ( ! empty($sitename) ) $sitename .= '.';
		$filename = $sitename . 'wp.psnotes.' . date( 'Y-m-d' ) . '.csv';
	
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Content-Type: text/csv; charset=' . get_option( 'blog_charset' ), true );
		
		echo $fields;
	}
}
?>