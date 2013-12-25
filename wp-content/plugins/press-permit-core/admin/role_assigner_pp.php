<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class PP_RoleAssigner {
	// additional arguments recognized by insert_role_assignments():
	//	is_auto_insertion = false  (if true, skips logging the item as having a manually modified role assignment)
	public static function assign_roles( $assignments, $agent_type = 'user', $args = array() ) {
		//$defaults = array();
		//$args = array_merge($defaults, (array) $args);
		//extract($args, EXTR_SKIP);

		global $wpdb;
		$results = $wpdb->get_results( $wpdb->prepare("SELECT agent_id, assignment_id, role_name FROM $wpdb->ppc_roles WHERE agent_type = %s", $agent_type ) );

		$stored_assignments = array();
		
		foreach ($results as $key => $ass) {
			// Note: each role should have at most one stored assignment for the item/role/agent combination.  But stored_assignments is keyed by assignment_id to deal with any redundant assignments
			$stored_assignments[$ass->role_name][$ass->agent_id][$ass->assignment_id] = true;
		}

		$any_changes = false;
		
		foreach ( array_keys($assignments) as $role_name ) {		
			if ( isset($stored_assignments[$role_name]) && ! array_diff_key( $assignments[$role_name], $stored_assignments[$role_name] ) && ! array_diff_key( $stored_assignments[$role_name], $assignments[$role_name] ) ) {
				// no changes for this role
				continue;
			}
			
			if ( isset( $stored_assignments[$role_name] ) )
				$assignments[$role_name] = array_diff_key( $assignments[$role_name], $stored_assignments[$role_name] );

			if ( $assignments[$role_name] ) {
				self::insert_role_assignments($role_name, $agent_type, $assignments[$role_name], $args );
				$any_changes = true;
			}
		}
		
		// return true if added, deleted or modified any assignments
		return $any_changes;
	}

	public static function insert_role_assignments ( $role_name, $agent_type, $agents, $args = array()) {
		//$defaults = array( );  // auto_insertion arg set for role propagation from parent objects
		//$args = array_merge( $defaults, (array) $args );
		//extract($args, EXTR_SKIP);

		if ( ! $agents )
			return;

		global $wpdb, $current_user;
		
		$assigner_id = $current_user->ID;
		$insert_data = compact( 'role_name', 'agent_type', 'assigner_id' );
		
		// Before inserting a role, delete any overlooked old assignment.
		foreach( array_keys($agents) as $agent_id ) {
			if ( ! $agent_id )
				continue;
		
			if ( $ass_ids = $wpdb->get_col( $wpdb->prepare( "SELECT assignment_id FROM $wpdb->ppc_roles WHERE role_name = %s AND agent_id = %d", $role_name, $agent_id ) ) ) {
				PP_RoleAssigner::remove_roles_by_id( $ass_ids );
			}

			// insert role for specified object and group(s)
			$insert_data['agent_id'] = $agent_id;
			$wpdb->insert( $wpdb->ppc_roles, $insert_data );
			$assignment_id = $wpdb->insert_id;
			
			do_action( 'pp_assigned_sitewide_role', $assignment_id, compact( 'role_name', 'agent_type', 'agent_id', 'assigner_id' ) );
		}
	}

	public static function remove_roles_by_id( $delete_assignments ) {
		$delete_assignments = (array) $delete_assignments;

		if ( ! count($delete_assignments) )
			return;

		global $wpdb;
		
		$where = "assignment_id IN ('" . implode("', '", $delete_assignments ) . "')";

		$roles = $wpdb->get_results( "SELECT * FROM $wpdb->ppc_roles WHERE $where" );	// deleted role data will be passed through action
		$wpdb->query( "DELETE FROM $wpdb->ppc_roles WHERE $where" );

		$item_role_actions = apply_filters( 'pp_individual_role_deletion_hooks', false );

		foreach( $roles as $role ) {
			do_action( 'pp_removed_sitewide_role', $role->assignment_id, (array) $role );
		}
		
		do_action( 'pp_removed_roles', $delete_assignments );
	}

// see function doc for ppc_assign_exceptions
// 
// additional arguments recognized by self::insert_exceptions():
//		 is_auto_insertion = false  (if true, skips logging the item as having a manually modified role assignment)
public static function assign_exceptions( $agents, $agent_type = 'user', $args = array() ) {   // agents[assign_for][agent_id] = has_access 
	$defaults = array( 'operation' => '', 'mod_type' => '', 'for_item_source' => '', 'for_item_type' => '', 'for_item_status' => '', 
					   'via_item_source' => '', 'item_id' => 0, 'via_item_type' => '', 'remove_assignments' => false, );
	
	$args = array_merge($defaults, (array) $args);
	extract($args, EXTR_SKIP);
	
	// temp workaround for Revisionary (otherwise lose page-assigned roles on revision approval)
	if ( ! empty($_REQUEST['page']) && ( 'rvy-revisions' == $_REQUEST['page'] ) ) {
		return;
	}
	
	if ( 'wp_role' == $agent_type )
		$agent_type = 'pp_group';
	
	global $wpdb;
	
	if ( ! $via_item_type && ( 'term' == $via_item_source ) ) { 	// separate sets of include/exclude exceptions for each taxonomy
		if ( $for_item_source == $via_item_source )
			$via_item_type = $for_item_type;
		//else
		//	return false;
	}

	$operation = pp_sanitize_key($operation);
	$via_item_source = pp_sanitize_key($via_item_source);
	$for_item_source = pp_sanitize_key($for_item_source);
	$for_item_type = pp_sanitize_key($for_item_type);
	$item_id = (int) $item_id;
	$agent_type = pp_sanitize_key($agent_type);
	$mod_type = pp_sanitize_key($mod_type);
	$via_item_type = pp_sanitize_key($via_item_type);
	$for_item_status = pp_sanitize_csv($for_item_status);
	
	$agent_ids = array();
	foreach( array_keys($agents) as $_assign_for )
		$agent_ids = array_merge( $agent_ids, array_keys($agents[$_assign_for]) );
	
	$where = "e.agent_type = '$agent_type' AND e.agent_id IN ('" . implode("','", $agent_ids) . "') AND e.operation = '$operation' AND e.via_item_source = '$via_item_source' AND e.via_item_type = '$via_item_type' AND e.for_item_source = '$for_item_source' AND e.for_item_type = '$for_item_type' AND e.for_item_status = '$for_item_status'";

	$qry = "SELECT e.agent_id, e.exception_id, e.for_item_status, e.mod_type, i.assign_for, i.inherited_from FROM $wpdb->ppc_exceptions AS e INNER JOIN $wpdb->ppc_exception_items AS i ON e.exception_id = i.exception_id WHERE i.item_id = '$item_id' AND $where";
	$results = $wpdb->get_results($qry);
	
	$stored_assignments = array();
	
	foreach ( $results as $key => $ass ) {
		$stored_assignments[$ass->mod_type][$ass->assign_for][$ass->agent_id] = $ass->exception_id;
	}

	$delete_agents_from_eitem = array( 'item' => array(), 'children' => array() );
	$delete_eids_from_eitem = array( 'item' => array(), 'children' => array() );
	
	/*
	$is_administrator = pp_is_administrator( $src_name, 'user' );
	
	if ( ! $is_administrator ) {
		$user_has_role = _pp_validate_assigner_roles($scope, $src_name, $item_id, $agents);
		$agents = array_intersect_key( $agents, $user_has_role );
	}
	*/
	
	$assigned_items = array(); // for use with 'item_roles_updated' action
	
	$any_changes = false;

	if ( $mod_type ) {
		foreach( array_keys($agents) as $assign_for ) {
			if ( ! isset( $delete_agents_from_eitem[$assign_for] ) )
				$delete_agents_from_eitem[$assign_for] = array();
		
			// insert any exceptions which are not stored already
			if ( $has_access = array_intersect( $agents[$assign_for], array(true) ) ) {
				$_stored = ( isset($stored_assignments[$mod_type][$assign_for]) ) ? $stored_assignments[$mod_type][$assign_for] : array();
				if ( $insert_agents = array_diff_key( $has_access, $_stored ) ) {
					$args['assign_for'] = $assign_for;			
					$_assigned_items = self::insert_exceptions( $mod_type, $operation, $via_item_source, $via_item_type, $for_item_source, $for_item_type, $item_id, $agent_type, $insert_agents, $args );
					$assigned_items = array_merge( $assigned_items, $_assigned_items );
					
					$any_changes = true;
				}
			}
			
			// if an include exception is passed in with value=false, remove corresponding stored exception
			if ( $no_access = array_intersect( $agents[$assign_for], array(false) ) ) {
				if ( $no_access = array_intersect_key( $stored_assignments[$mod_type][$assign_for], $no_access ) ) {
					$delete_agents_from_eitem[$assign_for] = array_merge( $delete_agents_from_eitem[$assign_for], array_keys($no_access) );
					$any_changes = true;
				}
			}
		}
	} else {
		$coded_mod_type = array( '0' => 'exclude', '1' => 'include', '2' => 'additional' );
	
		// usage from item edit UI
		foreach( array_keys($agents) as $assign_for ) {
			// if an agent has been set to default access, delete stored exceptions
			if ( $default_access = array_intersect( $agents[$assign_for], array('') ) ) {
				foreach( array_keys( $stored_assignments ) as $_mod_type ) {
					if ( isset( $stored_assignments[$_mod_type][$assign_for] ) ) {
						if ( $_default_access = array_intersect_key( $stored_assignments[$_mod_type][$assign_for], $default_access ) ) {		
							$delete_agents_from_eitem[$assign_for] = array_merge( $delete_agents_from_eitem[$assign_for], array_keys($_default_access) );
							$any_changes = true;
						}
					}
				}
				
				$agents[$assign_for] = array_diff( $agents[$assign_for], array('') );
			}

			foreach( $agents[$assign_for] as $agent_id => $exc_code ) {
				if ( is_numeric($exc_code) && in_array( $exc_code, array_keys($coded_mod_type) ) ) {
					// delete exceptions stored for different mod type
					foreach( array_keys( $stored_assignments ) as $_mod_type ) {
						if ( ( $_mod_type != $coded_mod_type[$exc_code] ) && isset( $stored_assignments[$_mod_type][$assign_for][$agent_id] ) ) {
							$delete_eids_from_eitem[$assign_for][]= $stored_assignments[$_mod_type][$assign_for][$agent_id];
							$any_changes = true;
						}
					}
					
					// disregard exceptions which are already stored
					if ( isset( $stored_assignments[ $coded_mod_type[$exc_code] ][$assign_for][$agent_id] ) )
						unset( $agents[$assign_for][$agent_id] );
				}
			}

			// any remaining posted exeptions (with exclude/include/additional setting) need to be inserted
			if ( $agents[$assign_for] ) {
				$args['assign_for'] = $assign_for;		
			
				foreach( $coded_mod_type as $exc_code => $_mod_type ) {	
					if ( $insert_agents = array_intersect( $agents[$assign_for], array("$exc_code") ) ) {
						$_assigned_items = self::insert_exceptions( $_mod_type, $operation, $via_item_source, $via_item_type, $for_item_source, $for_item_type, $item_id, $agent_type, $insert_agents, $args );
						$assigned_items = array_merge( $assigned_items, $_assigned_items );
						$any_changes = true;
					}
				}
			}
		}
	}

	foreach( $delete_eids_from_eitem as $assign_for => $delete_exception_ids ) {
		if ( $delete_exception_ids ) {
			$assign_for = pp_sanitize_key($assign_for);
			if ( $delete_eitem_ids = $wpdb->get_col( "SELECT eitem_id FROM $wpdb->ppc_exception_items WHERE assign_for = '$assign_for' AND item_id = '$item_id' AND exception_id IN ('" . implode( "','", $delete_exception_ids ) . "')" ) )
				self::remove_exception_items_by_id( $delete_eitem_ids );
		}
	}
	
	if ( $delete_agents_from_eitem['item'] || $delete_agents_from_eitem['children'] ) {
		// first retrieve ppc_exceptions records for each agent
		$stored_e = $wpdb->get_results( "SELECT e.agent_id, e.exception_id FROM $wpdb->ppc_exceptions AS e WHERE $where" );
		
		foreach( $delete_agents_from_eitem as $assign_for => $delete_agent_exceptions ) {
			$exception_ids = array();
			foreach( $stored_e as $row ) {
				if ( in_array( $row->agent_id, $delete_agent_exceptions ) )
					$exception_ids []= $row->exception_id;
			}
	
			if ( $exception_ids ) {
				$assign_for = pp_sanitize_key($assign_for);
				if ( $delete_eitem_ids = $wpdb->get_col( "SELECT eitem_id FROM $wpdb->ppc_exception_items WHERE assign_for = '$assign_for' AND item_id = '$item_id' AND exception_id IN ('" . implode( "','", $exception_ids ) . "')" ) ) {
					self::remove_exception_items_by_id( $delete_eitem_ids );
				}
			}
		}
	}

	if ( apply_filters( 'pp_exception_item_update_hooks', false ) ) {
		$assigned_items = array_unique( $assigned_items );
		foreach( $assigned_items as $item_id ) {
			do_action( 'pp_exception_items_updated', $via_item_source, $item_id );	// called once per item (even if multiple role assignments / removals for that item)
		}
	}

	// return true if added, deleted or modified any assignments
	return $any_changes;
	
	// possible @todo: reinstate this after further testing
	//_pp_delete_orphan_roles($scope, $src_name);
}

public static function insert_exceptions( $mod_type, $operation, $via_item_source, $via_item_type, $for_item_source, $for_item_type, $item_id, $agent_type, $agents, $args ) {	
	$defaults = array( 'assign_for' => 'item', 'remove_assignments' => false, 'for_item_status' => '', 'mod_type' => '', 'inherited_from' => array(), 'is_auto_insertion' => false );  // auto_insertion arg set for propagation from parent objects
	$args = array_merge($defaults, (array) $args);
	extract($args, EXTR_SKIP);

	if ( ! $agents )
		return;

	global $wpdb, $current_user;
	
	$updated_items = array(); // for use with do_action hook
	$updated_items[] = $item_id;

	$assigner_id = $current_user->ID;

	$operation = pp_sanitize_key($operation);
	$via_item_source = pp_sanitize_key($via_item_source);
	$for_item_source = pp_sanitize_key($for_item_source);
	$for_item_type = pp_sanitize_key($for_item_type);
	$item_id = (int) $item_id;
	$agent_type = pp_sanitize_key($agent_type);
	$mod_type = pp_sanitize_key($mod_type);
	$via_item_type = pp_sanitize_key($via_item_type);
	$for_item_status = pp_sanitize_csv($for_item_status);
	$assign_for = pp_sanitize_key($assign_for);
	
	if ( 'children' == $assign_for ) {
		if ( 'term' == $via_item_source ) {
			$descendant_ids = array();
			if ( $_term = $wpdb->get_row( "SELECT term_id, taxonomy FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = '$item_id' LIMIT 1" ) ) {
				if ( $_term_ids = pp_get_descendant_ids( 'term', $_term->term_id ) ) {
					$descendant_ids = pp_termid_to_ttid( $_term_ids, $_term->taxonomy );
				}
			}
		} else {
			$descendant_ids = pp_get_descendant_ids( $via_item_source, $item_id, array( 'include_attachments' => false ) );  // don't propagate page exceptions to attachments
		}
	
		if ( $descendant_ids ) {
			// TODO: reinstate this?
			
			/*
			global $pp_admin;
			
			if ( ! $is_auto_insertion ) {
				// don't allow a page parent change to modify role assignments for a descendant object which the current user can't administer
				$remove_ids = array();
				foreach ( $descendant_ids as $id ) {
					if ( 'term' == $scope ) {
						if ( ! $pp_admin->user_can_admin_terms($item_source, $id) )  // TODO: add $args with 'taxonomy'
							$remove_ids []= $id;
					} else {
						if ( ! $pp_admin->user_can_admin_object( $item_source, $id ) )
							$remove_ids []= $id;
					}
				}

				$descendant_ids = array_diff( $descendant_ids, $remove_ids );
			}
			*/
			
			$descendant_id_csv = implode( "','", $descendant_ids );
		}
	}
	
	// Before inserting an exception, delete any overlooked old exceptions for the same src/type/status.

	$match_cols = compact( 'mod_type', 'for_item_source', 'for_item_status', 'operation', 'agent_type', 'via_item_source', 'via_item_type' );
	
	$_clauses = array();
	foreach( $match_cols as $col => $val )
		$_clauses[] = "$col = '$val'";

	$qry_exc_select_base = "SELECT * FROM $wpdb->ppc_exceptions WHERE " . implode( ' AND ', $_clauses );
	$qry_exc_select_type_base = "SELECT for_item_type, exception_id FROM $wpdb->ppc_exceptions WHERE " . implode( ' AND ', $_clauses );
	
	$insert_exc_data = $match_cols;
	$insert_exc_data['assigner_id'] = $assigner_id;

	$qry_item_select_base = "SELECT eitem_id FROM $wpdb->ppc_exception_items WHERE assign_for = '$assign_for' AND item_id = '$item_id'";
	
	$qry_item_delete_base = "SELECT eitem_id FROM $wpdb->ppc_exception_items WHERE 1=1";
	
	foreach( array_keys($agents) as $agent_id ) {
		$agent_id = (int) $agent_id;
	
		// first, retrieve or create the pp_exceptions record for this user/group and src,type,status
		if ( ! $exc = $wpdb->get_row( "$qry_exc_select_base AND for_item_type = '$for_item_type' AND agent_id = '$agent_id'" ) ) {
			$insert_exc_data['agent_id'] = $agent_id;
			$insert_exc_data['for_item_type'] = $for_item_type;
			$wpdb->insert( $wpdb->ppc_exceptions, $insert_exc_data );
			$exception_id = $wpdb->insert_id;
		} else {
			$exception_id = $exc->exception_id;
		}

		$this_inherited_from = ( isset($inherited_from[$agent_id]) ) ? $inherited_from[$agent_id] : 0;

		// delete any existing items for this exception_id
		if ( $eitem_ids = $wpdb->get_col( $qry_item_select_base . " AND exception_id = '$exception_id'" ) ) {
			self::remove_exception_items_by_id( $eitem_ids );
		}

		// insert exception items
		$item_data = compact( 'item_id', 'assign_for', 'exception_id', 'assigner_id' );
		$item_data['inherited_from'] = $this_inherited_from;
		$wpdb->insert( $wpdb->ppc_exception_items, $item_data );
		do_action( 'pp_inserted_exception_item', array_merge( (array) $exc, $item_data ) );
		$assignment_id = $wpdb->insert_id;
		
		// insert exception for all descendant items
		if ( ( 'children' == $assign_for ) && $descendant_ids ) {
			if ( ! $this_inherited_from ) {
				$this_inherited_from = (int) $assignment_id;
				//$role_arr['inherited_from'] = $this_inherited_from;
			}

			$exceptions_by_type = array();
			$_results = $wpdb->get_results( "$qry_exc_select_type_base AND for_item_type = '$for_item_type' AND agent_id = '$agent_id'" );
			foreach( $_results AS $row )
				$exceptions_by_type[$row->for_item_type] = $row->exception_id;

			if ( ( 'term' == $via_item_source ) && taxonomy_exists($for_item_type) )  // need to allow for descendants of a different post type than parent
				$descendant_types = $wpdb->get_results( "SELECT term_taxonomy_id, taxonomy AS for_item_type FROM $wpdb->term_taxonomy WHERE term_taxonomy_id IN ('" . implode( "','", $descendant_ids ) . "')", OBJECT_K );
			elseif ( 'post' == $via_item_source )
				$descendant_types = $wpdb->get_results( "SELECT ID, post_type AS for_item_type FROM $wpdb->posts WHERE ID IN ('" . implode( "','", $descendant_ids ) . "')", OBJECT_K );
			else
				$descendant_types = array();
			
			foreach ( $descendant_ids as $id ) {
				if ( $for_item_type ) {
					// allow for descendants with post type different from parent
					if ( ! isset( $descendant_types[$id] ) ) {
						$child_for_item_type = $for_item_type;  // if child type could not be determined, assume parent type
					
					} elseif ( 'revision' == $descendant_types[$id]->for_item_type ) {
						continue;
						
					} else {
						$child_for_item_type = $descendant_types[$id]->for_item_type;
					}
				} else {
					$child_for_item_type = '';
				}
				
				if ( ! isset( $exceptions_by_type[$child_for_item_type] ) ) {
					$insert_exc_data['agent_id'] = $agent_id;
					$insert_exc_data['for_item_type'] = $child_for_item_type;
					$wpdb->insert( $wpdb->ppc_exceptions, $insert_exc_data );
					$exceptions_by_type[$child_for_item_type] = $wpdb->insert_id;
				}
				
				$child_exception_id = $exceptions_by_type[$child_for_item_type];
				
				// Don't overwrite an explicitly assigned exception with a propagated exception
				$have_direct_assignments = $wpdb->get_col( "SELECT item_id FROM $wpdb->ppc_exception_items WHERE exception_id = '$child_exception_id' AND inherited_from = '0' AND item_id IN ('$descendant_id_csv')" );
			
				if ( in_array( $id, $have_direct_assignments ) )
					continue;
					
				if ( $eitem_ids = $wpdb->get_col( $qry_item_delete_base . " AND exception_id = '$child_exception_id' AND item_id = '$id'" ) ) {
					self::remove_exception_items_by_id( $eitem_ids );
				}
				
				// note: Propagated roles will be converted to direct-assigned roles if the parent object/term is deleted.
				//$role_arr['item_id'] = $id;
				
				$item_data = array( 'item_id' => $id, 'assign_for' => 'item', 'exception_id' => $child_exception_id, 'inherited_from' => $this_inherited_from, 'assigner_id' => $assigner_id );
				$wpdb->insert( $wpdb->ppc_exception_items, $item_data );
				do_action( 'pp_inserted_exception_item', array_merge( (array) $exc, $item_data ) );
				//if ( $role_hooks ) {
				//	$assignment_id = $wpdb->insert_id;
				//	$role_arr['assign_for'] = 'item';
				//}

				$item_data['assign_for'] = 'children';
				$wpdb->insert( $wpdb->ppc_exception_items, $item_data );
				do_action( 'pp_inserted_exception_item', array_merge( (array) $exc, $item_data ) );
				//if ( $role_hooks ) {
				//	$assignment_id = $wpdb->insert_id;
				//	$role_arr['assign_for'] = 'children';
				//}

				$updated_items[] = $id;
			}
		}
	} // end foreach agent_id
	
	
	return $updated_items;
}

public static function remove_exception_items_by_id( $eitem_ids ) {
	$eitem_ids = (array) $eitem_ids;

	if ( ! count($eitem_ids) )
		return;

	global $wpdb;
	
	// Propagated roles will be deleted only if the original progenetor goes away.  Removal of a "link" in the parent/child propagation chain has no effect.
	$id_in = "'" . implode("', '", $eitem_ids ) . "'";
	
	$where = "i.eitem_id IN ($id_in) OR (i.inherited_from IN ($id_in) AND i.inherited_from != '0')";
	$exc_items = $wpdb->get_results( "SELECT e.agent_type, e.agent_id, e.for_item_source, e.for_item_type, e.for_item_status, e.operation, e.mod_type, e.via_item_source, e.via_item_type, i.eitem_id, i.exception_id, i.item_id FROM $wpdb->ppc_exception_items AS i INNER JOIN $wpdb->ppc_exceptions AS e ON e.exception_id = i.exception_id WHERE $where" );	// deleted entries will be returned
	
	$eitem_ids = array();
	foreach( $exc_items as $row ) {
		$eitem_ids []= $row->eitem_id;
	}
	
	$id_csv = implode( "','", $eitem_ids );
	$wpdb->query( "DELETE FROM $wpdb->ppc_exception_items WHERE eitem_id IN ('$id_csv')" );

	$deleted = array();
	
	static $do_item_actions;
	if ( ! isset( $do_item_actions ) )
		$do_item_actions = apply_filters( 'pp_exception_item_deletion_hooks', false );
	
	foreach( $exc_items as $row ) {
		$deleted[$row->eitem_id] = true;
		
		if ( $do_item_actions )
			do_action( 'pp_removed_exception_item', $row->eitem_id, $row );    // called once per removed role (potentially multiple per item)
	}
	
	do_action( 'pp_removed_exception_items', $eitem_ids );
	
	return $deleted;
}
	
} // end class
