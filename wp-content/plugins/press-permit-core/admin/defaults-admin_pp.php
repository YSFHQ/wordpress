<?php
add_filter( 'pp_pattern_roles', array( 'PP_AdminDefaults', 'flt_pattern_roles' ) );

class PP_AdminDefaults {
	public static function flt_pattern_roles( $roles ) {
		$roles['subscriber']->labels = (object) array( 'name' => __('Subscribers', 'pp'), 'singular_name' => __('Subscriber', 'pp') );
		$roles['contributor']->labels = (object) array( 'name' => __('Contributors', 'pp'), 'singular_name' => __('Contributor', 'pp') );
		$roles['author']->labels = (object) array( 'name' => __('Authors', 'pp'), 'singular_name' => __('Author', 'pp') );
		$roles['editor']->labels = (object) array( 'name' => __('Editors', 'pp'), 'singular_name' => __('Editor', 'pp') );
		
		return $roles;
	}
}
