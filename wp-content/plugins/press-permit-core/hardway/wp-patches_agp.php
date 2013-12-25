<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/*
File: wp-patches_agp.php
Description: Workarounds for bugs in the WordPress core which can be patched by filter
*/

// if WP throws out an invalid page permalink due to an unpublished ancestor, switch to page_id permalink
function _pp_flt_page_link( $link, $id ) {
	if ( strlen($link) > 7 && strpos($link, '//', 7) > 7 ) {
		static $home_path;
		if ( empty($home_path) )
			$home_path = get_option('home');

		$link = $home_path . "/?page_id=$id";
	}
	
	return $link;
}

add_filter('_get_page_link', '_pp_flt_page_link', 50, 2);
