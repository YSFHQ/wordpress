<?php
/**
 * Assign supplemental roles for a user or group

 * @param array roles : roles[role_name][agent_id] = true
 * @param string agent_type
 */
function ppc_assign_roles( $group_roles, $agent_type = 'pp_group', $args = array() ) {
	require_once( dirname(__FILE__).'/role_assigner_pp.php' );
	return PP_RoleAssigner::assign_roles( $group_roles, $agent_type, $args );
}

/**
 * Assign exceptions for a user or group

 * @param array agents : agents['item'|'children'][agent_id] = true|false
 * @param string agent_type
 * @param array args :
 *  - operation          ('read'|'edit'|'associate'|'assign'...)
 *  - mod_type           ('additional'|'exclude'|'include')
 *  - for_item_source    ('post' or 'term' - data source to which the role applies)
 *  - for_item_type      (post_type or taxonomy to which the role applies)
 *  - for_item_status    (status which the role applies to; default '' means all stati)
 *  - via_item_source    ('post' or 'term' - data source which the role is tied to)
 *  - item_id            (post ID or term_taxonomy_id)
 *  - via_item_type      (post_type or taxonomy of item which the role is tied to; default '' means unspecified when via_item_source is 'post')
 *  - remove_assignments (default false)
 */
function ppc_assign_exceptions( $agents, $agent_type = 'pp_group', $args = array() ) {
	require_once( dirname(__FILE__).'/role_assigner_pp.php' );
	return PP_RoleAssigner::assign_exceptions( $agents, $agent_type, $args );
}

function ppc_delete_agent_permissions( $agent_ids, $agent_type ) {
	global $wpdb;
	
	$agent_id_csv = implode( "','", array_map( 'intval', (array) $agent_ids ) );
	
	$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->ppc_roles WHERE agent_type = %s AND agent_id IN ('$agent_id_csv')", $agent_type ) );
	
	if ( $exc_ids = $wpdb->get_col( $wpdb->prepare( "SELECT exception_id FROM $wpdb->ppc_exceptions WHERE agent_type = %s AND agent_id IN ('$agent_id_csv')", $agent_type ) ) ) {
		$wpdb->query( "DELETE FROM $wpdb->ppc_exception_items WHERE exception_id IN ('" . implode( "','", $exc_ids ) . "')" );
		$wpdb->query( "DELETE FROM $wpdb->ppc_exceptions WHERE exception_id IN ('" . implode( "','", $exc_ids ) . "')" );
	}
}

function pp_get_groups( $agent_type = 'pp_group', $args = array() ) {
	if ( 'pp_group' == $agent_type ) {
		require_once( PPC_ABSPATH.'/groups-retrieval_pp.php' );
		return PP_GroupRetrieval::get_pp_groups( $args );
	} else
		return apply_filters( 'pp_get_groups', array(), $agent_type, $args );
}

/**
 * Retrieve a Permission Group object
 
 * @param int group_id
 * @param string agent_type (pp_group, bp_group, etc.)
 * @return object Permission Group
 *  - ID
 *  - group_name
 *  - group_description
 *  - metagroup_type
 *  - metagroup_id
 */
function pp_get_group( $group_id, $agent_type = 'pp_group' ) {
	return pp_get_agent( $group_id, $agent_type );
}

function pp_get_agent( $agent_id, $agent_type = 'pp_group' ) {
	if ( 'pp_group' == $agent_type ) {
		global $wpdb;
		if ( $result = $wpdb->get_row( $wpdb->prepare( "SELECT ID, group_name AS name, group_description, metagroup_type, metagroup_id FROM $wpdb->pp_groups WHERE ID = %d", $agent_id ) ) ) {
			$result->name = stripslashes($result->name);
			$result->group_description = stripslashes($result->group_description);
			$result->group_name = $result->name;	// TODO: review usage of these properties
		}
	} elseif ( 'user' == $agent_type ) {
		if ( $result = new WP_User( $agent_id ) ) {
			$result->name = $result->display_name;
		}
	} else 
		$result = null;
	
	return apply_filters( 'pp_get_group', $result, $agent_id, $agent_type );
}

/**
 * Retrieve a Permission Group object by providing its name

 * @param int group_name
 * @param string agent_type (pp_group, bp_group, etc.)
 * @return object Permission Group
 *  - ID
 *  - group_name
 *  - group_description
 *  - metagroup_type
 *  - metagroup_id
 */
function pp_get_group_by_name($name, $agent_type = 'pp_group') {
	global $wpdb;
	$groups_table = apply_filters( 'pp_use_groups_table', $wpdb->pp_groups, $agent_type );
	
	$result = $wpdb->get_row( $wpdb->prepare( "SELECT ID, group_name AS name, group_description FROM $groups_table WHERE group_name = %s", $name ) );
	return $result;
}

/**
 * Add User(s) to a Permission Group

 * @param int group_id
 * @param array user_ids
 * @param array args :
 *   - agent_type (default 'pp_group')
 *   - status ('active' | 'scheduled' | 'expired' | 'any')
 *   - date_limited (default false)
 *   - start_date_gmt
 *   - end_date_gmt
 */
function pp_add_group_user( $group_id, $user_ids, $args = array() ){
	require_once( dirname(__FILE__).'/groups-update_pp.php' );
	return PP_GroupsUpdate::add_group_user( $group_id, $user_ids, $args );
}

/**
 * Remove User(s) from a Permission Group

 * @param int group_id
 * @param array user_ids
 * @param array args :
 *   - group_type (default 'pp_group')
 */
function pp_remove_group_user($group_id, $user_ids, $args = array() ) {
	require_once( dirname(__FILE__).'/groups-update_pp.php' );
	return PP_GroupsUpdate::remove_group_user($group_id, $user_ids, $args);
}

/**
 * Update Group Membership for User(s)

 * @param int group_id
 * @param array user_ids
 * @param array args :
 *   - agent_type (default 'pp_group')
 *   - status ('active' | 'scheduled' | 'expired' | 'any')
 *   - date_limited (default false)
 *   - start_date_gmt
 *   - end_date_gmt
 */
function pp_update_group_user( $group_id, $user_ids, $args = array() ) {
	require_once( dirname(__FILE__).'/groups-update_pp.php' );
	return PP_GroupsUpdate::update_group_user( $group_id, $user_ids, $args );
}

/**
 * Create a new Permission Group
 
 * @param array group_vars_arr :
 *   - group_name
 *   - group_description (optional)
 *   - metagroup_type (optional, for internal use)
 * @return int ID of new group
 */
function pp_create_group ($group_vars_arr){
	require_once( dirname(__FILE__).'/groups-update_pp.php' );
	return PP_GroupsUpdate::create_group($group_vars_arr);
}

/**
 * Delete a Permission Group
 
 * @param array group_id
 * @param array agent_type (pp_group, bp_group, etc.)
 */
function pp_delete_group( $group_id, $agent_type ) {
	require_once( dirname(__FILE__).'/groups-update_pp.php');
	return PP_GroupsUpdate::delete_group($group_id, $agent_type);
}
