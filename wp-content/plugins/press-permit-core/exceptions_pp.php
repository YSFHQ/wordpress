<?php
class PP_Exceptions {
	public static function get_exceptions_clause( $operation, $post_type, $args = array() ) {
		$defaults = array( 'col_id' => 'ID', 'user' => 0 );
		$args = array_merge( $defaults, $args );
		extract( $args, EXTR_SKIP );

		if ( ! $user ) {
			global $pp_current_user;
			$user = $pp_current_user;
		}

		// Note: this does not apply term exceptions (not needed for current implementation, which only uses this function for 'associate' op )
		
		$additional_ids = $user->get_exception_posts( $operation, 'additional', $post_type );

		if ( $include_ids = $user->get_exception_posts( $operation, 'include', $post_type ) ) {
			if ( $additional_ids )
				$include_ids = array_unique( array_merge( $include_ids, $additional_ids ) );
			
			$where = " AND $col_id IN ('" . implode("','", array_unique($include_ids) ) . "')";
		} elseif ( $exclude_ids = array_diff( $user->get_exception_posts( $operation, 'exclude', $post_type ), $additional_ids ) ) {
			$where = " AND $col_id NOT IN ('" . implode("','", $exclude_ids) . "')";
		} else
			$where = '';

		return $where;
	}
	
	public static function add_exception_clauses( $where, $required_operation, $post_type, $args = array() ) {
		$defaults = array( 'source_alias' => '', 'apply_term_restrictions' => true, 'append_post_type_clause' => true, 'additions_only' => false );
		extract( array_merge( $defaults, $args ), EXTR_SKIP );
		
		global $wpdb, $pp_current_user;
		
		$src_table = ( $source_alias ) ? $source_alias : $wpdb->posts;
		
		$exc_post_type = apply_filters( 'pp_exception_post_type', $post_type, $required_operation, $args );
		
		if ( ! $additions_only ) {
			if ( $where ) {	 // where clause already indicates sitewide caps for one or more statuses (or just want the exceptions clause generated)
				if ( $append_clause = apply_filters( 'pp_append_query_clause', '', $post_type, $required_operation, $args ) ) {
					$where .= $append_clause;
				}
				
				foreach( array( 'include' => 'IN', 'exclude' => 'NOT IN' ) as $mod => $logic ) {
					if ( $ids = $pp_current_user->get_exception_posts( $required_operation, $mod, $exc_post_type ) ) {
						$_args = array_merge( $args, compact( 'mod', 'ids', 'src_table', 'logic' ) );
						$where .= " AND " . apply_filters( 'pp_exception_clause', "$src_table.ID $logic ('" . implode( "','", $ids ) . "')", $required_operation, $post_type, $_args );
						break;  // don't use both include and exclude clauses
					}
				}
				
				// term restrictions which apply only to this post type
				if ( $apply_term_restrictions )
					$where .= self::add_term_restrictions_clause( $required_operation, $post_type, $src_table );
			} else {
				$where = '1=2';
			}
		}
	
		$additions = array();
		$additional_ids = $pp_current_user->get_exception_posts( $required_operation, 'additional', $exc_post_type, array( 'status' => true ) );
		
		foreach( $additional_ids as $_status => $_ids ) {
			if ( $_status ) {	// db storage is with "post_status:" prefix to allow for implementation of other attributes
				if ( 0 === strpos( $_status, 'post_status:' ) )
					$_status = str_replace( 'post_status:', '', $_status );
				else
					continue;
			}

			if ( ! isset($additions[$_status] ) )
				$additions[$_status] = array();
			
			// facilitates user-add on post edit form without dealing with status caps
			$in_clause = "IN ('" . implode( "','", $_ids ) . "')";
			$additions[$_status][] = apply_filters( 'pp_additions_clause', "$src_table.ID $in_clause", $required_operation, $post_type, array( 'via_item_source' => 'post', 'status' => $_status, 'in_clause' => $in_clause, 'src_table' => $src_table ) );
		}
		
		$additional_ttids = array();
		foreach( pp_get_enabled_taxonomies( array( 'object_type' => $post_type ) ) as $taxonomy ) {
			$tt_ids = $pp_current_user->get_exception_terms( $required_operation, 'additional', $post_type, $taxonomy, array( 'status' => true, 'merge_universals' => true ) );

			// merge this taxonomy exceptions with other taxonomies
			foreach( array_keys($tt_ids) as $_status ) {
				if ( ! isset( $additional_ttids[$_status] ) )
					$additional_ttids[$_status] = array();

				$additional_ttids[$_status] = array_merge( $additional_ttids[$_status], $tt_ids[$_status] );
			}
		}
		
		if ( $additional_ttids ) {
			foreach( $additional_ttids as $_status => $_ttids ) {
				if ( $_status ) {
					if ( 0 === strpos( $_status, 'post_status:' ) )
						$_status = str_replace( 'post_status:', '', $_status );
					else
						continue;
				}
			
				if ( ! isset( $additions[$_status] ) )
					$additions[$_status] = array();
			
				$in_clause = "IN ( SELECT object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id IN ('" . implode( "','", $_ttids ) . "') )";
				$additions[$_status][] = apply_filters( 'pp_additions_clause', "$src_table.ID $in_clause", $required_operation, $post_type, array( 'via_item_source' => 'term', 'status' => $_status, 'in_clause' => $in_clause, 'src_table' => $src_table ) );
			}
		}
		
		foreach( array_keys($additions) as $_status ) {
			switch( $_status ) {
				case '':
					$_status_clause = '';
					break;
			
				case '{unpublished}':
					if ( 'read' != $required_operation ) { // sanity check
						$_stati = array_merge( pp_get_post_stati( array( 'public' => true, 'post_type' => $post_type ) ), pp_get_post_stati( array( 'private' => true, 'post_type' => $post_type ) ) );
						$_status_clause = "$src_table.post_status NOT IN ('" . implode( "','", $_stati ) . "') AND ";
						break;
					}
				default:
					$_status_clause = "$src_table.post_status = '$_status' AND ";
					break;
			}
	
			$additions[$_status] = $_status_clause . pp_implode( ' OR ', $additions[$_status] );
		}
		
		if ( $additions = apply_filters( 'pp_apply_additions', $additions, $where, $required_operation, $post_type, $args ) ) {
			$where = "( $where ) OR ( " . pp_implode( ' OR ', $additions ) . " )";

			/*
			$additions = pp_implode( ' OR ', $additions );
			
			if ( $append_post_type_clause )
				$where = "$src_table.post_type = '$post_type' AND ( ( $where ) OR ( $additions ) )";
			else
				$where = "( $where ) OR ( $additions )";
		
		} elseif ( $append_post_type_clause ) {
			$where = "$src_table.post_type = '$post_type' AND $where";
			*/
		}
		
		if ( $append_post_type_clause )
			$where = "$src_table.post_type = '$post_type' AND ( $where )";
	
		return $where;
	}
	
	public static function add_term_restrictions_clause( $required_operation, $post_type, $src_table, $args = array() ) {		
		global $wpdb, $pp_current_user;
		
		extract( array_merge( array( 'merge_additions' => false ), $args ), EXTR_SKIP );
		
		$where = '';
		$excluded_ttids = array();
		
		$tx_args = ( $post_type ) ? array( 'object_type' => $post_type ) : array();
		
		foreach( pp_get_enabled_taxonomies( $tx_args ) as $taxonomy ) {
			$tx_additional_ids = ( $merge_additions ) ? $pp_current_user->get_exception_terms( $required_operation, 'additional', $post_type, $taxonomy, array( 'status' => '', 'merge_universals' => true ) ) : array();

			// post may be required to be IN a term set for one taxonomy, and NOT IN a term set for another taxonomy
			foreach( array( 'include', 'exclude' ) as $mod ) {
				if ( $tt_ids = $pp_current_user->get_exception_terms( $required_operation, $mod, $post_type, $taxonomy, $args ) ) {
					if ( 'include' == $mod ) {
						if ( $tx_additional_ids )
							$tt_ids = array_merge( $tt_ids, $tx_additional_ids );
						
						$term_include_clause = apply_filters( 'pp_term_include_clause', "$src_table.ID IN ( SELECT object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id IN ('" . implode( "','", $tt_ids ) . "') )", compact( 'tt_ids', 'src_table' ) );
						$where .= " AND ( $term_include_clause )";
						
						continue 2;
					} else {
						if ( $tx_additional_ids )
							$tt_ids = array_diff( $tt_ids, $tx_additional_ids );

						$excluded_ttids = array_merge( $excluded_ttids, $tt_ids );
					}
				}
			}
		}
		
		if ( $excluded_ttids ) {
			$where .= " AND $src_table.ID NOT IN ( SELECT object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id IN ('" . implode( "','", $excluded_ttids ) . "') )";
		}
			
		return $where;
	}
	
	
} // end class
