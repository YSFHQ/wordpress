<?php
function pp_query_descendant_ids( $src_name, $parent_id, $args = array() ) {
	global $wpdb;

	$defaults = array( 'include_revisions' => false, 'include_attachments' => true, 'post_status' => false, 'append_clause' => '' );
	extract( array_merge( $defaults, $args ), EXTR_SKIP );
	
	$descendant_ids = array();
	$clauses = $append_clause;
	
	switch( $src_name ) {
		case 'post':
			$skip_types = array();
	
			if ( ! $include_revisions )
				$skip_types[]= 'revision';

			if ( ! $include_attachments )
				$skip_types[]= 'attachment';

			$clauses .= ( $skip_types ) ? "AND post_type NOT IN ('" . implode("','", $skip_types) . "')" : '';

			$clauses .= ( is_array($post_status) ) ? "AND post_status IN ('" . implode("','", $post_status) . "')" : '';

			$table = $wpdb->posts;
			$col_id = 'ID';
			$col_parent = 'post_parent';
			break;
	
		case 'term':
			$table = $wpdb->term_taxonomy;
			$col_id = 'term_id';
			$col_parent = 'parent';
			break;
	
		default:
			return array();
	}

	return _pp_do_query_descendant_ids( $table, $col_id, $col_parent, $parent_id, $clauses, $descendant_ids );
}
	
function _pp_do_query_descendant_ids( $table_name, $col_id, $col_parent, $parent_id, $type_clause, $descendant_ids = array() ) {
	global $wpdb;
	
	$descendant_ids = array();
	
	$parent_id = (int) $parent_id;
	if ( $results = $wpdb->get_col( "SELECT $col_id FROM $table_name WHERE $col_parent = '$parent_id' $type_clause" ) ) {
		foreach ( $results as $id ) {
			if ( ! in_array( $id, $descendant_ids ) ) {
				$descendant_ids []= $id;
				$next_generation = _pp_do_query_descendant_ids($table_name, $col_id, $col_parent, $id, $type_clause, $descendant_ids);
				$descendant_ids = array_unique( array_merge($descendant_ids, $next_generation) );
			}
		}
	}
	
	return $descendant_ids;
}
