<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Loads content definitions and adds most PP filters, 
 * after user is set and 'init' action fires
 * 
 * @package PP
 * @author Kevin Behrens <kevin@agapetry.net>
 * @copyright Copyright (c) 2011-2013, Agapetry Creations LLC
 * 
 */
class PP
{
	// === application status / memcache ===
	var $direct_file_access = false;
	var $listed_ids = array();  		// $listed_ids[object_type][object_id] = true : general purpose memory cache; primary use is with has_cap filter to avoid a separate db query for each listed item 
	var $teaser_post_types = array();
	var $filtering_enabled = true;
	
	function __construct() {	
		global $pagenow;
	
		$this->load_config();
		
		// determine if query filtering has been disabled by option storage or API
		global $pp_plugin_page;
		$no_filter_uris = apply_filters( 'pp_nofilter_uris', array() );
		if ( defined('DOING_CRON') || ( pp_unfiltered() && ! is_user_logged_in() ) || in_array( $pagenow, $no_filter_uris ) || in_array( $pp_plugin_page, (array) $no_filter_uris ) || ( defined('PP_DISABLE_QUERYFILTERS') && PP_DISABLE_QUERYFILTERS ) )
			$this->filtering_enabled = false;
		
		// legacy extension API (todo: check property instead)
		if ( ! defined( 'PP_ENABLE_QUERYFILTERS' ) )
			define( 'PP_ENABLE_QUERYFILTERS', $this->filtering_enabled );

		if ( ! $this->direct_file_access = isset($_REQUEST['pp_rewrite']) && ! empty($_REQUEST['attachment']) )
			$this->add_main_filters();

		// no further filtering on plugin-install.php, plugin-editor.php unless it's for one of our own
		if ( is_admin() && $this->plugin_page_bail() )
			return;

		// configuration / filter additions which depend on whether the current user is an Administrator
		$this->load_user_config();

		if ( is_admin() && ( 'async-upload.php' != $pagenow ) && ! defined('XMLRPC_REQUEST') ) {
			// filters which are only needed for the wp-admin UI
			global $pp_admin;
			require_once( dirname(__FILE__).'/admin/admin-ui_pp.php');
			$pp_admin = new PP_AdminUI();
		}

		add_filter( 'the_posts', array(&$this, 'flt_posts_listing'), 50 );
		
		do_action( 'pp_init' );
	}
	
	function load_config() {
		global $pp_role_defs;

		do_action( 'pp_load_config' );

		$pp_role_defs->define_roles();

		do_action( 'pp_apply_config_options' );
		do_action( 'pp_config_loaded' );
	}

	// configuration / filter addition which depends on whether the current user is an Administrator
	function load_user_config() {
		// ===== Query Filters to limit/enable the current user
		if ( $this->filtering_enabled ) {
			global $pagenow;
			
			$is_unfiltered = pp_unfiltered();
			$is_administrator = pp_is_content_administrator();
	
			// even users who are unfiltered in terms of their own access will normally have some of these filters applied to force inclusion of readable private posts in get_pages() listing, post counts, etc.
			if ( $is_front = pp_is_front() )
				$front_filtering = ! $is_unfiltered || ! defined( 'PP_ALLOW_UNFILTERED_FRONT' );

			// (also use content filters on front end to FILTER IN private content which WP inappropriately hides from administrators)
			if ( ( $is_front && $front_filtering ) || ( ! $is_unfiltered || ( 'nav-menus.php' == $pagenow ) ) ) {
				global $query_interceptor;
				if ( ! isset( $query_interceptor ) ) { // since this could possibly fire on multiple 'set_current_user' calls, avoid redundancy
					require_once( dirname(__FILE__).'/query-interceptor_pp.php');
					$query_interceptor = new PP_QueryInterceptor( array( 'direct_file_access' => $this->direct_file_access ) );
				}
			}

			if ( $is_front && $front_filtering ) {
				global $pp_qry_int_front;
				require_once( dirname(__FILE__).'/query-interceptor-front_pp.php');
				$pp_qry_int_front = new PP_QueryInterceptorFront();
				
				require_once( dirname(__FILE__).'/front_pp.php');

				if ( $is_unfiltered && $is_administrator )
					require_once( dirname(__FILE__).'/comments-int-administrator_pp.php' );
			}

			if ( ! $is_unfiltered ) {
				global $cap_interceptor;
				if ( ! isset( $cap_interceptor ) ) {
					require_once( dirname(__FILE__).'/cap-interceptor_pp.php');
					$cap_interceptor = new PP_CapInterceptor();
				}
				
				require_once( dirname(__FILE__).'/comments-interceptor_pp.php' );
			}

			if ( ( $is_front && $front_filtering ) || ( ! $is_unfiltered && ( ! defined( 'DOING_AUTOSAVE' ) || ! DOING_AUTOSAVE ) ) ) {
				pp_init_terms_interceptor();
			} elseif ( is_admin() && $is_unfiltered ) {
				require_once( dirname(__FILE__).'/admin/terms-interceptor-administrator_pp.php');  // for filtering of post count
			}

			// ported or low-level query filters to work around limitations in WP core API
			if ( ! $this->direct_file_access && ( ! $is_front || $front_filtering ) && ( ! defined('XMLRPC_REQUEST') || ! $is_administrator ) ) {  // don't add for direct file access or administrator XML-RPC
				//add_filter( 'query', array( &$this, 'get_pages_query_watch' ), 50, 1 );
				add_filter( 'get_pages', array( &$this, 'flt_get_pages' ), 1, 2 );
			}
		}
	}
	
	function flt_get_pages( $pages, $args ) {
		if ( ! isset($args['post_type']) )
			return $pages;
	
		require_once( dirname(__FILE__).'/hardway/hardway_pp.php' );
		return PP_Hardway::flt_get_pages( $pages, $args );
	}
	
	function add_main_filters() {
		include_once( dirname(__FILE__).'/hardway/wp-patches_agp.php' );
		
		// ===== Filters which support automated role maintenance following content creation/update
		if ( ! pp_is_front() || ! defined('PP_NO_FRONTEND_ADMIN') ) {	// advanced users can save some memory if no content/users will be edited via front end
			global $pp_admin_filters;
			require_once( dirname(__FILE__).'/admin/filters-maint_pp.php' );
			$pp_admin_filters = new PP_AdminFilters();
			do_action( 'pp_maint_filters' );
		}
	}
	
	function get_role_caps( $role_name ) {
		pp_init_cap_caster();
		
		global $wp_roles, $pp_cap_caster, $pp_role_defs;

		if ( isset( $pp_cap_caster->typecast_role_caps[$role_name] ) )
			return $pp_cap_caster->typecast_role_caps[$role_name];
		elseif ( strpos( $role_name, ':' ) ) {
			$arr_name = explode( ':', $role_name );
			if ( ! empty($arr_name[2]) ) {
				$pp_cap_caster->typecast_role_caps[$role_name] = $pp_cap_caster->get_typecast_caps( $role_name );
				return $pp_cap_caster->typecast_role_caps[$role_name];
			}
		} //elseif ( isset( $pp_role_caps[$role_name] ) )
			//return $pp_role_caps[$role_name];
		elseif ( isset( $wp_roles->role_objects[$role_name] ) )
			return array_keys( $wp_roles->role_objects[$role_name]->capabilities );
		elseif( isset( $pp_role_defs->dynamic_role_caps[$role_name] ) )
			return $pp_cap_caster->dynamic_role_caps[$role_name];
		else
			return apply_filters( 'pp_role_caps', array(), $role_name );
	}
	
	function flt_posts_listing( $results ) {
		$default_type = pp_find_post_type();

		// buffer all IDs in the results set					
		foreach ( $results as $row ) {
			$post_type = ( 'revision' == $row->post_type ) ? $default_type : $row->post_type;
			if ( ! isset($this->listed_ids[$post_type]) ) $this->listed_ids[$post_type] = array();
			$this->listed_ids[$post_type][$row->ID] = true;
		}

		return $results;
	}
	
	function set_listed_ids( $object_type, $ids ) {
		$this->listed_ids[$object_type] = $ids;
	}
	
	private function plugin_page_bail() {
		$bail = false;
		global $pp_extensions, $pagenow;

		if ( pp_is_plugin_admin() && isset($_REQUEST['plugin']) && ( 'press-permit-core' != $_REQUEST['plugin'] ) ) {
			$update_info = pp_get_all_updates_info();
			$bail = empty( $pp_extensions[ $_REQUEST['plugin'] ] ) && empty($update_info[ $_REQUEST['plugin'] ] );
		}
		
		if ( 'update.php' == $pagenow ) {
			if ( ! isset($update_info) ) { $update_info = pp_get_all_updates_info(); }
			$bail = empty($_REQUEST['action']) || ( ( 'press-permit-core' != $_REQUEST['action'] || ! ppcore_update_enabled() ) && empty( $pp_extensions[ $_REQUEST['action'] ] ) && empty($update_info[ $_REQUEST['action'] ] ) );
		}
		
		if ( $bail )
			do_action( 'pp_init' );
		
		return $bail;
	}
} // end class PP
