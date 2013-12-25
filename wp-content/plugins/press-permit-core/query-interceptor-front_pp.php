<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class PP_QueryInterceptorFront {
	var $archives_where = '';
	
	public function __construct() {
		if ( ! pp_is_content_administrator() && ( ! defined('PPCE_VERSION') || version_compare( PPCE_VERSION, '2.1.9', '>' ) ) ) { // previously, this code was in PPCE
			require_once( dirname(__FILE__).'/qry-front_non-administrator_pp.php');
		}
		
		add_filter( 'get_previous_post_where', array(&$this, 'flt_adjacent_post_where') );
		add_filter( 'get_next_post_where', array(&$this, 'flt_adjacent_post_where') );

		add_filter( 'getarchives_where', array(&$this, 'flt_getarchives_where') );

		add_filter( 'option_sticky_posts', array(&$this, 'flt_sticky_posts') );

		add_filter( 'shortcode_atts_gallery', array( &$this, 'flt_atts_gallery' ), 10, 3 );
		
		if ( ! empty( $_REQUEST['preview'] ) )
			add_filter( 'wp_link_pages_link', array( &$this, 'flt_wp_link_pages_link' ) );
		
		do_action( 'pp_query_interceptor_front' );
	}

	
	
	function flt_atts_gallery( $out, $pairs, $atts ) {
		if ( ! empty( $atts['include'] ) ) // force subsequent get_posts() query to be filtered for PP exceptions
			add_action( 'pre_get_posts', array(&$this, 'act_get_gallery_posts' ) );

		return $out;
	}
	
	function act_get_gallery_posts( &$query_obj ) {
		$query_obj->query_vars['suppress_filters'] = false;
		remove_action( 'pre_get_posts', array(&$this, 'act_get_gallery_posts' ) );
	}
	
	// custom wrapper to clean up after get_previous_post_where, get_next_post_where nonstandard arg syntax 
	// (uses alias p for post table, passes "WHERE post_type=...)
	public function flt_adjacent_post_where( $where ) {
		global $wpdb, $query_interceptor, $current_user;

		$post_type = pp_find_post_type();
		
		$limit_statuses = array_merge( pp_get_post_stati( array( 'public' => true, 'post_type' => $post_type ) ), pp_get_post_stati( array( 'private' => true, 'post_type' => $post_type ) ) );

		if ( ! empty($current_user->ID) )
			$where = str_replace( " AND p.post_status = 'publish'", " AND p.post_status IN ('" . implode( "','", $limit_statuses ) . "')", $where );

		// get_adjacent_post() function includes 'WHERE ' at beginning of $where
		$where = str_replace( 'WHERE ', 'AND ', $where );

		if ( $limit_statuses )
			$limit_statuses = array_fill_keys( $limit_statuses, true );
		
		$args = array( 'post_types' => $post_type, 'source_alias' => 'p', 'skip_teaser' => true, 'limit_statuses' => $limit_statuses );
		
		$where = 'WHERE 1=1 ' . $query_interceptor->flt_posts_where( $where, $args );
		
		return $where;
	}

	public function flt_getarchives_where ( $where ) {
		global $current_user, $wpdb, $query_interceptor;
		
		// possible @todo: implement in any other PP filters?
		require_once( dirname(__FILE__).'/lib/sql-tokenizer_pp.php' );
		$parser = new SqlTokenizer_WP();
		$post_type = $parser->ParseArg( $where, 'post_type' );
		
		$where = str_replace( "WHERE ", "WHERE $wpdb->posts.post_date > 0 AND ", $where );
		
		$stati = array_merge( pp_get_post_stati( array( 'public' => true, 'post_type' => $post_type ) ), pp_get_post_stati( array( 'private' => true, 'post_type' => $post_type ) ) );
		
		if ( ! empty($current_user->ID) )
			$where = str_replace( "AND post_status = 'publish'", "AND post_status IN ('" . implode( "','", $stati ) . "')", $where );

		$where = str_replace( "WHERE ", "AND ", $where );

		$where = $query_interceptor->flt_posts_where( $where, array( 'skip_teaser' => true, 'post_type' => $post_type ) );

		$where = 'WHERE 1=1 ' . $where;
		
		$this->archives_where = $where;
		
		return $where;
	}
	
	public function flt_get_archives_request( $request ) {
		if ( 0 === strpos( $request, "SELECT YEAR(post_date) AS" ) ) {
			$request = str_replace( "SELECT YEAR(post_date) AS", "SELECT DISTINCT YEAR(post_date) AS", $request );
		} else {
			global $wpdb;
			$request = str_replace( "SELECT * FROM $wpdb->posts", "SELECT DISTINCT * FROM $wpdb->posts", $request );
		}
		
		remove_filter( 'query', array( 'PP_QueryInterceptorFront', 'flt_get_archives_request' ), 50 );
		return $request;
	}
	
	public function flt_sticky_posts( $post_ids ) {
		if ( $post_ids && ! pp_is_content_administrator() ) {
			global $wpdb;
			$clauses = array_fill_keys( array( 'distinct', 'join', 'groupby', 'orderby', 'limits' ), '' );
			$clauses['fields'] = 'ID';
			$clauses['where'] = "AND ID IN ('" . implode( "','", $post_ids ) . "')";
			$clauses = apply_filters( 'pp_posts_clauses', $clauses );
			$post_ids = $wpdb->get_col( "SELECT {$clauses['distinct']} ID FROM $wpdb->posts {$clauses['join']} WHERE 1=1 {$clauses['where']}" );
		}

		return $post_ids;
	}
	
	function flt_wp_link_pages_link( $pagenum_link ) {
		$matches = array();
		if ( preg_match_all( '/href="([^"]*)"/', $pagenum_link, $matches ) ) {
			$append_arg = ( strpos( $matches[1][0], '?p=' ) || strpos( $matches[1][0], '&p=' ) ) ? '&preview=1' : '?preview=1';
			$pagenum_link = str_replace( $matches[1][0], $matches[1][0] . $append_arg, $pagenum_link );
			//$new_url = add_query_arg('preview', true, $matches[1][0]);
			//$pagenum_link = str_replace( $matches[1][0], $new_url, $pagenum_link );
		}

		return $pagenum_link;
	}
}
