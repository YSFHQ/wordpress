<?php

// ===== begin 2.1.35 cleanup =====

// returns propagated exceptions items for which (a) the base eitem no longer exists, or (b) the base eitem was changed to "item only"
function _ppc_get_orphaned_exception_items() {
	global $wpdb;
	return $wpdb->get_col( "SELECT eitem_id FROM $wpdb->ppc_exception_items WHERE inherited_from > 0 AND inherited_from NOT IN ( SELECT eitem_id FROM $wpdb->ppc_exception_items WHERE assign_for = 'children' )" );
}

function _ppc_expose_orphaned_exception_items() {
	global $wpdb;

	if ( $eitem_ids = _ppc_get_orphaned_exception_items() ) {
		$wpdb->query( "UPDATE $wpdb->ppc_exception_items SET inherited_from = 0 WHERE eitem_id IN ('" . implode( "','", $eitem_ids ) . "')" );
		
		// keep a log in case questions arise
		if ( ! $arr = get_option( 'ppc_exposed_eitem_orphans' ) )
			$arr = array();
	
		$arr = array_merge( $arr, $eitem_ids );
		update_option( 'ppc_exposed_eitem_orphans', $arr );
	}
}

// returns propagated exceptions items for which the affected item is an attachment
function _ppc_get_propagated_attachment_exceptions() {
	global $wpdb;
	return $wpdb->get_col( "SELECT eitem_id FROM $wpdb->ppc_exception_items AS i INNER JOIN $wpdb->ppc_exceptions AS e ON e.exception_id = i.exception_id WHERE e.for_item_source = 'post' AND i.inherited_from > 0 AND e.for_item_type = 'attachment'" );
}

function _ppc_expose_attachment_exception_items() {
	global $wpdb;

	if ( $eitem_ids = _ppc_get_propagated_attachment_exceptions() ) {
		$wpdb->query( "UPDATE $wpdb->ppc_exception_items SET inherited_from = 0 WHERE eitem_id IN ('" . implode( "','", $eitem_ids ) . "')" );
		
		// keep a log in case questions arise
		if ( ! $arr = get_option( 'ppc_exposed_attachment_eitems' ) )
			$arr = array();

		$arr = array_merge( $arr, $eitem_ids );
		update_option( 'ppc_exposed_attachment_eitems', $arr );
	}
}

function _ppc_delete_propagated_attachment_exceptions() {
	global $wpdb;
	
	// first, delete any attachment exception with assign_for = 'children'
	if ( $eitem_ids = $wpdb->get_col( "SELECT eitem_id FROM $wpdb->ppc_exception_items AS i INNER JOIN $wpdb->ppc_exceptions AS e ON e.exception_id = i.exception_id WHERE e.for_item_source = 'post' AND e.for_item_type = 'attachment' AND i.assign_for = 'children'" ) ) {
		$wpdb->query( "DELETE FROM $wpdb->ppc_exception_items WHERE eitem_id IN ('" . implode( "','", $eitem_ids ) . "')" );
	}
	
	// page exceptions should not be propagated to attachments (but were prior to 2.1.35)
	foreach( array( 'read', 'associate' ) as $operation ) { // retain editing exceptions - to be exposed by _ppc_get_propagated_attachment_exceptions()
		$mod_type_clause = ( 'associate' == $operation ) ? '' : "AND e.mod_type != 'include'"; // keep include exceptions in case they are being relied upon to modify access to other media.  They will be exposed by _ppc_get_propagated_attachment_exceptions().
		$qry = "SELECT eitem_id, item_id FROM $wpdb->ppc_exception_items AS i INNER JOIN $wpdb->ppc_exceptions AS e ON e.exception_id = i.exception_id WHERE i.inherited_from > 0 AND e.for_item_source = 'post' AND e.for_item_type = 'attachment' AND e.operation = '$operation' $mod_type_clause";

		if ( $results = $wpdb->get_results( $qry ) ) {
			$eitem_ids = array();
			$item_ids = array();
			
			foreach( $results as $row ) {
				$eitem_ids []= $row->eitem_id;
				$item_ids []= $row->item_id;
			}

			$wpdb->query( "DELETE FROM $wpdb->ppc_exception_items WHERE eitem_id IN ('" . implode( "','", $eitem_ids ) . "')" );
			
			// keep a log in case questions arise
			if ( ! $arr = get_option( "ppc_deleted_{$operation}_exc_attachments" ) )
				$arr = array();

			$arr = array_merge( $arr, array_unique($item_ids) );
			update_option( "ppc_deleted_{$operation}_exc_attachments", $arr );
		}
	}
}

// ===== end 2.1.35 cleanup =====

?>