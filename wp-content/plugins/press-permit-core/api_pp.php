<?php
global $pp_extensions;
$pp_extensions = array();

function pp_load_admin_api() {
	require_once( dirname(__FILE__).'/admin/admin-api_pp.php' );
}

function pp_register_extension( $slug, $label, $basename, $version, $min_pp_version = '0', $min_wp_version = '0' ) {
	global $pp_extensions, $pp_min_ext_version;
	
	$slug = pp_sanitize_key($slug);
	
	if ( ! isset($pp_extensions) || ! is_array($pp_extensions) )
		$pp_extensions = array();
		
	// avoid lockout in case of editing plugin via wp-admin
	if ( constant('PP_DEBUG') && is_admin() && ppc_editing_plugin() ) {
		return false;
	}

	$register = true;
	$error = false;
	
	if ( ! pp_wp_ver( $min_wp_version ) ) {
		require_once( dirname(__FILE__) . '/lib/error_pp.php' );
		$error = PP_Error::old_wp( $label, $min_wp_version );
		$register = false;
		
	} elseif ( version_compare( PPC_VERSION, $min_pp_version, '<' ) ) {
		require_once( dirname(__FILE__) . '/lib/error_pp.php' );
		$error = PP_Error::old_pp( $label, $min_pp_version );
		$register = false;
		
	} elseif( ! empty($pp_min_ext_version[$slug]) && version_compare( $version, $pp_min_ext_version[$slug], '<' ) ) {
		if ( is_admin() ) {
			require_once( dirname(__FILE__) . '/lib/error_pp.php' );
			$error = PP_Error::old_extension( $label, $pp_min_ext_version[$slug] );
			// but still register extension so it can be updated!
			
		} else {
			$error = true;
			$register = false;
		}
	}

	if ( $register ) {
		$version = pp_sanitize_word( $version );
		$pp_extensions[$slug] = (object) compact( 'slug', 'version', 'label', 'basename' );
	}

	return ! $error;
}

// =========================== Capabilities API ===========================

/**
 * Retrieve supplemental roles for a user or group

 * @param string agent_type
 * @param int agent_id
 * @param array args :
 *   - post_types (default true)
 *   - taxonomies (default true)
 *   - force_refresh (default false)
 * @return array : $roles[role_name] = role_assignment_id
 */
function ppc_get_roles( $agent_type, $agent_id, $args = array() ) {
	require_once( dirname(__FILE__).'/groups-retrieval_pp.php' );
	return PP_GroupRetrieval::get_roles( $agent_type, $agent_id, $args );
}

/**
 * Retrieve exceptions for a user or group

 * @param array args :
 *  - agent_type         ('user'|'pp_group'|'pp_net_group'|'bp_group')
 *  - agent_id           (group or user ID)
 *  - operations         ('read'|'edit'|'associate'|'assign'...)
 *  - for_item_source    ('post' or 'term' - data source to which the roles may apply)
 *  - post_types         (post_types to which the roles may apply)
 *  - taxonomies         (taxonomies to which the roles may apply)
 *  - for_item_status    (status to which the roles may apply i.e. 'post_status:private'; default '' means all stati)
 *  - via_item_source    ('post' or 'term' - data source which the role is tied to)
 *  - item_id            (post ID or term_taxonomy_id)
 *  - assign_for         (default 'item'|'children'|'' means both)
 *  - inherited_from     (base exception assignment ID to retrieve propagated assignments for; default '' means N/A)
 */
function ppc_get_exceptions( $args = array() ) {
	require_once( dirname(__FILE__).'/groups-retrieval_pp.php' );
	return PP_GroupRetrieval::get_exceptions( $args );
}

// $args['labels']['name'] = translationed caption
// $args['labels']['name'] = translated caption
// $args['default_caps'] = array( cap_name => true, another_cap_name => true ) defines caps for pattern roles which do not have a corresponding WP role 
//
function pp_register_pattern_role( $role_name, $args = array() ) {
	global $pp_role_defs;
	
	$role_obj = (object) $args;
	$role_obj->name = $role_name;
	
	$pp_role_defs->pattern_roles[$role_name] = $role_obj;
}

// =========================== Groups API ===========================
function pp_register_group_type( $agent_type, $args = array() ) {
	$defaults = array( 'labels' => array(), 'schema' => array() );
	$args = (object) array_merge( $defaults, (array) $args );

	$args->labels = (object) $args->labels;
	
	if ( empty( $args->labels->name ) )
		$args->labels->name = $agent_type;

	if ( empty( $args->labels->singular_name ) )
		$args->labels->singular_name = $agent_type;
	
	global $pp_group_types;
	$pp_group_types[$agent_type] = (object) $args;
}

function pp_get_group_type_object( $agent_type ) {
	global $pp_group_types;
	return ( isset( $pp_group_types[$agent_type] ) ) ? $pp_group_types[$agent_type] : false;
}

function pp_get_group_types( $args = array(), $return = 'name' ) {  // todo: handle $args
	global $pp_group_types;
	
	if ( ! empty( $args['editable'] ) ) {
		$editable_group_types = apply_filters( 'pp_editable_group_types', array( 'pp_group' ) );
		return ( 'object' == $return ) ? array_intersect_key( $pp_group_types, array_fill_keys( $editable_group_types, true ) ) : $editable_group_types;
	} else
		return ( 'object' == $return ) ? $pp_group_types : array_keys( $pp_group_types );
}

function pp_group_type_exists( $agent_type ) {
	global $pp_group_types;
	return isset( $pp_group_types[$agent_type] );
}

function pp_group_type_editable( $agent_type ) {
	return in_array( $agent_type, pp_get_group_types( array( 'editable' => true ) ) );
}

/**
 * Retrieve users who are members of a specified group

 * @param int group_id
 * @param string agent_type
 * @param string cols ('all' | 'id')
 * @param array args :
 *   - status ('active' | 'scheduled' | 'expired' | 'any')
 * @return array of objects or IDs
 */
function pp_get_group_members( $group_id, $agent_type = 'pp_group', $cols = 'all', $args = array() ) {
	if ( 'pp_group' == $agent_type ) {
		require_once( dirname(__FILE__).'/groups-retrieval_pp.php' );
		return PP_GroupRetrieval::get_pp_group_members( $group_id, $cols, $args );
	} else
		return apply_filters( 'pp_get_group_members', array(), $group_id, $agent_type, $cols, $args );
}

/**
 * Retrieve groups for a specified user

 * @param int user_id
 * @param string agent_type
 * @param array args :
 *   - cols ('all' | 'id')
 *   - status ('active' | 'scheduled' | 'expired' | 'any')
 *   - metagroup_type (default null)
 *   - query_user_ids (array, default false)
 *   - force_refresh (default false)
 * @return array (object or storage date string, with group id as array key)
 */
function pp_get_groups_for_user( $user_id, $agent_type = 'pp_group', $args = array() ) {
	if( 'pp_group' == $agent_type ) {
		require_once( dirname(__FILE__).'/groups-retrieval_pp.php' );
		return PP_GroupRetrieval::get_pp_groups_for_user( $user_id, $args );
	}

	return apply_filters( 'pp_get_groups_for_user', array(), $user_id, $agent_type, $args );
}

/**
 * Retrieve the Permission Group object for a WP Role or other metagroup, by providing its name

 * @param string metagroup_type
 * @param string metagroup_id
 * @param array args :
 *   - cols (return format - 'all' | 'id')
 * @return object Permission Group (unless cols = 'id')
 *  - ID
 *  - group_name
 *  - group_description
 *  - metagroup_type
 *  - metagroup_id
 */
function pp_get_metagroup( $metagroup_type, $metagroup_id, $args = array() ) {
	$defaults = array( 'cols' => 'all' );
	extract( array_merge( $defaults, $args ), EXTR_SKIP );
	
	global $wpdb;

	$site_key = md5( get_option( 'site_url' ) . constant('DB_NAME') . $wpdb->prefix ); // guard against groups table being imported into a different database (with mismatching options table)
	
	if ( ! $buffered_groups = pp_get_option( "buffer_metagroup_id_{$site_key}" ) )
		$buffered_groups = array();
	
	$key = $metagroup_id . ':' . $wpdb->pp_groups;  // PP setting may change to/from netwide groups after buffering
	if ( ! isset( $buffered_groups[$key] ) ) {
		if ( $group = $wpdb->get_row( "SELECT * FROM $wpdb->pp_groups WHERE metagroup_type = '$metagroup_type' AND metagroup_id = '$metagroup_id' LIMIT 1" ) ) {
			$group->group_id = $group->ID;
			$group->status = 'active';
			$buffered_groups[$key] = $group;
			pp_update_option( "buffer_metagroup_id_{$site_key}", $buffered_groups );
		}
	}
	
	if ( 'id' == $cols ) {
		return ( isset($buffered_groups[$key]) ) ? $buffered_groups[$key]->ID : false;
	} else {
		return ( isset($buffered_groups[$key]) ) ? $buffered_groups[$key] : false;
	}
}
