<?php
class PP_Find {
	public static function find_post_type( $post_id = 0, $return_default = true ) {
		if ( defined('DOING_AJAX') && DOING_AJAX ) { // todo: separate function to eliminate redundancy with PP_QueryInterceptor::flt_posts_clauses()
			$ajax_post_types = apply_filters( 'pp_ajax_post_types', array( 'ai1ec_doing_ajax' => 'ai1ec_event' ) );
			foreach( array_keys($ajax_post_types) as $arg ) {
				if ( ! empty( $_REQUEST[$arg] ) || ( $arg == $_REQUEST['action'] ) )
					return $ajax_post_types[$arg];
			}
		}

		if ( $post_id ) { // note: calling function already compared post_id to global $post
			if ( $_post = get_post( $post_id ) ) {
				$_type = $_post->post_type;
				
				//if ( 'revision' == $_type )
				//	$_type = get_post_field( 'post_type', $_post->post_parent );
			}

			if ( ! empty($_type) )
				return $_type;
		}
		
		// no post id was passed in, or we couldn't retrieve it for some reason, so check $_REQUEST args
		global $pagenow, $wp_query;
		
		if ( ! empty( $wp_query->queried_object ) ) {
			if ( isset( $wp_query->queried_object->post_type ) )
				$object_type = $wp_query->queried_object->post_type;
			elseif ( isset( $wp_query->queried_object->name ) ) {
				if ( post_type_exists( $wp_query->queried_object->name ) )  // bbPress forums list
					$object_type = $wp_query->queried_object->name;
			}
		} elseif ( in_array( $pagenow, array( 'post-new.php', 'edit.php' ) ) ) {
			$object_type = ! empty( $_GET['post_type'] ) ? pp_sanitize_key($_GET['post_type']) : 'post';
		
		} elseif ( in_array( $pagenow, array( 'edit-tags.php' ) ) ) {
			$object_type = ! empty( $_REQUEST['taxonomy'] ) ? pp_sanitize_key($_REQUEST['taxonomy']) : 'category';

		} elseif ( in_array( $pagenow, array( 'admin-ajax.php' ) ) && ! empty( $_REQUEST['taxonomy'] ) ) {
			$object_type = pp_sanitize_key($_REQUEST['taxonomy']);

		} elseif ( ! empty( $_POST['post_ID'] ) ) {
			if ( $_post = get_post( $_POST['post_ID'] ) )
				$object_type = $_post->post_type;

		} elseif ( ! empty( $_GET['post'] ) ) {	 // post.php
			if ( $_post = get_post( $_GET['post'] ) )
				$object_type = $_post->post_type;

		} else {
			global $pp_object_type;
			
			if ( ! empty( $pp_object_type ) )
				$object_type = $pp_object_type;
		}

		if ( empty($object_type) ) {
			if ( $return_default ) // default to post type
				return 'post';
		} elseif ( 'any' != $object_type ) {
			return $object_type;
		}
	}
} // end class
