<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( pp_is_active_widget_prefix( 'calendar-' ) )
	add_filter( 'query', '_pp_flt_calendar' );

if ( pp_get_option( 'strip_private_caption' ) ) {
	add_filter( 'the_title', '_pp_flt_title', 10, 3 );

	if ( defined ('WPLANG') && WPLANG )
		add_filter( 'gettext', '_pp_flt_gettext', 10, 3 );
}

add_action( 'wp_print_footer_scripts', '_pp_flt_hide_empty_menus' );

function pp_is_active_widget_prefix( $id_prefix ) {
	global $wp_registered_widgets;

	foreach ( (array) wp_get_sidebars_widgets() as $sidebar => $widgets ) {
		if ( 'wp_inactive_widgets' != $sidebar && is_array($widgets) ) {
			foreach ( $widgets as $widget ) {
				if ( isset($wp_registered_widgets[$widget]['id']) && ( 0 === strpos( $wp_registered_widgets[$widget]['id'], $id_prefix ) ) )
					return $sidebar;
			}
		}
	}

	return false;
}

function _pp_flt_hide_empty_menus() {
	if ( ! wp_script_is('jquery') )
		return;
?>
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready( function($) {
$("ul.menu").not(":has(li)").closest('div').prev('h3.widget-title').hide();
});
/* ]]> */
</script><?php
}

function _pp_flt_title($title) {
	if ( 0 === strpos( $title, 'Private: ' ) || 0 === strpos( $title, 'Protected: ' ) )
		$title = substr( $title, strpos( $title, ':' ) + 2 ); 
	
	return $title;
}

function _pp_flt_gettext($translated_text, $orig_text) {
	if ( ( 'Private: %s' == $orig_text ) || ( 'Protected: %s' == $orig_text ) )
		$translated_text = '%s';

	return $translated_text;
}

function _pp_flt_calendar( $query ) {
	if ( strpos( $query, "DISTINCT DAYOFMONTH" ) || strpos( $query, "post_title, DAYOFMONTH(post_date)" ) || strpos( $query, "MONTH(post_date) AS month" ) ) {
		$query = apply_filters( 'pp_posts_request', $query );
	}
	
	return $query;
}

