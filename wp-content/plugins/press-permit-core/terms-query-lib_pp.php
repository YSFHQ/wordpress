<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class PP_TermsQueryLib {
	// derived from WP 3.4 _pad_term_counts(), but includes private posts in counts based on current user's access 
	public static function tally_term_counts( &$terms, $taxonomy, $args = array() ) {
		global $wpdb, $pp_current_user;
		
		if ( ! $terms )
			return;

		$defaults = array ( 'pad_counts' => true, 'post_type' => '', 'required_operation' => '' );
		$args = array_merge( $defaults, (array) $args );
		extract($args);

		$term_items = array();

		if ( $terms ) {
			if ( ! is_object( reset($terms) ) )
				return $terms;
		
			foreach ( (array) $terms as $key => $term ) {
				$terms_by_id[$term->term_id] = & $terms[$key];
				$term_ids[$term->term_taxonomy_id] = $term->term_id;
			}
		}

		// Get the object and term ids and stick them in a lookup table
		$tax_obj = get_taxonomy($taxonomy);
		
		$object_types = ( $post_type ) ? $post_type : esc_sql($tax_obj->object_type);

		if ( pp_unfiltered() ) {
			$stati = get_post_stati( array( 'public' => true, 'private' => true ), 'names', 'or' );
			$type_status_clause = "AND post_type IN ('" . implode("', '", $object_types) . "') AND post_status IN ('" . implode("', '", $stati) . "')";
		} else {
			global $query_interceptor;
			$type_status_clause = $query_interceptor->get_posts_where( array( 'post_types' => $object_types, 'required_operation' => $required_operation ) ); // need to apply term restrictions in case post is restricted by another taxonomy
		}

		if ( ! $required_operation )
			$required_operation = ( pp_is_front() && ! is_preview() ) ? 'read' : 'edit';

		$results = $wpdb->get_results( "SELECT object_id, term_taxonomy_id FROM $wpdb->term_relationships INNER JOIN $wpdb->posts ON object_id = ID WHERE term_taxonomy_id IN ('" . implode("','", array_keys($term_ids)) . "') $type_status_clause" );
		foreach ( $results as $row ) {
			$id = $term_ids[$row->term_taxonomy_id];
			$term_items[$id][$row->object_id] = isset($term_items[$id][$row->object_id]) ? ++$term_items[$id][$row->object_id] : 1;
		}

		// Touch every ancestor's lookup row for each post in each term
		foreach ( $term_ids as $term_id ) {
			$child = $term_id;
			while ( !empty( $terms_by_id[$child] ) && $parent = $terms_by_id[$child]->parent ) {
				if ( !empty( $term_items[$term_id] ) )
					foreach ( $term_items[$term_id] as $item_id => $touches ) {
						$term_items[$parent][$item_id] = isset($term_items[$parent][$item_id]) ? ++$term_items[$parent][$item_id]: 1;
					}
				$child = $parent;
			}
		}

		foreach( array_keys($terms_by_id) as $key )
			$terms_by_id[$key]->count = 0;
		
		// Transfer the touched cells
		foreach ( (array) $term_items as $id => $items )
			if ( isset($terms_by_id[$id]) )
				$terms_by_id[$id]->count = count($items);
	}
} // end class
