<?php
add_action( 'init', '_pp_eo_register' );
function _pp_eo_register() {
	sseo_register_parameter( 'pp_group', __( 'Permission Group', 'pp' ) ); 
}

add_filter( 'eo_shortcode_matched', '_pp_flt_eo_shortcode_matched', 10, 3 );
function _pp_flt_eo_shortcode_matched( $matched, $params, $shortcode_content ) {
	if ( ! empty( $params['pp_group'] ) ) {
		global $pp_current_user;

		if ( array_intersect( $params['pp_group'], array_keys( $pp_current_user->groups['pp_group'] ) ) )
			return true;
	}

	return $matched;
}