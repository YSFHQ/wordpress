<?php
require_once( dirname(__FILE__).'/terms-query-lib_pp.php');

// flt_get_terms is required on the front end (even for administrators) so private posts are included in count, as basis for display when hide_empty arg is used
add_filter('get_terms', array( 'PP_TermCountInterceptor', 'flt_get_terms' ), 0, 3);   // WPML registers at priority 1

add_filter('get_terms_fields', array( 'PP_TermCountInterceptor', 'flt_get_terms_fields' ), 99, 2 );

class PP_TermCountInterceptor {
	// force get_pages to return all fields without post-processing results
	public static function flt_get_terms_fields( $fields, $args ) {
		if ( 'force_all' == $args['fields'] )
			$fields = array('t.*', 'tt.*');

		return $fields;
	}

	public static function flt_get_terms_args( $args ) {
		$args['child_of'] = (int) $args['child_of'];  // null value will confuse subsequent PP checks
	
		// turn off core post-processing of the result set, but buffer actual arg values for equivalent PP post-processing
		$buffer_args = array();
		foreach ( array( 'child_of' => 0, 'pad_counts' => false, 'hide_empty' => false, 'number' => '' ) as $arg_name => $force_val ) {
			if ( $args[$arg_name] != $force_val ) {
				$buffer_args[$arg_name] = $args[$arg_name];
				$args[$arg_name] = $force_val;
			}
		}
		
		if ( in_array( $args['fields'], array( 'ids', 'names', 'id=>parent' ) ) ) {
			// flt_get_terms() needs intermediate result set to be term objects with count property, even if final result set will be ids only.  We will convert result set back to expected format before returning.
			// This also prevents WP core get_terms() from padding term counts needlessly
			$buffer_args['fields'] = $args['fields'];
			$args['fields'] = 'force_all';
		}
		
		if ( $buffer_args )
			$args['actual_args'] = $buffer_args;
	
		return $args;
	}
	
	public static function flt_terms_clauses( $clauses, $args ) {
		extract( $args, EXTR_SKIP );

		if ( $hide_empty && ( empty($actual_args) || ! $actual_args['hide_empty'] ) && ! $hierarchical ) {	// hide_empty may have been set by flt_get_terms_args()
			$clauses['where'] .= ' AND tt.count > 0';
		}
		
		// we forced $args['fields'] to disable WP post-processing of result set. Now filter actual fields clause back to original.
		if ( isset($actual_args['fields']) ) {
			switch ( $actual_args['fields'] ) {
				case 'all':
					$selects = array('t.*', 'tt.*');
					break;
				case 'ids':
				case 'id=>parent':
					$selects = array('t.term_id', 'tt.parent', 'tt.count');
					break;
				case 'names':
					$selects = array('t.term_id', 'tt.parent', 'tt.count', 't.name');
					break;
				case 'count':
					//$orderby = '';
					//$order = '';
					$selects = array('COUNT(*)');
			}

			$clauses['fields'] = implode(', ', apply_filters( 'get_terms_fields', $selects, $args ));
			//$fields = $actual_args['fields'];	// for term_taxonomy_id check below
		}

		return $clauses;
	}

	public static function flt_get_terms($terms, $taxonomies, $args) {
		if ( ! is_array($terms) )
			return $terms;
		
		if ( empty($terms) )
			return array();
			
		global $terms_interceptor;
		if ( $terms_interceptor->skip_filtering( $taxonomies, $args ) )
			return $terms;

		extract( $args, EXTR_SKIP );
		
		if ( 'ids' == $fields )
			return $terms;
		
		// if some args were forced to prevent core post-processing, restore actual values now
		if ( ! empty( $args['actual_args'] ) )
			extract( $args['actual_args'] );

		if ( 'all' == $fields ) {
			// buffer term names in case they were filtered previously
			$term_names = pp_get_property_array( $terms, 'term_id', 'name' );
		}

		if ( ! empty($child_of) ) {
			$children = _get_term_hierarchy( reset($taxonomies) );
			if ( ! empty($children) )
				$terms = _get_term_children( $child_of, $terms, reset($taxonomies) );
		}
		
		// Replace DB-stored term counts with actual number of posts this user can read.
		// In addition, without the pp_tally_term_counts() call, WP will hide terms that have no public posts (even if this user can read some of the pvt posts).
		// Post counts will be incremented to include child terms only if $pad_counts is true
		if ( ! defined('XMLRPC_REQUEST') && ( 1 == count($taxonomies) ) ) {
			global $pagenow;
			if ( ! is_admin() || ! in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) ) {
				if ( $hide_empty || ! empty( $args['actual_args']['hide_empty'] ) ) {
					// need to tally for all terms in case some were hidden by core function due to lack of public posts
					$all_terms = get_terms( reset($taxonomies), array( 'fields' => 'all', 'pp_no_filter' => true, 'hide_empty' => false ) );
					PP_TermsQueryLib::tally_term_counts( $all_terms, reset($taxonomies), compact( 'pad_counts', 'skip_teaser', 'post_type' ) );

					foreach ( array_keys($terms) as $k ) {
						foreach( array_keys($all_terms) as $key ) {
							if ( $all_terms[$key]->term_taxonomy_id == $terms[$k]->term_taxonomy_id ) {
								$terms[$k]->count = $all_terms[$key]->count;
								break;
							}
						}
					}
				} else {
					PP_TermsQueryLib::tally_term_counts( $terms, reset($taxonomies), compact( 'pad_counts', 'skip_teaser', 'post_type' ) );
				}
			}
		}
		
		if ( $hide_empty || ! empty( $args['actual_args']['hide_empty'] ) ) {
			if ( $hierarchical ) {
				foreach( $taxonomies as $taxonomy ) {
					if ( empty($all_terms) || ( count($taxonomies) > 1 ) )
						$all_terms = get_terms( $taxonomy, array( 'fields' => 'all', 'pp_no_filter' => true, 'hide_empty' => false ) );
					
					// Remove empty terms, but only if their descendants are all empty too.
					foreach ( $terms as $k => $term ) {
						if ( ! $term->count ) {					
							if ( $descendants = _get_term_children( $term->term_id, $all_terms, $taxonomy ) ) {
								foreach ( $descendants as $child ) {
									if ( $child->count )
										continue 2;
								}
							}

							// It really is empty
							unset($terms[$k]);
						}
					}
				}
			} else {		
				foreach ( $terms as $key => $term )
					if ( ! $term->count )
						unset( $terms[$key] );
			}
		}
		
		if ( $hierarchical && ! $parent && ( count($taxonomies) == 1 ) ) {
			require_once( PPC_ABSPATH . '/lib/ancestry_lib_pp.php' );
			$ancestors = PP_Ancestry::get_term_ancestors( reset($taxonomies) ); // array of all ancestor IDs for keyed term_id, with direct parent first
			$remap_args = array_merge( compact( 'child_of', 'parent', 'exclude' ), array( 'orderby' => 'name', 'col_id' => 'term_id', 'col_parent' => 'parent' ) );
			PP_Ancestry::remap_tree( $terms, $ancestors, $remap_args );
		}
		
		reset ( $terms );
		
		// === Standard WP post-processing for include, fields, number args ===
		//		
		$_terms = array();
		if ( 'id=>parent' == $fields ) {
			while ( $term = array_shift($terms) )
				$_terms[$term->term_id] = $term->parent;
			$terms = $_terms;
		} elseif ( 'ids' == $fields ) {
			while ( $term = array_shift($terms) )
				$_terms[] = $term->term_id;
			$terms = $_terms;
		} elseif ( 'names' == $fields ) {
			while ( $term = array_shift($terms) )
				$_terms[] = $term->name;
			$terms = $_terms;
		}
			
		if ( 0 < $number && intval(@count($terms)) > $number ) {
			$terms = array_slice($terms, $offset, $number);
		}
		// === end standard WP block ===
		
		// restore buffered term names in case they were filtered previously
		if ( 'all' == $fields )
			pp_restore_property_array( $terms, $term_names, 'term_id', 'name' );

		return $terms;
	}
} // end class
