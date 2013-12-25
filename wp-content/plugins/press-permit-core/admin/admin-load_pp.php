<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action( 'pp_pre_init', '_pp_act_update_watch', 999 );
add_action( 'wp_dashboard_setup', 'pp_get_version_info' );	// retrieve version info in case there are any alerts
add_action( 'pp_duplicate_extension', '_pp_duplicate_extension', 10, 2 );

add_filter( 'pp_default_options', '_pp_default_admin_options', 1 );

if ( defined( 'SSEO_VERSION' ) )
	require_once( dirname(__FILE__).'/eyes-only-admin_pp.php' );

function _pp_default_admin_options( $options ) {
	$options['support_data'] = array_fill_keys( array( 'pp_options', 'wp_roles_types', 'theme', 'active_plugins', 'pp_permissions', 'pp_group_members', 'error_log', 'post_data', 'term_data' ), true );
	return $options;
}

// make sure empty terms are included in quick search results in "Add Supplemental Roles" term selection metaboxes
if ( pp_is_ajax( 'menu-quick-search' ) ) {
	function _term_select_include_empty( $args, $taxonomies ) {
		$args['hide_empty'] = 0;
		return $args;
	}
	add_filter( 'get_terms_args', '_term_select_include_empty', 50, 2 );

} elseif ( pp_is_ajax( 'pp-menu-quick-search' ) ) {
	require_once( dirname(__FILE__).'/includes/item-menu_pp.php' );
	add_action( 'wp_ajax_' . pp_sanitize_key($_REQUEST['action']), '_pp_ajax_menu_quick_search', 1 );
}

function pp_is_ajax( $action ) {
	return defined('DOING_AJAX') && DOING_AJAX && ! empty($_REQUEST['action']) && in_array($_REQUEST['action'], (array) $action );
}

function pp_admin_init() {
	global $cap_interceptor_admin;

	require_once( dirname(__FILE__).'/cap-interceptor-admin_pp.php' );
	$cap_interceptor_admin = new PP_CapInterceptorAdmin();

	if ( ! empty($_POST) || ! empty( $_REQUEST['action'] ) || ! empty($_REQUEST['action2']) || ! empty( $_REQUEST['pp_action'] )  )
		require_once( dirname(__FILE__).'/admin-handlers_pp.php' );

	// TODO: standardize ajax implementation
	//if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		foreach( array( 'item_ui', 'ui', 'exceptions_ui', 'submission', 'term_ui', 'user_ui', 'settings', 'items_metabox' ) as $ajax_type ) {
			if ( isset( $_REQUEST["pp_ajax_{$ajax_type}"] ) ) {
				$func = "require_once( '" . dirname(__FILE__) . "/ajax-{$ajax_type}_pp.php' ); exit;";
				add_action( 'pp_user_init', create_function( '', $func ) );
			}
		}
	//}
	
	if ( ! empty($_POST['pp_submit']) || ! empty($_POST['pp_defaults']) || ! empty($_POST['pp_role_usage_defaults']) || ! empty($_REQUEST['pp_refresh_updates']) || ! empty($_REQUEST['pp_upload_config']) || ! empty($_REQUEST['pp_support_forum']) ) {
		// For 'settings' admin panels, handle updated options right after current_user load (and before pp_init).
		// By then, check_admin_referer is available, but PP config and WP admin menu has not been loaded yet.
		require_once( PPC_ABSPATH . '/submittee_pp.php');	
		$handler = new PP_Submittee();
		$handler->process_submission();
	}
	
	if ( isset( $_GET['pp_agent_search'] ) ) {
		require_once( dirname(__FILE__).'/agent_query_pp.php' );
		exit;	
	}
}

function _pp_duplicate_extension( $ext_slug, $ext_folder ) {
	require_once( PPC_ABSPATH.'/lib/error_pp.php' );
	PP_Error::duplicate_extension( $ext_slug, $ext_folder );
}

function _pp_act_update_watch() {
	global $pagenow;

	if ( in_array( $pagenow, array( 'update.php', 'plugin-install.php', 'update-core.php', 'plugins.php' ) ) ) {
		require_once( dirname(__FILE__).'/plugin-update-watch_pp.php' );
		PP_UpdateWatch::update_watch();
	}
}

function pp_bulk_roles_enabled() {  // allow lockdown to non-Administrators (while still allowing item-specific role editing for those who have assign_roles capability)
	return ( current_user_can('pp_assign_roles') && current_user_can('pp_administer_content') && ! defined( 'PP_DISABLE_BULK_ROLES' ) ) || ( current_user_can('edit_users') );
}

function pp_pretty_slug( $slug ) {
	$val = str_replace( 'Pp', 'PP', ucwords( str_replace( '-', ' ', $slug ) ) );
	$val = str_replace( 'press', 'Press', $val ); // temp workaround
	$val = str_replace( 'Wpml', 'WPML', $val );
	return $val;
}

function pp_is_plugin_admin() {
	global $pagenow;
	return in_array( $pagenow, array( 'plugin-install.php', 'plugin-editor.php' ) );
}

function pp_user_can_admin_role( $role_name, $post_type, $user = '' ) {
	require_once( dirname(__FILE__).'/admin-roles_pp.php' );
	return PP_AdminRoles::user_can_admin_role( $role_name, $post_type, $user );
}

function ppc_get_role_title( $role_name, $args = array() ) {
	require_once( dirname(__FILE__).'/admin-roles_pp.php' );
	return PP_AdminRoles::get_role_title( $role_name, $args );
}

function pp_get_taxonomy_cap( $taxonomy, $cap_property ) {
	$tx_obj = get_taxonomy( $taxonomy );
	return ( $tx_obj && isset( $tx_obj->cap->$cap_property ) ) ? $tx_obj->cap->$cap_property : '';
}

function pp_get_type_cap( $post_type, $cap_property ) {
	$type_obj = get_post_type_object( $post_type );
	return ( $type_obj && isset( $type_obj->cap->$cap_property ) ) ? $type_obj->cap->$cap_property : '';
}

function pp_get_op_object( $operation, $post_type = '' ) {
	static $operations;
	
	if ( ! isset($operations) ) {
		$op_captions = apply_filters( 'pp_operation_captions', 
			array( 'read' => (object) array( 'label' => __('Read'), 'noun_label' => __('Reading', 'pp') ) )
		);

		$operations = array_intersect_key( $op_captions, array_fill_keys( pp_get_operations(), true ) );
	}

	$op_obj = ( isset( $operations[$operation] ) ) ? (object) (array) $operations[$operation] : false;  // deference op_obj from static array so type-specific filtering is not memcached
	return apply_filters( 'pp_operation_object', $op_obj, $operation, $post_type );
}

function pp_get_mod_object( $mod_type ) {
	static $mod_types;
	
	if ( ! isset($mod_types) ) {
		$mod_types = array(
			'include' => (object) array( 'label' => __('Only these:') ),
			'exclude' => (object) array( 'label' => __('Not these:') ),
			'additional' => (object) array( 'label' => __('Also these:') ),
		);
	}
	return ( isset($mod_types[$mod_type]) ) ? $mod_types[$mod_type] : (object) array();
}

function pp_plugin_info_url( $plugin_slug ) {
	return self_admin_url( "plugin-install.php?tab=plugin-information&plugin=$plugin_slug&TB_iframe=true&width=640&height=678" );
}

function _pp_any_group_manager() {
	$has_sitewide = current_user_can( 'pp_edit_groups' );
	
	if ( $has_sitewide && pp_is_user_administrator() )
		return true;
	else
		return apply_filters( 'pp_has_group_cap', $has_sitewide, 'pp_edit_groups', false, false );	
}

function pp_has_group_cap( $cap_name, $group_id, $group_type ) {
	$has_sitewide = current_user_can( $cap_name );
	
	if ( $has_sitewide && ! PP_MULTISITE && pp_is_user_administrator() )
		return true;
	else
		return apply_filters( 'pp_has_group_cap', $has_sitewide, $cap_name, $group_id, $group_type );
}

add_filter( 'pp_order_types', '_pp_order_types', 10, 2 );
function _pp_order_types( $types, $args = array() ) {
	$defaults = array( 'labels_property' => 'singular_name', 'order_property' => '', 'item_type' => '' );
	extract( array_merge( $defaults, $args ), EXTR_SKIP );
	
	if ( 'post' == $item_type )
		$post_types = get_post_types( array(), 'object' );
	elseif ( 'taxonomy' == $item_type )
		$taxonomies = get_taxonomies( array(), 'object' );
	
	$ordered_types = array();
	foreach( array_keys( $types ) as $name ) {
		if ( 'post' == $item_type )
			$ordered_types[$name] = ( isset($post_types[$name]->labels->singular_name) ) ? $post_types[$name]->labels->singular_name : '';
		elseif ( 'taxonomy' == $item_type )
			$ordered_types[$name] = ( isset($taxonomies[$name]->labels->singular_name) ) ? $taxonomies[$name]->labels->singular_name : '';
		else {
			if ( ! is_object($types[$name]) )
				return $types;
			
			if ( $order_property )
				$ordered_types[$name] = ( isset($types[$name]->$order_property) ) ? $types[$name]->$order_property : '';
			else
				$ordered_types[$name] = ( isset($types[$name]->labels->$labels_property) ) ? $types[$name]->labels->$labels_property : '';
		}
	}
	
	asort($ordered_types);
	
	foreach( array_keys($ordered_types) as $name )
		$ordered_types[$name] = $types[$name];

	return $ordered_types;
}

function _pp_can_set_exceptions( $operation, $for_item_type, $args = array() ) {
	require_once( dirname(__FILE__).'/admin-roles_pp.php' );
	return PP_AdminRoles::can_set_exceptions( $operation, $for_item_type, $args );
}
