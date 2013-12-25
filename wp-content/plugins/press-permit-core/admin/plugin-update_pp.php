<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

class PP_Updater {

	/**
	 * Perform automatic updates for the core plugin and addons
	 *
	 * @author Jonathan Davis
	 * @author Kevin Behrens
	 *
	 * @return void
	 **/
	public static function update( $plugin = '' ) {
		global $parent_file, $submenu_file;

		pp_get_version_info(true);
		
		if ( ! current_user_can('update_plugins') )
			wp_die( __('You do not have sufficient permissions to update plugins for this site.') );

		if ( ! $plugin )
			$plugin = isset($_REQUEST['plugin']) ? trim($_REQUEST['plugin']) : '';

		$plugin = urldecode($plugin);

		$title = '';
		
		global $pp_extensions;

		if ( isset( $pp_extensions[ $_REQUEST['action'] ] ) ) {
			$slug = $_REQUEST['action'];
			$title = sprintf( __('Upgrade %s','pp'), $pp_extensions[ $slug ]->label );
		}
		
		if ( $slug ) {
			// temp workaround
			global $pp_update_done;
			if ( ! empty($pp_update_done) ) { $return; }
			$pp_update_done = true;
		
			// check_admin_referer('upgrade-plugin_' . $plugin);
			$parent_file = 'plugins.php';
			$submenu_file = 'plugins.php';
			?>
<style>
.press-permit .updating { background-image:url('<?php echo admin_url('');?>/images/wspin_light.gif');background-repeat:no-repeat; }
.press-permit button.updating { background-position:5px 1px;padding-left:25px; }
.press-permit .icon32 { background:url('<?php echo PP_URLPATH;?>/admin/images/pp-logo-32.png') no-repeat; width:18px;height:32px;float:left;margin-right:15px }
.press-permit p { font-family: sans-serif;font-size: 12px;line-height: 1.4em; }
</style>
			<?php
			$nonce = 'upgrade-plugin_' . $plugin;
			$url = "update.php?action={$slug}&plugin=" . $plugin;

			$upgrader = new PP_Core_Upgrader( new PP_Upgrader_Skin( compact('title', 'nonce', 'url', 'plugin') ) );
			$upgrader->upgrade($plugin);

			define('UPDATED_PP_PLUGIN', true);
			
			$plugin_updates = get_site_transient('update_plugins');

			if ( is_object($plugin_updates) && isset($plugin_updates->response) ) {
				unset( $plugin_updates->response[$plugin] );
				set_site_transient( 'update_plugins', $plugin_updates );
			}
		}
	}
	
	public static function install( $plugin = '' ) {
		global $parent_file, $submenu_file;

		if ( ! current_user_can('install_plugins') )
			wp_die( __('You do not have sufficient permissions to install plugins for this site.') );

		if ( ! $plugin )
			$plugin = isset($_REQUEST['plugin']) ? trim($_REQUEST['plugin']) : '';

		$title = __ppw('Plugin Install');
		
		$pp_available_extensions = pp_get_all_updates_info();

		if ( isset( $pp_available_extensions[ $_REQUEST['action'] ] ) ) {
			$slug = $_REQUEST['action'];
			$title = sprintf( __ppw('Install %s'), str_replace( 'Pp', 'PP', ucwords( str_replace( '-', ' ', $slug ) ) ) );
		}

		if ( $slug ) {
			// temp workaround
			global $pp_install_done;
			if ( ! empty($pp_install_done) ) { $return; }
			$pp_install_done = true;

			$parent_file = 'plugins.php';
			$submenu_file = 'plugin-install.php';
?>		
<style>
.press-permit .updating { background-image:url('<?php echo admin_url('');?>/images/wspin_light.gif');background-repeat:no-repeat; }
.press-permit button.updating { background-position:5px 1px;padding-left:25px; }
.press-permit .icon32 { background:url('<?php echo PP_URLPATH;?>/admin/images/pp-logo-32.png') no-repeat; width:18px;height:32px;float:left;margin-right:15px }
.press-permit p { font-family: sans-serif;font-size: 12px;line-height: 1.4em; }
</style>
<?php
			$upgrader = new PP_Core_Upgrader( new PP_Installer_Skin( compact('title', 'url', 'nonce', 'plugin') ) );
			$upgrader->install( $plugin, "http://presspermit.com/index.php?PPServerRequest=download&update=$slug&version=VERSION&key=KEY&site=URL" );
		}
	}

} // end class PP_Updater


/**
 * PP_Upgrader class
 *
 * Provides foundational functionality specific to Press Permit update
 * processing classes.
 *
 * Extensions derived from the WordPress WP_Upgrader & Plugin_Upgrader classes:
 * @see wp-admin/includes/class-wp-upgrader.php
 *
 * @copyright WordPress {@link http://codex.wordpress.org/Copyright_Holders}
 *
 * @author Jonathan Davis
 * @author Kevin Behrens
 **/
class PP_Upgrader extends Plugin_Upgrader {

	function download_package($package) {
		if ( ! preg_match('!^(http|https|ftp)://!i', $package) && file_exists($package) ) //Local file or remote?
			return $package; //must be a local file..

		if ( empty($package) )
			return new WP_Error('no_package', $this->strings['no_package']);

		$this->skin->feedback( 'downloading_package', $package );

		$key_data = pp_get_option( 'support_key' );
		$key_arg = ( is_array($key_data) && isset($key_data[1]) ) ? $key_data[1] : '';
		
		$vars = array('VERSION','KEY','URL');
		$values = array( urlencode(PPC_VERSION), urlencode($key_arg), urlencode( get_option('siteurl') ) );
		$package = str_replace( $vars, $values, $package );

		$download_file = download_url($package);

		if ( is_wp_error($download_file) )
			return new WP_Error('download_failed', $this->strings['download_failed'], $download_file->get_error_message());

		return $download_file;
	}
}

/**
 * PP_Core_Upgrader class
 *
 * Adds auto-update support for the core plugin.
 *
 * Extensions derived from the WordPress WP_Upgrader & Plugin_Upgrader classes:
 * @see wp-admin/includes/class-wp-upgrader.php
 *
 * @copyright WordPress {@link http://codex.wordpress.org/Copyright_Holders}
 *
 * @author Jonathan Davis
 * @author Kevin Behrens
 **/
class PP_Core_Upgrader extends PP_Upgrader {
	function upgrade_strings($plugin) {
		$title = '';
		
		global $pp_extensions;

		if ( isset( $pp_extensions[ $_REQUEST['action'] ] ) ) {
			$title = $pp_extensions[ $_REQUEST['action'] ]->label;
		}
	
		if ( $title ) {
			$this->strings['up_to_date'] = __ppw( 'The plugin is at the latest version.' );
			$this->strings['no_package'] = sprintf( __ppw('%s upgrade package not available.','pp'), $title );
			$this->strings['downloading_package'] = sprintf(__ppw('Downloading update from <span class="code">%s</span>&#8230;'),untrailingslashit('http://presspermit.com/'));
			$this->strings['unpack_package'] = __ppw('Unpacking the update&#8230;');
			$this->strings['deactivate_plugin'] = sprintf( __ppw('Deactivating %s.','pp'), $title );
			$this->strings['remove_old'] = __ppw('Removing the old version of the plugin&#8230;');
			$this->strings['remove_old_failed'] = __ppw('Could not remove the old plugin.');
			$this->strings['process_failed'] = sprintf( __ppw('%s upgrade Failed.','pp'), $title );
			$this->strings['process_success'] = sprintf( __ppw('%s upgraded successfully.','pp'), $title );
		}
	}
	
	function install_strings($plugin) {
		if ( $title = pp_pretty_slug( $plugin ) ) {
			$this->strings['no_package'] = __ppw('Install package not available.');
			$this->strings['downloading_package'] = sprintf(__ppw('Downloading install package from <span class="code">%s</span>&#8230;'),untrailingslashit('http://presspermit.com/'));
			$this->strings['unpack_package'] = __ppw('Unpacking the package&#8230;');
			$this->strings['installing_package'] = __ppw('Installing the plugin&#8230;');
			$this->strings['process_failed'] = sprintf( __ppw('%s install Failed.','pp'), $title );
			$this->strings['process_success'] = sprintf( __ppw('%s installed successfully.','pp'), $title );
		}
	}
	
	function install( $slug, $package_url ) {
		$this->init();
		$this->install_strings($slug);

		add_filter('upgrader_source_selection', array(&$this, 'check_package') );

		$this->run(array(
					'package' => $package_url,
					'destination' => WP_PLUGIN_DIR,
					'clear_destination' => false, //Do not overwrite files.
					'clear_working' => true,
					'hook_extra' => array(),
					'plugin' => "{$slug}/{$slug}.php",
					));

		remove_filter('upgrader_source_selection', array(&$this, 'check_package') );

		if ( ! $this->result || is_wp_error($this->result) )
			return $this->result;

		// Force refresh of plugin update information
		delete_site_transient('update_plugins');
		wp_cache_delete( 'plugins', 'plugins' );

		return true;
	}

	function upgrade($plugin) {
		$this->init();
		$this->upgrade_strings($plugin);

		$current = get_site_transient('ppc_update_info');
	
		if ( ! isset( $current->response[ $plugin ] ) ) {
			$this->skin->set_result(false);
			$this->skin->error('up_to_date');
			$this->skin->after();
			return false;
		}

		// Get the URL to the zip file
		$r = $current->response[ $plugin ];

		add_filter('upgrader_clear_destination', array(&$this, 'delete_old_plugin'), 10, 4);

		$this->run(array(
					'package' => $r->package,
					'destination' => WP_PLUGIN_DIR,
					'clear_destination' => true,
					'clear_working' => true,
					'hook_extra' => array(
					'plugin' => $plugin
					)
				));

		// Cleanup our hooks, incase something else does a upgrade on this connection.
		remove_filter('upgrader_clear_destination', array(&$this, 'delete_old_plugin'));

		if ( ! $this->result || is_wp_error($this->result) )
			return $this->result;

		// Force refresh of plugin update information
		set_site_transient('ppc_update_info', false);
	}
}


/**
 * PP_Upgrader_Skin class
 *
 * Extensions derived from the WordPress Plugin_Upgrader_Skin class:
 * @see wp-admin/includes/class-wp-upgrader.php
 *
 * @author Jonathan Davis
 * @author Kevin Behrens
 **/
class PP_Upgrader_Skin extends Plugin_Upgrader_Skin {

	/**
	 * Custom heading
	 *
	 * @author Jonathan Davis
	 * @author Kevin Behrens
	 *
	 * @return void Description...
	 **/

	function header() {
		if ( $this->done_header )
			return;
		$this->done_header = true;
		echo '<div class="wrap press-permit">';
		echo screen_icon();
		echo '<h2>' . $this->options['title'] . '</h2>';
	}


	/**
	 * Displays a return to plugins page button after installation
	 *
	 * @author Jonathan Davis
	 *
	 * @return void
	 **/
	function after() {
		if ( PP_MULTISITE && pp_is_network_activated(PPC_BASENAME) )
			return;
		
		if ( ! empty($_REQUEST['pp_install']) ) {
			$url = 'admin.php?page=pp-settings&pp_tab=install';
			$title = __('Reload Press Permit Install Screen', 'pp');
		} else {
			$url = ( is_network_admin() ) ? 'network/plugins.php' : 'plugins.php';
			$title = __('Return to Plugins page');
		}

		$this->feedback('<a href="' . admin_url($url) . '" title="' . esc_attr($title) . '" target="_parent" class="button-secondary">' . $title . '</a>');
	}
} // END class PP_Upgrader_Skin


/**
 * PP_Installer_Skin class
 *
 * Extensions derived from the WordPress Plugin_Upgrader_Skin class:
 * @see wp-admin/includes/class-wp-upgrader.php
 *
 * @author Jonathan Davis
 * @author Kevin Behrens
 **/
class PP_Installer_Skin extends Plugin_Installer_Skin {

	/**
	 * Custom heading
	 *
	 * @author Jonathan Davis
	 * @author Kevin Behrens
	 *
	 * @return void Description...
	 **/

	function header() {
		if ( $this->done_header )
			return;
		$this->done_header = true;
		echo '<div class="wrap press-permit">';
		echo screen_icon();
		echo '<h2>' . $this->options['title'] . '</h2>';
	}


	/**
	 * Displays a return to plugins page button after installation
	 *
	 * @author Jonathan Davis
	 *
	 * @return void
	 **/
	function after() {
		$plugin_file = $this->upgrader->plugin_info();

		$install_actions = array();

		$from = isset($_GET['from']) ? stripslashes($_GET['from']) : 'plugins';

		$install_actions['activate_plugin'] = '<a href="' . wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $plugin_file, 'activate-plugin_' . $plugin_file) . '" title="' . esc_attr__('Activate this plugin') . '" target="_parent">' . __('Activate Plugin') . '</a>';

		if ( is_multisite() && current_user_can( 'manage_network_plugins' ) ) {
			$install_actions['network_activate'] = '<a href="' . wp_nonce_url('plugins.php?action=activate&amp;networkwide=1&amp;plugin=' . $plugin_file, 'activate-plugin_' . $plugin_file) . '" title="' . esc_attr__('Activate this plugin for all sites in this network') . '" target="_parent">' . __('Network Activate') . '</a>';
			unset( $install_actions['activate_plugin'] );
		}

		$title = __('Reload PP Install Screen', 'pp');
		$url = 'admin.php?page=pp-settings&pp_tab=install';
		$install_actions['pp_install'] = '<a href="' . admin_url($url) . '" title="' . esc_attr__($title) . '" target="_parent" class="button-secondary">' . $title . '</a>';

		$install_actions = apply_filters('install_plugin_complete_actions', $install_actions, $this->api, $plugin_file);
		if ( ! empty($install_actions) )
			$this->feedback(implode(' | ', (array)$install_actions));
	}
} // END class PP_Installer_Skin

