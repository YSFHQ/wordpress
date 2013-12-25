<?php
class PP_Users {
	public static function get_users( $args = array() ) {
		$defaults = array( 'cols' => 'all' );
		$args = array_merge( $defaults, (array) $args );
		extract($args, EXTR_SKIP);

		if ( 'id' == $cols )
			$qcols = 'ID';
		elseif ( 'id_name' == $cols )
			$qcols = "ID, user_login AS display_name";	// calling code assumes display_name property for user or group object
		elseif ( 'id_displayname' == $cols )
			$qcols = "ID, display_name";
		elseif ( 'all' == $cols )
			$qcols = "*";
		else
			$qcols = $cols;
			
		global $wpdb;
		
		$orderby = ( $cols == 'id' ) ? '' : 'ORDER BY display_name';

		//global $ppms_netwide_groups;
		//if ( PP_MULTISITE && ! empty($ppms_netwide_groups) && ! defined( 'PP_FORCE_ALL_SITE_USERS' ) )
		//	$qry = "SELECT $qcols FROM $wpdb->users INNER JOIN $wpdb->usermeta AS um ON $wpdb->users.ID = um.user_id AND um.meta_key = '{$wpdb->prefix}capabilities' $orderby";
		//else
			$qry = "SELECT $qcols FROM $wpdb->users $orderby";

		if ( 'id' == $cols )
			return $wpdb->get_col( $qry );
		else
			return $wpdb->get_results( $qry );
	}
} // end class
