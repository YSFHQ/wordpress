<?php
	function _pp_mnt_delete_post( $post_id ) {
		if ( ! $post_id )
			return;

		// could defer role/cache maint to speed potential bulk deletion, but script may be interrupted before admin_footer
		_pp_item_deletion_aftermath( 'post', $post_id );
	}

	function _pp_mnt_delete_term( $term_id, $tt_id, $taxonomy ) {
		global $wpdb;
	
		if ( ! $tt_id )
			return;
		
		// could defer role/cache maint to speed potential bulk deletion, but script may be interrupted before admin_footer
		_pp_item_deletion_aftermath( 'term', $tt_id );
	}
	
	function _pp_item_deletion_aftermath( $item_source, $item_id ) {
		global $wpdb;

		require_once( dirname(__FILE__).'/role_assigner_pp.php' );
		
		// delete role assignments for deleted term
		if ( $eitem_ids = $wpdb->get_col( "SELECT eitem_id FROM $wpdb->ppc_exception_items AS i INNER JOIN $wpdb->ppc_exceptions AS e ON e.exception_id = i.exception_id WHERE e.via_item_source = '$item_source' AND i.item_id = '$item_id'" ) ) {
			PP_RoleAssigner::remove_exception_items_by_id( $eitem_ids );

			// Propagated roles will be converted to direct-assigned roles if the original progenetor goes away.  Removal of a "link" in the parent/child propagation chain has no effect.
			$id_in = "'" . implode("', '", $eitem_ids) . "'";
			$wpdb->query( "UPDATE $wpdb->ppc_exception_items SET inherited_from = '0' WHERE inherited_from IN ($id_in)" );
		}
	}
	
