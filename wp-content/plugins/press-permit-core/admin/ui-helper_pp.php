<?php
class PP_UI_Helper {
	public static function handle_screen_options() {
		if ( isset( $_REQUEST['wp_screen_options'] ) ) {
			if ( isset($_REQUEST['wp_screen_options']['option']) && ( 'groups_per_page' == $_REQUEST['wp_screen_options']['option'] ) ) {
				global $current_user;
				update_user_option( $current_user->ID, $_REQUEST['wp_screen_options']['option'], $_REQUEST['wp_screen_options']['value'] );
			}
		}
	}
	
	public static function cred_scripts() {	
		$agent_type = ( isset($_REQUEST['agent_type']) ) ? pp_sanitize_key($_REQUEST['agent_type']) : 'pp_group';
		$agent_id = ( isset($_REQUEST['agent_id']) ) ? (int) $_REQUEST['agent_id'] : 0;
	
		if ( ! pp_has_group_cap( 'pp_manage_members', $agent_id, $agent_type ) && ! _pp_any_group_manager() && ! current_user_can( 'pp_assign_roles' ) && ! pp_bulk_roles_enabled() )
			return array();

		$vars = array( 
			'addRoles' => __('Add Roles', 'pp' ), 
			'clearRole' => __('clear', 'pp'), 
			'noConditions' => __('No statuses selected!', 'pp'),
			'pleaseReview' => __('Review selection(s) below, then click Save.', 'pp'),
			'alreadyRole' => __('Role already selected!', 'pp'),
			'noAction' => __('No Action selected!', 'pp'),
			'submissionMsg' => __('Role submission in progress...', 'pp'),
			'reloadRequired' => __('Reload form for further changes to this role', 'pp'),
			'ajaxurl' => admin_url(''),
		);

		$vars['agentType'] = $agent_type;
		$vars['agentID'] = $agent_id;
		
		$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
		wp_enqueue_script( 'pp_permit_edit', PP_URLPATH . "/admin/js/pp_permit_edit{$suffix}.js", array('jquery', 'jquery-form'), PPC_VERSION );
		wp_localize_script( 'pp_permit_edit', 'ppCred', $vars );
		
		return $vars;
	}
	
	public static function exception_scripts() {	
		if ( ! current_user_can( 'pp_assign_roles' ) && ! pp_bulk_roles_enabled() )
			return array();
	
		$vars = array( 
			'addExceptions' => __('Add Exceptions', 'pp' ), 
			'clearException' => __('clear', 'pp'), 
			'pleaseReview' => __('Review selection(s) below, then click Save.', 'pp'),
			'alreadyException' => __('Exception already selected!', 'pp'),
			'noAction' => __('No Action selected!', 'pp'),
			'submissionMsg' => __('Exception submission in progress...', 'pp'),
			'reloadRequired' => __('Reload form for further changes to this exception', 'pp'),
			'noMode' => __('No Assignment Mode selected!', 'pp'),
			'ajaxurl' => admin_url(''),
		);

		$vars['agentType'] = ( isset($_REQUEST['agent_type']) ) ? pp_sanitize_key($_REQUEST['agent_type']) : 'pp_group';
		$vars['agentID'] = ( isset($_REQUEST['agent_id']) ) ? (int) $_REQUEST['agent_id'] : 0;
		//$vars['agentID'] = ( isset($_REQUEST['group_id']) ) ? $_REQUEST['group_id'] : 0;
		
		// Simulate Nav Menu setup
		require_once( dirname(__FILE__) . '/includes/item-menu_pp.php' );
		
		/*
		if ( ! pp_wp_ver( '3.3' ) ) {
			global $wp_styles;
			$wp_styles->add( 'nav-menu', "/wp-admin/css/nav-menu.css" );
			wp_admin_css( 'nav-menu' );
		}
		*/
		
		$vars['noItems'] = __('No items selected!', 'pp');
		
		$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
		
		
		//wp_enqueue_script( 'nav-menu' );
		wp_enqueue_script( 'pp-item-menu', PP_URLPATH . "/admin/js/pp-item-menu{$suffix}.js", array('jquery', 'jquery-form'), PPC_VERSION );
		wp_localize_script( 'pp-item-menu', 'ppItems', array( 'ajaxurl' => admin_url(''), 'noResultsFound' => __('No results found.', 'pp') ) );
		
		wp_enqueue_script( 'pp_exception_edit', PP_URLPATH . "/admin/js/pp_exception_edit{$suffix}.js", array('jquery', 'jquery-form'), PPC_VERSION );
		wp_localize_script( 'pp_exception_edit', 'ppRestrict', $vars );
		
		wp_enqueue_script( 'common' );
		wp_enqueue_script( 'postbox' );
		
		return $vars;
	}
} // end class
