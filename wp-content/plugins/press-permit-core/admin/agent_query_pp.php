<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once( ABSPATH . '/wp-admin/includes/user.php' );
require_once( dirname(__FILE__).'/admin-api_pp.php' );

if ( ! isset($_GET['pp_agent_search']) )
	return;

$orig_search_str = $_GET['pp_agent_search'];
$search_str = sanitize_text_field($_GET['pp_agent_search']);
$agent_type = pp_sanitize_key($_GET['pp_agent_type']);
$agent_id = (int) $_GET['pp_agent_id'];
$topic = pp_sanitize_key($_GET['pp_topic']);
$omit_admins = (bool) $_GET['pp_omit_admins'];
$context = ( isset($_GET['pp_context']) ) ? pp_sanitize_key($_GET['pp_context']) : '';

if ( ! function_exists('ppc_administrator_roles') ) {
function ppc_administrator_roles() {			
	// WP roles containing the 'pp_administer_content' cap are always honored regardless of object or term restritions
	global $wp_roles;
	$admin_roles = array();
	
	if ( isset($wp_roles->role_objects) ) {
		foreach ( array_keys($wp_roles->role_objects) as $wp_role_name ) {
			if ( ! empty($wp_roles->role_objects[$wp_role_name]->capabilities['pp_administer_content']) ) {
				$admin_roles[$wp_role_name] = true;
			}
		}
	}
	
	return $admin_roles;
}
}

if ( 'user' == $agent_type ) {
	global $wpdb;

	if ( PP_MULTISITE && apply_filters( 'pp_user_search_site_only', true, compact( 'agent_type', 'agent_id', 'topic', 'context', 'omit_admins' ) ) ) {
		global $current_blog;
		$blog_prefix = $wpdb->get_blog_prefix($current_blog->blog_id);
		$join = "INNER JOIN $wpdb->usermeta AS um ON um.user_id = $wpdb->users.ID AND um.meta_key = '{$blog_prefix}capabilities'";
	} else {
		$join = '';
	}

	$role_filter = sanitize_text_field($_GET['pp_role_search']);
	$orderby = ( 0 === strpos( $orig_search_str, ' ' ) ) ? 'user_login' : 'user_registered DESC';
	
	if ( ! $search_str && ! $role_filter ) {
		$results = $wpdb->get_results("SELECT ID, user_login, display_name FROM $wpdb->users $join ORDER BY $orderby LIMIT 1000");

	} elseif ( $search = new WP_User_Query( 'search=*' . $search_str . "*&role=$role_filter" ) ) {
		$results = $wpdb->get_results( "SELECT ID, user_login, display_name $search->query_from $join $search->query_where ORDER BY $orderby LIMIT 1000" );
	}

	if ( $results ) {	
		$omit_users = array();
		
		// determine all current users for group in question
		if ( ! empty( $agent_id ) ) {
			$topic = isset( $topic ) ? $topic : '';
			$group_type = ( $context && pp_group_type_exists( $context ) ) ? $context : 'pp_group';
			$omit_users = pp_get_group_members( $agent_id, $group_type, 'id', array( 'member_type' => $topic, 'status' => 'any' ) );
		} elseif ( $omit_admins ) {
			if ( $admin_roles = ppc_administrator_roles() ) {	// Administrators can't be excluded; no need to include or enable them
				global $wpdb;
				$role_csv = implode( "','", array_keys($admin_roles) );
				$omit_users = $wpdb->get_col( "SELECT u.ID FROM $wpdb->users AS u INNER JOIN $wpdb->pp_group_members AS gm ON u.ID = gm.user_id INNER JOIN $wpdb->pp_groups AS g ON gm.group_id = g.ID WHERE g.metagroup_type = 'wp_role' AND g.metagroup_id IN ('$role_csv')" );
			}
		}

		foreach( $results as $row ) {
			if ( ! in_array( $row->ID, $omit_users ) ) {
				$title = ( $row->user_login != $row->display_name ) ? " title='" . esc_attr($row->display_name) . "'" : '';
				echo "<option value='$row->ID' class='pp-new-selection'{$title}>$row->user_login</option>";
			}
		}
	}
} else {
	$reqd_caps = apply_filters( 'pp_edit_groups_reqd_caps', array('pp_edit_groups') );

	// determine all currently stored groups (of any status) for user in question (not necessarily logged user)
	if ( ! empty( $agent_id ) )
		$omit_groups = pp_get_groups_for_user( $agent_id, $agent_type, array( 'status' => 'any' ) );
	else
		$omit_groups = array();

	if ( $groups = pp_get_groups( $agent_type, array( 'filtering' => true, 'include_norole_groups' => false, 'reqd_caps' => $reqd_caps, 'search' => $search_str ) ) ) {
		foreach( $groups as $row )
			if ( ( empty($row->metagroup_id) || is_null($row->metagroup_id) ) && ! isset( $omit_groups[$row->ID] ) )
				echo "<option value='$row->ID'>$row->name</option>";
	}
}

