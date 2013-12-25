<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! pp_bulk_roles_enabled() )
	exit;

global $wpdb;
	
$html = '';

$agent_type = ( ! empty($_GET['agent_type']) ) ? pp_sanitize_key( $_GET['agent_type'] ) : '';;
$agent_id = ( ! empty($_GET['agent_id']) ) ? (int) $_GET['agent_id'] : 0;

// safeguard prevents accidental modification of roles for other groups / users
if ( $agent_type && $agent_id )
	$agent_clause = "agent_type = '$agent_type' AND agent_id = '$agent_id' AND";
else
	$agent_clause = '';
	
$action = $_GET['pp_ajax_submission'];

switch ( $action ) {
	case 'roles_remove':
		if ( empty($_GET['pp_ass_ids']) )
			exit;

		if ( ! current_user_can( 'pp_assign_roles' ) || ! pp_bulk_roles_enabled() )
			exit;

		$deleted_ass_ids = array();
		
		$input_vals = explode( '|', pp_sanitize_csv($_GET['pp_ass_ids']) );
		foreach( $input_vals as $id_csv ) {
			$ass_ids = _pp_editable_assignment_ids( explode(',', $id_csv) );
			$deleted_ass_ids = array_merge( $deleted_ass_ids, $ass_ids );
		}
		
		if ( $deleted_ass_ids ) {
			require_once( dirname(__FILE__).'/role_assigner_pp.php' );
		
			$results = $wpdb->get_results( "SELECT agent_type, agent_id, role_name FROM $wpdb->ppc_roles WHERE $agent_clause assignment_id IN ('" . implode( "','", $deleted_ass_ids ) . "')" );
			foreach( $results as $row ) {
				$this_group_clase = ( $agent_clause ) ? $agent_clause : "agent_type = '$row->agent_type' AND agent_id = '$row->agent_id' AND";
				if ( $_ass_ids = $wpdb->get_col( "SELECT assignment_id FROM $wpdb->ppc_roles WHERE $this_group_clase role_name='$row->role_name'" ) ) {
					PP_RoleAssigner::remove_roles_by_id( $_ass_ids );
				}
			}
		}
		
		echo '<!--ppResponse-->' . implode( '|', $input_vals ) . '<--ppResponse-->';
		break;
		
	case 'exceptions_remove':
		if ( empty($_GET['pp_eitem_ids']) )
			exit;

		if ( ! current_user_can( 'pp_assign_roles' ) || ! pp_bulk_roles_enabled() )
			exit;

		$deleted_eitem_ids = array();
		
		$input_vals = explode( '|', pp_sanitize_csv($_GET['pp_eitem_ids']) );
		foreach( $input_vals as $id_csv ) {
			$eitem_ids = _pp_editable_eitem_ids( explode(',', $id_csv) );
			$deleted_eitem_ids = array_merge( $deleted_eitem_ids, $eitem_ids );

			// possible TODO: remove elem from $input_vals if content-specific assign_roles authentication fails
		}
		
		if ( $deleted_eitem_ids ) {
			require_once( dirname(__FILE__).'/role_assigner_pp.php' );
		
			$exc_clause = ( $agent_clause ) ? "exception_id IN ( SELECT exception_id FROM $wpdb->ppc_exceptions WHERE $agent_clause 1=1 ) AND" : '';

			$results = $wpdb->get_results( "SELECT exception_id, item_id FROM $wpdb->ppc_exception_items WHERE $exc_clause eitem_id IN ('" . implode( "','", $deleted_eitem_ids ) . "')" );  // safeguard against accidental deletion of a different agent's exceptions

			foreach( $results as $row ) {
											// also delete any redundant item exceptions for this agent
				if ( $_eitem_ids = $wpdb->get_col( "SELECT eitem_id FROM $wpdb->ppc_exception_items WHERE exception_id='$row->exception_id' AND item_id='$row->item_id'" ) ) {
					PP_RoleAssigner::remove_exception_items_by_id( $_eitem_ids );
				}
			}
		}
		
		echo '<!--ppResponse-->' . implode( '|', $input_vals ) . '<--ppResponse-->';
		break;
		
	case 'exceptions_propagate':
	case 'exceptions_unpropagate':
	case 'exceptions_children_only':
		if ( empty($_GET['pp_eitem_ids']) )
			exit;
	
		if ( ! current_user_can( 'pp_assign_roles' ) )
			exit;
	
		$edited_input_ids = array();
		
		$input_vals = explode( '|', pp_sanitize_csv($_GET['pp_eitem_ids']) );
		
		foreach( $input_vals as $id_csv ) {
			$eitem_ids = _pp_editable_eitem_ids( explode(',', $id_csv) );
			
			if ( $agent_type && $agent_id )
				$agent_clause = "e.agent_type = '$agent_type' AND e.agent_id = '$agent_id' AND";
			else
				$agent_clause = '';

			if ( $row = $wpdb->get_row( "SELECT * FROM $wpdb->ppc_exception_items AS i INNER JOIN $wpdb->ppc_exceptions AS e ON i.exception_id = e.exception_id WHERE $agent_clause eitem_id IN ('" . implode( "','", $eitem_ids ) . "') LIMIT 1" ) ) {
				$args = (array) $row;
				
				if ( 'exceptions_propagate' == $action ) {
					$agents = array( 'children' => array( $agent_id => true ) );
					ppc_assign_exceptions( $agents, $agent_type, $args );

				} elseif ( 'exceptions_unpropagate' == $action ) {
					$agents = array( 'item' => array( $agent_id => true ) );
					ppc_assign_exceptions( $agents, $agent_type, $args );

					$wpdb->delete( $wpdb->ppc_exception_items, array( 'assign_for' => 'children', 'exception_id' => $row->exception_id, 'item_id' => $row->item_id ) );
					$wpdb->delete( $wpdb->ppc_exception_items, array( 'inherited_from' => $row->eitem_id ) );

				} elseif ( 'exceptions_children_only' == $action ) {
					$agents = array( 'children' => array( $agent_id => true ) );
					ppc_assign_exceptions( $agents, $agent_type, $args );

					$wpdb->delete( $wpdb->ppc_exception_items, array( 'assign_for' => 'item', 'exception_id' => $row->exception_id, 'item_id' => $row->item_id ) );
				}
				
				$edited_input_ids []= $id_csv;
			}
		}
		
		echo '<!--ppResponse-->' . $_GET['pp_ajax_submission'] . '~' . implode( '|', $edited_input_ids ) . '<--ppResponse-->';
		break;
		
} // end switch

function _pp_editable_assignment_ids( $ass_ids ) {
	if ( pp_is_user_administrator() )
		return $ass_ids;
	
	global $wpdb, $pp_admin;
	$results = $wpdb->get_results( "SELECT assignment_id, role_name FROM $wpdb->ppc_roles WHERE assignment_id IN ('" . implode( "','", $ass_ids ) . "')" );
	
	$remove_ids = array();
	
	foreach( $results as $row ) {
		if ( ! $role_attrib = pp_get_role_attributes( $row->role_name ) )
			continue;

		if ( ! pp_user_can_admin_role( $role_attrib->base_role_name, $role_attrib->object_type ) ) {
			$remove_ids []= $row->assignment_id;
		}
	}

	$ass_ids = array_diff( $ass_ids, $remove_ids );
	return $ass_ids;
}

function _pp_editable_eitem_ids( $ass_ids ) {
	// governed universally by pp_bulk_roles_enabled();
	return $ass_ids;
}
