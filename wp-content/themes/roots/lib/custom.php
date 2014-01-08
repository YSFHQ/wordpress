<?php
/**
 * Custom functions
 */

/* 
 * Helper function to return the theme option value. If no value has been saved, it returns $default.
 * Needed because options are saved as serialized strings.
 *
 * This code allows the theme to work without errors if the Options Framework plugin has been disabled.
 */

if ( !function_exists( 'of_get_option' ) ) {
function of_get_option($name, $default = false) {
	
	$optionsframework_settings = get_option('optionsframework');
	
	// Gets the unique option id
	$option_name = $optionsframework_settings['id'];
	
	if ( get_option($option_name) ) {
		$options = get_option($option_name);
	}
		
	if ( isset($options[$name]) ) {
		return $options[$name];
	} else {
		return $default;
	}
}
}

if ( !function_exists( 'is_first_class' ) ) {
    function is_first_class() {
        $exceptions = array("/community/online-servers", "/login", "/2013-in-review");
        foreach ($exceptions as $except) if (strpos($_SERVER["REQUEST_URI"], $except)!==false) return true;
        return array_key_exists(11, pp_get_groups_for_user(get_current_user_id(),'pp_group',array("cols"=>"id","status"=>"active")));
    }
}
