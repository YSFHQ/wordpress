<?php
if ( ! class_exists( 'PP_Activation' ) ) {
class PP_Activation {
	public static function activate() {
		global $pp_db_setup_done;
		
		// set_current_user may have triggered DB setup already
		if ( empty ($pp_db_setup_done) ) {
			$ver = (array) get_option( 'pp_c_version' );
			$db_ver = ( isset( $ver['db_version'] ) ) ? $ver['db_version'] : '';
			
			require_once( dirname(__FILE__).'/db-setup_pp.php');
			PP_DB_Setup::db_setup( $db_ver );
		}
		
		require_once( dirname(__FILE__).'/admin/update_pp.php');
		PP_Updated::sync_wproles();
		
		update_option( 'pp_activation', true );
		
		do_action( 'pp_activate' );
	}

	public static function deactivate() {
		set_site_transient('ppc_update_info', false);
		
		/*
		delete_site_transient('update_plugins');
		*/

		$plugin_updates = get_site_transient('update_plugins');
		
		unset( $plugin_updates->response[PPC_BASENAME] );
		
		global $pp_extensions;
		if ( ! empty($pp_extensions) ) {
			foreach( array_keys($pp_extensions) as $ext )
				unset($plugin_updates->response[ $pp_extensions[$ext]->basename ]);
		}

		set_site_transient('update_plugins',$plugin_updates);

		do_action( 'pp_deactivate' );
	}
} // end class
} // endif
