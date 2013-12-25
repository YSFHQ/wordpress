<?php
/**
 * PP_TermsInterceptor class
 * 
 * @package PP
 * @author Kevin Behrens <kevin@agapetry.net>
 * @copyright Copyright (c) 2011-2013, Agapetry Creations LLC
 * 
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class PP_TermsInterceptor {
	var $no_filter = false;
	
	function __construct() {
		add_filter('get_terms_args', array(&$this, 'flt_get_terms_args'), 50, 2);
		add_filter('terms_clauses', array(&$this, 'flt_terms_clauses'), 50, 3);
		
		if ( pp_is_front() && ! pp_unfiltered() )
			add_filter('get_the_terms', array(&$this, 'flt_get_the_terms'), 10, 3 );
	}
	
	function flt_get_the_terms( $terms, $post_id, $taxonomy ) {
		static $busy;
		if ( ! empty( $busy ) ) { return; }
		$busy = true;	// @todo: necessary?
	
		if ( $terms ) {
			global $pp_current_user;
		
			if ( ! $_post = get_post( $post_id ) )
				return $terms;
		
			if ( ! apply_filters( 'pp_skip_the_terms_filtering', false, $post_id ) ) {
				if ( $restrict_ids = $pp_current_user->get_exception_terms( 'read', 'include', $_post->post_type, $taxonomy, array( 'merge_universals' => true, 'return_term_ids' => true ) ) ) {
					foreach( array_keys($terms) as $key )
						if ( ! in_array( $terms[$key]->term_id, $restrict_ids ) ) { unset( $terms[$key] ); }
				} elseif ( $restrict_ids = $pp_current_user->get_exception_terms( 'read', 'exclude', $_post->post_type, $taxonomy, array( 'merge_universals' => true, 'return_term_ids' => true ) ) ) {
					if ( $add_ids = $pp_current_user->get_exception_terms( 'read', 'additional', $_post->post_type, $taxonomy, array( 'merge_universals' => true, 'return_term_ids' => true ) ) )
						$restrict_ids = array_diff( $restrict_ids, $add_ids );
					
					foreach( array_keys($terms) as $key )
						if ( in_array( $terms[$key]->term_id, $restrict_ids ) ) { unset( $terms[$key] ); }
				}
			}
		}

		$busy = false;
		return $terms;
	}
	
	function skip_filtering( $taxonomies, $args ) {
		if ( ! $taxonomies = array_intersect( $taxonomies, pp_get_enabled_taxonomies() ) )
			return true;

		if ( ! empty( $args['pp_no_filter'] ) || $this->no_filter || apply_filters( 'pp_terms_skip_filtering', false, $taxonomies, $args ) )
			return true;
			
		if ( 'id=>parent' == $args['fields'] ) // internal storage of {$taxonomy}_children
			return true;
	}
	
	function flt_get_terms_args( $args, $taxonomies ) {
		if ( $this->skip_filtering( $taxonomies, $args ) )
			return $args;
		
		$pp_defaults = array( 'skip_teaser' => false,		'post_type' => '',		'required_operation' => '' );
		$args = wp_parse_args( $args, $pp_defaults );
		
		if ( ( 'all' == $args['fields'] ) || $args['hide_empty'] || $args['pad_counts'] ) {
			require_once( dirname(__FILE__).'/terms-interceptor-counts_pp.php' );
			$args = PP_TermCountInterceptor::flt_get_terms_args( $args );
		}
		
		return $args;
	}
	
	function flt_terms_clauses($clauses, $taxonomies, $args) {
		if ( $skip = $this->skip_filtering( $taxonomies, $args ) )
			return $clauses;
			
		global $pp_current_user;
		
		if ( ( 'all' == $args['fields'] ) || $args['hide_empty'] || $args['pad_counts'] ) {
			require_once( dirname(__FILE__).'/terms-interceptor-counts_pp.php' );	// adds get_terms filter to adjust post counts based on current user's access and pad_counts setting
			$clauses = PP_TermCountInterceptor::flt_terms_clauses( $clauses, $args );
		}
		
		if ( empty($args['required_operation']) ) {
			$args['required_operation'] = apply_filters( 'pp_get_terms_operation', pp_is_front() ? 'read' : 'assign', $taxonomies, $args );
		}
		
		// must consider all related post types when filtering terms list
		// NOTE: If hide_empty is true, additional filtering will be applied to the results based on a full posts query.  Posts may have direct restrictions which make them inaccessable regardless of term restrictions.
		$all_excluded_ttids = array();

		$enabled_types = pp_get_enabled_post_types();
		$required_operation = $args['required_operation'];
		
		foreach( $taxonomies as $taxonomy ) {
			$excluded_ttids = $included_ttids = array();
			$any_non_inclusions = false;
			
			if ( ! in_array( $required_operation, array( 'manage', 'associate' ) ) ) {
				$universal = array();
				$universal['include'] = $pp_current_user->get_exception_terms( $required_operation, 'include', '', $taxonomy );
				$universal['exclude'] = ( $universal['include'] ) ? array() : $pp_current_user->get_exception_terms( $required_operation, 'exclude', '', $taxonomy );
				$universal['additional'] = $pp_current_user->get_exception_terms( $required_operation, 'additional', '', $taxonomy );

				$universal = apply_filters( 'pp_get_terms_universal_exceptions', $universal, $required_operation, $taxonomy, $args );

				if ( ! empty( $args['object_type'] ) )
					$exception_types = array_intersect( (array) $args['object_type'], $enabled_types );
				else {
					$tx = get_taxonomy( $taxonomy );
					$exception_types = array_intersect( (array) $tx->object_type, $enabled_types );
				}
			} else {
				$universal = array_fill_keys( array( 'include', 'exclude', 'additional' ), array() );
				$exception_types = array( $taxonomy );
			}
			
			foreach( $exception_types as $post_type ) {
				$additional_tt_ids = apply_filters( 'pp_get_terms_additional', array_merge( $universal['additional'], $pp_current_user->get_exception_terms( $required_operation, 'additional', $post_type, $taxonomy ) ), $required_operation, $post_type, $taxonomy, $args );

				foreach( array( 'include', 'exclude' ) as $mod_type ) {
					$args['additional_tt_ids'] = $additional_tt_ids;
					$tt_ids = apply_filters( 'pp_get_terms_exceptions', $pp_current_user->get_exception_terms( $required_operation, $mod_type, $post_type, $taxonomy ), $required_operation, $mod_type, $post_type, $taxonomy, $args );
					
					// remove type-specific inclusions from universal exclusions
					if( 'include' == $mod_type )
						$universal['exclude'] = array_diff( $universal['exclude'], $tt_ids );
					
					// merge type-specific exceptions with universal
					if ( $tt_ids = array_merge( $universal[$mod_type], $tt_ids ) ) {
						// add additional terms to includes set, and remove from exclude set (but only if include/exclude set exists to begin with)
						if ( $additional_tt_ids )
							$tt_ids = ( 'include' == $mod_type ) ? array_merge( $tt_ids, $additional_tt_ids ) : array_diff( $tt_ids, $additional_tt_ids );
					}
					
					if ( $tt_ids ) {
						if ( 'include' == $mod_type ) {
							$included_ttids = array_merge( $included_ttids, $tt_ids );
							
							if ( count( $exception_types ) > 1 )
								continue 2;  // don't support simultaneous include and exclude terms for the same taxonomy (but do support term assign exclusion even if an edit inclusion is set for the same term)
						} else {
							$excluded_ttids []= $tt_ids;
							$any_non_inclusions = true;
						}
					} else {
						$any_non_inclusions = true;
						
						if ( 'exclude' == $mod_type )
							$excluded_ttids []= array();
					}
				}
			}
			
			if ( $excluded_ttids ) {
				// don't exclude a term unless it is excluded for all post types
				$exc = $excluded_ttids[0];
				for( $i=1; $i < count($excluded_ttids); $i++ ) {
					$exc = array_intersect( $exc, $excluded_ttids[$i] );
				}
				
				if ( $exc ) {
					// but don't exclude a term which is explicitly included for one or more post types
					if ( count( $exception_types ) > 1 )
						$all_excluded_ttids = array_merge( $all_excluded_ttids, array_diff( $exc, $included_ttids ) );
					else
						$all_excluded_ttids = array_merge( $all_excluded_ttids, $exc );
				}
			}
			
			if ( $included_ttids ) {
				if ( empty($wrapped) ) {
					$clauses['where'] = "( {$clauses['where']} )";
					$wrapped = true;
				}
			
				// include terms were specified for all post types
				if ( count($taxonomies) == 1 )
					$clauses['where'] = " ( " . $clauses['where'] . " ) AND tt.term_taxonomy_id IN ('" . implode( "','", array_unique($included_ttids) ) . "')";
				else
					$clauses['where'] .= " AND ( tt.taxonomy != '$taxonomy' OR tt.term_taxonomy_id IN ('" . implode( "','", array_unique($included_ttids) ) . "') )";
			}
		}
		
		if ( $all_excluded_ttids ) {
			if ( empty($wrapped) ) {
				$clauses['where'] = "( {$clauses['where']} )";
				$wrapped = true;
			}
			
			$clauses['where'] .= " AND tt.term_taxonomy_id NOT IN ('" . implode( "','", $all_excluded_ttids ) . "')";
		}

		return $clauses;
	}
}
