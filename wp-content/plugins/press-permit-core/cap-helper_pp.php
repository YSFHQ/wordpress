<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class PP_Cap_Helper {
	var $all_type_caps = array();		// $all_type_caps = array of cap names
	
	function __construct() {
		$this->force_distinct_post_caps();
	}

	function force_distinct_post_caps() {  // but only if the post type has PP filtering enabled
		global $wp_post_types;
		
		$core_meta_caps = array_fill_keys( array( 'read_post', 'edit_post', 'delete_post' ), true );
		
		$append_caps = array( 'edit_published_posts' => 'edit_posts', 'edit_private_posts' => 'edit_posts', 'delete_posts' => 'edit_posts', 'delete_others_posts' => 'delete_posts', 'delete_published_posts' => 'delete_posts', 'delete_private_posts' => 'delete_posts', 'read' => 'read' );
		
		if ( $force_create_posts_cap = pp_wp_ver( '3.5-beta' ) && pp_get_option('define_create_posts_cap') ) {
			foreach( array( 'post', 'page' ) as $post_type ) {
				if ( $force_create_posts_cap && ( $wp_post_types[$post_type]->cap->create_posts == $wp_post_types[$post_type]->cap->edit_posts ) )
					$wp_post_types[$post_type]->cap->create_posts = "create_{$post_type}s";
			}
			
			$append_caps['create_posts'] = 'create_posts';
		}
		
		// post types which are enabled for PP filtering must have distinct type-related cap definitions
		foreach( pp_get_enabled_post_types() as $post_type ) {
			// append missing capability definitions
			foreach( $append_caps as $prop => $default ) {
				if ( ! isset( $wp_post_types[$post_type]->cap->$prop ) )
					$wp_post_types[$post_type]->cap->$prop = ( 'read' == $prop ) ? 'read' : $wp_post_types[$post_type]->cap->$default;
			}

			$wp_post_types[$post_type]->map_meta_cap = true;
			
			$type_caps = array_diff_key( (array) $wp_post_types[$post_type]->cap, $core_meta_caps );
			
			$cap_base = ( 'attachment' == $post_type ) ? 'file' : $post_type;
			
			foreach( array( 'post', 'page' ) as $generic_type ) {
				if ( $post_type != $generic_type ) { // page is not prevented from having 'page' cap defs, but IS prevented from having 'post' cap defs
					
					// force distinct capability_type
					if ( $generic_type == $wp_post_types[$post_type]->capability_type ) {
						$wp_post_types[$post_type]->capability_type = $post_type;
					}
					
					// Replace "edit_posts" with "edit_doohickys". This is not ideal, but as of WP 3.4, no plural name is defined unless unless type-specific caps are already set.
					// If this is a problem, just define the type caps in the register_post_type call, or modify existing $wp_post_types[$post_type]->cap values by hooking to the init action at priority 40.
					//foreach( array_keys( array_intersect( (array) $wp_post_types[$generic_type]->cap, $type_caps ) ) as $cap_property ) {
					foreach( array_keys( $type_caps ) as $cap_property ) {
						if ( ! in_array( $type_caps[$cap_property], (array) $wp_post_types[$generic_type]->cap ) )
							continue;

						if ( 'create_posts' == $cap_property )
							$type_caps[$cap_property] = str_replace( "_$generic_type", "_$cap_base", $wp_post_types[$generic_type]->cap->$cap_property );  // if create_posts cap is not distinct, force edit_posts to edit_doohickys
						else
							$type_caps[$cap_property] = str_replace( "_$generic_type", "_$cap_base", $cap_property );
					}
				}
			}
			
			$wp_post_types[$post_type]->cap = (object) array_merge( (array) $wp_post_types[$post_type]->cap, $type_caps );
	
			$wp_post_types[$post_type]->plural_name = pp_plural_name_from_cap( $wp_post_types[$post_type] );
			
			$this->all_type_caps = array_merge( $this->all_type_caps, array_fill_keys( $type_caps, true ) );
		} // end foreach post type
		
		// need this for casting to other types even if "post" type is not enabled for PP filtering
		$wp_post_types['post']->cap->set_posts_status = 'set_posts_status';
	}
} // end class PP_Cap_Helper


// test_cap = 'edit_posts' or 'manage_terms'
// default_cap_prefix = 'edit_' or 'manage_'
//
// note: valid usage is after force_distinct_post_caps() and force_distinct_taxonomy_caps have been applied to object
function pp_plural_name_from_cap( $type_obj ) {
	if ( isset( $type_obj->cap->edit_posts ) ) {
		$test_cap = $type_obj->cap->edit_posts;
		$default_cap_prefix = 'edit_';
	} elseif( isset( $type_obj->cap->manage_terms ) ) {
		$test_cap = $type_obj->cap->manage_terms;
		$default_cap_prefix = 'manage_';
	} else
		$test_cap = false;

	if ( $test_cap && ( 0 === strpos( $test_cap, $default_cap_prefix ) ) 
	&& ( false === strpos( $test_cap, '_', strlen($default_cap_prefix) ) ) )
		return substr( $test_cap, strlen($default_cap_prefix) );
	
	return isset( $type_obj->name ) ? $type_obj->name . 's' : '';
} 
