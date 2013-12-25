<?php
if ( empty($_GET['pp_for_type']) )
	exit;

if ( ! pp_bulk_roles_enabled() )
	exit;

$agent_type = pp_sanitize_key($_GET['pp_agent_type']);
$agent_id = (int) $_GET['pp_agent_id'];

$for_type = pp_sanitize_csv($_GET['pp_for_type']);
$operation = ( isset($_GET['pp_operation']) ) ? pp_sanitize_key($_GET['pp_operation']) : '';
$via_type = ( isset($_GET['pp_via_type']) ) ? pp_sanitize_key($_GET['pp_via_type']) : '';
$mod_type = ( isset($_GET['pp_mod_type']) ) ? pp_sanitize_key($_GET['pp_mod_type']) : '';
$item_id = ( isset($_GET['pp_item_id']) ) ? (int) $_GET['pp_item_id'] : 0;

if ( '(all)' == $for_type ) {
	$for_src_name = 'post';
	$via_src_name = 'term';
	$for_type = '';
} else {
	//$for_src_name = ( ! $for_type || post_type_exists( $for_type ) ) ? 'post' : 'term';
	if ( ! $for_type || post_type_exists( $for_type ) )
		$for_src_name = 'post';
	elseif( taxonomy_exists( $for_type ) )
		$for_src_name = 'term';
	else
		$for_src_name = $for_type;
	
	//$via_src_name = post_type_exists( $via_type ) ? 'post' : 'term';
	if ( post_type_exists( $via_type ) )
		$via_src_name = 'post';
	elseif( taxonomy_exists( $via_type ) )
		$via_src_name = 'term';
	else
		$via_src_name = $via_type;
}

$html = '';

switch ( $_GET['pp_ajax_exceptions_ui'] ) {

case 'get_operation_options':
	if ( ! is_user_logged_in() ) { echo '<option>' . __('(login timed out)', 'pp') . '</option>'; exit; }  // TODO: deal with login timeout in JS to avoid multiple messages

	$ops = ( ( 'post' == $for_src_name ) && ( 'attachment' != $for_type ) ) ? array( 'read' => __('Read', 'pp') ) : array();
	$ops = apply_filters( 'pp_exception_operations', $ops, $for_src_name, $for_type );
	
	if ( 'pp_group' == $agent_type ) {
		$group = pp_get_group( $agent_id );
		if ( in_array( $group->metagroup_id, array( 'wp_anon', 'wp_all' ) ) )
			$ops = array_intersect_key( $ops, array( 'read' => true ) );
	}
	
	if ( count($ops) > 1 )
		$html .= "<option class='pp-opt-none' value=''>" . __('select...', 'pp') . "</option>";
	
	foreach( $ops as $val => $title ) {
		$html .= "<option value='$val'>$title</option>";
	}
	break;
	
case 'get_mod_options':
	if ( ! is_user_logged_in() ) { echo '<option>' . __('(login timed out)', 'pp') . '</option>'; exit; }  // TODO: deal with login timeout in JS to avoid multiple messages

	if ( $agent_id && ( 'pp_group' == $agent_type ) ) {
		$group = pp_get_group( $agent_id );
		$is_wp_role = ( 'wp_role' == $group->metagroup_type );
	} else
		$is_wp_role = false;
	
	if ( ! $is_wp_role || ! in_array( $group->metagroup_id, array( 'wp_anon', 'wp_all' ) ) )
		$modes['additional'] = __('Also these:', 'pp');
	
	if ( ( 'user' == $agent_type ) || $is_wp_role || ( 'assign' == $operation ) || defined( 'PP_GROUP_RESTRICTIONS' ) ) {
		$modes['exclude'] = __('Not these:', 'pp');
	}
	
	$modes['include'] = __('Only these:', 'pp');

	$modes = apply_filters( 'pp_exception_modes', $modes, $for_src_name, $for_type, $operation );
	
	foreach( $modes as $val => $title ) {
		$html .= "<option value='$val'>$title</option>";
	}
	break;

case 'get_via_type_options':
	if ( ! is_user_logged_in() ) { echo '<option>' . __('(login timed out)', 'pp') . '</option>'; exit; }  // TODO: deal with login timeout in JS to avoid multiple messages

	$types = array();
	
	if ( 'post' == $for_src_name ) {
		if ( 'associate' != $operation ) {
			if ( 'assign' != $operation ) {		// 'assign' op only pertains to terms
				if ( $type_obj = get_post_type_object( $for_type ) ) {
					$types = array( $for_type => __( 'selected:', 'pp' ) );
				}
			}
			
			$type_arg = ( $for_type ) ? array( 'object_type' => $for_type ) : array();
			$taxonomies = pp_get_enabled_taxonomies( $type_arg, 'object' );

			if ( $taxonomies ) {
				$tax_types = array();
				foreach( $taxonomies as $_taxonomy => $tx ) {
					$tax_types[$_taxonomy] = $tx->labels->name;
				}
				
				uasort( $tax_types, 'strnatcasecmp' );	// sort by values without resetting keys
				
				$types = array_merge( $types, $tax_types );
			}
		} else {
			// 'associate' exceptions regulate parent assignment. This does not pertain to taxonomies, but may apply to other post types as specified by the filter.
			$aff_types = (array) apply_filters( 'pp_parent_types', array( $for_type ), $for_type );
			foreach( $aff_types as $_type ) {
				if ( $type_obj = get_post_type_object($_type) )
					$types[$_type] = $type_obj->labels->name;
			}
		}
	} elseif ( in_array( $for_src_name, array( 'pp_group', 'pp_net_group' ) ) ) {
		if ( $group_type_obj = pp_get_group_type_object( $for_src_name ) )
			$types[$for_src_name] = $group_type_obj->labels->name;
	}

	$types = apply_filters( 'pp_exception_via_types', $types, $for_src_name, $for_type, $operation, $mod_type );

	foreach( $types as $val => $title ) {
		$class = ( $for_type == $val ) ? ' class="pp-post-object"' : '';
		$html .= "<option value='$val'$class>$title</option>";
	}

	break;
	
case 'get_assign_for_ui':
	if ( ! is_user_logged_in() ) { echo '<p>' . __('(login timed out)', 'pp') . '</p><div class="pp-checkbox"><input type="checkbox" name="pp_select_for_item" style="display:none"><input type="checkbox" name="pp_select_for_item" style="display:none"></div>'; exit; }

	if ( $via_type ) {
		$type_obj = pp_get_type_object( $via_src_name, $via_type );
		
		$html = '<div class="pp-checkbox">'
			. '<input type="checkbox" id="pp_select_x_item_assign" name="pp_select_x_for_item" checked="checked" value="1" /><label id="pp_x_item_assign_label" for="pp_select_x_item_assign"> ' . sprintf( __('selected %s:', 'pp'), $type_obj->labels->name ) . '</label>'
			. '</div>';
		
		if ( $type_obj && $type_obj->hierarchical && apply_filters( 'pp_do_assign_for_children_ui', true, $for_type, compact( 'operation', 'mod_type' ) ) ) {
			if ( ! $caption = apply_filters( 'pp_assign_for_children_caption', '', $for_type ) )
				$caption = sprintf( __('sub-%s of:', 'pp'), $type_obj->labels->name );
			
			$checked = ( apply_filters( 'pp_assign_for_children_checked', false, $for_type, compact( 'operation', 'mod_type' ) ) ) ? ' checked="checked" ' : '';
			$disabled = ( apply_filters( 'pp_assign_for_children_locked', false, $for_type, compact( 'operation', 'mod_type' ) ) ) ? ' disabled="disabled" ' : '';
			
			$html .= '<div class="pp-checkbox">'
			. '<input type="checkbox" id="pp_select_x_child_assign" name="pp_select_x_for_children" value="1"' . $checked . $disabled . ' /><label id="pp_x_child_assign_label" for="pp_select_x_child_assign"> ' . $caption . '</label>'
			. '</div>';
		}
		
		$html = apply_filters( 'pp_assign_for_ui', $html, $for_src_name, $for_type, $operation, $mod_type );
	}

	break;

case 'get_status_ui':
	if ( ! is_user_logged_in() ) { echo '<p>' . __('(login timed out)', 'pp') . '</p><div class="pp-checkbox"><input type="checkbox" name="pp_select_for_item" style="display:none"><input type="checkbox" name="pp_select_for_item" style="display:none"></div>'; exit; }
	
	$checked = ' checked="checked"';

	$html = '<p class="pp-checkbox">'
			. '<input type="checkbox" id="pp_select_x_cond_post_status_" name="pp_select_x_cond[]" value=""' . $checked . ' /> '
			. '<label for="pp_select_x_cond_post_status_">' . __('(all)', 'pp') . '</label>'
			. '</p>';

	if ( ( 'post' != $for_src_name ) || ( $mod_type != 'additional' ) )
		break;
	
	if ( 'term' == $via_src_name ) { 
		if ( 'forum' != $for_type ) {
			$pvt_obj = get_post_status_object( 'private' );

			$html .= '<p class="pp-checkbox pp_select_private_status">'
			. '<input type="checkbox" id="pp_select_x_cond_post_status_private" name="pp_select_x_cond[]" value="post_status:private" /><label for="pp_select_x_cond_post_status_private"> ' . sprintf( __('%s Visibility', 'pp'), $pvt_obj->label ) . '</label>'
			. '</p>';
		}
	}

	$type_obj = get_post_type_object( $for_type );
	$var = "{$operation}_{$for_type}";
	$type_caps = isset( $type_obj->cap->$var ) ? (array) $type_obj->cap->$var : array();
	
	$html = apply_filters( 'pp_permission_status_ui', $html, $for_type, $type_caps );
	$html = apply_filters( 'pp_exceptions_status_ui', $html, $for_type, compact( 'via_src_name', 'operation', 'type_caps' ) );
	
	break;
	
case 'get_item_path' :
	require_once( PPC_ABSPATH . '/lib/ancestry_lib_pp.php' );
	
	if ( 'term' == $via_src_name ) {
		$html = $item_id . chr(13) . PP_Ancestry::get_term_path( $item_id, $via_type );
	} else {
		$html = $item_id . chr(13) . PP_Ancestry::get_post_path( $item_id );
	}

	break;
} // end switch

if ( $html )
	echo $html;
