<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_filter( 'comments_clauses', array( 'CommentsInterceptor', 'flt_comments_clauses' ), 10, 2 );

class CommentsInterceptor {
	public static function flt_comments_clauses( $clauses, &$qry_obj, $args = array() ) {
		global $wpdb;
		
		$defaults = array( 'query_contexts' => array() );
		extract( array_merge( $defaults, $args ), EXTR_SKIP );
		
		$query_contexts[]= 'comments';
		
		if ( did_action( 'comment_post' ) )  // don't filter comment retrieval for email notification
			return $clauses;

		if ( is_admin() && defined( 'PP_NO_COMMENT_FILTERING' ) ) {
			global $current_user;

			if ( empty( $current_user->allcaps['moderate_comments'] ) )
				return $clauses;
		}

		if ( empty( $clauses['join'] ) || ! strpos( $clauses['join'], $wpdb->posts ) )
			$clauses['join'] .= " INNER JOIN $wpdb->posts ON $wpdb->posts.ID = $wpdb->comments.comment_post_ID";
		
		// (subsequent filter will expand to additional statuses as appropriate)
		$clauses['where'] = preg_replace( "/ post_status\s*=\s*[']?publish[']?/", " $wpdb->posts.post_status = 'publish'", $clauses['where'] );

		$post_type = '';
		$post_id = ( ! empty( $qry_obj->query_vars['post_id'] ) ) ? $qry_obj->query_vars['post_id'] : 0;

		if ( $post_id ) {
			if ( $_post = get_post( $post_id ) )
				$post_type = $_post->post_type;
		} else {
			$post_type = ( isset( $qry_obj->query_vars['post_type'] ) ) ? $qry_obj->query_vars['post_type'] : '';
		}
		
		if ( $post_type && ! in_array( $post_type, pp_get_enabled_post_types() ) )
			return $clauses;

		global $query_interceptor;
		$clauses['where'] = "1=1 " . $query_interceptor->flt_posts_where( 'AND ' . $clauses['where'], array( 'post_types' => $post_type, 'skip_teaser' => true, 'query_contexts' => $query_contexts ) );

		return $clauses;
	}
}
