<?php
// menu icons by Jonas Rask: http://www.jonasraskdesign.com/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define ( 'PP_URLPATH', plugins_url( '', PPC_FILE ) );

class PP_AdminUI
{
	var $errors;
	
	function __construct() {
		global $pagenow, $pp_plugin_page;
		
		do_action( '_pp_admin_ui' );

		// ============== UI-related filters ================
		add_action( 'admin_menu', array( &$this, 'build_menu') );
		add_action( 'show_user_profile', array( &$this, 'ui_user'), 2 );
		add_action( 'edit_user_profile', array( &$this, 'ui_user'), 2 );
		add_action( 'admin_print_scripts-user-new.php', array( &$this, 'insert_groups_ui') );
		add_filter( 'editable_roles', array( &$this, 'flt_hide_pp_only_roles' ) );

		add_action( 'admin_menu', array( &$this, 'ngg_uploader_workaround') );
		
		$is_post_admin = false;
		
		if ( array_intersect( array( $pagenow, $pp_plugin_page ), array('post-new.php', 'post.php') ) ) {
			global $pp_post_edit_ui;
			require_once( dirname(__FILE__).'/post-edit-ui_pp.php' );
			$pp_post_edit_ui = new PP_PostEditUI();
			$is_post_admin = true;

		} elseif ( ( 'edit-tags.php' == $pagenow ) && ! empty($_REQUEST['action']) && ( 'edit' ==  $_REQUEST['action'] ) ) {
			if ( current_user_can('pp_assign_roles') ) {
				global $pp_term_edit_ui;
				require_once( dirname(__FILE__).'/term-edit-ui_pp.php' );
				$pp_term_edit_ui = new PP_TermEditUI();
			}
		}

		if ( pp_is_user_administrator() || ( 0 === strpos( $pp_plugin_page, 'pp-' ) ) )
			add_action('in_admin_footer', array(&$this, 'ui_admin_footer') );
		
		if ( 'users.php' == $pagenow ) {
			require_once( dirname(__FILE__).'/users-ui_pp.php' );

		} elseif ( ( 'edit.php' == $pagenow ) || pp_is_ajax('inline-save') ) {
			$post_type = isset( $_REQUEST['post_type'] ) ? pp_sanitize_key($_REQUEST['post_type']) : 'post';
			if ( in_array( $post_type,  pp_get_enabled_post_types() ) ) {
				global $pp_post_listing_ui;
				require_once( dirname(__FILE__).'/post-listing-ui_pp.php' );
				$pp_post_listing_ui = new PP_PostsAdmin();
				$is_post_admin = true;
			}
		
		} elseif ( in_array( $pagenow, array( 'edit-tags.php' ) ) || ( defined('DOING_AJAX') && DOING_AJAX && in_array( $_REQUEST['action'], array( 'inline-save-tax', 'add-tag' ) ) ) ) {
			if ( ! empty( $_REQUEST['taxonomy'] ) && pp_is_taxonomy_enabled( $_REQUEST['taxonomy'] ) ) {
				global $pp_admin_terms_listing;
				require_once( dirname(__FILE__).'/term-listing-ui_pp.php' );
				$pp_admin_terms_listing = new PP_TermsAdmin();
			}

		} elseif ( in_array( $pagenow, array( 'plugins.php', 'plugin-install.php' ) ) ) {
			global $pp_plugin_admin;
			require_once( dirname(__FILE__).'/admin-plugins_pp.php' );
			$pp_plugin_admin = new PP_Plugin_Admin();
	
		} else {
			if ( strpos( $_SERVER['REQUEST_URI'], 'page=pp-groups' ) && isset( $_REQUEST['wp_screen_options'] ) ) {
				require_once( dirname(__FILE__).'/ui-helper_pp.php' );
				PP_UI_Helper::handle_screen_options();
			}
			
			if ( in_array( $pp_plugin_page, array( 'pp-edit-permissions' ) ) ) {  // pp-group-new
				add_action( 'admin_head', array(&$this, 'load_scripts' ) );

			} elseif ( in_array( $pp_plugin_page, array( 'pp-settings', 'pp-about' ) ) ) {
				wp_enqueue_style( 'plugin-install' );
				wp_enqueue_script( 'plugin-install' );
				add_thickbox();
			}
		}
		
		if ( $is_post_admin )
			do_action( 'pp_post_admin' );
		
		add_action( 'admin_head', array(&$this, 'admin_head') );
		
		wp_enqueue_style( 'pp', PP_URLPATH . '/admin/css/pp.css', array(), PPC_VERSION );
		
		if ( 0 === strpos( $pp_plugin_page, 'pp-' ) )
			wp_enqueue_style( 'pp-plugin-pages', PP_URLPATH . '/admin/css/pp-plugin-pages.css', array(), PPC_VERSION );
		
		if ( in_array( $pagenow, array( 'user-edit.php', 'user-new.php', 'profile.php' ) ) ) {
			wp_enqueue_style( 'pp-edit-permissions', PP_URLPATH . '/admin/css/pp-edit-permissions.css', array(), PPC_VERSION );
			wp_enqueue_style( 'pp-groups-checklist', PP_URLPATH . '/admin/css/pp-groups-checklist.css', array(), PPC_VERSION );
			
			if ( ! pp_wp_ver('3.8') )
				wp_enqueue_style( 'pp-edit-perm-legacy', PP_URLPATH . '/admin/css/pp-edit-permissions-legacy.css', array(), PPC_VERSION );

		} elseif ( in_array( $pp_plugin_page, array( 'pp-edit-permissions', 'pp-group-new' ) ) ) {
			wp_enqueue_style( 'pp-edit-permissions', PP_URLPATH . '/admin/css/pp-edit-permissions.css', array(), PPC_VERSION );
			wp_enqueue_style( 'pp-groups-checklist', PP_URLPATH . '/admin/css/pp-groups-checklist.css', array(), PPC_VERSION );
			
			if ( ! pp_wp_ver('3.8') )
				wp_enqueue_style( 'pp-edit-perm-legacy', PP_URLPATH . '/admin/css/pp-edit-permissions-legacy.css', array(), PPC_VERSION );

		} elseif ( 'pp-settings' == $pp_plugin_page ) {
			wp_enqueue_style( 'pp-settings', PP_URLPATH . '/admin/css/pp-settings.css', array(), PPC_VERSION );
			
		} elseif ( 'pp-about' == $pp_plugin_page ) {
			wp_enqueue_style( 'pp-about', PP_URLPATH . '/admin/css/pp-about.css', array(), PPC_VERSION );
		}
		
		global $pagenow;

		if ( in_array( $pagenow, array( 'edit.php', 'post.php' ) ) && pp_wp_ver( '3.5-beta' ) ) {
			add_action( 'admin_menu', array( &$this, 'reinstate_solo_submenus' ) );
			add_action( 'network_admin_menu', array( &$this, 'reinstate_solo_submenus' ) );
		}
		
		do_action( 'pp_admin_ui' );
	}
	
	function load_scripts() {
		require_once( dirname(__FILE__).'/ui-helper_pp.php' );
		PP_UI_Helper::cred_scripts();
		PP_UI_Helper::exception_scripts();
	}

	function reinstate_solo_submenus() {
		global $submenu;
		
		// Add a dummy submenu item to prevent WP from stripping out solitary submenus.  Otherwise menu access loses type sensitivity and requires "edit_posts" cap for all types.
		foreach( $submenu as $key => $data ) {
			if ( 1 == count( $submenu[$key] ) && ( 0 === strpos( $key, 'edit.php' ) ) ) {
				$submenu[$key][999] =  array( '', 'read', $key );
			}
		}
	}
	
	function menu_handler() {
		$pp_page = pp_sanitize_key($_GET['page']);
		
		if ( in_array( $pp_page, array( 'pp-settings', 'pp-groups', 'pp-users', 'pp-edit-permit', 'pp-edit-permissions', 'pp-group-new', 'pp-about', 'pp-attachments_utility' ) ) ) {
			include_once( PPC_ABSPATH . "/admin/{$pp_page}.php" );
			
			if ( 'pp-settings' == $pp_page )
				pp_options();
		}
		
		do_action( 'pp_menu_handler', $pp_page );
	}
	
	function admin_head() {
		global $pagenow, $pp_plugin_page;

		if ( empty($_REQUEST['noheader']) ) {
			global $wp_scripts;
			$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
			wp_enqueue_script( 'pp_misc', PP_URLPATH . "/admin/js/pp{$suffix}.js", array('jquery'), PPC_VERSION, true );
			$wp_scripts->in_footer []= 'pp_misc'; // otherwise it will not be printed in footer, as of WP 3.2.1
		}

		if ( in_array( $pagenow, array( 'post.php', 'post-new.php', 'edit.php', 'users.php', 'upload.php', 'edit-tags.php' ) ) || ! empty($pp_plugin_page) ) {
			require_once( dirname(__FILE__).'/admin-help_pp.php' );
			PP_AdminHelp::register_contextual_help();
		}
		
		if ( ( 'upload.php' == $pagenow ) && ( ! defined( 'PPFF_VERSION' ) || version_compare( PPFF_VERSION, '2.0-beta', '<' ) ) && current_user_can( 'pp_manage_settings' ) && pp_get_option('display_extension_hints') ) {
			require_once( dirname(__FILE__).'/media-ui-hints_pp.php');
			_pp_file_filtering_promo();
		}
		
		if ( 'pp-groups' == $pp_plugin_page ) {
			add_screen_option( 'per_page', array( 'label' => _x( 'Groups', 'groups per page (screen options)', 'pp' ), 'default' => 20, 'option' => 'groups_per_page' ) );
		}

		if ( ! empty($_REQUEST['page']) && strpos( $_SERVER['REQUEST_URI'], 'page=pp-groups' ) ) {
			global $pp_groups_list_table;
			require_once( dirname(__FILE__).'/includes/class-pp-groups-list-table.php' );
			$pp_groups_list_table = new PP_Groups_List_Table();
		}
		
		if ( ( 'user-edit.php' == $pagenow ) && pp_get_option('display_user_profile_groups') )
			add_thickbox();
	}

	function get_menu( $for ) {
		if  ( ( defined( 'OZH_MENU_VER' ) && ! defined( 'PP_FORCE_PLUGIN_MENU' ) ) || defined( 'PP_FORCE_USERS_MENU' ) ) {
			$arr = array( 'permits' => 'users.php', 'options' => 'options-general.php' );
		} else {
			$arr = array( 'permits' => 'pp-groups', 'options' => 'pp-groups' );
		}
		
		if ( isset($arr[$for]) )
			return $arr[$for];
	}
	
	function build_menu() {
		if ( strpos( $_SERVER['REQUEST_URI'], 'wp-admin/network/' ) )
			return;

		$do_groups = current_user_can('pp_edit_groups') || _pp_any_group_manager();
		$do_settings = current_user_can( 'pp_manage_settings' );
		
		if ( ! $do_groups && ! $do_settings )
			return;

		$pp_cred_menu = $this->get_menu( 'permits' );
		$pp_options_menu = $this->get_menu( 'options' );

		if ( 'pp-groups' == $pp_cred_menu )  {
			//  Manually set menu indexes for positioning below Users menu
			global $menu;
			$pp_cred_key = ( ! defined( 'PP_DISABLE_MENU_TWEAK' ) && isset( $menu[70] ) && $menu[70][2] == 'users.php' && ! isset( $menu[72] ) ) ? 72 : null;
			add_menu_page( __('Permissions', 'pp'), __('Permissions', 'pp'), 'read', $pp_cred_menu, array(&$this, 'menu_handler'), PP_URLPATH . '/admin/images/menu/users-c.png', $pp_cred_key );
		}

		$handler = array( &$this, 'menu_handler' );
		
		if ( $do_groups ) {
			add_submenu_page($pp_cred_menu, __('Groups', 'pp'), __('Groups', 'pp'), 'read', 'pp-groups', $handler );
			
			if ( current_user_can( 'pp_create_groups' ) )
				add_submenu_page($pp_cred_menu, __('Add New Permission Group'), '- ' . __ppw('Add New'), 'read', 'pp-group-new', $handler );
		}
		
		if ( current_user_can( 'list_users' ) && current_user_can( 'pp_administer_content' ) ) {
			add_submenu_page($pp_cred_menu, __('Users', 'pp'), __('Users', 'pp'), 'read', 'pp-users', $handler );
		}
		
		if ( $do_settings ) {
			do_action( 'pp_permissions_menu', $pp_options_menu, $handler );

			add_submenu_page($pp_options_menu, __('Settings', 'pp'), __('Settings', 'pp'), 'read', 'pp-settings', $handler );
		}

		// satisfy WordPress' demand that all admin links be properly defined in menu
		global $pp_plugin_page;
		if ( in_array( $pp_plugin_page, array( 'pp-edit-permissions', 'pp-attachments_utility' ) ) ) {
			$titles = array( 'pp-edit-permissions' => __('Edit Permissions', 'pp') );
			add_submenu_page( $pp_cred_menu, $titles[$pp_plugin_page], '', 'read', $pp_plugin_page, $handler );
		}

		do_action( 'pp_admin_menu' );
		
		if ( ( 'pp-groups' == $pp_cred_menu ) && $do_settings ) {
			add_submenu_page( $pp_cred_menu, __('About Press Permit', 'pp'), __('About', 'pp'), 'read', 'pp-about', array( &$this, 'menu_handler' ) );
		}
	}
	
	function ui_user() {
		global $profileuser, $pp_current_user;
		$pp_profile_user = ( $profileuser->ID == $pp_current_user->ID ) ? $pp_current_user : new PP_User($profileuser->ID);
		
		$is_administrator = pp_is_user_administrator() && pp_bulk_roles_enabled() && current_user_can('list_users');
		
		if ( $is_administrator || pp_get_option( 'display_user_profile_roles' ) || pp_get_option( 'display_user_profile_groups' ) ) {
			require_once( dirname(__FILE__).'/profile_ui_pp.php' );
			require_once( dirname(__FILE__).'/permissions-ui_pp.php' );
		}
	
		if ( $is_administrator || pp_get_option( 'display_user_profile_roles' ) ) {
			PP_ProfileUI::display_ui_user_assigned_roles($pp_profile_user);
		}
		
		if ( $is_administrator || pp_get_option( 'display_user_profile_groups' ) ) {
			PP_ProfileUI::display_ui_user_groups();
		}
		
		if ( $is_administrator || pp_get_option( 'display_user_profile_roles' ) ) {
			PP_ProfileUI::display_ui_user_roles($pp_profile_user);
		}
	}
	
	function insert_groups_ui() {
		if ( PP_MULTISITE || ! pp_get_option( 'new_user_groups_ui' ) )
			return;
	
		$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
		wp_enqueue_script( 'pp-new-user', PP_URLPATH . "/admin/js/pp_new_user{$suffix}.js", array(), PPC_VERSION );
		wp_localize_script( 'pp-new-user', 'ppUser', array( 'ajaxurl' => admin_url('') ) );
	}
	
	function flt_hide_pp_only_roles( $roles ) {
		if ( $pp_only = (array) pp_get_option( 'supplemental_role_defs' ) ) {
			global $pp_role_defs;
			
			if ( ! empty($_REQUEST['user_id']) ) {	// display role already set for this user, regardless of pp_only setting
				$user = new WP_User( (int) $_REQUEST['user_id'] );
				if ( ! empty($user->roles) )
					$pp_only = array_diff( $pp_only, $user->roles );
			}
		
			$roles = array_diff_key( $roles, array_fill_keys( $pp_only, true ) );
		}
		return $roles;
	}
	
	function ui_admin_footer() {
		if ( (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'msie 7') ) )
			echo '<span style="float:right; margin-left: 2em"><a href="http://presspermit.com/">' . __('Press Permit', 'pp') . '</a> ' . PPC_VERSION . ' | ' . '<a href="http://presspermit.com/forums/">' . __ppw('Support Forums', 'pp') . '</a>&nbsp;</span>';
	}
	
	// support NextGenGallery uploader and other custom jquery calls which WP treats as index.php ( otherwise user_can_access_admin_page() fails )
	function ngg_uploader_workaround() {
		global $pagenow;
		
		$site_url = parse_url( get_option( 'siteurl' ) );
		if ( isset($site_url['path']) && $_SERVER['REQUEST_URI'] == $site_url['path'] . '/wp-admin/' )
			return;

		if ( ( 'index.php' == $pagenow ) && strpos( $_SERVER['REQUEST_URI'], '.php' ) && ! strpos( $_SERVER['REQUEST_URI'], 'index.php' ) )  //  strpos( $_SERVER['REQUEST_URI'], 'admin/upload.php' )
			$pagenow = '';
	}
	
} // end class PP_Admin

function pp_init_agents_ui() {
	global $pp_agents_ui;

	if ( ! isset( $pp_agents_ui ) ) {
		require_once( dirname(__FILE__).'/agents_ui_pp.php');
		$pp_agents_ui = new PP_AgentsUI();
	}
	
	return $pp_agents_ui;
}

function pp_icon() {
	echo '<div style="float:left; margin: 5px 10px 0 0"><img src="' . PP_URLPATH . '/admin/images/pp-logo-64.png" alt="" /></div>';
}

function ppc_count_assigned_exceptions( $agent_type, $args = array() ) {
	require_once( dirname(__FILE__).'/admin-ui-roles_pp.php');
	return _ppc_count_assigned_exceptions( $agent_type, $args );
}

function ppc_count_assigned_roles( $agent_type, $args = array() ) {
	require_once( dirname(__FILE__).'/admin-ui-roles_pp.php');
	return _ppc_count_assigned_roles( $agent_type, $args );
}

function ppc_list_agent_exceptions( $agent_type, $id, $args = array() ) {
	require_once( dirname(__FILE__).'/admin-ui-roles_pp.php');
	return _ppc_list_agent_exceptions( $agent_type, $id, $args );
}

function pp_key_status() {
	$opt_val = pp_get_option( 'support_key' );
	
	if ( is_array($opt_val) && count($opt_val) >= 2 ) {
		if ( 1 == $opt_val[0] )
			return true;
		elseif ( -1 == $opt_val[0] )
			return 'expired';
	}
	
	return false;
}
