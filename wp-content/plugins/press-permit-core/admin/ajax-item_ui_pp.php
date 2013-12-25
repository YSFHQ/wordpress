<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( empty($_GET['via_item_source']) )
	exit;

$html = '';

switch ( $_GET['pp_ajax_item_ui'] ) {

case 'get_agent_exception_ui':
	if ( ! is_user_logged_in() ) { echo '<option>' . __('(login timed out)', 'pp') . '</option>'; exit; }

	if ( ! $arr_sfx = explode( ':', pp_sanitize_csv($_GET['id_sfx']) ) )
		return '';
	
	$op = $arr_sfx[0];
	$for_item_type = $arr_sfx[1];
	$agent_type = $arr_sfx[2];
	$item_id = $_GET['item_id'];
	$for_item_source = ( taxonomy_exists($for_item_type) ) ? 'term' : 'post';
	$agent_ids = explode( ',', pp_sanitize_csv($_GET['agent_ids']) );
	
	echo "<!--ppSfx-->$op|$for_item_type|$agent_type<--ppSfx-->"
		. "<!--ppResponse-->";
	
	require_once( dirname(__FILE__).'/item-exceptions-data_pp.php' );
	$exc_data = new PP_ItemExceptionsData();
	
	$args = array( 'post_types' => (array) $for_item_type, 'agent_type' => $agent_type, 'operations' => $op, 'agent_id' => $agent_ids );
	$exc_data->load_exceptions( pp_sanitize_key($_GET['via_item_source']), $for_item_source, pp_sanitize_key($_GET['via_item_type']), $item_id, $args );
	
	require_once( dirname(__FILE__).'/item-exceptions-render-ui_pp.php' );
	$exc_render = new PP_ItemExceptionsRenderUI();

	$echo = false;
	$reqd_caps = false;
	$hierarchical = ( 'term' == $_GET['via_item_source'] ) ? is_taxonomy_hierarchical($_GET['via_item_type']) : is_post_type_hierarchical($_GET['via_item_type']);
	$hierarchical = apply_filters( 'pp_do_assign_for_children_ui', $hierarchical, $_GET['via_item_type'], $args );
	$default_select = true;
	
	$exc_render->set_options( $agent_type );
	
	foreach( $agent_ids as $agent_id ) {
		if ( ! $agent_id )
			continue;

		$exc_render->draw_row( $agent_type, $agent_id, array(), $exc_data->inclusions_active, $exc_data->agent_info[$agent_type][$agent_id], compact( 'echo', 'default_select', 'for_item_type', 'op', 'reqd_caps', 'hierarchical' ) );
	}
	
	echo "<--ppResponse-->";
	
	break;

} // end switch
