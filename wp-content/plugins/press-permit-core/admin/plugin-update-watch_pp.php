<?php
class PP_UpdateWatch {
	public static function update_watch() {
		global $pp_extensions;
		
		if ( ( empty( $_REQUEST['action'] ) || ( in_array( $_REQUEST['action'], array( 'edit', 'trash', 'delete' ) ) ) && empty( $_REQUEST['action2'] ) ) )
			return;

		$pp_update_info = pp_get_all_updates_info();
		
		$action = ( isset($_REQUEST['action']) ) ? sanitize_key($_REQUEST['action']) : '';
		
		if ( ( -1 == $action ) || ! $action )
			$action = ( isset($_REQUEST['action2']) ) ? sanitize_key($_REQUEST['action2']) : '';

		if ( isset( $pp_extensions[$action] ) ) {
			add_action( "update-custom_{$action}", array( 'PP_UpdateWatch', 'plugin_update' ) );
		
		} elseif ( ! empty($_REQUEST['pp_install']) && isset( $pp_update_info[$action] ) ) {
			add_action( "update-custom_{$action}", array( 'PP_UpdateWatch', 'plugin_install' ) );
		
		} elseif ( in_array( $action, array( 'update-selected', 'do-plugin-upgrade' ) ) && ( ! empty($_REQUEST['checked']) || ! empty($_REQUEST['plugins']) ) ) {
			$slug_by_basename = array();

			foreach( $pp_extensions as $slug => $ext ) {
				$slug_by_basename[$ext->basename] = $slug;
			}
			
			$arr_checked = ( isset($_REQUEST['checked']) ) ? (array) $_REQUEST['checked'] : (array) $_REQUEST['plugins'];

			$msg = sprintf( __('Bulk Update is not currently supported for Press Permit. Please use individual plugin update links in the %1$splugins list%2$s or %3$sPP Install tab%4$s instead.', 'pp' ), '<a href="plugins.php?plugin_status=upgrade">', '</a>', '<a href="admin.php?page=pp-settings&pp-tab=install">', '</a>' );
			
			$any_pp_updates = false;
			foreach( $arr_checked as $basename ) {
				if ( isset( $slug_by_basename[$basename] ) ) {
					$any_pp_updates = true;
					ppc_notice( $msg );
					break;
				}
			}
			
			// for now, just remove PP and extensions from bulk update
			$any_other_updates = false;
			foreach( array( 'checked', 'plugins' ) as $varname ) {
				if ( isset($_REQUEST[$varname]) ) {
					if ( is_array( $_REQUEST[$varname] ) ) {
						$_REQUEST[$varname] = @array_diff( $_REQUEST[$varname], array_keys($slug_by_basename) );
						$_POST[$varname] = @array_diff( $_POST[$varname], array_keys($slug_by_basename) );
						if ( count($_REQUEST[$varname]) ) {
							$any_other_updates = true;
						}
					} else {
						$arr_selected = explode( ',', $_REQUEST[$varname] );
						$arr_selected = @array_diff( $arr_selected, array_keys($slug_by_basename) );
						if ( count($arr_selected ) ) {
							$any_other_updates = true;
						}
						$_REQUEST[$varname] = implode( ',', $arr_selected );
						$_POST[$varname] = implode( ',', $arr_selected );
					}
				}
			}

			if ( $any_pp_updates && ! $any_other_updates ) {
				wp_die( $msg );
			}
		}
	}
	
	public static function plugin_update( $plugin = '' ) {
		require_once( dirname(__FILE__).'/plugin-update_pp.php' );
		PP_Updater::update( $plugin );
	}

	public static function plugin_install( $plugin = '' ) {
		require_once( dirname(__FILE__).'/plugin-update_pp.php' );
		PP_Updater::install( $plugin );
	}
} // end class

