<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 *
 * @package PP
 * @author Kevin Behrens <kevin@agapetry.net>
 * @copyright Copyright (c) 2011-2013, Agapetry Creations LLC
 * 
 */
if ( ! class_exists('PP_User') ) {
class PP_User extends WP_User {
	var $groups = array(); 		// $groups[agent_type][group id] = 1
	var $site_roles = array();	
								// note: nullstring for_item_type means all post types
	var $except = array();	    // $except[{operation}_{for_item_source}][via_item_source][via_item_type]['include' or 'exclude'][for_item_type][for_item_status] = array of stored IDs / term_taxonomy_ids
	var $cfg = array();
	
	function __construct($id = 0, $name = '', $args = array()) {
		//pp_log_mem_usage( 'begin PP_User' );

		parent::__construct( $id, $name );
		
		// without this, logged users have no read access to sites they're not registered for
		if ( PP_MULTISITE && $id && ! is_admin() && empty( $this->allcaps ) )
			$this->caps['read'] = true;
		
		$agent_type = 'pp_group';		
		$this->groups[$agent_type] = $this->_get_pp_groups( compact( 'agent_type' ) );
		
		if ( ! empty($args['filter_usergroups']) && ! empty($args['filter_usergroups'][$agent_type]) )  // assist group admin
			$this->groups[$agent_type] = array_intersect_key($this->groups[$agent_type], $args['filter_usergroups'][$agent_type]);

		$this->site_roles = $this->get_site_roles();	// @todo: eliminate redundant call - get_user_typecast_caps()

		add_filter('map_meta_cap', array(&$this, 'reinstate_caps'), 99, 3);

		//pp_log_mem_usage( 'new PP_User done' );
	}
	
	function retrieve_extra_groups() {
		$custom_group_type = false;
		
		foreach( pp_get_group_types( array(), 'names' ) as $agent_type ) {
			if ( 'pp_group' != $agent_type ) {
				$this->groups[$agent_type] = pp_get_groups_for_user( $this->ID, $agent_type, array( 'cols' => 'id' ) );
				$custom_group_type = true;
			}
		}

		if ( $custom_group_type )	// retrieve_extra_groups() is called by pp_init_with_user().  @todo: eliminate earlier retrieval of site roles?
			$this->site_roles = $this->get_site_roles();
	}
	
	function get_usergroups_clause( $table_alias, $args = array() ) {
		$table_alias = ( $table_alias ) ? "$table_alias." : '';
		
		$arr = array();
		
		$arr []= "{$table_alias}agent_type = 'user' AND {$table_alias}agent_id = '$this->ID'";
		
		foreach( pp_get_group_types( array(), 'names' ) as $agent_type ) {
			if ( ! empty( $this->groups[$agent_type] ) ) {
				$arr []= "{$table_alias}agent_type = '$agent_type' AND {$table_alias}agent_id IN ('" . implode( "', '", array_keys($this->groups[$agent_type]) ) . "')";
			}
		}

		if ( ! empty($args['user_clause']) )
			$arr []= "{$table_alias}agent_type = 'user' AND {$table_alias}agent_id = '$this->ID'";

		$clause = pp_implode( ' OR ', $arr );

		if ( count($arr) > 1 )
			$clause = "( $clause )";
		
		if ( $clause )
			return " AND $clause";
		else
			return ' AND 1=2';
	}

	// return group_id as array keys
	function _get_pp_groups( $args = array() ) {
		$args = (array) $args;
		
		if ( ! $this->ID ) {
			$user_groups = array();
		
			if ( pp_get_option( 'anonymous_unfiltered' ) ) {
				$this->allcaps['pp_unfiltered'] = true;
			} else {
				if ( $anon_group = pp_get_metagroup( 'wp_role', 'wp_anon' ) )
					$user_groups[$anon_group->ID] = $anon_group;

				if ( $all_group = pp_get_metagroup( 'wp_role', 'wp_all' ) )
					$user_groups[$all_group->ID] = $all_group;
			}
		} else {
			$user_groups = pp_get_groups_for_user( $this->ID, $args['agent_type'], $args );
			
			if ( isset( $this->roles ) ) {
				if ( pp_get_option( 'dynamic_wp_roles' ) || defined( 'PP_FORCE_DYNAMIC_ROLES' ) ) {
					$have_role_group_names = array();
					foreach( $user_groups as $group ) {
						if ( 'wp_role' == $group->metagroup_type )
							$have_role_group_names []= $group->metagroup_id;
					}
					
					if ( $missing_role_group_names = array_diff( $this->roles, $have_role_group_names ) ) {
						global $wpdb;
					
						$groups_table = apply_filters( 'pp_use_groups_table', $wpdb->pp_groups );
						$add_metagroups = $wpdb->get_results( "SELECT * FROM $groups_table WHERE metagroup_type = 'wp_role' AND metagroup_id IN ('" . implode( "','", $missing_role_group_names ) . "')" );
						foreach( $add_metagroups as $row ) {
							$row->group_id = $row->ID;
							$row->status = 'active';
							$user_groups[$row->ID] = $row;
						}
					}
				}
			}
		}

		return $user_groups;
	}

	function get_site_roles( $args = array() ) {
		//$defaults = array( 'force_refresh' => false );
		//$args = array_merge( $defaults, (array) $args );
		//extract($args, EXTR_SKIP);

		global $wpdb, $pp_role_defs;
		
		$u_g_clause = $this->get_usergroups_clause( 'uro' );
		
		$cols = apply_filters( 'pp_get_site_roles_fields', 'role_name' );
		$qry = "SELECT $cols FROM $wpdb->ppc_roles AS uro WHERE 1=1 $u_g_clause";

		if ( $results = $wpdb->get_results($qry) ) {
			$site_roles = apply_filters( 'pp_get_site_roles_parse', false, $results, $args );
			
			if ( ! is_array( $site_roles ) ) {
				foreach( $results as $row )
					$site_roles[$row->role_name] = true;
			}
		} else
			$site_roles = array();

		return $site_roles;
	}
	
	function retrieve_exceptions( $operations = array(), $for_item_sources = array(), $args = array() ) {
		$args['ug_clause'] = $this->get_usergroups_clause( 'e', array( 'user_adjust' => pp_get_option( 'user_exceptions' ) ) );		// note: Custom Group and User assignments are only for additions
		$args['operations'] = $operations;
		$args['for_item_sources'] = $for_item_sources;
		$this->except = array_merge( $this->except, ppc_get_exceptions( $args ) );
	}
	
	function get_exception_posts( $operation, $mod_type, $post_type, $args = array() ) {
		if ( ! isset($this->except["{$operation}_post"]) ) {
			$this->retrieve_exceptions( $operation, 'post' );
		}

		$return = apply_filters( 'pp_get_exception_items', false, $operation, $mod_type, $post_type, $args );
		if ( false !== $return )
			return $return;
		
		$exceptions = ( isset( $this->except["{$operation}_post"]['post'][''][$mod_type][$post_type] ) ) ? $this->except["{$operation}_post"]['post'][''][$mod_type][$post_type] : array();
		$exceptions = apply_filters( '_pp_get_exception_items', $exceptions, $operation, $mod_type, $post_type, $args );
		
		$status = ( isset( $args['status'] ) ) ? $args['status'] : '';
		
		if ( empty($exceptions) )
			return array();
		
		if ( true === $status )
			return $exceptions;
		else
			return pp_array_flatten( array_intersect_key( $exceptions, array( $status => true ) ) );
	}
	
	function get_exception_terms( $operation, $mod_type, $post_type, $taxonomy, $args = array() ) {
		$status = ( isset( $args['status'] ) ) ? $args['status'] : '';
		
		if ( $post_type ) {
			$for_item_src = post_type_exists($post_type) ? 'post' : 'term';
		
			if ( ( 'post' == $for_item_src ) && $taxonomy && ! in_array( $taxonomy, pp_get_enabled_taxonomies( array( 'object_type' => $post_type ) ) ) )
				return array();
		} else 
			$for_item_src = 'post';		// nullstring post_type means all post types
			
		if ( ! isset($this->except["{$operation}_{$for_item_src}"]) ) {
			$this->retrieve_exceptions( $operation, $for_item_src );
		}

		$args['via_item_source'] = 'term';
		$args['via_item_type'] = $taxonomy;
		$args['status'] = true;	// prevent filter from flattening exceptions array, since we will do it below
		$type_restricts = apply_filters( 'pp_get_exception_items', false, $operation, $mod_type, $post_type, $args );
		
		if ( false === $type_restricts )
			$type_restricts = ( isset( $this->except["{$operation}_{$for_item_src}"]['term'][$taxonomy][$mod_type][$post_type] ) ) ? $this->except["{$operation}_{$for_item_src}"]['term'][$taxonomy][$mod_type][$post_type] : array();
	
		if ( $post_type && ! empty($args['merge_universals']) ) {
			$universal_restricts = apply_filters( 'pp_get_exception_items', false, $operation, $mod_type, '', $args );
			if ( false === $universal_restricts )
				$universal_restricts = ( isset( $this->except["{$operation}_{$for_item_src}"]['term'][$taxonomy][$mod_type][''] ) ) ? $this->except["{$operation}_{$for_item_src}"]['term'][$taxonomy][$mod_type][''] : array();

			foreach( array_keys($universal_restricts) as $_status ) {
				pp_set_array_elem( $type_restricts, array( $_status ) );
				$type_restricts[$_status] = array_unique( array_merge( $type_restricts[$_status], $universal_restricts[$_status] ) );
			}
		}

		if ( ! $type_restricts )
			return array();
		
		if ( true === $status )
			return $type_restricts;
		else
			$tt_ids = pp_array_flatten( array_intersect_key( $type_restricts, array( $status => true ) ) );
		
		if ( ! empty( $args['return_term_ids'] ) )
			return pp_ttid_to_termid( $tt_ids, $taxonomy );
		else
			return array_unique( $tt_ids );
	}
	
	function has_cap_sitewide( $cap_property, $post_type = false ) {
		$_types = get_post_types( array( 'public' => true ), 'object' );
		
		if ( $post_type )
			$_types = array_intersect_key( $_types, (array) $post_type );
		
		foreach( $_types as $type_obj ) {			
			if ( ! empty( $this->allcaps[$type_obj->cap->$cap_property] ) ) {
				return true;
			}
		}
		
		return false;
	}
	
	function reinstate_caps( $wp_blogcaps, $orig_reqd_caps, $args ) {
		global $current_user, $pp_current_user;
	
		if ( ( ! isset($args[1]) || $args[1] == $pp_current_user->ID ) && array_diff_key( $pp_current_user->allcaps, $current_user->allcaps ) ) {
			$current_user->allcaps = array_intersect( array_merge( $current_user->allcaps, $pp_current_user->allcaps ), array(true,1,'1') );
		}
		
		return $wp_blogcaps;
	}
} // end class PP_User
}
