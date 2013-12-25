<?php
class PP_QueryAttachments {
	public static function append_attachment_clause( $where, $clauses, $args ) {
		global $wpdb, $query_interceptor;

		if ( ! $where )
			return $where;
		
		if ( ! $args )
			$args = array();

		if ( empty( $args['src_table'] ) ) {
			$src_table = ( ! empty( $args['source_alias'] ) ) ? $args['source_alias'] : $wpdb->posts;
			$args['src_table'] = $src_table;
		} else
			$src_table = $args['src_table'];

		$_args = (array) $args;
		
		if ( $do_parent_subquery = ! isset($args['query_contexts']) || ! in_array( 'comments', (array) $args['query_contexts'] ) ) {  // comment queries don't append parent subquery, but do enforce edit_others_attached_files setting if PPCE is active
			// generate a subquery based on user's permissions to the post attachments are tied to
			$_post_types = array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) );

			
			if ( ! empty($args['limit_ids']) && ( 1 == count($args['limit_ids']) ) ) {
				if ( $_post = get_post( reset($args['limit_ids']) ) ) {
					if ( $_parent_type = array_intersect( $_post_types, (array) get_post_field( 'post_type', $_post->post_parent ) ) )
						$_post_types = $_parent_type;
				}
			}
			$_args['post_types'] = $_post_types; // set for parent subquery only
			$_args['source_alias'] = 'p';
			
			if ( ! empty($args['has_cap_check']) )
				unset( $_args['limit_statuses'] );
			
			if ( empty($_args['limit_statuses']) )
				$_args['skip_stati_usage_clause'] = true;
			
			$pp_where = $query_interceptor->flt_posts_where( '', $_args );
			$type_csv = implode( "','", $_post_types );
			$args['subqry'] = "SELECT ID FROM $wpdb->posts AS p WHERE 1=1 AND p.post_type IN ('$type_csv') $pp_where";		// pass this into filter even if not applying here
			$args['subqry_args'] = $_args;
			$args['subqry_typecsv'] = $type_csv;
		}

		//if ( ! isset( $args['required_operation'] ) )
		//	$args['required_operation'] = ( pp_is_front() && ! is_preview() ) ? 'read' : 'edit'; // TODO: just ensure this is passed through?
		
		if ( 'read' == $args['required_operation'] ) {
			global $current_user;
			
			$attached_vis_clause = ( $do_parent_subquery ) ? "$src_table.post_parent IN ({$args['subqry']})" : "1=1"; 
			$attached_vis_clause = apply_filters( 'pp_attached_visibility_clause', $attached_vis_clause, $clauses, $_args );
			
			$unattached_vis_clause = apply_filters( 'pp_unattached_visibility_clause', "$src_table.post_parent = '0'", $clauses, $_args );
			
			$own_clause = ( apply_filters( 'pp_read_own_attachments', false, $args ) ) ? "$src_table.post_author = '{$current_user->ID}' OR " : '';
			
			$where = str_replace( "$src_table.post_type = 'attachment'", "( $src_table.post_type = 'attachment' AND ( $own_clause ( $attached_vis_clause ) OR ( $unattached_vis_clause ) ) )", $where );
		}
	
		return apply_filters( 'pp_append_attachment_clause', $where, $clauses, $args );
	}
} // end class
