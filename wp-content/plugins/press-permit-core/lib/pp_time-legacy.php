<?php
// PP File URL Filter extension declares ppfx_time_gmt() function, but version prior to 2.0.5-beta need PPC to declare pp_get_time()
if ( defined('PPFF_VERSION') && version_compare( 'PPFF_VERSION', '2.0.5-beta', '<' ) ) {
	// make sure function not already defined because old PP plugin is active	
	if ( ! function_exists('pp_time_gmt') ) {
		function pp_time_gmt() {
			return strtotime( gmdate("Y-m-d H:i:s") );
		}
	}
}
