<?php
if ( ! defined( 'PP_MULTISITE' ) )
	define( 'PP_MULTISITE', is_multisite() ); // perf

if ( ! defined('PP_DEBUG' ) )
	define( 'PP_DEBUG', false );

if ( constant('PP_DEBUG') )
	include_once( dirname(__FILE__).'/debug.php' );
else
	include_once( dirname(__FILE__).'/debug_shell.php' );

if ( ! function_exists('ppc_early_exit') ) {
function ppc_early_exit() {
	global $pagenow;
	$is_admin = is_admin();

	if ( 
	( $is_admin && ! empty( $pagenow ) && in_array( $pagenow, array( 'index-extra.php' ) ) )
	|| ( ! function_exists('array_fill_keys') && ppc_error( 'old_php' ) ) 	 // some servers (Ubuntu) return irregular phpversion() format
	|| ( defined( 'PPC_VERSION' ) && ppc_error( 'multiple_pp' ) )
	|| ( ppc_is_plugin_active('press-permit.php') && ppc_error( 'pp_legacy_active' ) )
	|| ( $is_admin && strpos($_SERVER['SCRIPT_NAME'], 'wp-wall-ajax.php') ) // Early bailout for problematic 3rd party plugin ajax calls
	|| ( constant('PP_DEBUG') && $is_admin && ppc_editing_plugin() )		 // avoid lockout in case of erroneous plugin edit via wp-admin
	) {
		return true;
	}
	
	if ( ppc_is_plugin_active( 'role-scoper.php' ) )  {
		if ( is_admin() ) 
			ppc_error( 'rs_active' );
		else
			return true;
	}
}
}
	
if ( ! function_exists('ppc_error') ) {
function ppc_error( $err_slug, $arg2 = '' ) {
	include_once( dirname(__FILE__).'/error_pp.php');
	return ( 'old_wp' == $err_slug ) ? PP_Error::old_wp( 'Press Permit Core', $arg2 ) : PP_Error::error_notice( $err_slug );
}
}

if ( ! function_exists('ppc_notice') ) {
function ppc_notice( $message, $class = 'error fade', $trigger_error = false, $force = false ) {
	include_once( dirname(__FILE__).'/error_pp.php');
	return PP_Error::notice( $message, $class, $trigger_error, $force );
}
}

if ( ! function_exists('ppc_is_plugin_active') ) {
// written because WP function is_plugin_active() requires plugin folder in arg
function ppc_is_plugin_active($check_plugin_file) {
	$plugins = (array) get_option('active_plugins');
	foreach ( $plugins as $plugin_file ) {
		if ( false !== strpos($plugin_file, $check_plugin_file) ) {
			return $plugin_file;
		}
	}
	
	if ( PP_MULTISITE ) {
		$plugins = (array) get_site_option( 'active_sitewide_plugins');
		foreach ( array_keys($plugins) as $plugin_file ) { // network activated plugin names are array keys
			if ( false !== strpos($plugin_file, $check_plugin_file) ) {
				return $plugin_file;
			}
		}
	}
}
}

add_action( 'plugins_loaded', '_pp_declare_time_function', 20 );  // pp_time() function is normally only called after init by PP File URL Filter plugin
add_action( 'pp_activate', '_pp_declare_time_function', 1 );	 // ...but is called immediately on pp activation or deactivation
add_action( 'pp_deactivate', '_pp_declare_time_function', 1 );

function _pp_declare_time_function() {
	require_once( dirname(__FILE__).'/pp_time-legacy.php');
}
