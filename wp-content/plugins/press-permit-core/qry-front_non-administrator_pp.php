<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_filter( 'wp_get_nav_menu_items', array('PP_QueryInterceptorFront_NonAdmin', 'flt_nav_menu_items'), 50, 3);

global $wp_query;
if ( is_object($wp_query) && method_exists($wp_query, 'is_tax') && $wp_query->is_tax() )
	add_filter('posts_request', array('PP_QueryInterceptorFront_NonAdmin', 'flt_p2_request'), 1 );

do_action( 'pp_query_interceptor_front_non_administrator' );

class PP_QueryInterceptorFront_NonAdmin {
	// force scoping filter to process the query a second time, to handle the p2 clause imposed by WP core for custom taxonomy requirements
	public static function flt_p2_request( $request ) {
		if ( strpos( $request, 'p2.post_status' ) )
			$request = apply_filters( 'pp_posts_request', $request, array( 'source_alias' => 'p2' ) );

		return $request;
	}
	
	public static function flt_nav_menu_items( $items, $menu_name, $args ) {
		global $wpdb;
		$item_types = array();
		
		//d_echo( '********************** enter flt_nav_menu_items <br /> ' );
		
		foreach ( $items as $key => $item ) {
			if ( ! isset( $item_types[ $item->type ] ) )
				$item_types[ "{$item->type}" ] = array();
		
			$item_types[ $item->type ][ $item->object ] [$key] = $item->object_id;
		} 
		
		$teaser_enabled = apply_filters( 'pp_teaser_enabled', false, 'post' );
		
		// remove unreadable terms	
		if ( isset( $item_types['taxonomy'] ) ) {
			foreach( $item_types['taxonomy'] as $taxonomy => $item_ids ) {
				$okay_ids = get_terms( $taxonomy, 'fields=ids&hide_empty=1' );

				if ( $remove_ids = apply_filters( 'pp_nav_menu_hide_terms', array_diff( $item_ids, $okay_ids ), $taxonomy ) ) {
					$item_types['taxonomy'][$taxonomy] = array_diff( $item_types['taxonomy'][$taxonomy], $remove_ids );
					
					foreach( array_keys($items) as $key ) {
						if ( ! empty($items[$key]->type) && ( 'taxonomy' == $items[$key]->type ) && in_array( $items[$key]->object_id, $remove_ids ) ) {
							unset( $items[$key] );
						}
					}	
				}
			}
		}
		
		// remove or tease unreadable posts
		if ( isset( $item_types['post_type'] ) ) {
			$post_types = array_keys( $item_types['post_type'] );
			$post_ids = pp_array_flatten($item_types['post_type']);
		} else {
			$post_types = $post_ids = array();
		}
		
		$pub_stati = pp_get_post_stati( array( 'public' => true, 'post_type' => $post_types ) );
		$pvt_stati = pp_get_post_stati( array( 'private' => true, 'post_type' => $post_types ) );
		$stati = array_merge( $pub_stati, $pvt_stati );
		
		$clauses = array_fill_keys( array( 'distinct', 'join', 'groupby', 'orderby', 'limits' ), '' );
		$clauses['fields'] = 'ID';
		$clauses['where'] = "AND post_type IN ( '" . implode( "','", $post_types ) . "' ) AND ID IN ( '" . implode("','", $post_ids ) . "') AND post_status IN ('" . implode( "','", $stati ) . "')";
		
		$clauses = apply_filters( 'pp_posts_clauses', $clauses, array( 'post_types' => $post_types, 'skip_teaser' => true, 'required_operation' => 'read', 'force_types' => true ) );
		$okay_ids = $wpdb->get_col( "SELECT {$clauses['distinct']} ID FROM $wpdb->posts {$clauses['join']} WHERE 1=1 {$clauses['where']}" );
		
		foreach( $post_types as $_post_type ) {
			$remove_ids = array_diff( $item_types['post_type'][$_post_type], $okay_ids );
			if ( $remove_ids = apply_filters_ref_array( 'pp_nav_menu_hide_posts', array( $remove_ids, &$items, $_post_type ) ) ) {
				foreach( array_keys($items) as $key ) {
					if ( ! empty($items[$key]->type) && ( 'post_type' == $items[$key]->type ) && in_array( $items[$key]->object_id, $remove_ids ) ) {
						unset( $items[$key] );
					}
				}				
			}
		}

		//d_echo( '**************** exit flt_nav_menu_items <br /> ' );
		
		return $items;
	}
}
