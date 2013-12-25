<?php
/**
 * PP_Cap_Caster class
 * 
 * @package PP
 * @author Kevin Behrens <kevin@agapetry.net>
 * @copyright Copyright (c) 2011-2013, Agapetry Creations LLC
 * 
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class PP_Cap_Caster {
	var $pattern_role_type_caps = array();
	var $pattern_role_taxonomy_caps = array();
	var $pattern_role_cond_caps = array();
	var $pattern_role_arbitrary_caps = array();
	var $typecast_role_caps = array();

	function is_valid_pattern_role( $wp_role_name, $role_caps = false ) {
		if ( 'subscriber' != $wp_role_name ) {
			global $wp_roles;
		
			if ( false === $role_caps ) {
				$role_caps = ( isset($wp_roles->role_objects[$wp_role_name]) ) ? $wp_roles->role_objects[$wp_role_name]->capabilities : array();
			}

			$type_obj = get_post_type_object( 'post' );
			return isset($wp_roles->role_objects[$wp_role_name]) && ! empty( $role_caps[ $type_obj->cap->edit_posts ] );
		} else
			return true;
	}
	
	// If one of the standard WP roles is missing, define it for use as a template for type-specific role assignments
	function define_pattern_caps() {
		global $pp_role_defs, $wp_roles, $pp_cap_helper;

		$caps = array();
	
		foreach ( array_keys($pp_role_defs->pattern_roles) as $role_name ) {
			if ( isset( $wp_roles->role_objects[$role_name]->capabilities ) ) {
				$caps[$role_name] = array_intersect( $wp_roles->role_objects[$role_name]->capabilities, array( 1, '1', true ) );

				// if a standard WP role has been deleted, revert to default rolecaps
				if ( in_array( $role_name, array( 'subscriber', 'contributor', 'author', 'editor' ) ) && ( empty($caps[$role_name]) || ! $this->is_valid_pattern_role( $role_name, $caps[$role_name] ) ) ) {
					require_once( dirname(__FILE__).'/default-rolecaps_pp.php' );
					$caps[$role_name] = PP_Default_Rolecaps::get_default_rolecaps( $role_name );
				}
			}
		}
		
		$caps = apply_filters( 'pp_pattern_role_caps', $caps );
		
		$type_obj = get_post_type_object( 'post' );
		
		$type_caps = array();
		$type_caps['post'] = array_diff_key( get_object_vars( $type_obj->cap ), array_fill_keys( array( 'read_post', 'edit_post', 'delete_post' ), true ) );
		//foreach( $type_caps['post'] as $prop => $val ) {  // force_distinct_post_caps eliminates need for this
		//
		//  if ( ! is_scalar($val) ) // may be array if running in config mode alongside Role Scoper - TODO: confirm this no longer occurs
		//		$type_caps['post'][$prop] = $prop;
		//
		//	if ( ( 'edit_posts' == $val ) && ( 'edit_posts' != $prop ) )
		//		unset( $type_caps['post'][$prop] );
		//}
		
		$exclude_caps = array_fill_keys( apply_filters( 'pp_exclude_arbitrary_caps', array( 'level_0', 'level_1', 'level_2', 'level_3', 'level_4', 'level_5', 'level_6', 'level_7', 'level_8', 'level_9', 'level_10', 'edit_dashboard', 'add_users', 'create_users', 'edit_users', 'list_users', 'promote_users', 'remove_users', 'activate_plugins', 'delete_plugins', 'edit_plugins', 'install_plugins', 'update_plugins', 'delete_themes', 'edit_theme_options', 'edit_themes', 'install_themes', 'switch_themes', 'update_themes', 'export', 'import', 'manage_links', 'manage_categories', 'manage_options', 'update_core', 'pp_manage_settings', 'pp_administer_content', 'pp_unfiltered', 'pp_create_groups', 'pp_delete_groups', 'pp_edit_groups', 'pp_manage_members' ) ), true );
		foreach( pp_get_operations() as $op ) {
			$exclude_caps ["pp_set_{$op}_exceptions"] = true;
			$exclude_caps ["pp_set_term_{$op}_exceptions"] = true;
		}
		
		foreach( array_keys($caps) as $role_name ) {
			// log caps defined for the "post" type
			$this->pattern_role_type_caps[$role_name] = array_intersect( $type_caps['post'], array_keys($caps[$role_name]) );  // intersect with values of $post_type_obj->cap to account for possible customization of "post" type capabilities
			
			if ( ! $this->is_valid_pattern_role( $role_name, $this->pattern_role_type_caps[$role_name] ) ) {
				// role has no edit_posts cap stored, so use default rolecaps instead
				require_once( dirname(__FILE__).'/default-rolecaps_pp.php' );
				if ( $def_caps = PP_Default_Rolecaps::get_default_rolecaps( $role_name, false ) ) {
					$this->pattern_role_type_caps[$role_name] = array_intersect_key( $type_caps['post'], array_combine( array_keys($def_caps), array_keys($def_caps) ) );
				}
			}
			
			// log caps not defined for any post type or status
			if ( $misc_caps = array_diff_key( $caps[$role_name], $pp_cap_helper->all_type_caps, $exclude_caps ) )
				$this->pattern_role_arbitrary_caps[$role_name] = array_combine( array_keys($misc_caps), array_keys($misc_caps) );
		}
		
		do_action( 'pp_define_pattern_caps', $caps );
	}
	
	// $role_name : "baserolename:objtype:attribute:condition"
	// should only be called if $this->typecast_role_caps[$role_name] is not already set
	function get_typecast_caps( $role_name ) {
		$arr_name = explode( ':', $role_name );
		if ( empty($arr_name[2]) )
			return array();
	
		// this role typecast is not db-defined, so generate it
		$base_role_name = $arr_name[0];
		$src_name = $arr_name[1];
		$object_type = $arr_name[2];

		// typecast role assignment stored, but undefined source name or object_type
		if ( ! $type_obj = pp_get_type_object( $src_name, $object_type ) )
			return array();

		// disregard stored Supplemental Roles for Media when Media is no longer enabled for PP filtering (otherwise Post editing caps are granted)
		if ( ( 'attachment' == $object_type ) ) {
			static $media_filtering_enabled;
			
			if ( ! isset($media_filtering_enabled) )
				$media_filtering_enabled = pp_get_enabled_post_types( array( 'name' => 'attachment' ) ); 
			
			if ( ! $media_filtering_enabled )
				return array();
		}
			
		if ( empty( $this->pattern_role_type_caps ) )
			$this->define_pattern_caps();
		
		$pattern_role_caps = ( 'term' == $src_name ) ? $this->pattern_role_taxonomy_caps : $this->pattern_role_type_caps;
		
		if ( empty( $pattern_role_caps[$base_role_name] ) ) // if the role definition is not currently configured for Pattern Role usage, disregard the assignment
			return array();
			
		if ( ! defined('PPS_VERSION') && strpos( $role_name, 'post_status:private' ) && isset($pattern_role_caps[$base_role_name]['read']) )
			$pattern_role_caps[$base_role_name]['read_private_posts'] = true;

		if ( ! empty($arr_name[3]) ) { 
			if ( empty($arr_name[4]) ) { // disregard stored roles with invalid status
				return array();
			} elseif ( 'post_status' == $arr_name[3] ) {  // ignore supplemental roles for statuses which are no longer active for this post type
				if ( ! pp_get_post_stati( array( 'name' => $arr_name[4], 'post_type' => $object_type ) ) )
					return array();
			}
		}

		// add all type-specific caps whose base property cap is included in this pattern role
		// i.e. If 'edit_posts' is in the pattern role, grant $type_obj->cap->edit_posts
		//
		if ( $caps = array_intersect_key( (array) get_object_vars( $type_obj->cap ), $pattern_role_caps[$base_role_name] ) ) {
			// At least one type-defined cap is being cast from this pattern role for specified object_type
			$status_caps = apply_filters( 'pp_get_typecast_caps', $caps, $arr_name, $type_obj );
			
			if ( ! empty($arr_name[3]) && ( false === strpos( $role_name, 'post_status:private' ) ) && ( $caps == $status_caps ) ) { // if stored status value is invalid, don't credit user for "default statuses" type caps (but allow for subscriber-private if PPS inactive
				return array();
			} else {
				return apply_filters( 'pp_apply_arbitrary_caps', $status_caps, $arr_name, $type_obj );
			}
		}

		return $caps;
	}
	
	// pulls user's typecast site roles and merges those caps into $user->allcaps
	function get_user_typecast_caps( $user ) {
		$add_caps = array();

		$user->site_roles = $user->get_site_roles();

		foreach( array_keys($user->site_roles) as $role_name ) {
			// add all type-specific caps whose base property cap is included in this pattern role
			// i.e. If 'edit_posts' is in the pattern role, grant $type_obj->cap->edit_posts
			//
			// note: get_typecast_caps() returns arr[pattern_cap_name] = type_cap_name
			//   but we need to return arr[type_cap_name] = true
			$add_caps = array_merge( $add_caps, array_fill_keys( $this->get_typecast_caps( $role_name ), true ) );
		}
		
		return $add_caps;
	}
}
