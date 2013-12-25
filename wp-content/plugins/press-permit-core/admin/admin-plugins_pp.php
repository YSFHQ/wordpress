<?php
if ( ! empty($_REQUEST['activate']) || ! empty($_REQUEST['activate-multi']) ) {
	if ( get_option( 'pp_activation' ) ) {
		delete_option( 'pp_activation' );
		
		global $pp_plugin_admin;
		if ( $pp_plugin_admin )
			$pp_plugin_admin->activation_notice();
	}
}

class PP_Plugin_Admin {
	function __construct() {
		add_filter( 'plugin_row_meta', array( &$this, 'flt_plugin_action_links' ), 10, 2 );
		
		add_action( 'after_plugin_row_' . PPC_BASENAME, array(&$this, 'core_plugin_status'), 10, 3 );

		global $pp_extensions;
		foreach( $pp_extensions as $ext ) {
			add_action( 'after_plugin_row_' . $ext->basename, array(&$this, 'plugin_status'), 10, 3 );
		}
		
		add_action( 'load-plugins.php', array( &$this, 'suppress_wp_update_msg' ) );

		add_action( 'install_plugins_pre_plugin-information', array( &$this, 'changelog' ) );
		add_action( 'plugins_api', array( &$this, 'disable_plugins_api' ), 10, 3 );
	}
	
	function disable_plugins_api( $val, $action, $args ) {
		if ( $args && ! empty($args->slug) ) {
			global $pp_extensions;
		
			if ( isset( $pp_extensions[$args->slug] ) )
				return (object) array( 'body' => '' );
		}

		return $val;
	}
	
	// Suppress the standard WordPress plugin update message for PP Extensions on the plugins screen
	function suppress_wp_update_msg() {
		global $pp_extensions;
		foreach( $pp_extensions as $ext ) {
			remove_action( 'after_plugin_row_' . $ext->basename, 'wp_plugin_update_row' );
		}
	}
	
	// note: this is only called for PP Core plugin row
	function core_plugin_status( $plugin_file, $plugin_data, $status ) {
		if ( ! $this->type_usage_stored() ) {
			if ( $types = get_post_types( array( 'public' => true, '_builtin' => false ) ) ) {
				$url = admin_url('admin.php?page=pp-settings');
				echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update"><div class="update-message">';
				printf( __( 'Press Permit needs directions. Please go to %1$sPermissions > Settings%2$s and indicate which Post Types and Taxonomies should be filtered.', 'pp' ), '<a href="' . $url . '">', '</a>' );
				echo '</div></td></tr>';
			}
		}
	}
	
	// note: this is only called for PP Extension plugin rows
	function plugin_status( $plugin_file, $plugin_data, $status ) {
		require_once( dirname(__FILE__).'/plugin_pp.php' );
		PP_Plugin_Status::status( $plugin_file, $plugin_data, $status );
	}
	
	function changelog() {
		global $pp_extensions;
		if ( ! empty( $pp_extensions[ $_REQUEST["plugin"] ] ) ) {
			require_once( dirname(__FILE__).'/plugin_pp.php' );
			PP_Plugin_Status::changelog();
		}
	}
	
	// adds an Options link next to Deactivate, Edit in Plugins listing
	function flt_plugin_action_links($links, $file) {
		if ( $file == PPC_BASENAME ) {
			$links[] = "<a href='http://presspermit.com/forums/'>" . __ppw('Support Forums') . "</a>";
			
			if ( ! is_network_admin() ) {
			$page = 'pp-settings';
			$links[] = "<a href='admin.php?page=$page'>" . __ppw('Settings') . "</a>";
			}
		}
	
		return $links;
	}
	
	function type_usage_stored() {
		$types_vals = get_option( 'pp_enabled_post_types' );
		if ( ! is_array($types_vals) )
			$txs_val = get_option( 'pp_enabled_taxonomies' );
		
		return is_array($types_vals) || is_array($txs_val);
	}
	
	function activation_notice() {
		$types_vals = get_option( 'pp_enabled_post_types' );
		if ( ! is_array($types_vals) )
			$txs_val = get_option( 'pp_enabled_taxonomies' );
		
		if ( ! $this->type_usage_stored() && ! is_network_admin() ) {
			$url = admin_url('admin.php?page=pp-settings');
			ppc_notice( sprintf( __( 'Thanks for activating Press Permit Core. Please go to %1$sPermissions > Settings%2$s and indicate which Post Types and Taxonomies should be filtered.', 'pp' ), '<a href="' . $url . '">', '</a>' ), 'updated', false, true );
		}
	}
	
} // end class
