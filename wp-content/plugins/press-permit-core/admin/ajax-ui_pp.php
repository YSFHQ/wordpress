<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( empty($_GET['pp_src_name']) || empty($_GET['pp_object_type']) )
	exit;

if ( ! pp_bulk_roles_enabled() )
	exit;
	
$for_item_source = pp_sanitize_key($_GET['pp_src_name']);
$for_item_type = pp_sanitize_key($_GET['pp_object_type']);
$role_name = ( isset($_GET['pp_role_name']) ) ? pp_sanitize_csv($_GET['pp_role_name']) : '';

if ( $force_vars = apply_filters( 'pp_ajax_role_ui_vars', array(), compact( 'for_item_source', 'for_item_type', 'role_name' ) ) )
	extract( $force_vars );

$html = '';

switch ( $_GET['pp_ajax_ui'] ) {

case 'get_role_options':
	if ( ! is_user_logged_in() ) { echo '<option>' . __('(login timed out)', 'pp') . '</option>'; exit; }

	global $pp_admin, $wp_roles, $pp_role_defs;

	//$is_tx_management = ( 'term' == $for_item_source );
	
	if ( $roles = _pp_get_type_roles( $for_item_source, $for_item_type ) ) {
		foreach ( $roles as $_role_name => $role_title ) {
			if ( pp_user_can_admin_role( $_role_name, $for_item_type ) ) {
				$selected = ( $_role_name == $role_name ) ? "selected='selected'" : '';
				$html .= "<option value='$_role_name' $selected>$role_title</option>";
			}
		}
	} else {
		$caption = __( '(invalid role definition)', 'pp' );
		$html .= "<option value='' $selected>$caption</option>";
	}
	break;

case 'get_conditions_ui':
	if ( ! is_user_logged_in() ) { echo '<p>' . __('(login timed out)', 'pp') . '</p><div class="pp-checkbox"><input type="checkbox" name="pp_select_for_item" style="display:none"><input type="checkbox" name="pp_select_for_item" style="display:none"></div>'; exit; }

	global $pp_role_defs;
	
	$checked = ( ! empty($pp_role_defs->direct_roles[$role_name]) ) ? ' checked="checked"' : '';
	
	$standard_stati_ui = '<p class="pp-checkbox">'
			. '<input type="checkbox" id="pp_select_cond_" name="pp_select_cond[]" value=""' . $checked . ' /> '
			. '<label id="lbl_pp_select_cond_" for="pp_select_cond_">' . __('standard statuses', 'pp') . '</label>'
			. '</p>';
	
	if ( ( 'post' != $for_item_source ) || ( 'attachment' == $for_item_type ) ) {
		$html = $standard_stati_ui;

	} elseif ( $role_name ) {
		global $pp;
		$type_obj = pp_get_type_object( $for_item_source, $for_item_type );
		$type_caps = $pp->get_role_caps($role_name);

		$direct_assignment = ( false === strpos( $role_name, ':' ) );
		
		if ( ! empty( $type_caps[ 'edit_posts' ] ) || $direct_assignment ) {
			$html = $standard_stati_ui;
		} else
			$html = '';

		// edit_private, delete_private caps are normally cast from pattern role
		if ( isset( $type_caps[ 'read' ] ) && ( empty( $type_caps[ 'edit_posts' ] ) || $direct_assignment )
		//	|| isset( $type_caps['edit_published_posts'] ) && ! isset( $type_caps[ 'edit_private_posts' ] ) 
		) {
			$pvt_obj = get_post_status_object('private');
		
			$html .= '<p class="pp-checkbox pp_select_private_status">'
			. '<input type="checkbox" id="pp_select_cond_post_status_private" name="pp_select_cond[]" value="post_status:private" /><label for="pp_select_cond_post_status_private"> ' . sprintf( __('%s Visibility', 'pp'), $pvt_obj->label ) . '</label>'
			. '</p>';
		}
		
		if ( $direct_assignment ) {
			continue;
		}

		$html = apply_filters( 'pp_permission_status_ui', $html, $for_item_type, $type_caps, $role_name );
	}

	break;
	
} // end switch

if ( $html )
	echo $html;

function _pp_get_type_roles( $for_item_source, $for_item_type, $require_caps = false ) {
	global $pp_role_defs;
	
	$type_roles = array();
	
	if ( empty( $pp_role_defs->disabled_pattern_role_types[$for_item_type] ) && post_type_exists($for_item_type) ) {
		if ( $require_caps ) {
			$pp_cap_caster = pp_init_cap_caster();
			if ( empty( $pp_cap_caster->pattern_role_type_caps ) )
				$pp_cap_caster->define_pattern_caps();
			
			$check_prop = ( taxonomy_exists($for_item_type) ) ? $pp_cap_caster->pattern_role_taxonomy_caps : $pp_cap_caster->pattern_role_type_caps;
		} else
			$check_prop = array();
			
		foreach( $pp_role_defs->pattern_roles as $role_name => $pattern_role ) {
			if ( ! $check_prop || ! empty( $check_prop[$role_name] ) )
				$type_roles["{$role_name}:{$for_item_source}:{$for_item_type}"] = $pattern_role->labels->singular_name;
		}
	}
	
	// Consider WP roles which are enabled for direct supplemental assignment, 
	// but only if they have at least one type-defined capability and are not disabled for the object type
	static $wp_type_roles = array();
	if ( ! isset( $wp_type_roles[$for_item_source] ) || ! isset( $wp_type_roles[$for_item_source][$for_item_type] ) ) {
		
		$wp_type_roles[$for_item_source][$for_item_type] = array();
		
		if ( $pp_role_defs->direct_roles ) {
			global $wp_roles;
			
			if ( '-1' === $for_item_type ) {
				foreach( array_keys($pp_role_defs->direct_roles) as $role_name ) {
					$wp_type_roles[$for_item_source][$for_item_type][$role_name] = $pp_role_defs->direct_roles[$role_name]->labels->singular_name;
				}
			
			} elseif ( $type_obj = pp_get_type_object( $for_item_source, $for_item_type ) ) {
				$type_caps = (array) $type_obj->cap;

				$check_type_caps = array_diff_key( array_fill_keys( $type_caps, true ), array( 'read' => true ) );
				
				$cap_caster = pp_init_cap_caster();
				$cap_caster->define_pattern_caps();

				foreach( array_keys($pp_role_defs->direct_roles) as $role_name ) {
					if ( array_intersect_key( $check_type_caps, $wp_roles->role_objects[$role_name]->capabilities ) || ! empty($cap_caster->pattern_role_arbitrary_caps[$role_name])  ) {
						$wp_type_roles[$for_item_source][$for_item_type][$role_name] = $pp_role_defs->direct_roles[$role_name]->labels->singular_name;
					}
				}
			}
		}
	}
	
	$type_roles = array_merge( $type_roles, $wp_type_roles[$for_item_source][$for_item_type] );
	
	return apply_filters( 'pp_get_type_roles', $type_roles, $for_item_source, $for_item_type );
}
