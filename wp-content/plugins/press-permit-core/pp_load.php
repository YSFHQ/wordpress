<?php
/**
 * functions loaded early on PP plugin load
 * 
 * @package PP
 * @author Kevin Behrens <kevin@agapetry.net>
 * @copyright Copyright (c) 2011-2013, Agapetry Creations LLC
 * 
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define( 'UNFILTERED_PP', false);
define( 'FILTERED_PP', true);

define( 'PP_MIN_DATE_STRING', '0000-00-00 00:00:00' );
define( 'PP_MAX_DATE_STRING', '2035-01-01 00:00:00' );

$dir = dirname(__FILE__);

require_once( "$dir/lib/agapetry_lib.php" );
require_once( "$dir/api_pp.php" );
require_once( "$dir/defaults_pp.php" );
require_once( "$dir/defaults_ppx.php" );
require_once( "$dir/db-config_pp.php" );

if ( PP_MULTISITE ) {
	$func = "require('" . dirname(__FILE__) . "/db-config_pp.php');";
	add_action( 'switch_blog', create_function( '', $func ) );
}

add_action( 'set_current_user', '_pp_act_set_current_user', 99 );
add_action( 'init', '_pp_act_on_init', 50 );

global $pp_plugin_page, $pp_default_options, $pp_netwide_options, $pp_role_defs;

// ensure plugin_page is available immediately
$pp_plugin_page = ( is_admin() && isset( $_GET['page'] ) ) ? pp_sanitize_key($_GET['page']) : '';

pp_refresh_options();  // retrieve stored options
$pp_default_options = pp_default_options();

if ( PP_MULTISITE )
	$pp_netwide_options = apply_filters( 'pp_netwide_options', array( 'support_key', 'beta_updates' ) );

pp_register_group_type( 'pp_group', is_admin() ? array( 'labels' => (object) array ( 'name' => __('Groups', 'pp'), 'singular_name' => __('Group', 'pp') ) ) : array() );

if ( defined( 'SSEO_VERSION' ) )
	require_once( dirname(__FILE__).'/eyes-only-helper_pp.php' );

if ( is_admin() )
	require_once( $dir.'/admin/admin-load_pp.php' );

$pp_role_defs = new PP_Role_Defs();

//if ( ! pp_wp_ver( '3.0' ) )
//	require_once( dirname(__FILE__).'/wp-legacy_pp.php' );

class PP_Role_Defs {
	var $anon_user_caps = array();
	var $pattern_roles = array();
	var $direct_roles = array();
	var $disabled_pattern_role_types = array();
	
	function __construct() {	// note: API function pp_register_pattern_role() also available
		$this->pattern_roles = array( 'subscriber' => (object) array(), 'contributor' => (object) array(), 'author' => (object) array(), 'editor' => (object) array() );
	
		add_action( 'plugins_loaded', array( &$this, 'act_plugins_loaded' ) ); 
	}
	
	function define_roles() {
		$this->anon_user_caps = apply_filters( 'pp_anon_user_caps', array( 'read' ) );
		
		if ( $direct_roles = apply_filters( 'pp_default_direct_roles', array() ) ) {
			global $wp_roles;
			
			if ( ! empty($wp_roles) ) {
				$direct_roles = array_intersect( $direct_roles, array_keys($wp_roles->role_names) );

				foreach( $direct_roles as $role_name ) {
					$caption = $wp_roles->role_names[$role_name];
					$this->direct_roles[$role_name] = (object) array( 'labels' => (object) array( 'name' => $caption, 'singular_name' => $caption ) );
				}
			}
		} else {
			$this->direct_roles = array();
		}
	}
	
	function act_plugins_loaded() {
		if ( ! defined( 'PPP_VERSION' ) || ! defined( 'PPCE_VERSION' ) )
			add_filter( 'pp_unfiltered_post_types', '_pp_no_bbpress', 1 );  // requires additional code, avoid appearance of support
		else
			add_filter( 'pp_unfiltered_post_types', '_pp_bbpress_forum_only', 1 );  // requires additional code, avoid appearance of support
			
		$this->pattern_roles = apply_filters( 'pp_pattern_roles', $this->pattern_roles );
	}
} // end class PP_Roles

function _pp_no_bbpress( $unfiltered_types ) {
	return array_merge( $unfiltered_types, array( 'forum', 'topic', 'reply' ) );
}

function _pp_bbpress_forum_only( $unfiltered_types ) {
	return array_merge( $unfiltered_types, array( 'topic', 'reply' ) );
}
	
// this fires late on the 'init' action (priority 50)
function _pp_act_on_init() {
	if ( defined( 'INIT_ACTION_DONE_PP' ) ) { return; }
	define ( 'INIT_ACTION_DONE_PP', true );

	// --- version check ---
	$ver = get_option('pp_c_version');
	
	if ( ! $ver || empty($ver['db_version']) || version_compare( PPC_DB_VERSION, $ver['db_version'], '!=') ) {
		require_once( dirname(__FILE__).'/db-setup_pp.php');
		PP_DB_Setup::db_setup($ver['db_version']);
		
		if ( ! $ver ) {
			require_once( dirname(__FILE__).'/admin/update_pp.php');
			PP_Updated::version_updated( '' );
		}
		
		update_option( 'pp_c_version', array( 'version' => PPC_VERSION, 'db_version' => PPC_DB_VERSION ) );
	}
	
	if ( $ver && ! empty($ver['version']) ) {
		// These maintenance operations only apply when a previous version of PP was installed 
		if ( version_compare( PPC_VERSION, $ver['version'], '!=') ) {
			require_once( dirname(__FILE__).'/admin/update_pp.php');
			PP_Updated::version_updated( $ver['version'] );
			update_option( 'pp_c_version', array( 'version' => PPC_VERSION, 'db_version' => PPC_DB_VERSION ) );
		}

		if ( PP_MULTISITE && ! pp_get_option( 'wp_role_sync' ) ) {
			require_once( dirname(__FILE__).'/admin/update_pp.php');
			PP_Updated::sync_wproles();
		}
	} else {
		// first execution after install
		if ( ! get_option( 'ppperm_added_role_caps_21beta' ) ) {
			pp_populate_roles();
		}
	}
	// --- end version check ---
	
	global $pp_default_options, $pp_site_options, $pp_cap_helper;
	$pp_default_options = apply_filters( 'pp_default_options', $pp_default_options );

	// already loaded these early, so apply filter again for extensions
	$pp_site_options = apply_filters( 'pp_options', $pp_site_options );
	
	if ( PP_MULTISITE ) {
		global $pp_netwide_options;
		$pp_netwide_options = apply_filters( 'pp_netwide_options', array( 'support_key', 'beta_updates' ) );
	}
	
	// PP_Cap_Helper() instantiation forces type-specific cap names for enabled post types and taxonomies
	require_once( dirname(__FILE__).'/cap-helper_pp.php' );
	$pp_cap_helper = new PP_Cap_Helper();

	do_action( 'pp_pre_init' );
	
	if ( is_admin() ) {
		load_plugin_textdomain( 'pp', false, PPC_FOLDER . '/languages' );
		pp_admin_init();
	}
}

function _pp_act_set_current_user() {
	global $current_user, $pp_current_user;
	$pp_current_user = pp_get_user($current_user->ID);
	
	static $done;
	
	if ( ! empty($done) ) {
		global $cap_interceptor;
		if ( isset($cap_interceptor) )
			$cap_interceptor->memcache = array();
	}
	$done = true;
	
	if ( defined('INIT_ACTION_DONE_PP') )
		pp_init_with_user();
	else
		add_action('init', 'pp_init_with_user', 70);  // _pp_on_init() and 3rd party filters related to type / taxonomy / cap definitions must execute first
}

// this fires once $current_user is set and other init action handlers up to at least priority 50 are done
function pp_init_with_user() {
	global $current_user, $pp_current_user, $pp;

	if ( empty( $pp_current_user) || ! defined( 'INIT_ACTION_DONE_PP' ) )
		return;

	require_once( dirname(__FILE__).'/pp_main.php');
	
	if ( empty($pp) )
		$pp = new PP();
	else
		$pp->load_user_config();
	
	$pp_current_user->retrieve_extra_groups();  // retrieve BP groups and other group types registered by 3rd party

	pp_supplement_user_allcaps( $pp_current_user );
	$current_user->allcaps = array_merge( $current_user->allcaps, $pp_current_user->allcaps );  // copies above changes and any 3rd party filtering
	
	do_action( 'pp_user_init' );
}

function pp_supplement_user_allcaps( &$user ) {
	global $pp_cap_helper;
	
	if ( pp_is_content_administrator() ) {
		// give content administrators (users with pp_administer_content capability in WP role) all PP-defined caps and type-specific post caps
		$user->allcaps = apply_filters( 'pp_administrator_caps', array_merge( $user->allcaps, $pp_cap_helper->all_type_caps ) );
	} else {
		if ( ! $user->ID ) {
			global $pp_role_defs;
			$user->allcaps = array_merge( $user->allcaps, array_fill_keys( $pp_role_defs->anon_user_caps, true ) );
		} else {
			global $wp_roles, $wp_post_types, $wpdb;

			// merge in caps from supplemental direct role assignments
			foreach( array_keys( $user->site_roles ) as $role_name ) {
				if ( isset( $wp_roles->role_objects[$role_name] ) )
					$user->allcaps = array_merge( $user->allcaps, $wp_roles->role_objects[$role_name]->capabilities );
				elseif ( ! strpos( $role_name, ':' ) )
					$user->allcaps = array_merge( $user->allcaps, array_fill_keys( apply_filters( 'pp_role_caps', array(), $role_name ), true ) );
			}
			
			if ( PP_MULTISITE && ! is_user_member_of_blog() && empty($user->allcaps) ) {
				$user->allcaps['read'] = true;
			}
		}

		// merge in caps from typecast WP role assignments (and also clear false-valued allcaps entries)
		$pp_cap_caster = pp_init_cap_caster();
		$user->allcaps = array_merge( array_diff( $user->allcaps, array( false, 0 ) ), $pp_cap_caster->get_user_typecast_caps($user) );
	}
}

function pp_get_user( $user_id, $name = '', $args = array() ) {
	require_once( dirname(__FILE__).'/pp-user.php');
	$user = new PP_User( $user_id, $name, $args );
	return $user;
}

function pp_init_cap_caster() {
	global $pp_cap_caster;
	if ( ! isset( $pp_cap_caster ) ) {
		require_once( dirname(__FILE__).'/cap-caster_pp.php' );
		$pp_cap_caster = new PP_Cap_Caster();
	}

	return $pp_cap_caster;
}

function pp_init_terms_interceptor() {
	global $terms_interceptor;
	if ( empty( $terms_interceptor ) ) {
		require_once( dirname(__FILE__).'/terms-interceptor_pp.php' );
		$terms_interceptor = new PP_TermsInterceptor();
	}
	
	return $terms_interceptor;
}

function pp_refresh_options() {
	global $wpdb, $pp_site_options;
	
	do_action( 'pp_refresh_options' );
	
	$pp_site_options = array();

	foreach ( $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE 'pp_%'") as $row )
		$pp_site_options[$row->option_name] = $row->option_value;

	// this would normally be handled in PPP, but leave here so bbp roles are never listed as WP role groups
	if ( function_exists('bbp_get_version') && version_compare( bbp_get_version(), '2.2', '>=' ) ) {
		$pp_only_roles = ( isset($pp_site_options['pp_supplemental_role_defs']) ) ? maybe_unserialize( $pp_site_options['pp_supplemental_role_defs'] ) : array();
		$pp_site_options['pp_supplemental_role_defs'] = serialize( array_merge( $pp_only_roles, array( 'bbp_participant', 'bbp_moderator', 'bbp_keymaster', 'bbp_blocked', 'bbp_spectator' ) ) );
	}
	
	$pp_site_options = apply_filters( 'pp_options', $pp_site_options );
}

function pp_delete_option( $option_basename, $args = array() ) {
	if ( PP_MULTISITE ) {
		global $pp_netwide_options;
		if ( ! empty($pp_netwide_options) && in_array( $option_basename, (array) $pp_netwide_options ) ) {
			delete_site_option( "pp_{$option_basename}" );
			return;
		}
	}
		
	delete_option( "pp_{$option_basename}" );
}

function pp_update_option( $option_basename, $option_val, $args = array() ) {
	if ( PP_MULTISITE ) {
		global $pp_netwide_options;
		if ( ! empty($pp_netwide_options) && in_array( $option_basename, (array) $pp_netwide_options ) ) {
			global $pp_net_options;
			$pp_net_options["pp_$option_basename"] = $option_val;
			update_site_option( "pp_$option_basename", $option_val );
			return;
		}
	}
	
	global $pp_site_options;
	$pp_site_options["pp_$option_basename"] = $option_val;
	update_option( "pp_$option_basename", $option_val );

	do_action( 'pp_update_option', $option_basename, $option_val, $args );
}

function pp_get_option( $option_basename ) {
	if ( PP_MULTISITE ) {
		global $pp_netwide_options;
		
		if ( ! empty($pp_netwide_options) && in_array( $option_basename, (array) $pp_netwide_options ) ) {
			global $pp_net_options;

			if ( ! is_array($pp_net_options) || ! isset($pp_net_options["pp_$option_basename"]) ) {	// in case PP Compatibility is not activated
				if ( in_array( $option_basename, array( 'support_key', 'beta_updates' ) ) )
					$pp_net_options["pp_$option_basename"] = get_site_option( "pp_$option_basename" );
			}
			
			if ( isset($pp_net_options["pp_$option_basename"]) )
				return maybe_unserialize( $pp_net_options["pp_$option_basename"] );
				
			if ( isset( $pp_default_options[$option_basename] ) )
				return maybe_unserialize( $pp_default_options[$option_basename] );
		}
	}
	
	global $pp_default_options, $pp_site_options;
	
	if ( isset($pp_site_options["pp_$option_basename"]) )
		return maybe_unserialize( $pp_site_options["pp_$option_basename"] );

	if ( isset( $pp_default_options[$option_basename] ) )
		return maybe_unserialize( $pp_default_options[$option_basename] );

	// return null if option not set in db or defaults
}

function pp_get_type_option( $option_name, $object_type, $default_fallback = false ) {
	if ( $arr = (array) pp_get_option( $option_name ) ) {
		if ( isset( $arr[$object_type] ) )
			return $arr[$object_type];
		elseif( $default_fallback && isset( $arr[''] ) )
			return $arr[''];
	}

	return false;
}

function pp_get_enabled_types( $src_name, $args = array(), $output = 'names' ) {
	return ( 'post' == $src_name ) ? pp_get_enabled_post_types( $args, $output ) : array();
}

function pp_get_enabled_post_types( $args = array(), $output = 'names' ) {
	$args['public'] = true;
	
	$omit_types = apply_filters( 'pp_unfiltered_post_types', array() );
	
	$object_types = array_diff_key( get_post_types( $args, 'names' ), array_fill_keys( $omit_types, true ) );
	
	if ( $enabled = (array) pp_get_option( "enabled_post_types" ) )
		$object_types = array_intersect( $object_types, array_keys( array_intersect( $enabled, array(true,1) ) ) );

	$object_types = apply_filters( 'pp_enabled_post_types', $object_types );
	
	if ( 'names' == $output )
		return $object_types;

	$arr = array();
	foreach( $object_types as $_object_type ) 
		$arr[$_object_type]= get_post_type_object( $_object_type );

	return $arr;
}

// returns all taxonomies for specified object type(s), omitting disabled types and disabled taxonomies
function pp_get_enabled_taxonomies( $args = array(), $output = 'names' ) {
	$taxonomies = array();
	$orig_args = $args;
	
	if ( isset($args['object_type']) ) {
		$object_type = $args['object_type'];
		unset( $args['object_type'] );
	} else {
		$object_type = '';
	}

	$args['public'] = true;
	
	if ( false === $object_type ) {
		$taxonomies = get_taxonomies( $args );
	} else {
		$object_types = ( $object_type ) ? (array) $object_type : pp_get_enabled_post_types();

		foreach( get_taxonomies( $args, 'object' ) as $tx ) {
			if ( array_intersect( $object_types, $tx->object_type ) )
				$taxonomies []= $tx->name;
		}
	}
	
	$taxonomies = pp_remove_disabled_taxonomies( $taxonomies );
	$taxonomies = apply_filters( 'pp_enabled_taxonomies', $taxonomies, array_merge( $args, $orig_args ) );

	if ( 'names' == $output )
		return $taxonomies;

	$arr = array();
	foreach( $taxonomies as $taxonomy ) 
		$arr [$taxonomy]= get_taxonomy( $taxonomy );

	return $arr;
}

function pp_remove_disabled_taxonomies( $taxonomies ) {
	if ( $enabled = (array) pp_get_option( "enabled_taxonomies" ) )
		$taxonomies = array_intersect( $taxonomies, array_keys( array_intersect( $enabled, array( 1, '1', true ) ) ) );

	if ( $omit_types = apply_filters( 'pp_unfiltered_taxonomies', array() ) )
		$taxonomies = array_diff_key( $taxonomies, $omit_types );

	return $taxonomies;
}

function pp_is_taxonomy_enabled( $taxonomy ) {
	if ( pp_remove_disabled_taxonomies( (array) $taxonomy ) )
		return true;
}

function pp_find_post_type( $post_id = 0, $return_default = true ) {
	global $typenow, $post;
	
	if ( ! $post_id && ! empty($typenow) )
		return $typenow;
	
	if ( is_object($post_id) )
		$post_id = $post_id->ID;
	
	if ( $post_id && ! empty($post) && ( $post->ID == $post_id ) ) {
		return $post->post_type;
	}

	require_once( dirname(__FILE__).'/pp_find-post-type.php' );
	return PP_Find::find_post_type( $post_id, $return_default );
}

function pp_is_administrator( $user_id = false, $admin_type = 'content' ) {
	global $current_user;

	$user = ( ( false === $user_id ) || ( $user_id == $current_user->ID ) ) ? $current_user : new WP_User($user_id);
	
	if ( PP_MULTISITE && $user->ID && is_super_admin($user->ID) )
		return true;

	$caps = array( 'content' =>  'pp_administer_content', 'user' => 'edit_users', 'option' => 'pp_manage_settings', 'unfiltered' => 'pp_unfiltered' );

	if ( 'unfiltered' == $admin_type ) {
		if ( ! empty( $user->allcaps[ $caps['unfiltered'] ] ) || ! empty( $user->allcaps[ $caps['content'] ] ) || apply_filters( 'pp_unfiltered_content', false ) )  // pp_administer_content cap also grants pp_unfiltered implicitly
			return true;
	} elseif ( $user->ID ) {
		if ( ! empty( $user->allcaps[ $caps[$admin_type] ] ) )
			return true;
	}

	return false;
}

function pp_is_user_administrator( $user = false ) { return pp_is_administrator( $user, 'user' ); } 
function pp_is_content_administrator( $user = false ) { return pp_is_administrator( $user, 'content' ); }
function pp_unfiltered( $user = false ) { return pp_is_administrator( $user, 'unfiltered' ); }

function pp_is_front() {
	return ( ! is_admin() && ! defined('XMLRPC_REQUEST') && ! defined('DOING_AJAX') && ! defined('DOING_CRON') );
}

function pp_get_role_attributes( $role_name ) {
	$arr_name = explode( ':', $role_name );

	$return['base_role_name'] = $arr_name[0];
	$return['src_name'] = ( ! empty($arr_name[1]) ) ? $arr_name[1] : '';
	$return['object_type'] = ( ! empty($arr_name[2]) ) ? $arr_name[2] : '';
	$return['attribute'] = ( ! empty($arr_name[3]) ) ? $arr_name[3] : '';
	$return['condition'] = ( ! empty($arr_name[4]) ) ? $arr_name[4] : '';

	return (object) $return;
}

function pp_ttid_to_termid( $tt_ids, &$taxonomy, $all_terms = false ) {
	if ( ! $taxonomy && is_array($tt_ids) )  // if multiple terms are involved, avoid complication of multiple taxonomies
		return false;
	
	if ( ! $all_terms ) {
		static $buffer_all_terms;
		
		if ( ! isset($buffer_all_terms) ) {
			global $wpdb;
		
			$buffer_all_terms = array();
		
			$results = $wpdb->get_results( "SELECT taxonomy, term_id, term_taxonomy_id FROM $wpdb->term_taxonomy" );
			foreach( $results as $row ) {
				$buffer_all_terms[$row->taxonomy][] = $row;
			}
		}
		
		$all_terms = $buffer_all_terms;
	}

	$term_ids = array();
	
	if ( is_object($all_terms) || ! $tt_ids ) // error on invalid taxonomy
		return $tt_ids;
	
	foreach ( (array) $tt_ids as $tt_id ) {
		foreach( array_keys( $all_terms ) as $_taxonomy ) {
			if ( $taxonomy && ( $_taxonomy != $taxonomy ) )	 // if conversion is for a single term, taxonomy specification is not required
				continue;
			
			foreach ( array_keys($all_terms[$_taxonomy]) as $key ) {
				if ( $all_terms[$_taxonomy][$key]->term_taxonomy_id == $tt_id ) {
					$term_ids []= $all_terms[$_taxonomy][$key]->term_id;
					
					if ( ! $taxonomy )
						$taxonomy = $_taxonomy;				// set byref variable to determined taxonomy

					break;
				}
			}
		}
	}

	return ( is_array($tt_ids) ) ? $term_ids : current($term_ids);
}

function pp_termid_to_ttid( $term_ids, $taxonomy, $all_terms = false ) {
	if ( ! $all_terms ) {
		static $buffer_all_terms;
		
		if ( ! isset($buffer_all_terms) ) {
			global $wpdb;

			$buffer_all_terms = array();
		
			$results = $wpdb->get_results( "SELECT taxonomy, term_id, term_taxonomy_id FROM $wpdb->term_taxonomy" );
			foreach( $results as $row ) {
				$buffer_all_terms[$row->taxonomy][] = $row;
			}
		}
		
		$all_terms = $buffer_all_terms;
	}

	$tt_ids = array();
	
	if ( is_object($all_terms) || ! $term_ids || ! isset( $all_terms[$taxonomy] ) ) // error on invalid taxonomy
		return $term_ids;
	
	foreach ( (array) $term_ids as $term_id ) {		
		foreach ( array_keys($all_terms[$taxonomy]) as $key )
			if ( ( $all_terms[$taxonomy][$key]->term_id == $term_id ) ) {
				$tt_ids []= $all_terms[$taxonomy][$key]->term_taxonomy_id;
				break;
			}
	}

	return ( is_array($term_ids) ) ? $tt_ids : current($tt_ids);
}

function pp_get_post_id() {
	global $post;
	
	if ( defined('XMLRPC_REQUEST') ) {
		global $pp_xmlrpc_post_id;
		if ( ! empty( $pp_xmlrpc_post_id ) )
			return $pp_xmlrpc_post_id;
	}

	if ( ! empty($post) && is_object($post) ) {
		if ( 'auto-draft' == $post->post_status )
			return 0;
		else
			return $post->ID;

	} elseif ( ! is_admin() && is_singular() ) {
		global $wp_query;
		
		if ( ! empty($wp_query) ) {
			if ( ! empty($wp_query->query_vars) && ! empty( $wp_query->query_vars['p'] ) ) {
				return $wp_query->query_vars['p'];
			} elseif ( ! empty( $wp_query->query['post_type'] ) && ! empty( $wp_query->query['name'] ) ) {
				global $wpdb;
				return $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = %s AND post_name = %s LIMIT 1", $wp_query->query['post_type'], $wp_query->query['name'] ) );
			}
		}
	} elseif ( isset( $_REQUEST['post'] ) ) {
		return (int) $_REQUEST['post'];
	} elseif ( isset( $_REQUEST['post_ID'] ) ) {
		return (int) $_REQUEST['post_ID'];
	} elseif ( isset( $_REQUEST['post_id'] ) ) {
		return (int) $_REQUEST['post_id'];
	}
}

function pp_get_descendant_ids( $item_source, $item_id, $args = array() ) {
	// previously, removed some items based on user_can_admin_object()
	require_once( dirname(__FILE__).'/lib/ancestry-query_pp.php' );
	return pp_query_descendant_ids( $item_source, $item_id, $args );
}

function pp_get_operations() {
	return array_unique( apply_filters( 'pp_operations', array( 'read' ) ) );
}

function pp_get_version_info( $force_refresh = false, $return_raw = false, $update_transient = false ) {
	if ( ! pp_update_info_enabled() )
		return array();
	
	require_once( dirname(__FILE__).'/admin/plugin_pp.php' );
	return PP_Plugin_Status::get_version_info( $force_refresh, $return_raw, $update_transient );
}

function pp_get_extension_info( $force_refresh = false ) {
	require_once( dirname(__FILE__).'/admin/plugin_pp.php' );
	return PP_Plugin_Status::get_extension_info( $force_refresh );
}

function pp_populate_roles() {
	require_once( dirname(__FILE__).'/admin/update_pp.php');
	PP_Updated::populate_roles( true );
}

function pp_update_info_enabled() {
	if ( defined( 'PP_FORCE_PPCOM_INFO' ) || pp_get_option( 'ppcom_update_info' ) )
		return true;
	
	return pp_key_active();
}

function pp_key_active() {
	$opt_val = (array) pp_get_option( 'support_key' );
	return ! empty($opt_val[0]) && in_array( $opt_val[0], array( 1, -1 ) );
}

function ppcore_update_enabled() {
	if ( ! pp_update_info_enabled() ) return false;
	
	require_once( dirname(__FILE__).'/admin/admin-load_pp.php' );
	
	$update_info = pp_get_all_updates_info();
	return ! empty( $update_info['press-permit-core'] );
}

function pp_get_all_updates_info( $force_refresh = false ) {
	$update_info = (array) get_site_transient('ppc_all_updates');

	if ( empty( $update_info ) || $force_refresh ) {
		$update_info = (array) pp_get_version_info( true, true ); // force refresh
	}
	return $update_info;
}
