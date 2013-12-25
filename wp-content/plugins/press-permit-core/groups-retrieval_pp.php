<?php
/**
 * PP_GroupRetrieval class
 * 
 * @package PP
 * @author Kevin Behrens <kevin@agapetry.net>
 * @copyright Copyright (c) 2011-2013, Agapetry Creations LLC
 * 
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

do_action( 'pp_groups_retrieval' );
	
class PP_GroupRetrieval {
	// returns all groups
	public static function get_pp_groups( $args = array() ) {
		global $wpdb;
		
		$defaults = array( 'agent_type' => 'pp_group', 'cols' => "DISTINCT ID, group_name AS name, group_description, metagroup_type, metagroup_id", 'omit_ids' => array(), 'ids' => array(), 'where' => '', 
		'join' => '', 'require_meta_types' => array(), 'skip_meta_types' => array(), 'search' => '', 'order_by' => 'group_name'
		);
		$args = array_merge( $defaults, $args );
		extract( $args, EXTR_SKIP );
		
		$groups_table = apply_filters( 'pp_use_groups_table', $wpdb->pp_groups, $agent_type );
		
		if ( $ids )
			$where .= "AND ID IN ('" . implode( "','", array_map( 'intval', (array) $ids ) ) . "')";

		if ( $omit_ids )
			$where .= "AND ID NOT IN ('" . implode( "','", array_map( 'intval', (array) $omit_ids ) ) . "')";
		
		if ( $skip_meta_types )
			$where .= " AND metagroup_type NOT IN ('" . implode( "','", (array) $skip_meta_types ) . "')";
		
		if ( $require_meta_types )
			$where .= " AND metagroup_type IN ('" . implode( "','", (array) $require_meta_types ) . "')";
	
		if ( $search ) {
			$searches = array();
			foreach ( array('group_name', 'group_description') as $col )
				$searches[] = $col . $wpdb->prepare( " LIKE %s", "%$search%" );
			$where .= 'AND ( ' . implode(' OR ', $searches) . ' )';
		}
	
		$query = "SELECT $cols FROM $groups_table $join WHERE 1=1 $where ORDER BY $order_by";
		$results = $wpdb->get_results($query, OBJECT_K);

		foreach( array_keys($results) as $key ) {
			$results[$key]->name = stripslashes($results[$key]->name);
			
			if ( isset($results[$key]->group_description) )
				$results[$key]->group_description = stripslashes($results[$key]->group_description);

			// strip out Revisionary metagroups if we're not using them (@todo: API)
			if ( $results[$key]->metagroup_type ) {
				if ( ! defined('RVY_VERSION') && ( 'rvy_notice' == $results[$key]->metagroup_type ) ) {
					unset( $results[$key] );
				}
			}
		}

		return $results;
	}
	
	public static function get_pp_group_members( $group_id, $cols = 'all', $args = array() ) {
		global $wpdb;
		
		$defaults = array( 'agent_type' => 'pp_group', 'member_type' => 'member', 'status' => 'active', 'maybe_metagroup' => false );
		extract( array_merge( $defaults, $args ), EXTR_SKIP );
		
		// If $group_id is an array of group objects, extract IDs into a separate array (@todo: review calling code)
		if ( is_array($group_id) ) {
			$first = current($group_id);
			
			if ( is_object($first) ) {
				$actual_ids = array();
				
				foreach( $group_id as $group )
					$actual_ids []= $group->ID;
					
				$group_id = $actual_ids;
			}
		}
		
		if ( 'any' == $status )
			$status = '';

		$group_in = "'" . implode("', '", array_map('intval', (array) $group_id) ) . "'";
		
		$members_table = apply_filters( 'pp_use_group_members_table', $wpdb->pp_group_members, $agent_type );
		
		$status_clause = ( $status ) ? $wpdb->prepare( "AND status = %s", $status ) : '';
		$mtype_clause = $wpdb->prepare( "AND member_type = %s", $member_type );
		
		if ( 'id' == $cols ) {
			$query = "SELECT u2g.user_id 
				  FROM $members_table AS u2g
				  WHERE u2g.group_id IN ($group_in) AND u2g.group_id > 0 $mtype_clause $status_clause";
			
			if ( ! $results = $wpdb->get_col( $query ) )
				$results = array();

		} elseif ( 'count' == $cols ) {
			$query = "SELECT COUNT(u2g.user_id)
				  FROM $members_table AS u2g
				  WHERE u2g.group_id IN ($group_in) AND u2g.group_id > 0 $mtype_clause $status_clause";
			
			$results = $wpdb->get_var( $query );
			
		} else {
			switch( $cols ) {
				case 'id_displayname' : 
					$qcols = "u.ID, u.display_name AS display_name";
					$orderby = "ORDER BY u.display_name";
					break;
				case 'id_name' : 
					$qcols = "u.ID, u.user_login AS name";	// calling code assumes display_name property for user or group object
					$orderby = "ORDER BY u.user_login";
					break;
				default:
					$orderby = apply_filters( 'pp_group_members_orderby', "ORDER BY u.user_login" );
					$qcols = "u.*, u2g.*";
			}

			$query = "SELECT $qcols FROM $wpdb->users AS u"
					. " INNER JOIN $members_table AS u2g ON u2g.user_id = u.ID $mtype_clause $status_clause"
					. " AND u2g.group_id IN ($group_in) $orderby";
					
			$results = $wpdb->get_results( $query, OBJECT_K );
		}
		
		return $results;
	}
	
	public static function get_pp_groups_for_user( $user_id, $args = array() ) {
		$defaults = array( 'agent_type' => 'pp_group', 'member_type' => 'member', 'status' => 'active', 'cols' => 'all', 'metagroup_type' => null, 'force_refresh' => false, 'query_user_ids' => false );
		$args = array_merge( $defaults, $args );
		
		if ( is_null( $args['metagroup_type'] ) )
			unset( $args['metagroup_type'] );
		
		extract($args, EXTR_SKIP);
		
		global $wpdb;
		
		$groups_table = apply_filters( 'pp_use_groups_table', $wpdb->pp_groups, $agent_type );
		$members_table = apply_filters( 'pp_use_group_members_table', $wpdb->pp_group_members, $agent_type );
		
		if ( empty($members_table) ) {
			return array();
		}
		
		if ( ( $cols == 'all' ) || ! empty($metagroup_type) )
			$join = apply_filters( 'pp_get_groups_for_user_join', "INNER JOIN $groups_table AS g ON $members_table.group_id = g.ID", $user_id, $args );
		else
			$join = '';

		if ( 'any' == $status ) { $status = ''; }
		$status_clause = ( $status ) ? $wpdb->prepare( "AND status = %s", $status ) : '';
		$metagroup_clause = ( ! empty($metagroup_type) ) ? $wpdb->prepare( "AND g.metagroup_type = %s", $metagroup_type ) : '';
		$user_id = (int) $user_id;
		
		$user_groups = array();
		
		if ( 'pp_group' == $agent_type ) {
			static $all_group;
			static $auth_group;
		
			if ( ! isset($all_group) )
				$all_group = pp_get_metagroup( 'wp_role', 'wp_all' );
				
			if ( ! isset($auth_group) )
				$auth_group = pp_get_metagroup( 'wp_role', 'wp_auth' );
		}
		
		if ( 'all' == $cols ) {
			$query = "SELECT * FROM $members_table $join WHERE user_id = '$user_id' AND member_type = '$member_type' $status_clause $metagroup_clause ORDER BY $members_table.group_id";
			
			$results = $wpdb->get_results($query);

			foreach( $results as $row )
				$user_groups[$row->group_id] = (object) (array) $row;  // force storage by value
	
			if ( 'pp_group' == $agent_type ) {
				if ( $all_group )
					$user_groups[$all_group->ID] = $all_group;
				
				if ( $auth_group )
					$user_groups[$auth_group->ID] = $auth_group;
			}

			$user_groups = apply_filters( 'pp_get_pp_groups_for_user', $user_groups, $results, $user_id, $args );
		} else {
			if ( $query_user_ids ) {
				static $user_groups;
				if ( ! isset( $user_groups ) )
					$user_groups = array();
				
				if ( ! isset( $user_groups[$agent_type] ) )
					$user_groups[$agent_type] = array();
					
			} else {
				$user_groups = array( $agent_type => array() );
				$query_user_ids = $user_id;
			}
			
			if ( ! isset( $user_groups[$agent_type][$user_id] ) || $force_refresh ) {
				$query = "SELECT user_id, group_id, add_date_gmt FROM $members_table $join WHERE user_id IN ('" . implode( "','", (array) $query_user_ids ) . "') AND member_type = '$member_type' $status_clause $metagroup_clause ORDER BY group_id";

				$results = $wpdb->get_results($query);

				foreach( $results as $row )
					$user_groups[$agent_type][$row->user_id][$row->group_id] = $row->add_date_gmt;
			}
			
			if ( 'pp_group' == $agent_type ) {
				foreach( (array) $query_user_ids as $_user_id ) {
					if ( $all_group )
						$user_groups[$agent_type][$_user_id][$all_group->ID] = '0000-00-00 00:00:00';
					
					if ( $auth_group )
						$user_groups[$agent_type][$_user_id][$auth_group->ID] = '0000-00-00 00:00:00';
				}
			}
			
			return ( isset( $user_groups[$agent_type][$user_id] ) ) ? $user_groups[$agent_type][$user_id] : array();
		}
		
		return $user_groups;
	}
	
	public static function get_metagroup_name( $metagroup_type, $meta_id, $default_name = '' ) {
		global $wp_roles;

		if ( 'wp_auth' == $meta_id ) {
			return	__('{Authenticated}', 'pp');
		
		} elseif ( 'wp_anon' == $meta_id ) {
			return	__('{Anonymous}', 'pp');
			
		} elseif ( 'wp_all' == $meta_id ) {
			return	__('{All}', 'pp');
		
		} elseif ( 'wp_role' == $metagroup_type ) {
			$role_display_name = isset( $wp_roles->role_names[$meta_id] ) ? __($wp_roles->role_names[$meta_id]) : $meta_id;
			return sprintf( __('[WP %s]', 'pp'), $role_display_name );
		} else {
			return $default_name;
		} 
	}
	
	public static function get_metagroup_descript( $metagroup_type, $meta_id, $default_descript = '' ) {
		if ( 'wp_auth' == $meta_id ) {
			return __('Authenticated site users (logged in)', 'pp');
			
		} elseif ( 'wp_anon' == $meta_id ) {
			return __('Anonymous users (not logged in)', 'pp');
			
		} elseif ( 'wp_all' == $meta_id ) {
			return __('All users (including anonymous)', 'pp');
			
		} elseif ( 'wp_role' == $metagroup_type ) {
			$role_display_name = self::get_metagroup_name( $metagroup_type, $meta_id );
			$role_display_name = str_replace('[WP ', '', $role_display_name);
			$role_display_name = str_replace(']', '', $role_display_name);
			return sprintf( __( 'All users with the WordPress role of %s', 'pp'), $role_display_name );

		} else {
			return $default_descript;	
		}
	}
	
	public static function get_roles( $agent_type, $agent_id, $args = array() ) {
		global $wpdb;
		
		$defaults = array( 'post_types' => true, 'taxonomies' => true, 'query_agent_ids' => false, 'force_refresh' => false );
		$args = array_merge( $defaults, $args );
		extract( $args, EXTR_SKIP );
		
		$roles = array();

		$agent_type = pp_sanitize_key($agent_type);
		
		/* TODO: filter roles based on enabled types, taxonomies
		if ( ! is_array($post_types) )
			$post_types = pp_get_enabled_post_types( array(), 'object' );
			
		if ( ! is_array($taxonomies) )
			$taxonomies = pp_get_enabled_taxonomies( array(), 'object' );
		*/
		
		if ( $query_agent_ids ) {
			static $agent_roles;
			static $last_query_key;
			if ( ! isset($agent_roles) ) {
				$agent_roles = array();
				$last_query_agent_ids = false;
			}
			$query_key = serialize($query_agent_ids) . $agent_type;
		} else {
			$agent_roles = array();
			$query_agent_ids = (array) $agent_id;
			$query_key = false;
		}
		
		if ( ! isset( $agent_roles[$agent_id] ) || ( $last_query_key !== $query_key ) ) {
			foreach( $query_agent_ids as $_id )
				$agent_roles[$_id] = array();
		
			$results = $wpdb->get_results( "SELECT assignment_id, role_name, agent_id FROM $wpdb->ppc_roles WHERE agent_type = '$agent_type' AND agent_id IN ('" . implode( "','", array_map('intval', $query_agent_ids ) ) . "') ORDER BY role_name" );

			$no_ext = ! defined( 'PPCE_VERSION' ) && ! defined( 'PPS_VERSION' );
			$no_custom_stati = ! defined( 'PPS_VERSION' );
			
			foreach( $results as $row ) {
				// roles for these post statuses will not be applied if corresponding extensions are inactive, so do not indicate in users/groups listing or profile
				if ( $no_ext && strpos( $row->role_name, ':post_status:' ) && ! strpos( $row->role_name, ':post_status:private' ) )
					continue;
				elseif ( $no_custom_stati && strpos( $row->role_name, ':post_status:' ) && ! strpos( $row->role_name, ':post_status:private' ) && ! strpos( $row->role_name, ':post_status:draft' ) )
					continue;

				$agent_roles[$row->agent_id][$row->role_name] = $row->assignment_id;
			}
			
			$last_query_key = $query_key;
		}
		
		return isset( $agent_roles[$agent_id] ) ? $agent_roles[$agent_id] : array();
	}
	
	public static function get_exceptions( $args = array() ) {
		$defaults = array( 'operations' => array(), 'inherited_from' => '', 'for_item_source' => false, 'via_item_source' => false,
							'assign_for' => 'item', 'for_item_status' => false, 'post_types' => true, 'taxonomies' => true, 'item_id' => false, 
							'agent_type' => '', 'agent_id' => 0, 'query_agent_ids' => array(), 'ug_clause' => '', 
							'return_raw_results' => false, 'extra_cols' => array(), 'cols' => array() );
		$args = array_merge( $defaults, $args );
		extract( $args, EXTR_SKIP );
		
		global $wpdb;
		
		$except = array();
		
		$operations = (array) $operations;

		if ( $operations )
			$operations = array_intersect( $operations, pp_get_operations() );  // avoid application of exceptions which are disabled due to plugin deactivation
		else
			$operations = pp_get_operations();

		if ( ! is_array($post_types) )
			$post_types = pp_get_enabled_post_types();
		
		if ( ! is_array($taxonomies) )
			$taxonomies = pp_get_enabled_taxonomies();

		$default_arr = array( 'include', 'exclude' );
		if ( ! defined( 'PP_NO_ADDITIONAL_ACCESS' ) )
			$default_arr []= 'additional';
		
		$valid_src_types = array( 'post' => array( 	// post exceptions can come from posts or terms
									'post' => array( '' => array_fill_keys( $default_arr, array_fill_keys( $post_types, array() ) ) ),
									//'term' => array_fill_keys( $default_arr, array_fill_keys( $taxonomies, array() ) ),
									'term' => array(),
								   ),
								  'term' => array( 	// term exceptions only come from terms
									'term' => array(), /* array_fill_keys( $default_arr, array_fill_keys( $taxonomies, array() ) ),
								   */
								   ),
								   'pp_group' => array( // pp group management exceptions only come from groups
									'pp_group' => array(),
								   ),
								);
		
		if ( $add_source_types = apply_filters( 'pp_add_exception_source_types', array() ) )	// valid return array is arr[for_item_source] = arr[via_item_src][via_item_type] = array('include', 'exclude' )
			$valid_src_types = array_merge( $valid_src_types, $add_source_types );
		
		if ( $for_item_source ) {
			$for_item_source = array_flip( (array) $for_item_source );
			if ( ! $for_item_sources = array_intersect_key( $for_item_source, $valid_src_types ) )
				return array();
		} else {
			$for_item_sources = $valid_src_types;
		}
		
		$for_item_clauses = array();
		
		foreach( array_keys($for_item_sources) as $for_src_name ) {
			if ( isset( $valid_src_types[$for_src_name] ) ) {

				foreach( $operations as $op ) {
					$except["{$op}_{$for_src_name}"] = $valid_src_types[$for_src_name];
				}
				
				$for_types = array();

				foreach( array_keys($valid_src_types[$for_src_name]) as $via_src_name ) {
					if ( 'post' == $via_src_name ) {
						foreach( array_keys($valid_src_types[$for_src_name][$via_src_name]) as $via_type ) {
							foreach( array_keys($valid_src_types[$for_src_name][$via_src_name][$via_type]) as $mod_type ) {
								$for_types = array_merge( $for_types, array_keys($valid_src_types[$for_src_name][$via_src_name][$via_type][$mod_type]) );
							}
						}

					} elseif ( 'term' == $via_src_name ) {
						if ( 'term' == $for_src_name )
							$for_types = $taxonomies;
						else
							$for_types = $post_types;
					} else {
						$for_types = false;
					}
				}
				
				if ( false === $for_types )
					$for_item_clauses[]= "e.for_item_source = '$for_src_name'";
				else
					$for_item_clauses[]= "e.for_item_source = '$for_src_name' AND e.for_item_type IN ('', '" . implode( "','", array_unique($for_types) ) . "')";
			}
		}

		if ( $type_clause = pp_implode( 'OR', $for_item_clauses ) )
			$type_clause = "AND ( $type_clause )";
		
		if ( $via_item_source )
			$type_clause .= $wpdb->prepare( "AND e.via_item_source = '$via_item_source'", $via_item_source );
		
		if ( $agent_type && ! $ug_clause )
			$ug_clause = $wpdb->prepare( " AND e.agent_type = %s AND e.agent_id IN ('" . implode( "','", array_map( 'intval', (array) $agent_id ) ) . "')", $agent_type );

		$operation_clause = "AND e.operation IN ('" . implode( "','", $operations ) . "')";
		$mod_clause = ( defined( 'PP_NO_ADDITIONAL_ACCESS' ) ) ? "AND e.mod_type != 'additional'" : '';
		$assign_for_clause = ( $assign_for ) ? $wpdb->prepare( "AND i.assign_for = %s", $assign_for ) : '';
		$inherited_from_clause = ( $inherited_from !== '' ) ? $wpdb->prepare( "AND i.inherited_from = %d", $inherited_from ) : '';
		$status_clause = ( false !== $for_item_status ) ? $wpdb->prepare( "AND e.for_item_status = %s", $for_item_status ) : '';
		
		if ( ! $status_clause && ! defined( 'PPS_VERSION' ) )
			$status_clause = "AND e.for_item_status IN ('','post_status:private','post_status:draft')";  // exceptions for other statuses will not be applied correctly without custom statuses extension

		if ( ! $cols )
			$cols = "e.operation, e.for_item_source, e.for_item_type, e.mod_type, e.via_item_source, e.via_item_type, e.for_item_status, i.item_id, i.assign_for";
		
		$extra_cols_clause = ( $extra_cols ) ? ', ' . implode( ",", $extra_cols ) : '';
		
		$id_clause = ( false !== $item_id ) ? $wpdb->prepare( "AND i.item_id = %d", $item_id ) : '';
		
		$results = $wpdb->get_results( "SELECT $cols{$extra_cols_clause} FROM $wpdb->ppc_exceptions AS e INNER JOIN $wpdb->ppc_exception_items AS i ON e.exception_id = i.exception_id WHERE ( 1=1 $operation_clause $assign_for_clause $inherited_from_clause $mod_clause $type_clause $status_clause $id_clause ) $ug_clause" );
		
		if ( $return_raw_results )
			return $results;
		
		foreach( $results as $row ) {
			// note: currently only additional access can be status-specific
			$except["{$row->operation}_{$row->for_item_source}"][$row->via_item_source][$row->via_item_type][$row->mod_type][$row->for_item_type][$row->for_item_status] []= $row->item_id;
		}
		
		return $except;
	}
}
