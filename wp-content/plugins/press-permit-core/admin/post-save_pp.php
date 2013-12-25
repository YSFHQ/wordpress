<?php
class PP_PostSave {
	public static function act_save_item( $item_source, $post_id, $post ) {
		if ( ! empty( $_REQUEST['action'] ) && ( 'untrash' == $_REQUEST['action'] ) )
			return;
		
		// WP always passes post object into do_action('save_post')
		if ( ! is_object($post) ) {
			if ( ! $post = get_post( $post_id ) )
				return;
		}
		
		// operations in this function do not apply to revision save
		if ( 'revision' == $post->post_type )
			return;
		
		if ( ! in_array( $post->post_type, pp_get_enabled_post_types() ) ) {
			if ( ! empty( $_REQUEST['pp_enable_post_type'] ) ) {
				$enabled = get_option( 'pp_enabled_post_types' );
				$enabled[$post->post_type] = '1';
				update_option( 'pp_enabled_post_types', $enabled );
			}
			return;
		}

		// don't execute this action handler more than one per post save
		static $saved_items;
		if ( ! isset($saved_items) ) { $saved_items = array(); }
		if ( isset($saved_items[$post_id]) ) { return; }
		$saved_items[$post_id] = 1;
	
		$is_new = self::is_new_post( $post_id, $post );
		
		if ( is_post_type_hierarchical( $post->post_type ) ) {
			$parent_info = self::get_post_parent_info( $post_id, $post, true );
			extract( $parent_info, EXTR_SKIP );	// $set_parent, $last_parent

			if ( is_numeric( $last_parent ) ) // not theoretically necessary, but an easy safeguard to avoid re-inheriting parent roles
				$is_new = false;
		}

		if ( empty( $_REQUEST['page'] ) || ( 'rvy-revisions' != $_REQUEST['page'] ) ) {
			require_once( dirname(__FILE__).'/item-save_pp.php' );
			PP_ItemSave::item_update_process_exceptions( 'post', 'post', $post_id, compact( 'is_new', 'set_parent', 'last_parent', 'disallow_manual_entry', 'via_item_type' ) );
		}
	}
	
	public static function is_new_post( $post_id, $post_obj = false ) {
		global $pp_admin_filters;
		
		if ( ! $post_obj )
			$post_obj = get_post($post_id);
		
		return ( 'auto-draft' == $post_obj->post_status )
			|| empty( $pp_admin_filters->last_post_status[$post_id] ) 
			|| ( 'auto-draft' == $pp_admin_filters->last_post_status[$post_id] )
			|| ( 'new' == $pp_admin_filters->last_post_status[$post_id] );
	}
	
	public static function get_post_parent_info( $post_id, $post_obj = false, $update_meta = false ) {
		if ( ! $post_obj )
			$post_obj = get_post($post_id);
	
		// parent settings can affect the auto-assignment of propagating roles / conditions
		$set_parent = $post_obj->post_parent;
		$last_parent = ( $post_id > 0 ) ? get_post_meta( $post_id, '_pp_last_parent', true ) : 0;
			
		if ( $update_meta ) {
			if ( isset( $set_parent ) && ( $set_parent != $last_parent ) && ( $set_parent || $last_parent ) )
				update_post_meta( $post_id, '_pp_last_parent', (int) $set_parent );
		}
		
		return compact( 'last_parent', 'set_parent' );
	}
} // end class
