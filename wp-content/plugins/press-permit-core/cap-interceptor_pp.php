<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * PP_CapInterceptor class
 * 
 * @package PP
 * @author Kevin Behrens <kevin@agapetry.net>
 * @copyright Copyright (c) 2011-2013, Agapetry Creations LLC
 * 
 */
class PP_CapInterceptor
{	
	var $in_process = false;
	var $args = array();
	var $flags = array();						// $flags[flag_name] = boolean, reset after each user_has_cap call
	var $flag_defaults = array();
	var $meta_caps = array();
	var $cap_data_sources = array();
	
	// memory buffer to prevent redundant filtering / queries within a single http request
	var $memcache = array(); 		//  keys: 'cache_tested_ids', 'cache_okay_ids', 'cache_where_clause'
	
	function __construct() {
		$this->flag_defaults = array_fill_keys( array( 'memcache_disabled', 'force_memcache_refresh', 'cache_key_suffix' ), false );
		$this->flags = $this->flag_defaults;
		
		$this->meta_caps = apply_filters( 'pp_meta_caps', array( 'read_post' => 'read', 'read_page' => 'read' ) );
		
		$this->cap_data_sources = array();	// $arr[$cap_name] = src_name (but default to 'post' data source if unspecified)
		$this->cap_data_sources = apply_filters( 'pp_cap_data_sources', $this->cap_data_sources );
		
		// Since PP activation implies that this plugin should take custody
		// of access control, set priority high so we have the final say on has_cap filtering.
		// This filter will not mess with any caps which are not part of a PP role assignment or exception.
		add_filter( 'user_has_cap', array(&$this, 'flt_user_has_cap'), 99, 3 );  // apply PP filter last

		if ( defined( 'PP_DISABLE_CAP_CACHE' ) ) {
			$func = creation_function( '$a,$b,$c,$d,&$cap_interceptor', '$cap_interceptor->flags["memcache_disabled"]=true;' );
			add_action( 'pp_has_cap_pre', $func, 10, 5 );
		}
		
		do_action_ref_array( 'pp_cap_interceptor', array(&$this) );
	}
	
	// Capability filter applied by WP_User->has_cap (usually via WP current_user_can function)
	//
	// $wp_sitecaps = current user's site-wide capabilities
	// $reqd_caps = primitive capabilities being tested / requested
	// $args = array with:
	// 		$args[0] = original capability requirement passed to current_user_can (possibly a meta cap)
	// 		$args[1] = user being tested
	// 		$args[2] = post id (could be a post_id, link_id, term_id or something else)
	//
	function flt_user_has_cap( $wp_sitecaps, $orig_reqd_caps, $args ) {
		if ( $this->in_process || ! isset( $args[0] ) )
			return $wp_sitecaps;

		global $pp_current_user;
		
		if ( $args[1] != $pp_current_user->ID )
			return $wp_sitecaps;
			
		//$args[0] = (array) $args[0];
		$args = (array) $args;
		$orig_cap = reset($args);
		
		if ( isset( $args[2] ) ) {
			if ( is_object($args[2]) )
				$args[2] = ( isset($args[2]->ID) ) ? $args[2]->ID : 0;
		} else
			$item_id = 0;
		
		$item_id = ( isset( $args[2] ) ) ? $args[2] : 0;
	
		if ( ( 'read_post' == $orig_cap ) && ( count($orig_reqd_caps) > 1 || ( 'read' != reset($orig_reqd_caps) ) ) ) {
			global $pp;
			
			if ( ! $pp->direct_file_access ) {
				// deal with map_meta_cap() changing 'read_post' requirement to 'edit_post'			
				$types = get_post_types( array( 'public' => true ), 'object' );
				foreach( array_keys($types) as $_post_type ) {
					if ( array_intersect( array_intersect_key( (array) $types[$_post_type]->cap, array_fill_keys( array( 'edit_posts', 'edit_others_posts', 'edit_published_posts', 'edit_private_posts' ), true ) ), $orig_reqd_caps ) ) {
						$orig_cap = 'edit_post';
						break;
					}
				}
			}
		}

		if ( is_array($orig_cap) || ! isset( $this->meta_caps[ $orig_cap ] ) ) { // Revisionary may pass array into args[0]
	
			// If we would fail a straight post cap check, pass it if appropriate additions stored
			if ( array_diff( $orig_reqd_caps, array_keys( array_intersect( $wp_sitecaps, array( true, 1, '1' ) ) ) ) ) {
				global $pp_cap_helper;

				$is_post_cap = false;
				$item_type = '';
				
				if ( $type_caps = array_intersect( $orig_reqd_caps, array_keys( $pp_cap_helper->all_type_caps ) ) ) {
					if ( in_array( $orig_cap, array_keys($this->meta_caps) ) || in_array( $orig_cap, array_keys($pp_cap_helper->all_type_caps) ) ) {
						$is_post_cap = true;
						
						if ( ! $item_id && apply_filters( 'pp_do_find_post_id', true, $orig_reqd_caps, $args ) ) {
							if ( $item_id = pp_get_post_id() )
								$item_id = apply_filters( 'pp_get_post_id', $item_id, $orig_reqd_caps, $args );
						}

						// If a post id can be determined from url or POST, will check for exceptions on that post only
						// Otherwise, credit basic read / edit_posts / delete_posts cap if corresponding addition is stored for any post/category of this type
						if ( $item_id ) {
							if ( $_post = get_post( $item_id ) ) {
								$item_type = $_post->post_type;
								$item_status = $_post->post_status;
							}
						} else {
							$item_type = $this->post_type_from_caps( $type_caps );
							$item_status = '';
						}
						
						if ( $item_type && ! in_array( $item_type,  pp_get_enabled_post_types() ) )
							return $wp_sitecaps;
					}
				}
				
				if ( $params = apply_filters( 'pp_user_has_cap_params', array(), $orig_reqd_caps, compact( 'item_id', 'orig_cap', 'item_type' ) ) ) {
					extract( array_diff_key( $params, array_fill_keys( array( 'wp_sitecaps', 'pp_current_user', 'orig_cap', 'orig_reqd_caps' ), true ) ) );  // prevent some vars from being overwritten my extract
				
					//if ( isset( $params['return_caps'] ) )
					//	return $params['return_caps'];
				}
				
				if ( $type_caps ) {
					static $buffer_qualified;
					if ( ! isset($buffer_qualified) || $this->flags['memcache_disabled'] ) { $buffer_qualified = array(); }
					$bkey = $item_type . $item_id . md5( serialize($type_caps) );
					if ( ! empty( $buffer_qualified[$bkey] ) )
						return array_merge( $wp_sitecaps, array_fill_keys( $orig_reqd_caps, true ) );
					
					global $query_interceptor;
					
					if ( $is_post_cap && apply_filters( 'pp_use_post_exceptions', true, $item_type ) ) {
						$pass = false;
						$base_caps = $query_interceptor->get_base_caps( $type_caps, $item_type );
						
						/*
						if ( ! $item_id && array_diff( $type_caps, $base_caps ) ) {
							return $wp_sitecaps;	// don't credit edit_others, edit_private, etc. based on addition stored for another post (this was needed due to previous failure to apply post_id passed into has_cap filter). But it is problematic for edit.php (display of Mine for lack of edit_others_posts)
						}
						*/

						if ( count( $base_caps ) == 1 ) {
							$base_cap = reset( $base_caps );
							switch( $base_cap ) {
								case 'read' :
									$op = 'read';
									break;
								default :
									$op = apply_filters( 'pp_cap_operation', false, $base_cap, $item_type );
							}
							
							if ( $op ) {
								$_args = compact( 'item_type', 'orig_reqd_caps' );
								$valid_stati = apply_filters( 'pp_exception_stati', array_fill_keys( array( '', "post_status:$item_status" ), true ), $item_status, $op, $_args );
								
								$exc_post_type = apply_filters( 'pp_exception_post_type', $item_type, $op, $_args );
								
								if ( $additional_ids = $pp_current_user->get_exception_posts( $op, 'additional', $exc_post_type, array( 'status' => true ) ) )
									$additional_ids = pp_array_flatten( array_intersect_key( $additional_ids, $valid_stati ) );

								if ( $additional_ids ) {
									$has_post_additions = apply_filters( 'pp_has_post_additions', null, $additional_ids, $item_type, $item_id, compact('op','orig_reqd_caps') ); 
									if ( is_null( $has_post_additions ) )
										$has_post_additions = ( ! $item_id || in_array( $item_id, $additional_ids ) );
								} else
									$has_post_additions = false;
									
								if ( $has_post_additions ) {
									$pass = true;
								} else {
									if ( $enabled_taxonomies = pp_get_enabled_taxonomies( array( 'object_type' => $item_type ) ) ) {
										global $wpdb;
										
										if ( ! $item_id || $post_ttids = $wpdb->get_col( "SELECT tr.term_taxonomy_id FROM $wpdb->term_relationships AS tr INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id WHERE tt.taxonomy IN ('" . implode( "','", $enabled_taxonomies ) . "') AND tr.object_id = '$item_id'" ) ) {
											foreach( pp_get_enabled_taxonomies( array( 'object_type' => $item_type ) ) as $taxonomy ) {
												if ( $additional_tt_ids = $pp_current_user->get_exception_terms( $op, 'additional', $item_type, $taxonomy, array( 'status' => true ) ) )
													$additional_tt_ids = pp_array_flatten( array_intersect_key( $additional_tt_ids, $valid_stati ) );
				
												if ( $include_tt_ids = $pp_current_user->get_exception_terms( $op, 'include', $item_type, $taxonomy, array( 'status' => true ) ) )
													$additional_tt_ids = array_merge( $additional_tt_ids, pp_array_flatten( array_intersect_key( $include_tt_ids, $valid_stati ) ) );

												if ( $additional_tt_ids ) {
													if ( ! $item_id || array_intersect( $additional_tt_ids, $post_ttids ) ) {
														$pass = true;
														break;
													}
												}
											}
										}
									}
								}
							}
						}
					} else {
						if ( ! isset($params) )
							$params = apply_filters( 'pp_user_has_cap_params', array(), $orig_reqd_caps, compact( 'item_id', 'orig_cap', 'item_type' ) );
						
						$pass = apply_filters( 'pp_credit_cap_exception', false, array_merge( $params, compact( 'item_id' ) ) );
					}
					
					if ( $pass ) {
						$buffer_qualified[$bkey] = true;
						return array_merge( $wp_sitecaps, array_fill_keys( $orig_reqd_caps, true ) );
					}
				}
			} else {
				if ( $params = apply_filters( 'pp_user_has_cap_params', array(), $orig_reqd_caps, compact( 'item_id', 'orig_cap', 'item_type' ) ) ) {
					if ( empty($params['item_id']) )
						$params['item_id'] = ( $item_id ) ? $item_id : pp_get_post_id();

					$wp_sitecaps = apply_filters( 'pp_user_has_caps', $wp_sitecaps, $orig_reqd_caps, $params );
					extract( array_diff_key( $params, array_fill_keys( array( 'wp_sitecaps', 'pp_current_user', 'orig_cap', 'orig_reqd_caps' ), true ) ) );
				}
			}

			return $wp_sitecaps;
		}
		
		$this->in_process = true;
		$this->args = $args;
		
		$src_name = ( isset($this->cap_data_sources[ $orig_cap ]) ) ? $this->cap_data_sources[ $orig_cap ] : 'post';
		
		switch( $src_name ) {
			case 'post' :
				if ( ! isset($required_operation) )
					$required_operation = $this->meta_caps[ $orig_cap ];
				
				$return = $this->_flt_user_has_post_cap( $wp_sitecaps, $orig_reqd_caps, $args, compact( 'required_operation' ) );
				break;
			default:
		} // end switch
		
		$this->in_process = false;

		$this->flags = $this->flag_defaults;

		return $return;
	}
	
	// PP_CapInterceptor::_flt_user_has_post_cap
	//
	// The intent here is to add to $reqd_caps and/or $wp_sitecaps based on PP-assigned item attributes and role assignments
	//
	function _flt_user_has_post_cap( $wp_sitecaps, $orig_reqd_caps, $args, $pp_args ) {
		// ================= EARLY EXIT CHECKS (if the provided reqd_caps do not need filtering or need special case filtering ==================
		global $pp_current_user, $pagenow;
		
		if ( isset($args[1]) && ( $args[1] != $pp_current_user->ID ) )
			return $wp_sitecaps;
		// =================================================== (end early exit checks) ======================================================
		
		// ========================================== ARGUMENT TRANSLATION AND STATUS DETECTION =============================================
		$post_id = ( isset($args[2]) ) ? $args[2] : pp_get_post_id();

		$post_type = pp_find_post_type( $post_id ); // will be pulled from object

		$pp_reqd_caps = (array) $args[0]; // already cast to array
		
		//=== Allow PP extensions or other plugins to modify some variables
		//

		if ( $_vars = apply_filters_ref_array( 'pp_has_post_cap_vars', array( null, $wp_sitecaps, $pp_reqd_caps, array( 'post_type' => $post_type, 'post_id' => $post_id, 'user_id' => $args[1], 'required_operation' => $pp_args['required_operation'] ), &$this ) ) ) {
			extract( array_intersect_key( $_vars, array_fill_keys( array( 'post_type', 'post_id', 'pp_reqd_caps', 'return_caps', 'required_operation' ), 'true' ) ) );
		}

		if ( ! empty( $return_caps ) )
			return array_merge( $wp_sitecaps, $return_caps );
		
		if ( ! $post_id || ! in_array( $post_type, pp_get_enabled_post_types() ) )
			return $wp_sitecaps;

		extract( $pp_args, EXTR_SKIP );

		// Note: At this point, we have a nonzero post_id...
		do_action_ref_array( 'pp_has_post_cap_pre', array( $pp_reqd_caps, 'post', $post_type, $post_id, &$this ) );	// cache clearing / refresh forcing can be applied here
		
		$memcache_disabled = $this->flags['memcache_disabled'];
		
		// skip the memcache under certain circumstances
		if ( ! $memcache_disabled ) {
			if ( 
			( ! empty( $_POST ) && ( 'post.php' == $pagenow ) && pp_get_type_cap( $post_type, 'edit_post' ) == reset($pp_reqd_caps) )  				   // edit_post cap check on wp-admin/post.php submission   			
			|| ( ! empty($_GET['doaction']) && in_array( reset($pp_reqd_caps), array( 'delete_post', pp_get_type_cap( $post_type, 'delete_post' ) ) ) )  // bulk post/page deletion is broken by hascap buffering
			/* || 'async-upload.php' == $pagenow */
			) { 
				$this->memcache = array();
				$memcache_disabled = true;
			}
		}

		// ============ QUERY for required caps on object id (if other listed ids are known, query for them also).  Cache results to static var. ===============
		$query_args = array( 'required_operation' => $required_operation, 'post_types' => $post_type, 'skip_teaser' => true );

		// generate a string key for this set of required caps, for use below in checking, caching the filtered results
		$cap_arg = ( 'edit_page' == $args[0] ) ? 'edit_post' : $args[0]; // minor perf boost on uploads.php, TODO: move to PPCE
		$capreqs_key = ( $memcache_disabled ) ? false : $cap_arg . $this->flags['cache_key_suffix'] . md5(serialize($query_args));
		
		// Check whether this object id was already tested for the same reqd_caps in a previous execution of this function within the same http request
		if ( ! isset($this->memcache['tested_ids'][$post_type][$capreqs_key][$post_id]) || ! empty($this->flags['force_memcache_refresh']) ) {
			global $pp, $wpdb, $query_interceptor;
			
			// If this cap inquiry is for a single item but multiple items are being listed, we will query for the original metacap on all items (mapping it for each applicable status) and buffer the results
			//
			// (Perf enhancement when listing display will check caps for each item to determine whether to display an action link)
			//  note: don't use wp_object_cache because it includes posts not present in currently displayed resultset listing page
			if ( isset($pp->listed_ids[$post_type]) && ( ! is_admin() || ( 'index.php' != $pagenow ) ) )  // there's too much happening on the dashboard to buffer listed IDs reliably.
				$listed_ids = array_keys( $pp->listed_ids[$post_type] );
			else
				$listed_ids = array();
			
			// make sure our current post_id is in the query list
			$listed_ids[] = (int) $post_id;
			$listed_ids = array_unique( $listed_ids );
			
			//if ( ! isset( $this->memcache['request'][$capreqs_key] ) || $this->flags['force_memcache_refresh'] ) {
				//$query_args['listed_ids'] = $listed_ids;
				
				if ( count($listed_ids) == 1 )
					$query_args['limit_statuses'] = array( get_post_field( 'post_status', $post_id ) => true );
	
				$query_args['limit_ids'] = $listed_ids;
				$query_args['has_cap_check'] = $args[0];
				$request = $query_interceptor->construct_posts_request( array( 'fields' => "$wpdb->posts.ID" ), $query_args );

				// update static variable
				//if ( ! $memcache_disabled )
				//	$this->memcache['request'][$capreqs_key] = $request;
			//} else {
			//	$request = $this->memcache['request'][$capreqs_key];
			//}

			// run the query
			$request .= " AND $wpdb->posts.ID IN ('" . implode( "', '", $listed_ids ) . "')";
			$qkey = md5($request);
			
			// a different has_cap inquiry may have generated the same query, so check cache before executing the query
			if ( isset( $this->memcache['okay_ids'][$qkey] ) && ! $memcache_disabled ) {
				$okay_ids = $this->memcache['okay_ids'][$qkey];
			} else {
				$okay_ids = $wpdb->get_col($request);
				
				if ( ! $memcache_disabled )
					$this->memcache['okay_ids'][$qkey] = $okay_ids;
			}

			// update tested_ids cache to log filtered results for this post id, and possibly also for other listed IDs
			if ( ! $memcache_disabled ) {
				foreach ( $listed_ids as $_id )
					$this->memcache['tested_ids'][$post_type][$capreqs_key][$_id] = in_array( $_id, $okay_ids );
			}

			$this_id_okay = in_array( $post_id, $okay_ids );
		} else {
			// results of this has_cap inquiry are already stored (from another call within current http request)
			$this_id_okay = $this->memcache['tested_ids'][$post_type][$capreqs_key][$post_id];
		}

		do_action_ref_array( 'pp_has_post_cap_done', array( $this_id_okay, $pp_reqd_caps, 'post', $post_type, $post_id, &$this ) );	// followup to cache processing can be applied here
		
		if ( $this_id_okay ) {
			//d_echo( "PASSED for {$orig_reqd_caps[0]}<br />" );
			return array_merge( $wp_sitecaps, array_fill_keys( $orig_reqd_caps, true ) );
		} else {
			// ================= TEMPORARY DEBUG CODE ===================
			//dump($args);
			//dump($orig_reqd_caps);
			//dump($pp_reqd_caps);
			//d_echo( "user_has_cap FAILED ($post_id) !!!!!!!" );
			//d_echo( '<br />' );
			// ============== (end temporary debug code ==================

			return array_diff_key( $wp_sitecaps, array_fill_keys( $orig_reqd_caps, true ) );  // return user's sitewide caps, minus the caps we were checking for
		}
	}
	
	function post_type_from_caps( $caps ) {
		foreach( pp_get_enabled_post_types( array(), 'object' ) as $post_type => $type_obj ) {
			if ( array_intersect( (array) $type_obj->cap, $caps ) )
				return $post_type;
		}

		return false;
	}
} // end class PP_Cap_Interceptor
