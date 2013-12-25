<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! current_user_can( 'create_users' ) || ! current_user_can( 'pp_manage_members' ) )
	return;

switch ( $_GET['pp_ajax_user_ui'] ) {
case 'new_user_groups_ui':	
	require_once( dirname(__FILE__).'/profile_ui_pp.php' );
	PP_ProfileUI::display_ui_user_groups( false );
	break;
}
