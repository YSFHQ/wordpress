<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once( dirname(__FILE__).'/pp-permits-helper.php' );

PP_Permits_Handler::handle_request();

class PP_Permits_Handler {
	public static function handle_request() {
		global $pp_admin;

		$url = $referer = $redirect = $update = '';
		PP_Permits_Helper::get_url_properties( $url, $referer, $redirect );

		if ( ! $agent_type = apply_filters( 'pp_query_group_type', '' ) )
			$agent_type = 'pp_group';
		
		if ( ! empty( $_REQUEST['action2'] ) && ! is_numeric($_REQUEST['action2']) )
			$action = $_REQUEST['action2'];
		elseif ( ! empty( $_REQUEST['action'] ) && ! is_numeric($_REQUEST['action']) )
			$action = $_REQUEST['action'];
		elseif ( ! empty( $_REQUEST['pp_action'] ) )
			$action = $_REQUEST['pp_action'];
		else
			$action = '';
			
		switch ( $action ) {
		
		case 'dodelete':
			check_admin_referer('delete-groups');

			if ( ! current_user_can( 'pp_delete_groups' ) )
				wp_die( __( 'You are not permitted to do that.', 'pp' ) );
			
			if ( empty($_REQUEST['groups']) && empty($_REQUEST['group']) ) {
				wp_redirect($redirect);
				exit();
			}

			if ( empty($_REQUEST['groups']) )
				$groupids = array(intval($_REQUEST['group']));
			else
				$groupids = (array) $_REQUEST['groups'];
			
			$update = 'del';
			$delete_ids = array();

			foreach ( (array) $groupids as $id) {
				$id = (int) $id;

				if ( $group_obj = pp_get_group($id, $agent_type) ) {
					if ( ! empty($group->obj->metagroup_id) )
						continue;
				}
				
				//if ( ! current_user_can( 'pp_delete_groups', $id ) )
				//	continue;

				pp_delete_group( $id, $agent_type );
				$delete_ids[]= $id;
			}

			if ( ! $delete_ids )
				wp_die( __( 'You can&#8217;t delete that group.', 'pp' ) );

			$redirect = add_query_arg( array('delete_count' => count($delete_ids), 'update' => $update), $redirect);
			wp_redirect($redirect);
			exit();

			break;

		case 'delete' :
			check_admin_referer('bulk-groups');

			if ( ! current_user_can( 'pp_delete_groups' ) )
				wp_die( __( 'You are not permitted to do that.', 'pp' ) );
			
			if ( ! empty($_REQUEST['groups']) ) {
				$redirect = add_query_arg( array('pp_action' => 'bulkdelete', 'agent_type' => $agent_type, 'wp_http_referer' => isset($_REQUEST['wp_http_referer']) ? $_REQUEST['wp_http_referer'] : '', 'groups' => $_REQUEST['groups']), $redirect);
				wp_redirect($redirect);
				exit();
			}
			
			if ( empty($_REQUEST['group']) ) { // && empty($_REQUEST['user']) ) {
				wp_redirect($redirect);
				exit();
			}
			
			break;
			
		default:
		} // end switch
	}
}
