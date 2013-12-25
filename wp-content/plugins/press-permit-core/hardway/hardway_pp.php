<?php
// *** Note: this file is only loaded if we fail to catch and modify the get_pages() query

// In effect, override corresponding WP functions with a scoped equivalent, 
// Any previous result set modifications by other plugins
// would be discarded.  These filters are set to execute as early as possible to avoid such conflict.
//
// (note: if wp_cache is not enabled, WP core queries will execute pointlessly before these filters have a chance)

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once( PPC_ABSPATH . '/lib/ancestry_lib_pp.php' );

// note: get_pages() hook is set by PP_Main

do_action( 'pp_hardway' );

/**
 * Replace the output from WP get_pages() with this filterable equivalent, 
 * since get_pages() does not yet provide arg and query hooks
 * 
 * @package PP
 * @author Kevin Behrens <kevin@agapetry.net>
 * @copyright Copyright (c) 2011-2013, Agapetry Creations LLC
 *
 */	
class PP_Hardway
{
	//  PP-filtered equivalent to WP core get_pages
	//	As of WP 3.6, lack of query filters necessitates replicating the whole function  
	//
	public static function flt_get_pages($results, $args = array()) {
		$results = (array) $results;

		global $wpdb;
		
		// === BEGIN PP ADDITION: global var; various special case exemption checks ===
		//
		global $pp, $current_user;

		// buffer titles in case they were filtered previously
		$titles = pp_get_property_array( $results, 'ID', 'post_title' );

		// depth is not really a get_pages arg, but remap exclude arg to exclude_tree if wp_list_terms called with depth=1
		if ( ! empty($args['exclude']) && empty($args['exclude_tree']) && ! empty($args['depth']) && ( 1 == $args['depth'] ) )
			if ( 0 !== strpos( $args['exclude'], ',' ) ) // work around wp_list_pages() bug of attaching leading comma if a plugin uses wp_list_pages_excludes filter
				$args['exclude_tree'] = $args['exclude'];
		//
		// === END PP ADDITION ===
		// =================================
	
		$defaults = array(
			'child_of' => 0, 'sort_order' => 'ASC',
			'sort_column' => 'post_title', 'hierarchical' => 1,
			'exclude' => array(), 'include' => array(),
			'meta_key' => '', 'meta_value' => '',
			'authors' => '', 'parent' => -1, 'exclude_tree' => '',
			'number' => '', 'offset' => 0,
			'post_type' => 'page', 'post_status' => 'publish',
			
			'depth' => 0, 'suppress_filters' => 0,
			'required_operation' => '', 'alternate_operation' => '',
		);	// PP arguments added above
		
		// === BEGIN PP ADDITION: support front-end optimization
		$post_type = ( isset( $args['post_type'] ) ) ? $args['post_type'] : $defaults['post_type'];
		
		$enabled_post_types = pp_get_enabled_post_types();
		if ( ! in_array( $post_type, $enabled_post_types ) )
			return $results;

		if ( pp_is_front() ) {
			if ( ( 'page' == $post_type ) && defined( 'PP_GET_PAGES_LEAN' ) ) // custom types are likely to have custom fields
				$defaults['fields'] = "$wpdb->posts.ID, $wpdb->posts.post_title, $wpdb->posts.post_parent, $wpdb->posts.post_date, $wpdb->posts.post_date_gmt, $wpdb->posts.post_status, $wpdb->posts.post_name, $wpdb->posts.post_modified, $wpdb->posts.post_modified_gmt, $wpdb->posts.guid, $wpdb->posts.menu_order, $wpdb->posts.comment_count";
			else
				$defaults['fields'] = "$wpdb->posts.*";
		} else {
			// required for xmlrpc getpagelist method	
			$defaults['fields'] = "$wpdb->posts.*";
		}
		// === END PP MODIFICATION ===
		
		$r = wp_parse_args( $args, $defaults );	

		$args['number'] = (int) $r['number'];
		$args['offset'] = absint( $r['offset'] );
		$args['child_of'] = (int) $r['child_of'];

		extract( apply_filters( 'pp_get_pages_args', $r ), EXTR_SKIP );  // PPE filter modifies append_page, exclude_tree

		// workaround for CMS Tree Page View (passes post_parent instead of parent)
		if ( ( -1 == $parent ) && isset($args['post_parent']) )
			$parent = $args['post_parent'];
		
		// Make sure the post type is hierarchical
		$hierarchical_post_types = get_post_types( array( 'public' => true, 'hierarchical' => true ) );
		if ( !in_array( $post_type, $hierarchical_post_types ) )
			return $results;
	
		// Make sure we have a valid post status
		if ( is_admin() && ( 'any' === $post_status ) )
			$post_status = '';

		if ( $post_status ) {
			if ( ! is_array( $post_status ) && strpos( $post_status, ',' ) )
				$post_status = explode( ',', $post_status );
				
			if ( array_diff( (array) $post_status, get_post_stati() ) )
				return $results;
		}

		$intercepted_pages = apply_filters( 'pp_get_pages_intercept', false, $results, $args );		
		if ( is_array( $intercepted_pages ) )
			return $intercepted_pages;
		
		//$pp->last_get_pages_args = $r; // don't copy entire args array unless it proves necessary
		$pp->last_get_pages_depth = $depth;
		$pp->last_get_pages_suppress_filters = $suppress_filters;
		
		if ( $suppress_filters )
			return $results;

		$orderby_array = array();
		$allowed_keys = array('author', 'post_author', 'date', 'post_date', 'title', 'post_title', 'name', 'post_name', 'modified',
							  'post_modified', 'modified_gmt', 'post_modified_gmt', 'menu_order', 'parent', 'post_parent',
							  'ID', 'rand', 'comment_count');
		foreach ( explode( ',', $sort_column ) as $orderby ) {
			$orderby = trim( $orderby );
			if ( !in_array( $orderby, $allowed_keys ) )
				continue;

			switch ( $orderby ) {
				case 'menu_order':
					break;
				case 'ID':
					$orderby = "$wpdb->posts.ID";
					break;
				case 'rand':
					$orderby = 'RAND()';
					break;
				case 'comment_count':
					$orderby = "$wpdb->posts.comment_count";
					break;
				default:
					if ( 0 === strpos( $orderby, 'post_' ) )
						$orderby = "$wpdb->posts." . $orderby;
					else
						$orderby = "$wpdb->posts.post_" . $orderby;
			}
			$orderby_array[] = $orderby;
		}
		$sort_column = ! empty( $orderby_array ) ? implode( ',', $orderby_array ) : "$wpdb->posts.post_title";
			
		// $args can be whatever, only use the args defined in defaults to compute the key
		$key = md5( serialize( compact(array_keys($defaults)) ) );
		$last_changed = wp_cache_get( 'last_changed', 'posts' );
		if ( ! $last_changed ) {
			$last_changed = microtime();
			wp_cache_set( 'last_changed', $last_changed, 'posts' );
		}

		$cache_key = "pp_get_pages:$key:$last_changed";
		if ( $cache = wp_cache_get( $cache_key, 'posts' ) ) {
			// Convert to WP_Post instances
			$pages = array_map( 'get_post', $cache );
			$pages = apply_filters('pp_get_pages', $pages, $r);
			return $pages;
		}
		
		$inclusions = '';
		if ( !empty($include) ) {
			$child_of = 0; //ignore child_of, parent, exclude, meta_key, and meta_value params if using include
			$parent = -1;
			$exclude = '';
			$meta_key = '';
			$meta_value = '';
			$hierarchical = false;
			$incpages = wp_parse_id_list($include);
			if ( ! empty( $incpages ) ) {
				foreach ( $incpages as $incpage ) {
					if (empty($inclusions))
						$inclusions = ' AND ( ID = ' . intval($incpage) . ' ';
					else
						$inclusions .= ' OR ID = ' . intval($incpage) . ' ';
				}
			}
		}
		if (!empty($inclusions))
			$inclusions .= ')';
	
		$exclusions = '';
		if ( !empty($exclude) ) {
			$expages = wp_parse_id_list($exclude);
			if ( ! empty( $expages) ) {
				foreach ( $expages as $expage ) {
					if (empty($exclusions))
						$exclusions = ' AND ( ID <> ' . intval($expage) . ' ';
					else
						$exclusions .= ' AND ID <> ' . intval($expage) . ' ';
				}
			}
		}
		if (!empty($exclusions))
			$exclusions .= ')';
	
		$author_query = '';
		if (!empty($authors)) {
			$post_authors = wp_parse_id_list($authors);
	
			if ( ! empty( $post_authors ) ) {
				foreach ( $post_authors as $post_author ) {
					//Do we have an author id or an author login?
					if ( 0 == intval($post_author) ) {
						$post_author = get_user_by( 'login', $post_author );
						if ( empty($post_author) )
							continue;
						if ( empty($post_author->ID) )
							continue;
						$post_author = $post_author->ID;
					}
	
					if ( '' == $author_query )
						$author_query = ' post_author = ' . intval($post_author) . ' ';
					else
						$author_query .= ' OR post_author = ' . intval($post_author) . ' ';
				}
				if ( '' != $author_query )
					$author_query = " AND ($author_query)";
			}
		}
	
		$join = '';
		$where = "$exclusions $inclusions ";

		if ( ! empty( $meta_key ) || ! empty($meta_value) ) {
			$join = " INNER JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id";   // PP MODIFICATION: was LEFT JOIN in WP 3.0 core
			
			// meta_key and meta_value might be slashed
			$meta_key = stripslashes($meta_key);
			$meta_value = stripslashes($meta_value);
			
			if ( ! empty( $meta_key ) )
				$where .= $wpdb->prepare(" AND $wpdb->postmeta.meta_key = %s", $meta_key);
			if ( ! empty( $meta_value ) )
				$where .= $wpdb->prepare(" AND $wpdb->postmeta.meta_value = %s", $meta_value);
		}
	
		if ( $parent >= 0 )
			$where .= $wpdb->prepare(' AND post_parent = %d ', $parent);
			
		// === BEGIN PP MODIFICATION:
		// allow pages of multiple statuses to be displayed (requires default status=publish to be ignored)
		//
		$where_post_type = $wpdb->prepare( "post_type = %s", $post_type );
		$where_status = '';
		
		global $current_user;
		
		$is_front = pp_is_front();
		if ( $is_front && ! empty($current_user->ID) )
			$frontend_list_private = ! defined('PP_SUPPRESS_PRIVATE_PAGES'); // currently using Page option for all hierarchical types
		else
			$frontend_list_private = false;

		$force_publish_status = ! $frontend_list_private && ( 'publish' == $post_status );
			
		if ( $is_front ) {
			// since we will be applying status clauses based on content-specific roles, only a sanity check safeguard is needed when post_status is unspecified or defaulted to "publish"			
			$safeguard_statuses = array();
			foreach( pp_get_post_stati( array( 'internal' => false, 'post_type' => $post_type), 'object' ) as $status_name => $status_obj )
				if ( ( ! $is_front ) || $status_obj->private || $status_obj->public )
					$safeguard_statuses []= $status_name;
		}

		// WP core does not include private pages in query.  Include private statuses in anticipation of user-specific filtering		
		if ( is_array($post_status) )
			$where_status = "AND post_status IN ('" . implode("','", $post_status ) . "')";
		elseif ( $post_status && ( ( 'publish' != $post_status ) || ( $is_front && ! $frontend_list_private ) ) )
			$where_status = $wpdb->prepare( "AND post_status = %s", $post_status );
		elseif( $is_front )
			$where_status = "AND post_status IN ('" . implode("','", $safeguard_statuses ) . "')";
		else
			$where_status = "AND post_status NOT IN ('" . implode( "','", get_post_stati( array( 'internal' => true ) ) ) . "')";

		$where_id = '';
		global $pagenow;
		if ( is_admin() && in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) ) {
			global $post;
			if ( $post )
				$where_id = "AND ID != $post->ID";
		}
		
		$where = "AND $where_post_type AND ( 1=1 $where_status $where $author_query $where_id )";
		$orderby = "ORDER BY $sort_column $sort_order";
		$limits = ( !empty($number) ) ? ' LIMIT ' . $offset . ',' . $number : '';
		$query = "SELECT $fields FROM $wpdb->posts $join WHERE 1=1 $where $orderby $limits";
		
		if ( pp_is_front() && apply_filters( 'pp_teaser_enabled', false, 'post', $post_type ) && ! defined('PP_TEASER_HIDE_PAGE_LISTING') ) {
			// We are in the front end and the teaser is enabled for pages	

			// TODO: move to PPTX
			$query = str_replace( "post_status = 'publish'", " post_status IN ('" . implode( "','", $safeguard_statuses ) . "')", $query );
			
			$pages = $wpdb->get_results($query);			// execute unfiltered query
			
			// Pass results of unfiltered query through the teaser filter.
			// If listing private pages is disabled, they will be omitted completely, but restricted published pages
			// will still be teased.  This is a slight design compromise to satisfy potentially conflicting user goals without yet another option
			$pages = apply_filters('pp_posts_teaser', $pages, $post_type, array('request' => $query, 'force_teaser' => true) );
	
			$tease_all = true;
		} else {
			$_args = array( 'skip_teaser' => true, 'retain_status' => $force_publish_status );
			
			$groupby = $distinct = '';
			
			global $pagenow;
			if ( in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) ) {
				$clauses = apply_filters( 'pp_get_pages_clauses', compact( 'distinct', 'fields', 'join', 'where', 'groupby', 'orderby', 'limits' ), $post_type, $args );
			} else {
				// Pass query through the request filter
				$_args['post_types'] = $post_type;
				
				if ( $required_operation )
					$_args['required_operation'] = $required_operation;
				else
					$_args['required_operation'] = ( pp_is_front() && ! is_preview() ) ? 'read' : 'edit';
				
				if ( ( ( 'edit' == $_args['required_operation'] ) && isset($args['post_parent']) ) || ( 'associate' == $alternate_operation ) )  // workaround for CMS Page View
					$_args['alternate_required_ops'] = array('associate');

				$clauses = apply_filters( 'pp_posts_clauses', compact( 'distinct', 'fields', 'join', 'where', 'groupby', 'orderby', 'limits' ), $_args );
			}

			// Execute the filtered query
			$pages = $wpdb->get_results( "SELECT {$distinct} {$clauses['fields']} FROM $wpdb->posts {$clauses['join']} WHERE 1=1 {$clauses['where']} {$clauses['orderby']} {$clauses['limits']}" );
		}
		
		if ( empty($pages) )
			// alternate hook name (WP core already applied get_pages filter)
			return apply_filters('pp_get_pages', array(), $r);
		
		if ( $child_of )
			$pages = get_page_children( $child_of, $pages );
		
		// restore buffered titles in case they were filtered previously
		pp_restore_property_array( $pages, $titles, 'ID', 'post_title' );
		//
		// === END PP MODIFICATION ===
		// ====================================

		$num_pages = count($pages);
		for ($i = 0; $i < $num_pages; $i++) {
			$pages[$i] = sanitize_post($pages[$i], 'raw');
		}
		
		// Press Permit note: WP core get_pages has already updated wp_cache and pagecache with unfiltered results.
		update_post_cache($pages);

		// === BEGIN PP MODIFICATION: Support a disjointed pages tree with some parents hidden ========
		if ( $child_of || empty($tease_all) ) {  // if we're including all pages with teaser, no need to continue thru tree remapping
			$ancestors = PP_Ancestry::get_page_ancestors(); // array of all ancestor IDs for keyed page_id, with direct parent first

			$orderby = $sort_column;
			
			$remap_args = compact( 'child_of', 'parent', 'exclude', 'depth', 'orderby' );  // one or more of these args may have been modified after extraction 
			
			PP_Ancestry::remap_tree( $pages, $ancestors, $remap_args );
		}
		// === END PP MODIFICATION ===
		// ====================================

		if ( ! empty($exclude_tree) ) {
			$exclude = array();
	
			$exclude = (int) $exclude_tree;
			$children = get_page_children($exclude, $pages);	// PP note: okay to use unfiltered WP function here since it's only used for excluding
			$excludes = array();
			foreach ( $children as $child )
				$excludes[] = $child->ID;
			$excludes[] = $exclude;

			$total = count($pages);
			for ( $i = 0; $i < $total; $i++ ) {
				if ( isset($pages[$i]) && in_array($pages[$i]->ID, $excludes) )
					unset($pages[$i]);
			}
		}
		
		if ( ! empty( $append_page ) && ! empty( $pages ) ) {
			$found = false;
			foreach( array_keys($pages) as $key ) { 
				if ( $append_page->ID == $pages[$key]->ID ) {
					$found = true;
					break;
				}
			}
			
			if ( empty($found) )
				$pages []= $append_page;
		}
		
		// re-index the array, just in case anyone cares
        $pages = array_values($pages);
        
		$page_structure = array();
		foreach ( $pages as $page )
			$page_structure[] = $page->ID;

		wp_cache_set( $cache_key, $page_structure, 'posts' );
		
		// Convert to WP_Post instances
		$pages = array_map( 'get_post', $pages );
		
		// alternate hook name (WP core already applied get_pages filter)
		return apply_filters('pp_get_pages', $pages, $r);
	}
	
	public static function get_restriction_clause( $operation, $post_type, $args = array() ) {
		require_once( PPC_ABSPATH.'/exceptions_pp.php' );
		return PP_Exceptions::get_exceptions_clause( $operation, $post_type, $args );
	}
	
} // end class PP_Hardway

/*
// ( derived from WP core _get_term_hierarchy() )
// Removed option buffering since hierarchy is user-specific (get_terms query will be wp-cached anyway)
// Also adds support for taxonomies that don't use wp_term_taxonomy schema
function pp_get_terms_children( $taxonomy, $option_value = '' ) {
	require_once( PPC_ABSPATH.'/lib/ancestry_lib_pp.php' );
	return PP_Ancestry::get_terms_children( $taxonomy, $option_value );
}
*/
