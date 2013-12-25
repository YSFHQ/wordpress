<?php
class PP_QueryInterceptorExtra {
// Other supported arguments, processed by downstream functions:
//  skip_teaser			- applied by flt_posts_where()
//  required_operation	- ''
//  source_alias		- ''
//
	public static function flt_posts_request( $request, $args = array() ) {
		if ( pp_unfiltered() )
			return $request;
		
		$defaults = array( 'post_types' => array(), 'source_alias' => '', 'only_append_where' => '' );
		$args = array_merge( $defaults, $args );
		extract($args, EXTR_SKIP);

		global $wpdb, $query_interceptor;
		
		if ( apply_filters( 'pp_posts_request_bypass', false, $request, $args ) )
			return $request;

		//d_echo( "<br />flt_objects_request: $request<br />" );

		if ( ! preg_match('/\s*WHERE\s*1=1/', $request) )
			$request = preg_replace('/\s*WHERE\s*/', ' WHERE 1=1 AND ', $request);

		$clauses = array();
		$pos_where = 0;
		$pos_suffix = 0;
		$clauses['where'] = agp_parse_after_WHERE_11( $request, $pos_where, $pos_suffix );	 // NOTE: any existing where, orderby or group by clauses remain in $where
		if ( ! $pos_where && $pos_suffix ) {
			$request = substr($request, 0, $pos_suffix) . ' WHERE 1=1' .  substr($request, $pos_suffix);
			$pos_where = $pos_suffix;
		}

		if ( ! $only_append_where ) {
			if ( ! isset($args['source_alias']) ) {
				// If the query uses an alias for the posts table, be sure to use that alias in the WHERE clause also.
				//
				// NOTE: if query refers to non-active site, this code will prevent a DB syntax error, but will not cause the correct roles / statuses to be applied.
				// 		Other plugins need to use switch_to_blog() rather than just executing a query on a non-main site.
				$matches = array();
				if ( $return = preg_match( '/SELECT .* FROM [^ ]+posts AS ([^ ]) .*/', $request, $matches ) )
					$args['source_alias'] = $matches[2];
				elseif ( $return = preg_match( '/SELECT .* FROM ([^ ]+)posts .*/', $request, $matches ) )
					$args['source_alias'] = $matches[1] . 'posts';
			}

			if ( false !== strpos( $request, ' COUNT( * ) AS num_posts' ) )
				$args['include_trash'] = true;

			// attachment filtering is applied here
			$clauses['where'] = apply_filters( 'pp_posts_clauses_where', $query_interceptor->flt_posts_where( $clauses['where'], $args ), $clauses, $args );
		}
		//dump($clauses);
		
		if ( $pos_where === false )
			$request .= " WHERE 1=1 $only_append_where " . $clauses['where'];
		else
			$request = substr( $request, 0, $pos_where ) . " WHERE 1=1 $only_append_where " . $clauses['where']; // any pre-exising join clauses remain in $request

		//d_echo( "<br /><br />filtered: $request<br /><br />" );
		
		return $request;
	} // end function flt_posts_request
} // end class
	
function agp_parse_after_WHERE_11( $request, &$pos_where, &$pos_suffix ) {
	$request_u = strtoupper($request);
	$pos_where = strpos( $request_u, ' WHERE 1=1');
	
	if ( ! $pos_where ) {
		if ( $pos_suffix = agp_get_suffix_pos($request) )
			$where =  substr($request, $pos_suffix); 
	} else {
		// note: this will still also contain any orderby/limit/groupby clauses ( okay since we won't append anything to the end )
		$where = substr($request, $pos_where + strlen(' WHERE 1=1 ')); 
	}
	
	return $where;	
}
