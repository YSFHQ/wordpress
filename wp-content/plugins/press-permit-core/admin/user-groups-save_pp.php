<?php
class PP_UserGroupsSave {
	public static function add_user_groups( $user_id, $omit_group_ids = array() ) {
		//foreach( apply_filters( 'pp_membership_editable_group_types', array( 'pp_group' ) ) as $agent_type ) {
		foreach( pp_get_group_types( array( 'editable' => true ) ) as $agent_type ) {
			if ( empty( $_POST[$agent_type] ) )
				continue;

			//if ( ! pp_has_group_cap( 'pp_manage_members', 0, $agent_type ) )
			//	continue;
				
			global $pp_current_user;

			$status = ( isset( $_POST['pp_membership_status'] ) ) ? pp_sanitize_key($_POST['pp_membership_status'])	: 'active';
			
			if ( $user_id == $pp_current_user->ID )
				$stored_groups = (array) $pp_current_user->groups[$agent_type];
			else {
				$user = pp_get_user($user_id, '', array( 'skip_role_merge' => 1 ) );
				$stored_groups = ( isset($user->groups[$agent_type]) ) ? (array) $user->groups[$agent_type] : array();
			}

			// by retrieving filtered groups here, user will only modify membership for groups they can administer
			$is_administrator = pp_is_user_administrator();

			$posted_groups = ( isset($_POST[$agent_type] ) ) ? $_POST[$agent_type] : array();
			
			if ( $omit_group_ids )
				$posted_groups = array_diff( $posted_groups, $omit_group_ids );

			foreach ( $posted_groups as $group_id ) {
				if ( isset($stored_groups[$group_id]) )
					continue;

				if ( pp_has_group_cap( 'pp_manage_members', $group_id, $agent_type ) ) {
					$args = compact( 'agent_type', 'status' );
					$args = apply_filters( 'pp_add_group_args', $args, $group_id );

					pp_add_group_user( (int) $group_id, $user_id, $args );
				}
			}
		}
	}
	
	public static function remove_user_groups( $user_id, $omit_group_ids = array() ) {
		foreach( pp_get_group_types( array( 'editable' => true ) ) as $agent_type ) {
			$posted_groups = ( isset($_POST[$agent_type] ) ) ? array_map( 'intval', $_POST[$agent_type] ) : array();
			
			$stored_groups = array_keys( pp_get_groups_for_user( $user_id, $agent_type, array( 'cols' => 'id' ) ) );

			if ( $omit_group_ids )
				$stored_groups = array_diff( $stored_groups, $omit_group_ids );

			$delete_groups = array_diff( $stored_groups, $posted_groups );

			foreach ( $delete_groups as $group_id ) {
				if ( pp_has_group_cap( 'pp_manage_members', $group_id, $agent_type ) ) {
					pp_remove_group_user( $group_id, $user_id, compact('agent_type') );
				}
			}
		}
	}
} // end class
