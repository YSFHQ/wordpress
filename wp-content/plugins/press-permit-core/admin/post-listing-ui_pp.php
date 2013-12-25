<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class PP_PostsAdmin {
	var $exceptions;

	function __construct() {
		$post_type = ( isset($_REQUEST['post_type']) ) ? $_REQUEST['post_type'] : 'post';

		wp_enqueue_style( 'pp-post-listing', PP_URLPATH . '/admin/css/pp-post-listing.css', array(), PPC_VERSION );
	
		add_filter("manage_{$post_type}_posts_columns", array(&$this, 'flt_manage_posts_columns'), 50 ); // need late priority to avoid being wiped by other plugins
		add_action("manage_{$post_type}_posts_custom_column", array(&$this, 'flt_manage_posts_custom_column'), 10, 2);

		do_action('pp_post_listing_ui');

		add_filter( 'posts_fields', array( &$this, 'posts_fields' ) );	 // perf
	}

	// perf enhancement: query only fields which pertain to listing or quick edit
	function posts_fields($cols) {
		global $typenow, $pp;
		
		if ( defined( 'PP_LEAN_' . strtoupper($typenow) . '_LISTING' ) && $pp->filtering_enabled ) {
			global $wpdb;
			$p = $wpdb->posts;
			$cols = str_replace( "$p.*", "$p.ID, $p.post_author, $p.post_date, $p.post_date_gmt, $p.post_title, $p.post_status, $p.comment_status, $p.ping_status, $p.post_password, $p.post_name, $p.to_ping, $p.pinged, $p.post_parent, $p.post_modified, $p.post_modified_gmt, $p.guid, $p.post_type, $p.post_mime_type, $p.menu_order, $p.comment_count", $cols );
		}

		return $cols;
	}
	
	function flt_manage_posts_columns($columns) {
		if ( current_user_can( 'pp_assign_roles' ) ) {
			if ( ! isset( $this->exceptions ) ) {
				global $wpdb, $pp, $typenow;
				
				$this->exceptions = array();
				
				if ( ! empty($pp->listed_ids[$typenow]) ) {
					$agent_type_csv = implode( "','", array_merge( array( 'user' ), pp_get_group_types() ) );
					
					$id_csv = implode( "','", array_keys($pp->listed_ids[$typenow]) );
					$results = $wpdb->get_results( "SELECT DISTINCT i.item_id, e.operation FROM $wpdb->ppc_exceptions AS e INNER JOIN $wpdb->ppc_exception_items AS i ON e.exception_id = i.exception_id WHERE e.for_item_source = 'post' AND e.via_item_source = 'post' AND e.agent_type IN ('$agent_type_csv') AND i.item_id IN ('$id_csv')" );
					
					foreach( $results as $row ) {
						if ( ! isset( $this->exceptions[$row->item_id] ) )
							$this->exceptions[$row->item_id] = array();
						
						$this->exceptions[$row->item_id] []= $row->operation;
					}
				}
			}
			
			if ( $this->exceptions )
				$columns['pp_exceptions'] = __( 'Exceptions', 'pp' );
		}

		return $columns;
	}
	
	function flt_manage_posts_custom_column($column_name, $id) {
		if ( 'pp_exceptions' == $column_name ) {
			if ( ! empty( $this->exceptions[$id] ) ) {
				global $typenow;
			
				$op_names = array();
				
				foreach( $this->exceptions[$id] as $op ) {
					if ( $op_obj = pp_get_op_object( $op, $typenow ) ) {
						$op_names []= ( isset( $op_obj->abbrev ) ) ? $op_obj->abbrev : $op_obj->label;
					}
				}

				uasort( $op_names, 'strnatcasecmp' );
				echo implode(", ", $op_names);
			}
		}
	}
	
} // end class
