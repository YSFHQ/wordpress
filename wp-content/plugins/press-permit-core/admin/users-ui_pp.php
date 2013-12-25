<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_filter('manage_users_columns', array('PP_AdminUsers', 'flt_users_columns'));
add_action('manage_users_custom_column', array('PP_AdminUsers', 'flt_users_custom_column'), 99, 3); // filter late in case other plugin filters do not retain passed value

add_filter('pre_user_query', array('PP_AdminUsers', 'flt_user_query_exceptions' ) );

class PP_AdminUsers {
	public static function flt_users_columns($defaults) {
		$defaults['pp_groups'] = __('Groups', 'pp');
		
		$title = __( 'Click to show only users who have supplemental roles', 'pp' );
		$defaults['pp_roles'] = sprintf( __('Roles %1$s*%2$s', 'pp'), "<a href='?pp_has_roles=1' title='$title'>", '</a>' );
		
		unset($defaults['role']);
		unset($defaults['bbp_user_role']);
		
		$title = __( 'Click to show only users who have exceptions', 'pp' );
		$defaults['pp_exceptions'] = sprintf( __('Exceptions %1$s*%2$s', 'pp'), "<a href='?pp_has_exceptions=1' title='$title'>", '</a>' );
		return $defaults;
	}

	public static function flt_users_custom_column($content = '', $column_name, $id) {
		switch( $column_name ) {
			case 'pp_groups' :
				global $wp_list_table;
				
				//if ( ! $agent_type = apply_filters( 'pp_query_group_type', '' ) )
				//	$agent_type = 'pp_group';
				
				static $all_groups;
				static $all_group_types;
				
				if ( ! isset($all_groups) ) {
					$all_groups = array();
					$all_group_types = pp_get_group_types( array( 'editable' => true ) );
				}

				$all_group_names = array();
				
				foreach( $all_group_types as $agent_type ) {
					if ( ! isset($all_groups[$agent_type]) )
						$all_groups[$agent_type] = pp_get_groups( $agent_type );
		
					if ( empty($all_groups[$agent_type]) )
						continue;

					$group_names = array();
					
					if ( $group_ids = pp_get_groups_for_user( $id, $agent_type, array( 'cols' => 'id', 'query_user_ids' => array_keys( $wp_list_table->items ) ) ) ) {
						foreach ( array_keys($group_ids) as $group_id ) {
							if ( isset( $all_groups[$agent_type][$group_id] ) ) {
								if ( empty($all_groups[$agent_type][$group_id]->metagroup_type) || ( 'wp_role' != $all_groups[$agent_type][$group_id]->metagroup_type ) ) {
									$group_names [ $all_groups[$agent_type][$group_id]->name ] = $group_id;
								}
							}
						}

						if ( $group_names ) {
							uksort($group_names, "strnatcasecmp");

							foreach( $group_names as $name => $_id )
								$all_group_names[] = "<a href='" . "admin.php?page=pp-edit-permissions&amp;action=edit&amp;agent_type=$agent_type&amp;agent_id=$_id'>$name</a>";

							//$group_names = array_merge( $group_names, $this_group_names );
						}
					}
				}
				
				return implode(", ", $all_group_names);
				break;
				
			case 'pp_roles' :
				global $wp_list_table, $wp_roles;
				static $role_info;
				
				$role_str = '';
				
				if ( ! isset($role_info) )
					$role_info = ppc_count_assigned_roles( 'user', array( 'query_agent_ids' => array_keys( $wp_list_table->items ) ) );
				
				$user_object = new WP_User( (int) $id );

				static $hide_roles;
				if ( ! isset($hide_roles) ) {
					$hide_roles = ( ! defined('bbp_get_version') ) ? array( 'bbp_participant', 'bbp_moderator', 'bbp_keymaster', 'bbp_blocked', 'bbp_spectator' ) : array();
					$hide_roles = apply_filters( 'pp_hide_roles', $hide_roles );
				}
				$user_object->roles = array_diff( $user_object->roles, $hide_roles );
				
				$role_titles = array();
				foreach( $user_object->roles as $role_name ) {
					if ( isset( $wp_roles->role_names[$role_name] ) )
						$role_titles []= $wp_roles->role_names[$role_name];
				}
				
				if ( isset( $role_info[$id] ) && isset( $role_info[$id]['roles'] ) )
					$role_titles = array_merge( $role_titles, array_keys($role_info[$id]['roles']) );
					
				$display_limit = 3;
				if ( count($role_titles) > $display_limit ) {
					$excess = count($role_titles) - $display_limit;
					$role_titles = array_slice( $role_titles, 0, $display_limit	);
					$role_titles []= sprintf( __('%s&nbsp;more', 'pp'), $excess );
				}
	
				$role_str = '<span class="pp-group-site-roles">' . implode( ', ', $role_titles ) . '</span>';
				
				if ( current_user_can('edit_user', $id) && current_user_can('pp_assign_roles') ) {
					$edit_link = "admin.php?page=pp-edit-permissions&amp;action=edit&amp;agent_id=$id&amp;agent_type=user";
					$role_str = "<a href=\"$edit_link\">$role_str</a><br />";
				}
				
				return $role_str;
				break;
				
			case 'pp_exceptions' :
				global $wp_list_table;
				return ppc_list_agent_exceptions( 'user', $id, array( 'query_agent_ids' => array_keys( $wp_list_table->items ) ) );
				break;
			
			default :
				return $content;
		}
	}
	
	public static function flt_user_query_exceptions( $query_obj ) {
		if ( ! empty( $_REQUEST['pp_user_exceptions'] ) ) {
			global $wpdb;
			$query_obj->query_where .= " AND ID IN ( SELECT agent_id FROM $wpdb->ppc_exceptions AS e INNER JOIN $wpdb->ppc_exception_items AS i ON e.exception_id = i.exception_id WHERE e.agent_type = 'user' )";
		}
		
		if ( ! empty( $_REQUEST['pp_user_roles'] ) ) {
			global $wpdb;
			$query_obj->query_where .= " AND ID IN ( SELECT agent_id FROM $wpdb->ppc_roles WHERE agent_type = 'user' )";
		}

		if ( ! empty( $_REQUEST['pp_user_perms'] ) ) {
			global $wpdb;
			$query_obj->query_where .= " AND ( ID IN ( SELECT agent_id FROM $wpdb->ppc_roles WHERE agent_type = 'user' ) OR ID IN ( SELECT agent_id FROM $wpdb->ppc_exceptions AS e INNER JOIN $wpdb->ppc_exception_items AS i ON e.exception_id = i.exception_id WHERE e.agent_type = 'user' ) )";
		}
		
		if ( ! empty( $_REQUEST['pp_has_exceptions'] ) ) {
			global $wpdb;
			$query_obj->query_where .= " AND ID IN ( SELECT agent_id FROM $wpdb->ppc_exceptions AS e INNER JOIN $wpdb->ppc_exception_items AS i ON e.exception_id = i.exception_id WHERE e.agent_type = 'user' ) OR ID IN ( SELECT user_id FROM $wpdb->pp_group_members AS ug INNER JOIN $wpdb->ppc_exceptions AS e ON e.agent_id = ug.group_id AND e.agent_type = 'pp_group' )";
		}
		
		if ( ! empty( $_REQUEST['pp_has_roles'] ) ) {
			global $wpdb;
			$query_obj->query_where .= " AND ID IN ( SELECT agent_id FROM $wpdb->ppc_roles WHERE agent_type = 'user' ) OR ID IN ( SELECT user_id FROM $wpdb->pp_group_members AS ug INNER JOIN $wpdb->ppc_roles AS r ON r.agent_id = ug.group_id AND r.agent_type = 'pp_group' )";
		}
		
		if ( ! empty( $_REQUEST['pp_has_perms'] ) ) {
			global $wpdb;
			$query_obj->query_where .= " AND ID IN ( SELECT agent_id FROM $wpdb->ppc_exceptions AS e INNER JOIN $wpdb->ppc_exception_items AS i ON e.exception_id = i.exception_id WHERE e.agent_type = 'user' ) OR ID IN ( SELECT user_id FROM $wpdb->pp_group_members AS ug INNER JOIN $wpdb->ppc_exceptions AS e ON e.agent_id = ug.group_id AND e.agent_type = 'pp_group' ) OR ID IN ( SELECT agent_id FROM $wpdb->ppc_roles WHERE agent_type = 'user' ) OR ID IN ( SELECT user_id FROM $wpdb->pp_group_members AS ug INNER JOIN $wpdb->ppc_roles AS r ON r.agent_id = ug.group_id AND r.agent_type = 'pp_group' )";
		}
		
		return $query_obj;
	}
	
} // end class
