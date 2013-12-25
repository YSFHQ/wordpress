<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// derived from http://us3.php.net/manual/en/ref.array.php#80631
function pp_array_flatten( $arr_md, $go_deep = false ) { //flattens multi-dim arrays (if go_deep, supports > 2D but destroys keys)
    if ( ! is_array($arr_md) ) return array();
 
	$arr_flat = array(); 
    
    foreach ($arr_md as $element) {
       	if ( is_array($element) ) {
       		if ( $go_deep )
           		$arr_flat = array_merge($arr_flat, pp_array_flatten($element));
           	else
				$arr_flat = array_merge($arr_flat, $element);
        } else
            array_push($arr_flat, $element);
    }
 
    return $arr_flat;
}

function pp_set_array_elem( &$arr, $dims ) {			
	$elem =& $arr;

	foreach( $dims as $dim => $val ) {
		if ( ! isset( $elem[$val] ) )
			$elem[$val] = array();

		$elem =& $elem[$val];
	}
}

function pp_implode( $delim, $arr, $wrap_open = ' ( ', $wrap_close = ' ) ' ) {
	if ( ! is_array($arr) )
		return $arr;

	$delim = "$wrap_close $delim $wrap_open";

	if ( count($arr) ) {
		$arr = array_unique($arr);

		/*
		if ( constant( 'PP_DEBUG' ) ) {
			$test = implode($delim, $arr);
			if ( strpos( $test, 'Array' ) ) {
				dump($test);
				agp_bt_die();
			}
		}
		*/
		
		if ( constant( 'PP_DEBUG' ) ) {
			$test = reset( $arr );
			if ( is_array($test) )
				agp_bt_die();
		}
		
		return $wrap_open . implode($delim, $arr) . $wrap_close;
	} else {
		return reset($arr);
	}
}

function pp_get_property_array( &$arr, $id_prop, $buffer_prop ) {
	if ( ! is_array($arr) )
		return;

	$buffer = array();
		
	foreach ( array_keys($arr) as $key )
		$buffer[ $arr[$key]->$id_prop ] = ( isset($arr[$key]->$buffer_prop) ) ? $arr[$key]->$buffer_prop : '';

	return $buffer;
}

function pp_restore_property_array( &$target_arr, $buffer_arr, $id_prop, $buffer_prop ) {
	if ( ! is_array($target_arr) || ! is_array($buffer_arr) )
		return;
		
	foreach ( array_keys($target_arr) as $key )
		if ( isset( $buffer_arr[ $target_arr[$key]->$id_prop ] ) )
			$target_arr[$key]->$buffer_prop = $buffer_arr[ $target_arr[$key]->$id_prop ];
}

/*  // moved to pp_bootstrap_lib.php to work around conflict with old PP File Filtering extension
// returns true GMT timestamp
function pp_time_gmt() {	
	return strtotime( gmdate("Y-m-d H:i:s") );
}
*/

function pp_is_attachment() {
	global $wp_query;
	return ! empty($wp_query->query_vars['attachment_id']) || ! empty($wp_query->query_vars['attachment']);
}

// support array matching for post type
function pp_get_post_stati( $args, $return = 'names', $operator = 'and' ) {
	if ( isset($args['post_type']) ) {
		$post_type = $args['post_type'];
		unset( $args['post_type'] );
		$stati = get_post_stati( $args, 'object', $operator );
		
		foreach( $stati as $status => $obj ) {
			if ( ! empty($obj->post_type) && ! array_intersect( (array) $post_type, (array) $obj->post_type ) )
				unset( $stati[$status] );
		}
		
		return ( 'names' == $return ) ? array_keys($stati) : $stati;
	} else {
		return get_post_stati( $args, $return, $operator );
	}
}

function pp_get_type_object( $src_name, $object_type ) {
	if ( 'post' == $src_name ) {
		return get_post_type_object( $object_type );
	} elseif( 'term' == $src_name ) {
		return get_taxonomy( $object_type );
	} else {
		if ( $group_type_object = pp_get_group_type_object( $object_type ) ) {
			$group_type_object->hierarchical = false;
			return $group_type_object;

		} elseif ( $type_obj = apply_filters( 'pp_exception_type', null, $src_name, $object_type ) ) {
			return $type_obj;
		}
	}
}

function pp_sanitize_key( $key ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower($key) );
}

function pp_sanitize_word( $key ) {
	return preg_replace( '/[^A-Za-z0-9_\-\.:]/', '', $key );
}

function pp_sanitize_csv( $key ) {
	return preg_replace( '/[^A-Za-z0-9_\-\.,}{:\|\(\)\s\t\r\n]/', '', $key );
}

// wrapper for __(), prevents WP strings from being forced into plugin .po
function __ppw( $string, $unused = '' ) {
	return __( $string );		
}

function pp_wp_ver($wp_ver_requirement) {
	static $cache_wp_ver;
	
	if ( empty($cache_wp_ver) ) {
		global $wp_version;
		$cache_wp_ver = $wp_version;
	}
	
	if ( ! version_compare($cache_wp_ver, '0', '>') ) {
		// If global $wp_version has been wiped by WP Security Scan plugin, temporarily restore it by re-including version.php
		if ( file_exists (ABSPATH . WPINC . '/version.php') ) {
			include ( ABSPATH . WPINC . '/version.php' );
			$return = version_compare($wp_version, $wp_ver_requirement, '>=');
			$wp_version = $cache_wp_ver;	// restore previous wp_version setting, assuming it was cleared for security purposes
			return $return;
		} else
			// Must be running a future version of WP which doesn't use version.php
			return true;
	}

	// normal case - global $wp_version has not been tampered with
	return version_compare($cache_wp_ver, $wp_ver_requirement, '>=');
}

function pp_is_mu_plugin( $plugin_path ) {
	return ( defined('WPMU_PLUGIN_DIR') && ( false !== strpos( $plugin_path, WPMU_PLUGIN_DIR ) ) );
}

function pp_is_network_activated( $plugin_file ) {
	return ( array_key_exists( $plugin_file, (array) maybe_unserialize( get_site_option( 'active_sitewide_plugins') ) ) );
}
