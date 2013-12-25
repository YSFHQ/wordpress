<?php

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

class PP_TermSave {
	// This handler is meant to fire whenever a term is inserted or updated.
	// If the client does use such a hook, we will force it by calling internally from mnt_create and mnt_edit
	
	public static function act_save_item( $term_id, $tt_id, $taxonomy ) {
		if ( ! in_array( $taxonomy, pp_get_enabled_taxonomies() ) ) {
			if ( ! empty( $_REQUEST['pp_enable_taxonomy'] ) ) {
				$enabled_taxonomies = get_option( 'pp_enabled_taxonomies' );
				$enabled_taxonomies[$taxonomy] = '1';
				update_option( 'pp_enabled_taxonomies', $enabled_taxonomies );
			}
			return;
		}
		
		static $saved_terms;
	
		if ( ! isset($saved_terms) )
			$saved_terms = array();
	
		// so this filter doesn't get called by hook AND internally
		if ( isset($saved_terms[$taxonomy][$tt_id]) )
			return;

		// parent settings can affect the auto-assignment of propagating exceptions
		$set_parent = ( is_taxonomy_hierarchical($taxonomy) && ! empty($_REQUEST['parent']) ) ? (int) $_REQUEST['parent'] : 0;

		if ( $set_parent < 0 )
			$set_parent = 0;

		$saved_terms[$taxonomy][$tt_id] = 1;
		
		// Determine whether this object is new (first time this PP filter has run for it, though the object may already be inserted into db)
		$last_parent = 0;
		
		if ( ! $last_parents = get_option( "pp_last_{$taxonomy}_parents" ) )
			$last_parents = array();
		
		if ( ! isset($last_parents[$tt_id]) ) {
			$is_new = true;
			$last_parents = array();
		} else
			$is_new = false;
		
		if ( isset( $last_parents[$tt_id] ) )
			$last_parent = $last_parents[$tt_id];

		if ( ($set_parent != $last_parent) && ($set_parent || $last_parent) ) {
			$last_parents[$tt_id] = $set_parent;
			update_option( "pp_last_{$taxonomy}_parents", $last_parents);
		}
		
		$exceptions_customized = false;
		if ( ! $is_new ) {
			if ( $custom_exc_objects = get_option( "pp_custom_{$taxonomy}" ) )
				$exceptions_customized = isset( $custom_exc_objects[$tt_id] );
		}

		global $typenow;
		
		require_once( dirname(__FILE__).'/item-save_pp.php' );
		$args = compact( 'is_new', 'set_parent', 'last_parent', 'disallow_manual_entry' );
		$args['via_item_type'] = $taxonomy;
		PP_ItemSave::item_update_process_exceptions( 'term', 'post', $tt_id, $args );
		
		do_action( 'pp_update_item_exceptions', 'term', $tt_id, $args );
	}
} // end class
	
