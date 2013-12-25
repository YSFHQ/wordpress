<?php
add_filter( 'pp_default_options', 'ppx_default_options' );
add_filter( 'pp_options', 'ppx_force_defaults' );

function ppx_default_options( $defaults = array() ) {
	$extra = array( 
		'display_hints' => 1,
		'display_extension_hints' => 1,
		'dynamic_wp_roles' => 0,
		'non_admins_set_read_exceptions' => 1,
		'user_search_by_role' => 0,
		'anonymous_unfiltered' => 0,
	);
	
	return apply_filters( 'ppx_default_options', array_merge( $defaults, $extra ) );
}

// if Advanced Options are not enabled, ignore stored settings
function ppx_force_defaults( $options ) {
	if ( ! pp_get_option( 'advanced_options' ) ) {
		foreach( ppx_default_options() as $key => $val ) {
			$options["pp_{$key}"] = $val;
		}
	}
	return $options;
}
