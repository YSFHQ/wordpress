<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_filter( 'comments_clauses', array( 'CommentsInterceptor_Administrator', 'flt_comments_clauses' ), 10, 2 );

class CommentsInterceptor_Administrator {
	public static function flt_comments_clauses( $clauses, &$qry_obj ) {
		global $wpdb;
		
		$stati = get_post_stati( array( 'public' => true, 'private' => true ), 'names', 'or' );
		
		if ( ! defined( 'PP_NO_ATTACHMENT_COMMENTS' ) )
			$stati []= 'inherit';
		
		$status_csv = "'" . implode( "','", $stati ) . "'";
		$clauses['where'] = preg_replace( "/\s*AND\s*{$wpdb->posts}.post_status\s*=\s*[']?publish[']?/", "AND {$wpdb->posts}.post_status IN ($status_csv)", $clauses['where'] );

		return $clauses;
	}
}
