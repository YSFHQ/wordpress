<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class PP_CapInterceptorAdmin {
	function __construct() {
		add_filter( 'map_meta_cap', array(&$this, 'flt_adjust_reqd_caps'), 1, 4 );
	}
	
	// hooks to map_meta_cap
	function flt_adjust_reqd_caps( $reqd_caps, $orig_cap, $user_id, $args ) {
		global $pagenow, $current_user;
		
		// Work around WP's occasional use of literal 'cap_name' instead of $post_type_object->cap->$cap_name
		// note: cap names for "post" type may be customized too
		if ( in_array( $pagenow, array( 'edit.php', 'post.php', 'post-new.php', 'press-this.php', 'admin-ajax.php', 'upload.php', 'media.php' ) ) && in_array( 'edit_posts', $reqd_caps ) && ( $user_id == $current_user->ID ) && ! $this->doing_admin_menus() ) {
			static $did_admin_init = false;
			if ( ! $did_admin_init )
				$did_admin_init = did_action( 'admin_init' );

			if ( $did_admin_init ) {
				if ( ! empty($args[0]) )
					$item_id = ( is_object($args[0]) ) ? $args[0]->ID : $args[0];
				else
					$item_id = 0;
				
				if ( $type_obj = get_post_type_object( pp_find_post_type( $item_id ) ) ) {				
					$key = array_search( 'edit_posts', $reqd_caps );
					if ( false !== $key )	
						$reqd_caps[$key] = $type_obj->cap->edit_posts;
				}
			}
		}

		//===============================
		
		return $reqd_caps;
	}
	
	private function doing_admin_menus() {
		return ( ( did_action( '_admin_menu' ) && ! did_action('admin_menu') ) 	 // menu construction
				|| ( did_action( 'admin_head' ) && ! did_action('adminmenu') )	 // menu display
				);
	}
}
