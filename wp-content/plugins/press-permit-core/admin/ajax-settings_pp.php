<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

switch ( $_GET['pp_ajax_settings'] ) {
	case 'activate_key':
		check_admin_referer('wp_ajax_pp_activate_key');
		PP_Key::activate_key();
		break;
	
	case 'deactivate_key':
		check_admin_referer('wp_ajax_pp_deactivate_key');
		PP_Key::deactivate_key();
		break;

	case 'refresh_version':
		check_admin_referer('wp_ajax_pp_refresh_version');
		PP_Key::refresh_version();
		break;
}

class PP_Key {
	public static function activate_key () {
		if ( PP_MULTISITE && ! is_super_admin() && ( pp_is_network_activated(PPC_BASENAME) || pp_is_mu_plugin(PPC_FILE) ) )
			return;
		
		require_once( dirname(__FILE__).'/plugin_pp.php' );

		$request_vars = array(
			'key' => pp_sanitize_key($_GET['key']),
			'site' => site_url(''),
		);
		$response = PP_Plugin_Status::callhome( 'activate-key', $request_vars );
		$result = json_decode($response);
		if ( $result[0] == "1" )
			pp_update_option( 'support_key', $result );
		
		echo $response;
		exit();
	}

	public static function deactivate_key () {
		if ( PP_MULTISITE && ! is_super_admin() && ( pp_is_network_activated(PPC_BASENAME) || pp_is_mu_plugin(PPC_FILE) ) )
			return;
		
		require_once( dirname(__FILE__).'/plugin_pp.php' );
	
		$support_key = pp_get_option( 'support_key' );
		$request_vars = array(
			'key' => $support_key[1],
			'site' => site_url(''),
		);
		
		$response = PP_Plugin_Status::callhome( 'deactivate-key', $request_vars );
		
		$result = json_decode($response);
		if ( $result[0] == "0" ) 
			pp_delete_option( 'support_key' );
	
		echo $response;
		exit();
	}
	
	public static function refresh_version () {
		//delete_site_transient( 'update_plugins' );

		require_once( dirname(__FILE__).'/plugin_pp.php' );
		PP_Plugin_Status::get_version_info( true, false, true );
		exit();
	}
}
