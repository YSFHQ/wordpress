<?php
class PP_Permits_Helper {
	public static function get_url_properties( &$url, &$referer, &$redirect ) {
		$url = apply_filters( 'pp_groups_base_url', 'admin.php' );

		if ( empty($_REQUEST) ) {
			$referer = '<input type="hidden" name="wp_http_referer" value="'. esc_attr(stripslashes($_SERVER['REQUEST_URI'])) . '" />';
		} elseif ( ! empty($_REQUEST['wp_http_referer']) ) {
			$redirect = remove_query_arg(array('wp_http_referer', 'updated', 'delete_count'), stripslashes($_REQUEST['wp_http_referer']));
			$referer = '<input type="hidden" name="wp_http_referer" value="' . esc_attr($redirect) . '" />';
		} else {
			$redirect = "$url?page=pp-groups";
			$referer = '';
		}
	}
}
