<?php

function _ppc_count_assigned_exceptions( $agent_type, $args = array() ) {
	global $wpdb;
	
	$defaults = array( 'query_agent_ids' => false, 'join_groups' => true );
	extract( array_merge( $defaults, $args ), EXTR_SKIP );
	
	$item_types = array_merge( pp_get_enabled_post_types(), pp_get_enabled_taxonomies(), pp_get_group_types( array( 'editable' => true ) ) );

	$type_clause = "AND e.for_item_type IN ('','" . implode( "','", $item_types ) . "')";
	$type_clause .= " AND e.via_item_type IN ('','" . implode( "','", $item_types ) . "')";
	$ops_clause = "AND operation IN ('" . implode( "','", pp_get_operations() ) . "')";
	
	$count = array();
	
	$agent_type = pp_sanitize_key($agent_type);

	if ( ( 'user' == $agent_type ) && $join_groups ) {
		$results = array();

		foreach( pp_get_group_types( array(), 'object' ) as $group_type => $gtype_obj ) {
			global $wpdb;
			if ( ! empty( $gtype_obj->schema['members'] ) ) {
				extract( $gtype_obj->schema['members'] );	// members_table, col_group, col_user
			} else {
				$members_table = $wpdb->pp_group_members;
				$col_member_group = 'group_id';
				$col_member_user = 'user_id';
			}

			$agent_clause = ( $query_agent_ids ) ? "AND gm.$col_member_user IN ('" . implode( "','", (array) $query_agent_ids ) . "')" : '';
			
			if ( ( 'groups_only' === $join_groups ) || ( 'pp_group' != $group_type ) )
				$agent_type_clause = "( e.agent_type = '$group_type' AND gm.$col_member_group = e.agent_id )";	// NOTE: every site user has at least one record in pp_group_members (for primary WP site role)
			else
				$agent_type_clause = "( e.agent_type = 'user' AND gm.user_id = e.agent_id ) OR ( e.agent_type = 'pp_group' AND gm.group_id = e.agent_id )";
	
			$_results = $wpdb->get_results( "SELECT gm.$col_member_user as qry_agent_id, e.exception_id, e.for_item_source, e.for_item_type, e.via_item_type, e.operation, COUNT(DISTINCT i.exception_id, i.item_id) AS exc_count FROM $wpdb->ppc_exception_items AS i INNER JOIN $wpdb->ppc_exceptions AS e ON i.exception_id = e.exception_id INNER JOIN $members_table AS gm ON ( $agent_type_clause ) WHERE i.inherited_from = '0' $ops_clause $type_clause $agent_clause GROUP BY qry_agent_id, e.for_item_source, e.for_item_type, e.operation" );	
			$results = array_merge( $results, $_results );
		}
	} else {
		$agent_clause = ( $query_agent_ids ) ? "AND e.agent_id IN ('" . implode( "','", (array) $query_agent_ids ) . "')" : '';
		$results = $wpdb->get_results( "SELECT e.agent_id AS qry_agent_id, e.exception_id, e.for_item_source, e.for_item_type, e.operation, e.via_item_type, COUNT(DISTINCT i.exception_id, i.item_id) AS exc_count FROM $wpdb->ppc_exception_items AS i INNER JOIN $wpdb->ppc_exceptions AS e ON i.exception_id = e.exception_id WHERE i.inherited_from = '0' AND e.agent_type = '$agent_type' $ops_clause $type_clause $agent_clause GROUP BY qry_agent_id, e.for_item_source, e.for_item_type, e.operation" );
	}
	
	foreach( $results as $row ) {
		if ( ! $row->for_item_type )
			$type_label = '';
		else {
			if ( ! $type_obj = pp_get_type_object( $row->for_item_source, $row->for_item_type ) )
				continue;
				
			$type_label = $type_obj->labels->singular_name;
		}
		
		if ( $op_obj = pp_get_op_object( $row->operation, $row->for_item_type ) ) {
			if ( 'assign' == $row->operation ) {
				if ( $tx_obj = get_taxonomy( $row->via_item_type ) )
					$lbl = str_replace( 'Term', $tx_obj->labels->singular_name, $op_obj->label );  // todo: better i8n
				else
					$lbl = $op_obj->label;

				//$lbl = sprintf( __('%2$s: %1$s', 'pp'), $op_lbl, $type_label );
			} elseif ( isset( $op_obj->abbrev ) )
				$lbl = $op_obj->abbrev;
			else
				$lbl = sprintf( __('%1$s %2$s', 'pp'), $op_obj->label, $type_label );
		} else {
			$lbl = $type_label;
		}
		
		if ( ! isset($count[$row->qry_agent_id]['exceptions'][$lbl]) )
			$count[$row->qry_agent_id]['exceptions'][$lbl] = 0;

		$count[$row->qry_agent_id]['exceptions'][$lbl] += $row->exc_count;
		
		if ( ! isset($count[$row->qry_agent_id]['exc_count']) )
			$count[$row->qry_agent_id]['exc_count'] = 0;
		
		$count[$row->qry_agent_id]['exc_count'] += $row->exc_count;
	}
	
	return $count;
}

function _ppc_count_assigned_roles( $agent_type, $args = array() ) {
	global $wpdb;
	
	$defaults = array( 'query_agent_ids' => false, 'join_groups' => true );
	extract( array_merge( $defaults, $args ), EXTR_SKIP );

	$count = array();
	
	if ( ( 'user' == $agent_type ) && $join_groups ) {
		$agent_clause = ( $query_agent_ids ) ? "AND gm.user_id IN ('" . implode( "','", array_map( 'intval', (array) $query_agent_ids ) ) . "')" : '';
		$results = $wpdb->get_results( "SELECT u.ID AS agent_id, r.role_name, COUNT(*) AS rolecount FROM $wpdb->users AS u INNER JOIN $wpdb->pp_group_members AS gm ON ( gm.user_id = u.ID $agent_clause ) INNER JOIN $wpdb->ppc_roles AS r ON ( ( r.agent_type = 'user' AND r.agent_id = gm.user_id ) OR ( r.agent_type = 'pp_group' AND r.agent_id = gm.group_id ) ) GROUP BY u.ID, r.role_name" );
	} else {
		$agent_clause = ( $query_agent_ids ) ? "AND agent_id IN ('" . implode( "','", array_map( 'intval', (array) $query_agent_ids ) ) . "')" : '';
		$results = $wpdb->get_results( $wpdb->prepare( "SELECT agent_id, role_name, COUNT(*) AS rolecount FROM $wpdb->ppc_roles WHERE agent_type = %s $agent_clause GROUP BY agent_id, role_name", $agent_type ) );
	}

	$item_types = array_merge( pp_get_enabled_post_types(), pp_get_enabled_taxonomies() );
	
	foreach( $results as $row ) {
		$arr_role = explode( ':', $row->role_name );
		//$base_role_name = $arr_role[0];
		//$src_name = $arr_role[1];
		//$item_type = $arr_role[2];
		
		$no_ext = ! defined( 'PPCE_VERSION' ) && ! defined( 'PPS_VERSION' );
		$no_custom_stati = ! defined( 'PPS_VERSION' );

		if ( isset($arr_role[2]) && in_array( $arr_role[2], $item_types ) ) {		
			// roles for these post statuses will not be applied if corresponding extensions are inactive, so do not indicate in users/groups listing or profile
			if ( $no_ext && strpos( $row->role_name, ':post_status:' ) && ! strpos( $row->role_name, ':post_status:private' ) )
				continue;
			elseif ( $no_custom_stati && strpos( $row->role_name, ':post_status:' ) && ! strpos( $row->role_name, ':post_status:private' ) && ! strpos( $row->role_name, ':post_status:draft' ) )
				continue;
		
			if ( $role_title = ppc_get_role_title( $row->role_name, array( 'slug_fallback' => false ) ) ) {
				$count[$row->agent_id]['roles'][$role_title] = $row->rolecount;
				
				if ( ! isset($count[$row->agent_id]['role_count']) )
					$count[$row->agent_id]['role_count'] = 0;
				
				$count[$row->agent_id]['role_count'] += $row->rolecount;
			}
		}
	}
	
	return $count;
}

function _ppc_list_agent_exceptions( $agent_type, $id, $args = array() ) {
	global $wp_list_table;
	static $exception_info;
	
	$defaults = array( 'query_agent_ids' => array(), 'show_link' => true, 'join_groups' => true, 'force_refresh' => false, 'display_limit' => 3 );
	$args = array_merge( $defaults, $args );
	extract( $args, EXTR_SKIP );
	
	if ( ! $query_agent_ids )
		$query_agent_ids = (array) $id;
	
	if ( ! isset($exception_info) || $force_refresh )
		$exception_info = ppc_count_assigned_exceptions( $agent_type, $args );

	$exc_str = '';

	if ( isset( $exception_info[$id] ) ) {
		if ( isset( $exception_info[$id]['exceptions'] ) ) {
			$exc_titles = array();
			$i=0;
			foreach( $exception_info[$id]['exceptions'] as $exc_title => $exc_count ) {
				$i++;
				$exc_titles []= sprintf( __('%1$s (%2$s)', 'pp'), $exc_title, $exc_count );
				if ( $i >= $display_limit )
					break;
			}
			
			$exc_str = '<span class="pp-group-site-roles">' . implode( ',&nbsp; ', $exc_titles ) . '</span>';
			
			if ( count($exception_info[$id]['exceptions']) > $display_limit ) {
				$exc_str = sprintf( __('%s, more...', 'pp'), $exc_str );
			}
			
			if ( $show_link && current_user_can('edit_user', $id) && current_user_can('pp_assign_roles') ) {
				$edit_link = "admin.php?page=pp-edit-permissions&amp;action=edit&amp;agent_id=$id&amp;agent_type=user";
				$exc_str = "<a href=\"$edit_link\">$exc_str</a><br />";
			}
		}
	}
	return $exc_str;
}
