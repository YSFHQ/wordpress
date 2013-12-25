<?php
/**
 * Plugin Name: Press Permit Core
 * Plugin URI:  http://presspermit.com
 * Description: Advanced yet accessible content permissions. Give users or groups type-specific roles. Enable or block access for specific posts or terms.
 * Author:      Agapetry Creations LLC
 * Author URI:  http://agapetry.com/
 * Version:     2.1.38
 * Text Domain: pp
 * Domain Path: /languages/
 * Min WP Version: 3.4
 */

/*
Portions created by Kevin Behrens / Agapetry Creations are Copyright © 2011-2013 by Agapetry Creations LLC.

This file is part of Press Permit Core.

Press Permit Core is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Press Permit Core is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this plugin.  If not, see <http://www.gnu.org/licenses/>.
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( defined( 'PPC_FOLDER' ) ) {
	require_once( dirname(__FILE__).'/lib/pp_bootstrap_lib.php' );
	ppc_error('multiple_pp');
	return;
} else {
	define( 'PPC_FILE', __FILE__ );
	define( 'PPC_BASENAME', plugin_basename(__FILE__) );
	define( 'PPC_FOLDER', dirname( plugin_basename(__FILE__) ) );

	add_action( 'plugins_loaded', '_pp_act_load', -10 ); // negative priority to precede any default WP action handlers
	
	function _pp_act_load() {
		$min_wp_version = '3.4';
	
		require_once( dirname(__FILE__).'/lib/pp_bootstrap_lib.php' );
		
		global $wp_version;
		if ( version_compare( $wp_version, $min_wp_version, '<' ) ) {
			ppc_error( 'old_wp', $min_wp_version );
			return;
		}
		
		if ( defined('WPMU_PLUGIN_DIR') && ( false !== strpos( __FILE__, WPMU_PLUGIN_DIR ) ) )
			define( 'PPC_ABSPATH', WPMU_PLUGIN_DIR . DIRECTORY_SEPARATOR . basename(PPC_FOLDER) );
		elseif ( defined('WP_PLUGIN_DIR') )
			define( 'PPC_ABSPATH', WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . basename(PPC_FOLDER) );
		else
			define( 'PPC_ABSPATH', WP_CONTENT_DIR . '/plugins/' . PPC_FOLDER );
	
		if ( ! ppc_early_exit() ) {
			define( 'PPC_VERSION', '2.1.38' );
			define( 'PPC_DB_VERSION', '2.0.1' );

			global $pp_min_ext_version;
			$pp_min_ext_version = array( 
				'pp-buddypress-role-groups' => '2.1-beta',
				'pp-circles' => '2.1.2-beta',
				'pp-collaborative-editing' => '2.1.6-beta',
				'pp-compatibility' => '2.1.2-beta',
				'pp-content-teaser' => '2.0.3-beta',
				'pp-custom-post-statuses' => '2.1.5-beta',
				'pp-file-url-filter' => '2.1.3-beta',
				'pp-import' => '2.1.4',
				'pp-membership' => '2.0-beta',
				'pp-for-wpml' => '2.0.1-beta',
			);

			require_once( dirname(__FILE__).'/pp_load.php' );
		}
	}
	
	register_activation_hook( __FILE__, 'ppc_activate' );
	register_deactivation_hook( __FILE__, 'ppc_deactivate' );
	
	function ppc_activate() {
		require_once( dirname(__FILE__).'/pp_activation.php' );
		PP_Activation::activate();
	}

	function ppc_deactivate() {
		require_once( dirname(__FILE__).'/pp_activation.php' );
		PP_Activation::deactivate();
	}
}
