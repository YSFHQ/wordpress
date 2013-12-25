<?php
if ( ! class_exists('PP_Error') ) {
class PP_Error {
	public static function old_pp( $ext_title, $min_pp_version ) {
		self::error_box( sprintf( __('%1$s won&#39;t work until you upgrade Press Permit Core to version %2$s or later.', 'pp'), $ext_title, $min_pp_version ) );
		return true;
	}
	
	public static function old_wp( $ext_title, $min_wp_version ) {
		self::error_box( sprintf( __('%1$s won&#39;t work until you upgrade WordPress to version %2$s or later.', 'pp'), $ext_title, $min_wp_version ) );
		return true;
	}
	
	public static function old_extension( $ext_title, $min_ext_version ) {		
		self::error_box( sprintf( __('This version of %1$s cannot work with your current PP Core. Please upgrade it to %2$s or later.', 'pp'), $ext_title, $min_ext_version ) );
		return true;
	}
	
	public static function duplicate_extension( $ext_slug, $ext_folder ) {		
		self::error_box( sprintf( __('Duplicate Press Permit extension activated (%1$s in folder %2$s).', 'pp'), $ext_slug, $ext_folder ) );
		return true;
	}
	
	public static function error_notice( $err ) {
		switch( $err ) {
			case 'multiple_pp' :
				global $pagenow;
			
				if ( is_admin() && ( 'plugins.php' == $pagenow ) && ! strpos( urldecode($_SERVER['REQUEST_URI']), 'deactivate' ) ) {
					$message = sprintf( '<strong>Error:</strong> Multiple copies of %1$s activated. Only the copy in folder "%2$s" is functional.', 'Press Permit', PPC_FOLDER );
					add_action('all_admin_notices', create_function('', 'echo \'<div id="message" class="error fade" style="color: black">' . $message . '</div>\';'));
				}
				break;
				
			case 'rs_active' :
				define( 'PP_DISABLE_QUERYFILTERS', true );
				$message = sprintf( '<strong>Note:</strong> Press Permit is running in configuration only mode. Access filtering will not be applied until Role Scoper is deactivated.' );
				add_action('all_admin_notices', create_function('', 'echo \'<div id="message" class="error fade" style="color: black">' . $message . '</div>\';'));
				define( 'PP_CONFIG_ONLY', true );
				define( 'PP_DISABLE_MENU_TWEAK', true );
				return false;
				break;
			
			case 'pp_legacy_active' :
				ppc_notice('Press Permit Core 2 cannot operate with an older version of Press Permit active.');
				break;

			case 'old_php' :
				ppc_notice('Sorry, Press Permit requires PHP 5.2 or higher. Please upgrade your server or deactivate Press Permit.');
				break;
			
			default :
		}
		
		return true;
	}

	static function error_box( $msg ) {
		global $pagenow;
		
		if ( isset($pagenow) && ( 'update.php' != $pagenow ) ) {
			$func_body = "echo '" 
			. '<div id="message" class="error fade" style="color: black"><p><strong>' . $msg . '</strong></p></div>'
			. "';";

			add_action('all_admin_notices', create_function('', $func_body) );
		}
	}
	
	public static function notice( $message, $class = 'error fade', $trigger_error = false, $force = false ) {
		if ( $force || constant( 'PP_DEBUG' ) ) {
			// slick method copied from NextGEN Gallery plugin
			add_action( 'all_admin_notices', create_function('', 'echo \'<div id="message" class="' . $class . '" style="color: black"><p>' . $message . '</p></div>\';') );
			
			if ( $trigger_error ) {
				trigger_error("$plugin_name internal notice: $message");
				$err = new WP_Error('Press Permit', $message);
			}
		}
	}
} // end class
} // endif exists
