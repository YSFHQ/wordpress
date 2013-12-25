<?php
class PP_TermsAdmin {
	var $exceptions = array();

	function __construct() {
		if ( constant('PP_ENABLE_QUERYFILTERS') ) {
			add_action( 'admin_print_footer_scripts', array(&$this, 'hide_main_option_js' ) );
			add_action( 'admin_print_footer_scripts',  array(&$this, 'resize_js' ) );
			add_action( 'admin_print_footer_scripts',  array(&$this, 'misc_js' ) );
		}	

		if ( empty($_REQUEST['tag_ID']) ) {
			$taxonomy = pp_sanitize_key($_REQUEST['taxonomy']);
			add_filter( "manage_edit-{$taxonomy}_columns",  array(&$this, 'define_cols' ) ); // actual filter handle as of WP 3.4
			add_filter( "manage_{$taxonomy}_columns",  array(&$this, 'define_cols' ) );
			add_filter( "manage_{$taxonomy}_custom_column",  array(&$this, 'custom_column'), 10, 3 );
	
			add_action( 'after-' . $taxonomy . '-table', array(&$this, 'show_notes') );
		}
	}
	
	function show_notes() {
		global $typenow;
		
		if ( empty($_REQUEST['pp_universal']) ) {
			$taxonomy = pp_sanitize_key($_REQUEST['taxonomy']);
			$tx_obj = get_taxonomy( $taxonomy );
			$type_obj = get_post_type_object( $typenow );
			$url = "edit-tags.php?taxonomy=$taxonomy&pp_universal=1";
			?>
			<div class="form-wrap"><p>
			<?php
			printf( __( 'Listed exceptions are those assigned for the "%1$s" type. You can also %2$sdefine universal %3$s exceptions which apply to all related post types%4$s.', 'pp' ), $type_obj->labels->singular_name, "<a href='$url'>", $tx_obj->labels->singular_name, '</a>' );
			?>
			</p></div>
			<?php
		}
	}
	
	function define_cols( $columns ) {
		global $typenow;
		
		if ( empty($_REQUEST['pp_universal']) ) {
			$taxonomy = pp_sanitize_key($_REQUEST['taxonomy']);
			$type_obj = get_post_type_object( $typenow );
			$title = __( 'Click to list/edit universal exceptions', 'pp' );
			$lbl = ( $type_obj && $type_obj->labels ) ? $type_obj->labels->singular_name : '';
			$caption = sprintf( __('%1$s Exceptions %2$s*%3$s', 'pp'), $lbl, "<a href='edit-tags.php?taxonomy=$taxonomy&pp_universal=1' title='$title'>", '</a>' );
		} else {
			$caption = __('Universal Exceptions', 'pp');
		}
	
		return array_merge( $columns, array( 'pp_exceptions' => $caption ) );
	}

	function custom_column( $val, $column_name, $id ) {
		if ( 'pp_exceptions' != $column_name )
			return;

		static $got_data;
		if ( empty( $got_data ) ) {
			$this->log_term_data();
			$got_data = true;
		}

		global $taxonomy;
		$id = pp_termid_to_ttid( $id, $taxonomy );
		
		if ( ! empty( $this->exceptions[$id] ) ) {
			global $typenow;
			
			$op_names = array();
			
			foreach( $this->exceptions[$id] as $op ) {
				if ( $op_obj = pp_get_op_object( $op, $typenow ) )
					$op_names []= $op_obj->label;
			}

			uasort( $op_names, 'strnatcasecmp' );
			echo implode(", ", $op_names);
		}
	}
	
	function log_term_data() {
		global $wp_object_cache, $wpdb, $pp, $typenow;

		$taxonomy = pp_sanitize_key($_REQUEST['taxonomy']);
		
		if ( ! empty( $wp_object_cache->cache[ $taxonomy ] ) ) {
			if ( isset($wp_object_cache->cache[ $taxonomy ]) ) {	// Note: As of WP 3.5, array is keyed "blog_id:term_id" on Multisite installs 
				$listed_term_ids = array();
				foreach( $wp_object_cache->cache[ $taxonomy ] as $term ) {
					$listed_tt_ids[]= $term->term_taxonomy_id;
				}
			}

			if ( empty($_REQUEST['paged']) ) {
				$listed_tt_ids []= 0;
			}
		} else
			return;
		
		if ( empty($typenow) && empty($_REQUEST['pp_universal']) )
			$typenow = ( isset( $_REQUEST['post_type'] ) ) ? pp_sanitize_key($_REQUEST['post_type']) : '';
		
		$this->exceptions = array();
		
		if ( ! empty($listed_tt_ids) ) {
			$agent_type_csv = implode( "','", array_merge( array( 'user' ), pp_get_group_types() ) );
			$id_csv = implode( "','", $listed_tt_ids );
			$post_type = ( ! empty($_REQUEST['pp_universal']) ) ? '' : $typenow;

			$for_type_csv = ( $typenow ) ? "'$post_type'" : "'', '$taxonomy'";
			$results = $wpdb->get_results( "SELECT DISTINCT i.item_id, e.operation FROM $wpdb->ppc_exceptions AS e INNER JOIN $wpdb->ppc_exception_items AS i ON e.exception_id = i.exception_id WHERE e.for_item_source = 'post' AND e.for_item_type IN ($for_type_csv) AND e.via_item_source = 'term' AND e.via_item_type = '$taxonomy' AND e.agent_type IN ('$agent_type_csv') AND i.item_id IN ('$id_csv')" );
			
			foreach( $results as $row ) {
				if ( ! isset( $this->exceptions[$row->item_id] ) )
					$this->exceptions[$row->item_id] = array();
				
				$this->exceptions[$row->item_id] []= $row->operation;
			}
		}
	}

	function resize_js() {
	?>
	<script type="text/javascript">
	/* <![CDATA[ */
	jQuery(document).ready( function($) {
		$('#col-left').css('width','25%');
		$('#col-right').css('width','75%');
		$('.column-slug').css('width','15%');
		$('.column-posts').css('width','10%');
	});
	/* ]]> */
	</script>
	<?php
	}
	
	function misc_js() {
		if ( empty($_REQUEST['pp_universal'] ) )
			return;
	?>
	<script type="text/javascript">
	/* <![CDATA[ */
	function updateQueryStringParameterPP(uri, key, value) { <?php /* http://stackoverflow.com/a/6021027 */ ?>
	  var re = new RegExp("([?|&])" + key + "=.*?(&|$)", "i");
	  separator = uri.indexOf('?') !== -1 ? "&" : "?";
	  if (uri.match(re)) {
		return uri.replace(re, '$1' + key + "=" + value + '$2');
	  }
	  else {
		return uri + separator + key + "=" + value;
	  }
	}
	
	jQuery(document).ready( function($) {
		$('#the-list tr').each(function(i,e){ 
			$(e).find("a.row-title,span.edit a").each(function(ii,ee){ 
				$(ee).attr('href', $(ee).attr('href') + '&pp_universal=1' );
			});
		});
	});
	/* ]]> */
	</script>
	<?php
	}

	// In "Add New Term" form, hide the "Main" option from Parent dropdown if the logged user doesn't have manage_terms cap site-wide
	function hide_main_option_js() {
		if ( ! empty($_REQUEST['action']) && ( 'edit' == $_REQUEST['action'] ) )
			return;
		
		if ( ! empty( $_REQUEST['taxonomy'] ) ) {  // using this with edit-link-categories
			if ( $tx_obj = get_taxonomy( $_REQUEST['taxonomy'] ) )
				$cap_name = $tx_obj->cap->manage_terms;
		}

		if ( empty($cap_name) )
			$cap_name = 'manage_categories';

		global $pp_current_user;
			
		if ( ! empty( $pp_current_user->allcaps ) )
			return;
	?>
	<script type="text/javascript">
	/* <![CDATA[ */
	jQuery(document).ready( function($) {
		$('#parent option[value="-1"]').remove();
	});
	/* ]]> */
	</script>
	<?php
	}
} // end class
