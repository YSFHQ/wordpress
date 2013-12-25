<?php
class PP_ItemSave {
	public static function item_update_process_exceptions( $via_item_source, $for_item_source, $item_id, $args = array() ) {
		$defaults = array_fill_keys( array( 'is_new', 'set_parent', 'last_parent', 'disallow_manual_entry', 'force_for_item_type' ), false );
		$args = apply_filters( 'pp_item_update_process_roles_args', 
								array_merge( $defaults, array( 'via_item_type' => '', 'for_item_status' => '' ), (array) $args, compact( 'via_item_source', 'for_item_source', 'item_id' ) ), 
								$via_item_source, $for_item_source, $item_id 
							);
		extract( $args, EXTR_SKIP );
		
		if ( $can_assign_roles = current_user_can( 'pp_assign_roles' ) ) {
			if ( apply_filters( 'pp_disable_exception_edit', false, $via_item_source, $item_id ) )
				$can_assign_roles = false;
		}
	
		if ( ! $disallow_manual_entry ) {
			$disallow_manual_entry = defined('XMLRPC_REQUEST');
		}

		$posted_exceptions = ( isset($_POST['pp_exceptions']) ) ? $_POST['pp_exceptions'] : array();
		
		if ( $posted_exceptions && ! $disallow_manual_entry && $can_assign_roles ) {
			foreach( array_keys($posted_exceptions) as $for_item_type ) {
				$_for_type = ( '(all)' == $for_item_type ) ? '' : $for_item_type;

				if ( $_for_type && ( 'post' == $for_item_source ) && ! post_type_exists( $_for_type ) )
					continue;

				if ( ( 'term' == $for_item_source ) && ! taxonomy_exists( $_for_type ) )
					continue;
				
				foreach( array_keys($posted_exceptions[$for_item_type]) as $op ) {
					if ( ! _pp_can_set_exceptions( $op, $for_item_type, compact( 'via_item_source', 'via_item_type', 'item_id', 'for_item_source' ) ) )
						continue;
				
					foreach( array_keys($posted_exceptions[$for_item_type][$op]) as $agent_type ) {
						$args['for_item_type'] = $_for_type;
						$args['operation'] = $op;
						$args['agent_type'] = $agent_type;
					
						if ( ppc_assign_exceptions( $posted_exceptions[$for_item_type][$op][$agent_type], $agent_type, $args ) ) {   // assignments[assign_for][agent_id] = has_access 
							$roles_customized = true;  // may be true already based on a prior role edit
						}
					} // end foreach group type
				}
			}
		}

		if ( ( 'post' == $via_item_source ) && ( 'post' == $for_item_source ) ) {
			if ( $post = get_post( $item_id ) ) {
				if ( 'attachment' == $post->post_type )  // don't propagate page exceptions to attachments
					return;
			}
		}
		
		self::inherit_parent_exceptions( $item_id, compact( 'via_item_source', 'via_item_type', 'set_parent', 'last_parent', 'is_new' ) );
	} // end function
	
	public static function inherit_parent_exceptions( $item_id, $args = array() ) {
		$defaults = array( 'via_item_source' => '', 'via_item_type' => '', 'set_parent' => '', 'last_parent' => '', 'is_new' => true );
		extract( array_merge( $defaults, $args ), EXTR_SKIP );
		
		$is_new_term = ( 'term' != $via_item_source ) ? false : ! empty($_REQUEST['action']) && ( 'add-tag' == $_REQUEST['action'] );
		
		// don't execute this action handler more than one per post save (may be called directly on pre-save cap check)
		static $did_items;
		if( 'post' == $via_item_source ) {
			if ( ! isset($did_items) ) { $did_items = array(); }
			if ( isset($did_items[$item_id]) ) { return; }
			$did_items[$item_id] = 1;
		}
		
		if ( ! apply_filters( 'pp_do_inherit_parent_exceptions', true, $item_id, $args ) )
			return;
		
		// Inherit exceptions from new parent post/term, but only for new items or if parent is changed
		if ( isset($set_parent) && ( $set_parent != $last_parent ) || $is_new_term ) {			
			// retain all explicitly selected exceptions
			global $wpdb;
			$descendant_ids = pp_get_descendant_ids( $via_item_source, $item_id );
			if ( $descendant_ids && ( 'term' == $via_item_source ) )
				$descendant_ids = pp_termid_to_ttid( $descendant_ids, $via_item_type );

			// clear previously propagated role assignments for this item and its branch of sub-items
	
			if ( ! $is_new ) {
				_pp_clear_exceptions( $via_item_source, $item_id, array( 'inherited_only' => true ) );
				_pp_clear_exceptions( $via_item_source, $descendant_ids, array( 'inherited_only' => true ) );
			}

			// assign propagating exceptions from new parent
			if ( $set_parent ) {
				$id_clause = "AND i.item_id IN ('" . implode( "','", array_merge( $descendant_ids, (array) $item_id ) ) . "')";
				$retain_exceptions = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->ppc_exception_items AS i INNER JOIN $wpdb->ppc_exceptions AS e ON e.exception_id = i.exception_id WHERE i.assign_for = 'item' AND i.inherited_from = '0' AND e.via_item_source = %s $id_clause", $via_item_source ) );
				
				if ( 'term' == $via_item_source ) {
					$parent_term = get_term( $set_parent, $via_item_type );
					$set_parent = $parent_term->term_taxonomy_id;
				}

				// propagate exception from new parent to this item and its branch of sub-items
				$_args = compact( 'retain_exceptions', 'force_for_item_type' );
				$_args['parent_exceptions'] = _pp_get_parent_exceptions( $via_item_source, $item_id, $set_parent ); 
				$any_inserts = _pp_inherit_parent_exceptions( $via_item_source, $item_id, $set_parent, $_args );

				foreach( $descendant_ids as $_descendant_id ) {
					$any_inserts = $any_inserts || _pp_inherit_parent_exceptions( $via_item_source, $_descendant_id, $set_parent, $_args );
				}
			} 
		} // endif new parent selection (or new item)
		
		return ! empty($any_inserts);
	}
	
	// PP Pro does not currently handle bbPress exceptions on individual topics and replies, so make sure those are not propagated
	public static function default_disable_parent_exceptions( $inherit_parent_exceptions, $item_id, $args ) {
		if ( $inherit_parent_exceptions && in_array( get_post_field( 'post_type', $item_id ), array( 'topic', 'reply' ) ) )
			return false;

		return $inherit_parent_exceptions;
	}
}
add_filter( 'pp_do_inherit_parent_exceptions', array( 'PP_ItemSave', 'default_disable_parent_exceptions' ), 5, 3 );

function _pp_clear_exceptions( $via_item_source, $item_id, $args = array() ) {
	$defaults = array ( 'inherited_only' => false );
	$args = array_merge( $defaults, (array) $args );
	extract($args, EXTR_SKIP);
	
	global $wpdb;
	
	if ( ! $item_id )
		return;
	
	$inherited_clause = ( $inherited_only ) ? "AND inherited_from > 0" : '';

	if ( is_array( $item_id ) )
		$id_clause = "AND i.item_id IN ('" . implode( "','", $item_id ) . "')";
	else
		$id_clause = $wpdb->prepare( "AND i.item_id = %d", $item_id );
	
	if ( $ass_ids = $wpdb->get_col( $wpdb->prepare( "SELECT i.eitem_id FROM $wpdb->ppc_exception_items AS i INNER JOIN $wpdb->ppc_exceptions AS e ON e.exception_id = i.exception_id WHERE e.via_item_source = %s $inherited_clause $id_clause", $via_item_source ) ) ) {
		require_once( dirname(__FILE__).'/role_assigner_pp.php' );
		PP_RoleAssigner::remove_exception_items_by_id( $ass_ids );
	}
}

function _pp_inherit_parent_exceptions( $via_item_source, $item_id, $parent_id, $args = array() ) {
	require_once( dirname(__FILE__).'/role_assigner_pp.php' );
	
	$defaults = array( 'parent_exceptions' => array(), 'retain_exceptions' => array(), 'force_for_item_type' => '' );
	$args = array_merge( $defaults, $args );
	extract( $args, EXTR_SKIP );
	
	if ( ! $parent_exceptions )
		$parent_exceptions = _pp_get_parent_exceptions( $via_item_source, $item_id, $parent_id ); 
		
	foreach( $retain_exceptions as $r ) {	// can't just compare exception_id because want to avoid inheriting an include exception if an exclude is manually stored, etc.
		foreach( $parent_exceptions as $ekey => $e ) {
			if ( $r->item_id == $item_id
			&& $r->via_item_source == $e->via_item_source
			&& $r->agent_type == $e->agent_type
			&& $r->agent_id == $e->agent_id
			&& $r->for_item_source == $e->for_item_source
			&& $r->for_item_type == $e->for_item_type
			&& $r->operation == $e->operation
			&& $r->for_item_status == $e->for_item_status 
			) {
				unset( $parent_exceptions[$ekey] );
			}
		}
	}

	if ( $parent_exceptions ) {
		$item_type = get_post_field( 'post_type', $item_id );
	
		foreach( $parent_exceptions as $exc ) {
			$insert_agents = array( $exc->agent_id => true );
			$args = array( 'for_item_status' => $exc->for_item_status, 'inherited_from' => array( $exc->agent_id => $exc->eitem_id ) );	

			//$for_item_type = ( $force_for_item_type ) ? $force_for_item_type : $exc->for_item_type;
			if ( $force_for_item_type )
				$for_item_type = $force_for_item_type;
			else
				$for_item_type = ( 'revision' == $item_type ) ? $exc->for_item_type : $item_type;
			
			foreach( array( 'item', 'children' ) as $assign_for ) {
				$args['assign_for'] = $assign_for;

				if ( PP_RoleAssigner::insert_exceptions( $exc->mod_type, $exc->operation, $exc->via_item_source, $exc->via_item_type, $exc->for_item_source, $for_item_type, $item_id, $exc->agent_type, $insert_agents, $args ) )
					$any_inserts = true;
			}
		}
	}
	
	return ! empty($any_inserts);
}

function _pp_get_parent_exceptions( $via_item_source, $item_id, $parent_id ) {
	global $wpdb;

	// Since this is a new object, propagate roles from parent (if any are marked for propagation)
	return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->ppc_exception_items AS i INNER JOIN $wpdb->ppc_exceptions AS e ON e.exception_id = i.exception_id WHERE e.via_item_source = %s AND i.assign_for = 'children' AND i.item_id = %d", $via_item_source, $parent_id ) );
}
