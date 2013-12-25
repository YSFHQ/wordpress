<?php
class PP_ItemExceptionsData {
	var $loaded_item_id;
	var $agent_info = array();
	var $current_exceptions;
	var $inclusions_active = array();

	function load_exceptions( $via_item_source, $for_item_source, $via_item_type, $item_id, $args = array() ) {
		global $wpdb, $wp_roles;
	
		$this->loaded_item_id = $item_id;
		
		if ( ! isset($this->current_exceptions) ) {
			$this->current_exceptions = array();
			$this->agent_info = array();
		}
	
		$args = array_merge( 
			compact( 'for_item_source', 'item_id' ), 
			array( 'for_item_type' => '', 'for_item_status' => '' ),
			$args, 
			array(
				'cols' => 'e.agent_type, e.agent_id, e.operation, e.mod_type, i.eitem_id, i.assign_for',
				'return_raw_results' => true,
				'inherited_from' => '',
				'assign_for' => '',
				'hierarchical' => false,
			)
		);
		
		$for_item_type = ( isset($args['for_item_type']) ) ? $args['for_item_type'] : $via_item_type;

		extract( $args, EXTR_SKIP );

		if ( ( 'term' == $via_item_source ) && ! $for_item_type ) {
			unset( $args['for_item_source'] );
			$args['post_types'] = array( '' );
		}

		//if ( 'term' == $for_item_source )
			$args['cols'] .= ', e.for_item_type'; 
		
		if ( ! empty($agent_type) )
			$agents_by_type = array( $agent_type => array() );  // need this for ajax when an agent type has no current stored
		else
			$agents_by_type = array();

		$exc = ppc_get_exceptions( $args );
		
		foreach( $exc as $row ) {
			pp_set_array_elem( $this->current_exceptions, array( $row->for_item_type, $row->operation, $row->agent_type, $row->agent_id, $row->assign_for, $row->mod_type ) );
			$this->current_exceptions[ $row->for_item_type ][ $row->operation ][ $row->agent_type ][ $row->agent_id ][ $row->assign_for ][ $row->mod_type ] = $row->eitem_id;
			
			if ( ! isset($agents_by_type[$row->agent_type]) )
				$agents_by_type[$row->agent_type] = array();
				
			$agents_by_type[$row->agent_type] []= $row->agent_id;
		}
		
		foreach( array_keys($agents_by_type) as $agent_type ) {		
			$agents_by_type[$agent_type] = array_unique($agents_by_type[$agent_type]);
			
			$ids = $agents_by_type[$agent_type];
			if ( ! empty($agent_id) )
				$ids = array_merge( $ids, (array) $agent_id );	// ajax passes in specific id(s)

			if ( 'user' == $agent_type ) {
				$this->agent_info['user'] = $wpdb->get_results( "SELECT ID, user_login as name, display_name FROM $wpdb->users WHERE ID IN ('" . implode( "','", array_map( 'intval', $ids ) ) . "') ORDER BY user_login", OBJECT_K );
			} elseif ( 'pp_group' != $agent_type ) {
				$args = array( 'ids' => $ids );
				$this->agent_info[$agent_type] = pp_get_groups( $agent_type, $args );
			}
		}
		
		// retrieve info for all WP roles regardless of exception storage
		$_args = array( 'cols' => "ID, group_name AS name, metagroup_type, metagroup_id" );
		
		if( ! empty($agent_id) )	// ajax usage
			$_args['ids'] = (array) $agent_id;
		else {
			$_where = ( isset($agents_by_type['pp_group']) ) ? "ID IN ('" . implode( "','", $agents_by_type['pp_group'] ) . "')" : "  metagroup_type != 'wp_role'";
			if ( ! $pp_only_roles = pp_get_option( 'supplemental_role_defs' ) )
				$pp_only_roles = array();

			$_args['where'] = " AND ( $_where OR ( metagroup_type = 'wp_role' AND metagroup_id NOT IN ('" . implode( "','", $pp_only_roles ) . "') ) )";
		}

		$this->agent_info['pp_group'] = pp_get_groups( 'pp_group', $_args );
	
		$this->agent_info['wp_role'] = array();
		
		// rekey WP role exceptions
		foreach( $this->agent_info['pp_group'] as $agent_id => $group ) {
			if ( 'wp_role' == $group->metagroup_type ) {
				/*
				if ( 'wp_anon' == $group->metagroup_id ) {
					unset( $this->agent_info['pp_group'][$agent_id] );
					continue;
				}
				*/
				
				$this->agent_info['wp_role'][$agent_id] = (object) $this->agent_info['pp_group'][$agent_id];
				
				if ( $role_exists = isset( $wp_roles->role_names[ $group->metagroup_id ] ) )
					$this->agent_info['wp_role'][$agent_id]->name = $wp_roles->role_names[ $group->metagroup_id ];
				
				unset( $this->agent_info['pp_group'][$agent_id] );
				
				if ( $role_exists || in_array( $group->metagroup_id, array( 'wp_anon', 'wp_all', 'wp_auth' ) ) ) {
					foreach( array_keys($this->current_exceptions) as $for_item_type ) {
						foreach( array_keys($this->current_exceptions[$for_item_type]) as $op ) {
							if ( isset( $this->current_exceptions[$for_item_type][$op]['pp_group'][$agent_id] ) ) {
								$this->current_exceptions[$for_item_type][$op]['wp_role'][$agent_id] = (array) $this->current_exceptions[$for_item_type][$op]['pp_group'][$agent_id];
								unset( $this->current_exceptions[$for_item_type][$op]['pp_group'][$agent_id] );
							}
						}
					}
				}
			}
		}

		// don't include orphaned assignments in metabox tab count
		foreach( array_keys($this->current_exceptions) as $for_item_type ) {
			foreach( array_keys($this->current_exceptions[$for_item_type]) as $op ) {
				foreach( array_keys($this->current_exceptions[$for_item_type][$op]) as $agent_type )
					$this->current_exceptions[$for_item_type][$op][$agent_type] = array_intersect_key( $this->current_exceptions[$for_item_type][$op][$agent_type], $this->agent_info[$agent_type] );
			}
		}

		// determine if inclusions are set for any agents
		$where = ( 'term' == $via_item_source ) ? "AND e.via_item_type = '$via_item_type'" : '';
		$where .= "AND e.for_item_source = '$for_item_source'";
		
		$query_users = ( isset( $this->agent_info['user'] ) ) ? array_keys($this->agent_info['user']) : array();
		if ( ! empty( $args['agent_type'] ) && ( 'user' == $args['agent_type'] ) && ! empty( $args['agent_id'] ) )
			$query_users = array_merge( $query_users, (array) $args['agent_id'] );
		
		$user_clause = ( $query_users ) ? "OR ( e.agent_type = 'user' AND e.agent_id IN ('" . implode( "','", $query_users ) . "') )" : '';
		
		//$agents_clause = "( ( e.agent_type = 'pp_group' AND e.agent_id IN ('" . implode( "','", array_keys($this->agent_info['wp_role']) ) . "') ) $user_clause )";
		$agents_clause = "( ( e.agent_type = 'pp_group' ) $user_clause )";
		
		$_assignment_modes = ( $hierarchical ) ? array( 'item', 'children' ) : array( 'item' );
		
		// Populate only for wp roles, groups and users with stored exceptions.  Will query for additional individual users as needed.
		foreach( $_assignment_modes as $_assign_for ) {
			$results = $wpdb->get_results( "SELECT DISTINCT e.agent_type, e.agent_id, e.operation, e.for_item_type FROM $wpdb->ppc_exceptions AS e INNER JOIN $wpdb->ppc_exception_items AS i ON e.exception_id = i.exception_id WHERE $agents_clause AND i.assign_for = '$_assign_for' AND e.mod_type = 'include' $where" );
			foreach( $results as $row ) {
				//$_agent_type = ( 'pp_group' == $row->agent_type ) ? 'wp_role' : $row->agent_type;
				if ( ( 'pp_group' == $row->agent_type ) && in_array( $row->agent_id, array_keys($this->agent_info['wp_role']) ) )
					$_agent_type = 'wp_role';
				else
					$_agent_type = $row->agent_type;
				
				pp_set_array_elem( $this->inclusions_active, array( $row->for_item_type, $row->operation, $_agent_type, $row->agent_id, $_assign_for ) );
				$this->inclusions_active[$row->for_item_type][$row->operation][$_agent_type][$row->agent_id][$_assign_for] = true;
			}
		}
	}
	
}

