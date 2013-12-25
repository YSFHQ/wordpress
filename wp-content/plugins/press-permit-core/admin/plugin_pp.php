<?php
class PP_Plugin_Status {
	public static function get_remote_request_params(){
		$_key = pp_get_option( 'support_key' );
		if ( is_array($_key) && isset($_key[1]) )
			$key = $_key[1];
		else
			$key = '';
		
		global $wpdb;
		
		$data = array(
			'call' => 'pp',
			'key' => $key,
			'core' => PPC_VERSION,
			'wp' => get_bloginfo('version'),
			'is_ms' => PP_MULTISITE,
			'php' => phpversion(),
			'mysql' => $wpdb->db_version(),
		);
		
		$data['bp'] = ( defined( 'BP_VERSION' ) ) ? BP_VERSION : 0;
		$data['bbp'] = ( function_exists( 'bbp_get_version' ) ) ? bbp_get_version() : 0;
		$data['rvy'] = ( defined( 'RVY_VERSION' ) ) ? RVY_VERSION : 0;
		$data['cme'] = ( defined( 'CAPSMAN_ENH_VERSION' ) ) ? CAPSMAN_ENH_VERSION : 0;
		
		global $pp_extensions;
		foreach( $pp_extensions as $slug => $ext )
			$data[$slug] = ( isset($ext->version) ) ? $ext->version : 0;
		
		$data['beta_updates'] = pp_get_option( 'beta_updates' );
		
		return $data;
    }

	// function callhome() derived from Gravity Forms and Shopp
	public static function callhome( $request_topic, $request_vars = array(), $post_vars = false ) {
		$request_vars = array_merge( self::get_remote_request_params(), (array) $request_vars, array( 'PPServerRequest' => $request_topic ) ); //, 'site' => urlencode( get_option('siteurl') ) ) );
		//dump($request_vars);
		//die('test');
		
		$query = http_build_query( $request_vars, '', '&' );
		//pp_errlog($query);
		
		$args = array(
			'headers' => array(
				'Content-Type' => 'application/x-www-form-urlencoded; charset=' . get_option('blog_charset'),
				'User-Agent' => 'WordPress/' . get_bloginfo("version"),
				'Referer' => get_bloginfo("url")
			),
		);
		
		if ( 'config-info' == $request_topic )
			$args['timeout'] = 30;
		else
			$args['timeout'] = in_array( $request_topic, array( 'update-check', 'changelog' ) ) ? 8 : 20;
		
		if ( false !== $post_vars )
			$args['body'] = $post_vars;
		
		$server_response = wp_remote_post( 'http://presspermit.com/' . "index.php?$query", $args );
		
		//dump($server_response);
		
		$result = false;
		
		if ( is_wp_error($server_response) ) {
			// @todo
		} else {
			if ( isset($server_response['body']) ) {
				$start_tag = '<!--PPServer-->';
				$pos = strpos( $server_response['body'], $start_tag );
				if ( false !== $pos ) {
					$end_pos = strpos( $server_response['body'], '<!--End PPServer-->' );
					if ( $end_pos ) {
						$result = substr( $server_response['body'], $pos + strlen($start_tag), $end_pos - ( $pos + strlen($start_tag) ) );
						$result = maybe_unserialize($result);
					}
				}
			}
		}

		if ( 'config-upload' == $request_topic )
			return ( ! empty($server_response) && ! is_wp_error($server_response) );
		else
			return $result;
	}

	// function get_verision_info() derived from Gravity Forms and Shopp
	public static function get_version_info( $force_refresh = false, $return_all = false, $update_transient = false ){
		$updates = get_site_transient('ppc_update_info'); // pull current stored values from DB

		if ( ! is_object($updates) )
			$updates = (object) array( 'response' => array() );
		
		static $done;  // sanity check - never call more than once in a single http request (but support exception for retrieval of Available Extensions info)
		if ( ! empty($updates->response) && isset($done) && ! ( $force_refresh || ! $return_all ) ) { return $updates; }
		$done = true;

		$ext_by_basename = array();
		global $pp_extensions;
		
		foreach( $pp_extensions as $ext ) {
			$ext_by_basename[$ext->basename] = $ext;
		}
		
        if ( empty($_REQUEST['deactivate']) && empty($_REQUEST['deactivate-multi']) && ( $force_refresh || empty($updates) || ! isset($updates->response[PPC_BASENAME]) || array_diff_key( $ext_by_basename, $updates->response ) ) ) {
			$raw_response = self::callhome( "update-check-ppc" );

			$all_updates = ( isset( $raw_response->all ) ) ? $raw_response->all : array();

			set_site_transient( 'ppc_all_updates', $all_updates );

			foreach( $pp_extensions as $ext_slug => $ext ) {
				if ( isset($raw_response->extensions) && isset($raw_response->extensions[$ext_slug]) ) {
					$updates->response[$ext->basename] = (object) $raw_response->extensions[$ext_slug];
				} else {
					$updates->response[$ext->basename] = false;
				}
			}

			unset($raw_response->extensions);
			$updates->response[PPC_BASENAME] = $raw_response;  // for support key status and alerts
			
			if ( $raw_response && ( -1 !== $raw_response ) ) {
				if ( ! defined( 'UPDATED_PP_PLUGIN' ) )
					set_site_transient('ppc_update_info', $updates, 7200); //caching for 2 hours
			}
        }
		
		if ( $updates && ! empty($updates->response) ) {
			foreach( $updates->response as $info ) {
				if ( ! empty( $info->alert ) && current_user_can( 'pp_manage_settings' ) ) {
					static $did_alerts;
					
					if ( ! isset($did_alerts) )
						$did_alerts = array();
					
					if ( ! isset( $did_alerts[$info->slug] ) ) {
						global $pp_plugin_page;
						
						if ( empty($pp_plugin_page) ) {
							$message = '<span class="pp_alert">' . esc_html($info->alert) . '</span>';
							add_action('admin_notices', create_function('', 'echo \'<div id="message" class="error fade" style="color: black">' . $message . '</div>\';'), 5 );
						}
						
						$did_alerts[$info->slug] = true;
					}	
				}
			}
		}
		
		$plugin_updates = (object) get_site_transient('update_plugins');

		$any_update_modifications = false;
		if ( $updates && ! empty($updates->response) ) {
			if ( ! empty($updates->response[PPC_BASENAME]) ) {
				if ( $update_transient ) {
					// update expiration date (in case of new expiration approach, expiration or renewal)
					$_key = pp_get_option( 'support_key' );

					if ( ! is_array($_key) )
						$_key = array();
					
					if ( isset( $updates->response[PPC_BASENAME]->key_status ) )
						$_key[0] = $updates->response[PPC_BASENAME]->key_status;
					
					if ( isset( $updates->response[PPC_BASENAME]->expire_date_gmt ) )
						$_key['expire_date_gmt'] = $updates->response[PPC_BASENAME]->expire_date_gmt;
					else
						unset( $_key['expire_date_gmt'] );
						
					pp_update_option('support_key', $_key);
				}
			}
			
			foreach( $ext_by_basename as $ext_base => $ext ) {
				if ( ! empty( $updates->response[$ext_base]->package ) ) {
					$plugin_updates->response[$ext_base] = (object) array( 'slug' => $ext->slug );	// Add this PP extension to the WP plugin update notification count
					$any_update_modifications = true;
				
				} elseif ( isset( $plugin_updates->response ) && isset( $plugin_updates->response[$ext_base] ) ) {
					unset( $plugin_updates->response[$ext_base] );
					$any_update_modifications = true;
				}
			}
		}

		if ( $update_transient && $any_update_modifications && ! defined('UPDATED_PP_PLUGIN') ) {
			set_site_transient( 'update_plugins', $plugin_updates );
		}
		
		if ( $return_all ) {
			return $all_updates;
		} else {
			return $updates;
		}
	}
	
	public static function get_extension_info( $force_refresh = false ){
		global $pp_extensions;
		
		$ext_info = get_site_transient('ppc_extension_info'); // pull current stored values from DB
		
		if ( ! $force_refresh ) {
			foreach( $pp_extensions as $slug => $info ) {
				if ( empty( $ext_info->blurb[$slug] ) ) {
					$force_refresh = true;
					break;
				}
			}
		}
		
		if ( $force_refresh ) {
			if ( $raw_response = self::callhome( "ext-info" ) ) {
				$ext_info = maybe_unserialize( gzuncompress ( base64_decode( $raw_response ) ) );
				set_site_transient( 'ppc_extension_info', $ext_info );
			}
		}
		
		return $ext_info;
	}
	
	/**
	 * Reports on the availability of new updates and the update key
	 *
	 * @author Jonathan Davis
	 * @author Kevin Behrens
	 *
	 * @return void
	 **/
	public static function status ( $plugin_file, $plugin_data, $status ) {
		$updates = self::get_version_info();
		
		$key = pp_get_option( 'support_key' );
		
		$activated = isset($key[0])?($key[0] == '1'):false;

		global $pp_extensions;
		$slug = '';
		foreach( $pp_extensions as $_slug => $ext ) {
			if ( $plugin_file == $ext->basename ) {
				$slug = $_slug;
				$current_version = $ext->version;
				break;
			}
		}
		
		if ( ! $slug )
			return;

		$version_info = isset( $updates->response[$plugin_file] ) ? $updates->response[$plugin_file] : false;
		
		if ( ! empty( $version_info ) && isset($version_info->new_version) ) { // Core update available
			if ( version_compare( $version_info->new_version, $current_version, '>' )
			|| ( ! pp_get_option('beta_updates') && preg_match( "/dev|alpha|beta|rc/i", $current_version ) )
			) { 
				$network = ( is_network_admin() ) ? 'network/' : '';
				$details_url = admin_url("{$network}plugin-install.php?tab=plugin-information&plugin=$slug&TB_iframe=true&width=600&height=800");
				$update_url = wp_nonce_url( "update.php?action=$slug&plugin=" . $plugin_file, 'upgrade-plugin_' . $slug );
				
				if ( ! $activated ) { // Key not active for extension update
					$update_url = 'http://presspermit.com/' . 'purchase/';
					$message = sprintf(__('There is a new version of %1$s, but <a href="%2$s">your support key</a> is not activated. No automatic update available. <a href="%3$s">Purchase a Press Permit Pro support key</a> for one-click updates and support resources. <a href="%4$s" class="thickbox" title="%5$s">View version %6$s details</a>.','pp'), $plugin_data['Name'], admin_url('admin.php?page=pp-settings&pp_tab=install'), 'http://presspermit.com/' . 'purchase/', $details_url, esc_attr($plugin_data['Name']), $version_info->new_version);
					
					set_site_transient('ppc_update_info', false);
				} else 
					$message = sprintf(__ppw('There is a new version of %1$s available. <a href="%2$s" class="thickbox" title="%3$s">View version %4$s details</a> or <a href="%5$s">upgrade automatically</a>.'),$plugin_data['Name'],$details_url,esc_attr($plugin_data['Name']),$version_info->new_version,$update_url);

				echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update"><div class="update-message">'.$message.'</div></td></tr>';

				return;
			}
		}

		if ( ! $activated ) { // No update availableKey not active
			$message = sprintf(__('Activate your <a href="%1$s">support key</a> for Press Permit Pro extensions and support resources.','pp'), admin_url( 'admin.php?page=pp-settings&pp_tab=install' ), 'http://presspermit.com/' . "purchase/" );
			echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update"><div class="update-message">'.$message.'</div></td></tr>';
				
			set_site_transient('ppc_update_info', false);

			return;
		}
	}
	
	
	/**
	 * Loads the change log for an available update
	 *
	 * @author Jonathan Davis
	 * @author Kevin Behrens
	 *
	 * @return void
	 **/
	public static function changelog () {
		global $pp_extensions;
		if ( ! isset( $pp_extensions[$_REQUEST["plugin"]] ) ) return;

		$response = self::callhome( 'changelog-ppc', array( 'plugin' => $_REQUEST["plugin"] ) );

		echo '<html><head>';
		echo '<link rel="stylesheet" href="'.admin_url().'/css/install.css" type="text/css" />';
		echo '<link rel="stylesheet" href="'.PP_URLPATH.'/admin/css/pp.css" type="text/css" />';
		echo '</head>';
		echo '<body id="change_log" class="pp-update">';
		echo $response;
		echo "</body>";
		echo '</html>';
		exit();
	}
}

