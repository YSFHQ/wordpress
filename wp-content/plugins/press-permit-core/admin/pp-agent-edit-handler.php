<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once( dirname(__FILE__).'/groups-update_pp.php' );

//wp_reset_vars(array('action', 'redirect', 'agent_id', 'wp_http_referer'));
$action = ( isset($_REQUEST['action']) ) ? $_REQUEST['action'] : '';

$url = apply_filters( 'pp_groups_base_url', 'admin.php' );
$redirect = $err = false;

//$http_referer = ( isset( $_SERVER['HTTP_REFERER'] ) ) ? $http_referer : '';

if ( empty( $_REQUEST['agent_type'] ) )
	return;

$agent_type = pp_sanitize_key($_REQUEST['agent_type']);

switch( $action ) {
	case 'update' :
		$agent_id = (int) $_REQUEST['agent_id'];
		check_admin_referer( 'pp-update-group_' . $agent_id );
		
		if ( ! pp_has_group_cap( 'pp_edit_groups', $agent_id, $agent_type ) )
			wp_die( __( 'You are not permitted to do that.', 'pp' ) );

		if ( pp_group_type_editable( $agent_type ) ) {
			$group = pp_get_group( $agent_id, $agent_type );
			
			//if ( current_user_can( 'pp_edit_groups' ) )
				$retval = _pp_trigger_group_edit( $agent_id, $agent_type );
			//else
			//	wp_die( __( 'You are not permitted to do that.', 'pp' ) );
		} else {
			$retval = true;
		}

		do_action( 'pp_edited_group', $agent_type, $agent_id );

		if ( ! empty($retval) && ! is_wp_error( $retval ) ) {
			$redirect = "$url?page=pp-edit-permissions&agent_id=$agent_id&agent_type=$agent_type&updated=1";
		}
		break;
		
	case 'pp_updateclone' :
		if ( current_user_can( 'pp_assign_roles' ) && pp_bulk_roles_enabled() ) {
			require_once( dirname(__FILE__).'/permissions-clone_pp.php' );
			PP_Clone::clone_permissions( 'pp_group', (int) $_REQUEST['agent_id'], pp_sanitize_key($_REQUEST['pp_select_role']) );
		}
		
		break;
		
	case 'pp_updateroles' :
		$agent_id = (int) $_REQUEST['agent_id'];
		check_admin_referer( 'pp-update-roles_' . $agent_id, '_pp_nonce_roles' );

		if ( current_user_can( 'pp_assign_roles' ) && pp_bulk_roles_enabled() ) {
			_pp_edit_group_roles($agent_id, $agent_type);
		}

		// cludged update of group members on role selection, due to inability to put them in the same form
		if ( $agent_id && pp_group_type_editable( $agent_type ) && pp_has_group_cap( 'pp_edit_groups', $agent_id, $agent_type ) ) {
			_pp_trigger_group_edit( $agent_id, $agent_type );
		}

		global $current_user;
		update_user_option( $current_user->ID, 'pp-permissions-tab', 'pp-add-roles' );

		$redirect = "$url?page=pp-edit-permissions&agent_id=$agent_id&agent_type=$agent_type&updated=1&pp_roles=1";

		break;
		
	case 'pp_updateexceptions' :
		$agent_id = (int) $_REQUEST['agent_id'];
		check_admin_referer( 'pp-update-exceptions_' . $agent_id, '_pp_nonce_exceptions' );

		if ( current_user_can( 'pp_assign_roles' ) && pp_bulk_roles_enabled() ) {
			_pp_edit_agent_exceptions($agent_id, $agent_type);
		}

		// cludged update of group members on role selection, due to inability to put them in the same form
		if ( pp_group_type_editable( $agent_type ) && pp_has_group_cap( 'pp_edit_groups', $agent_id, $agent_type ) ) {
			_pp_trigger_group_edit( $agent_id, $agent_type );
		}

		global $current_user;
		update_user_option( $current_user->ID, 'pp-permissions-tab', 'pp-add-exceptions' );
		
		$redirect = "$url?page=pp-edit-permissions&agent_id=$agent_id&agent_type=$agent_type&updated=1&pp_exc=1";
			
		break;
		
	case 'creategroup' :
		if ( ! current_user_can( 'pp_create_groups' ) )
			wp_die( __( 'You are not permitted to do that.', 'pp' ) );
		
		check_admin_referer( 'pp-create-group', '_wpnonce_pp-create-group' );
		
		$agent_type = ( isset($_REQUEST['agent_type']) ) ? pp_sanitize_key($_REQUEST['agent_type']) : '';
		if ( ! $agent_type = apply_filters( 'pp_query_group_type', $agent_type ) )
			$agent_type = 'pp_group';

		$retval = _pp_add_group( $agent_type );

		if ( ! is_wp_error( $retval ) ) {
			if ( current_user_can( 'pp_assign_roles' ) && pp_bulk_roles_enabled() )
				_pp_edit_group_roles($retval, $agent_type);

			$type_arg = ( 'pp_group' == $agent_type ) ? '' : "&agent_type=$agent_type";
			$redirect = "$url?page=pp-edit-permissions&action=edit&agent_id=$retval{$type_arg}&created=1";
		}
		break;
		
} // end switch

if ( ! empty($retval) && is_wp_error( $retval ) ) {
	global $pp_admin;
	$pp_admin->errors = $retval;
} elseif ( $redirect ) {
	if ( ! empty( $_REQUEST['wp_http_referer'] ) )
		$redirect = add_query_arg('wp_http_referer', urlencode($_REQUEST['wp_http_referer']), $redirect);
	
	$redirect = add_query_arg('update', 1, $redirect);
	
	wp_redirect($redirect);
	exit;
}

function _pp_trigger_group_edit( $group_id, $agent_type, $members_only = false ) {	
	if ( ! in_array( $agent_type, array( 'pp_group', 'pp_net_group' ) ) )
		return true;
	
	$group = pp_get_group( $group_id, $agent_type );

	if ( isset($group->metagroup_type) && in_array( $group->metagroup_type, array( 'wp_role', 'meta_role' ) ) )
		$retval = true;
	elseif ( $group )
		$retval = _pp_edit_group($group_id, $agent_type, $members_only);
	else
		$retval = false;
	
	return $retval;
}

function _pp_edit_group_roles( $agent_id, $agent_type ) {
	if ( ! current_user_can('pp_assign_roles') || ! pp_bulk_roles_enabled() )
		return;
	
	$type_objs = array();
	
	if ( isset( $_POST['pp_add_role'] ) ) {
		// note: group editing capability already verified at this point
		
		// also support bulk-assignment of user roles
		$agent_ids = ( ( 'user' == $agent_type ) && ! $agent_id && isset($_REQUEST['member_csv']) ) ? explode( ',', $_REQUEST['member_csv'] ) : array($agent_id);

		foreach( $agent_ids as $_agent_id ) {
			if ( $_agent_id ) {
				foreach( $_POST['pp_add_role'] as $add_role ) {
					extract($add_role);

					if ( $attrib_cond )
						$attrib_cond = ':' . $attrib_cond;

					ppc_assign_roles( array( "{$role}{$attrib_cond}" => array( $_agent_id => true ) ), $agent_type );
				}
			}
		}
	}
}

function _pp_edit_agent_exceptions( $agent_id, $agent_type ) {
	if ( ! current_user_can('pp_assign_roles') || ! pp_bulk_roles_enabled() )
		return;
	
	$type_objs = array();
	
	if ( isset( $_POST['pp_add_exception'] ) ) {
		// note: group editing capability already verified at this point
		
		foreach( $_POST['pp_add_exception'] as $exception ) {
			$exception = apply_filters( 'pp_add_exception', $exception );
			
			extract($exception);
			
			$args = compact( 'mod_type', 'item_id', 'operation' );
			$args['for_item_status'] = $attrib_cond;
			
			if ( taxonomy_exists($via_type) ) {
				$args['via_item_source'] = 'term';
				$args['via_item_type'] = $via_type;
				$args['item_id'] = pp_termid_to_ttid( $item_id, $via_type );
			} elseif ( ! $via_type || post_type_exists($via_type) ) {
				$args['via_item_source'] = 'post';
				$args['via_item_type'] = '';
			} else {
				$args['via_item_source'] = $via_type;
				$args['via_item_type'] = '';
			}
			
			if ( taxonomy_exists($for_type) ) {
				$args['for_item_source'] = 'term';
			} elseif ( ! $for_type || post_type_exists($for_type) || ( '(all)' == $for_type ) ) {
				$args['for_item_source'] = 'post';
			} else {
				$args['for_item_source'] = $for_type;
			}

			$args['for_item_type'] = ( '(all)' == $for_type ) ? '' : $for_type;
			
			$agents = array();
			
			// also support bulk-assignment of user exceptions
			$agent_ids = ( ( 'user' == $agent_type ) && ! $agent_id && isset($_REQUEST['member_csv']) ) ? explode( ',', $_REQUEST['member_csv'] ) : array($agent_id);

			foreach( $agent_ids as $_agent_id ) {
				if ( $_agent_id ) {
					foreach( array( 'item' => $for_item, 'children' => $for_children ) as $assign_for => $is_assigned ) {
						if ( $is_assigned )
							$agents[$assign_for][$_agent_id] = true;
					}
				}
			}

			ppc_assign_exceptions( $agents, $agent_type, $args );
		}
	}
}

function _pp_add_group( $agent_type ) {
	return _pp_edit_group( 0, $agent_type );
}

/**
 * Edit group settings based on contents of $_POST
 *
 * @param int $group_id Optional. Group ID.
 * @return int group id of the updated group
 */
function _pp_edit_group( $group_id = 0, $agent_type = 'pp_group', $members_only = false ) {
	global $wpdb;

	if ( $group_id ) {
		$update = true;
		$group = pp_get_group( $group_id, $agent_type );
	} else {
		$update = false;
		$group = (object) array();
	}

	if ( ! $members_only ) {
		if ( isset( $_REQUEST['group_name'] ) )
			$group->group_name = sanitize_text_field( $_REQUEST['group_name'] );

		if ( isset( $_REQUEST['description'] ) )
			$group->group_description = sanitize_text_field( $_REQUEST['description'] );

		$errors = new WP_Error();
		
		/* checking that username has been typed */
		if ( ! $group->group_name )
			$errors->add( 'group_name', __( '<strong>ERROR</strong>: Please enter a group name.', 'pp' ) );

		elseif ( ! $update && ! PP_GroupsUpdate::group_name_available( $group->group_name, $agent_type ) )
			$errors->add( 'user_login', __( '<strong>ERROR</strong>: This group name is already registered. Please choose another one.', 'pp' ) );

		// Allow plugins to return their own errors.
		do_action_ref_array( 'pp_group_profile_update_errors', array ( &$errors, $update, &$group ) );

		if ( $errors->get_error_codes() )
			return $errors;

		if ( $update ) {
			PP_GroupsUpdate::update_group( $group_id, $group, $agent_type );
		} else {
			$group_id = PP_GroupsUpdate::create_group( $group, $agent_type );
		}
	}
	
	if ( $group_id ) {
		$member_types = array();

		if ( pp_has_group_cap( 'pp_manage_members', $group_id, $agent_type ) )
			$member_types []= 'member';
		
		foreach( $member_types as $member_type ) {
			if ( isset($_REQUEST["{$member_type}_csv"]) && ( $_REQUEST["{$member_type}_csv"] != -1 ) ) {  
				// handle member changes
				$current = pp_get_group_members( $group_id, $agent_type, 'id', compact('member_type') );
				$selected = ( isset( $_REQUEST["{$member_type}_csv"] ) ) ? explode(",", pp_sanitize_csv($_REQUEST["{$member_type}_csv"]) ) : array();

				if ( ( 'member' != $member_type ) || ! apply_filters( 'pp_custom_agent_update', false, $agent_type, $group_id, $selected ) ) {
					if ( $add_users = array_diff( $selected, $current ) ) {
						pp_add_group_user( $group_id, $add_users, compact('agent_type', 'member_type') );
					}
					
					if ( $remove_users = array_diff( $current, $selected ) ) {
						pp_remove_group_user( $group_id, $remove_users, compact('agent_type', 'member_type') );
					}
				}
			}
		} // end foreach member_types

		do_action( 'pp_edited_group', $agent_type, $group_id, $update );
	}
	
	return $group_id;
}

