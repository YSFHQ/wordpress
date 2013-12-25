<?php
class PP_TermEditUI {
	var $item_roles_ui;

	function __construct() {
		wp_enqueue_script('post');
		wp_enqueue_script('postbox');
		
		wp_enqueue_style( 'pp-item-edit', PP_URLPATH . '/admin/css/pp-item-edit.css', array(), PPC_VERSION );
		
		//$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
		//wp_enqueue_script( 'pp_agent_search', PP_URLPATH . "/admin/js/listbox{$suffix}.js", array('jquery', 'jquery-form'), PPC_VERSION );  // this caused redundant loading
	
		require_once( dirname(__FILE__).'/item-edit-ui_pp.php' );
		add_action( 'admin_print_scripts', 'pp_item_edit_js' );
		
		add_action( 'admin_menu', array(&$this, 'add_meta_boxes') );
		
		if ( ! empty($_REQUEST['taxonomy']) ) {
			if ( pp_is_taxonomy_enabled($_REQUEST['taxonomy']) ) {
				add_action( 'admin_head', array( &$this, 'wp_scripts' ) );

				add_action( 'edit_category_form', array( &$this, 'exception_edit_ui' ) );
				add_action( 'edit_tag_form', array( &$this, 'exception_edit_ui' ) );
			} else {
				add_action( 'edit_category_form', array( &$this, 'tx_enable_ui' ) );
				add_action( 'edit_tag_form', array( &$this, 'tx_enable_ui' ) );
			}
			
			if ( ! empty($_REQUEST['pp_universal']) ) {
				add_action( 'edit_category_form', array( &$this, 'pp_universal_hidden_input' ) );
				add_action( 'edit_tag_form', array( &$this, 'pp_universal_hidden_input' ) );
			}
		}

		do_action( 'pp_term_edit_ui' );
	}
	
	function add_meta_boxes() {
		// ========= register WP-rendered metaboxes ============
		global $typenow;

		$taxonomy = ( isset($_REQUEST['taxonomy']) ) ? pp_sanitize_key($_REQUEST['taxonomy']) : '';

		if ( ! in_array( $taxonomy, pp_get_enabled_taxonomies() ) )
			return;
		
		$tt_id = ( ! empty( $_REQUEST['tag_ID'] ) ) ? pp_termid_to_ttid( (int) $_REQUEST['tag_ID'], $taxonomy ) : 0;
		
		$post_type = ( ! empty($_REQUEST['pp_universal']) ) ? '' : $typenow;
		
		$hidden_types = apply_filters( 'pp_hidden_post_types', array() );
		$hidden_taxonomies = apply_filters( 'pp_hidden_taxonomies', array() );
		if ( ! empty( $hidden_taxonomies[$taxonomy] ) || ( $post_type && ! empty( $hidden_types[$post_type] ) ) )
			return;
		
		//TODO: selectively enable role assignment by non-administrator
		if ( ! current_user_can('pp_assign_roles') || apply_filters( 'pp_disable_exception_ui', false, 'term', $tt_id, $post_type ) )
			return;
		
		//if ( ! $this->_roles_editable( $src_name, $object_type ) )
		//	return;

		$tx = get_taxonomy( $taxonomy );
		$type_obj = get_post_type_object( $post_type );
		$register_type = ( $post_type ) ? $post_type : 'post';
		
		$ops = _pp_can_set_exceptions( 'read', $post_type, array( 'via_item_source' => 'term', 'via_item_type' => $taxonomy, 'for_item_source' => 'post' ) ) ? array( 'read' => true ) : array();
		$operations = apply_filters( 'pp_item_edit_exception_ops', $ops, 'post', $taxonomy, $post_type );
		
		$boxes = array();

		foreach( array_keys($operations) as $op ) {
			if ( $op_obj = pp_get_op_object( $op, $post_type ) ) {
				if ( 'assign' == $op )
					$title = ( $post_type ) ? sprintf( __( '%1$s %2$s %3$s Exceptions', 'pp' ), $type_obj->labels->singular_name, $tx->labels->singular_name, $op_obj->noun_label ) : sprintf( __( '%1$s %2$s Exceptions (all post types)', 'pp' ), $tx->labels->singular_name, $op_obj->noun_label );
				elseif ( in_array( $op, array( 'read', 'edit' ) ) )
					$title = ( $post_type ) ? sprintf( __( '%1$s %2$s Exceptions (all post statuses)', 'pp' ), $type_obj->labels->singular_name, $op_obj->noun_label ) : sprintf( __( '%1$s Exceptions (all post types, statuses)', 'pp' ), $op_obj->noun_label );
				else
					$title = ( $post_type ) ? sprintf( __( '%1$s %2$s Exceptions', 'pp' ), $type_obj->labels->singular_name, $op_obj->noun_label ) : sprintf( __( '%1$s Exceptions (all post types)', 'pp' ), $op_obj->noun_label );
				
				pp_set_array_elem( $boxes, array( $op, "pp_{$op}_{$post_type}_exceptions" ) );				
				$boxes[$op]["pp_{$op}_{$post_type}_exceptions"]['for_item_type'] = $post_type;
				$boxes[$op]["pp_{$op}_{$post_type}_exceptions"]['title'] = $title;
			}
		}

		$boxes = apply_filters( 'pp_term_exceptions_metaboxes', $boxes, $taxonomy, $post_type );
		
		foreach( $boxes as $op => $boxes ) {
			foreach( $boxes as $box_id => $_box ) {
				// $screen = null, $context = 'advanced', $priority = 'default', $callback_args = null
				add_meta_box( $box_id, $_box['title'], array(&$this, 'draw_exceptions_ui'), $register_type, 'normal', 'default', array( 'for_item_type' => $_box['for_item_type'], 'op' => $op ) );
			}
		}
	}
	
	function act_prep_metaboxes() {
		global $tag_ID;
		if ( empty($tag_ID) )
			return;
		
		static $been_here;
		if ( isset($been_here) ) return;
		$been_here = true;

		global $typenow;	// pp_find_post_type();

		$post_type = ( ! empty($_REQUEST['pp_universal']) ) ? '' : $typenow;
		$taxonomy = ( isset($_REQUEST['taxonomy']) ) ? pp_sanitize_key($_REQUEST['taxonomy']) : '';
		
		//$is_administrator = pp_is_user_administrator();
		//$can_admin_object = $is_administrator || $pp_admin->user_can_admin_object('post', $object_id, $object_type); 
		
		if ( current_user_can( 'pp_assign_roles' ) ) {
			$this->init_item_exceptions_ui();
	
			$tt_id = pp_termid_to_ttid( $tag_ID, $taxonomy );
	
			$args = array( 'for_item_type' => $post_type, 'hierarchical' => is_taxonomy_hierarchical($post_type) );	// via_src, for_src, via_type, item_id, args
			$this->item_exceptions_ui->data->load_exceptions( 'term', 'post', $taxonomy, $tt_id, $args );
			
			do_action( 'pp_prep_metaboxes', 'term', $taxonomy, $tt_id );
		}
	}

	function exception_edit_ui( $tag ) {
		global $taxonomy, $typenow;		// only deal with post type which edit form was linked from?
		
		if ( $typenow && ! in_array( $typenow, pp_get_enabled_post_types() ) )
			return;
		
		$post_type = ( ! empty($_REQUEST['pp_universal']) ) ? '' : $typenow;

		if ( ! current_user_can('pp_assign_roles') || apply_filters( 'pp_disable_exception_ui', false, 'term', $tag->term_taxonomy_id, $post_type ) )
			return;
		
		submit_button( __('Update') );

		if ( $post_type )
			self::universal_exceptions_note( $tag, $taxonomy, $post_type );
		?>
		<div id="poststuff" class="metabox-holder">
		<div id="post-body">
		<div id="post-body-content">
		<?php
		
		require_once( ABSPATH . 'wp-admin/includes/meta-boxes.php' );

		$this->act_prep_metaboxes();
		
		$type = ( $post_type ) ? $post_type : 'post';
		do_meta_boxes( $type, 'normal', $tag );
		
		?>
		</div> <!-- post-body-content -->
		</div> <!-- post-body -->
		</div> <!-- poststuff -->
		<?php
		
		if ( $post_type )
			self::universal_exceptions_note( $tag, $taxonomy, $post_type );
	}
	
	function universal_exceptions_note( $tag, $taxonomy, $post_type ) {		
		$tx_obj = get_taxonomy( $taxonomy );
		$type_obj = get_post_type_object( $post_type );
		?>
		<div class="form-wrap"><p>
		<?php 
		// if _wp_original_http_referer is not passed, redirect will be from universal exceptions edit form to type-specific exceptions edit form
		if ( ! $referer = wp_get_original_referer() )
			$referer = wp_get_referer();

		$url = add_query_arg( '_wp_original_http_referer', urlencode($referer), "edit-tags.php?action=edit&amp;taxonomy=$taxonomy&amp;tag_ID={$tag->term_id}&amp;pp_universal=1" );
		printf( __( 'Displayed exceptions are those assigned for the "%1$s" type. You can also %2$sdefine universal %3$s exceptions which apply to all related post types%4$s.', 'pp' ), $type_obj->labels->singular_name, "<a href='$url'>", $tx_obj->labels->singular_name, '</a>' );?>
		</p></div>
		<?php
	}
	
	function tx_enable_ui( $tag ) {
		global $taxonomy, $typenow;

		if ( $typenow && ! in_array( $typenow, pp_get_enabled_post_types() ) )
			return;
		
		?>
		<br /><br />
		<div id="poststuff" class="metabox-holder">
		<div id="post-body">
		<div id="post-body-content">
		<?php
		
		require_once( ABSPATH . 'wp-admin/includes/meta-boxes.php' );

		add_meta_box( "pp_enable_taxonomy", __( 'Press Permit Settings', 'pp' ), array(&$this, 'draw_settings_ui'), $taxonomy, 'normal', 'default', array() );
		do_meta_boxes( $taxonomy, 'normal', $tag );
		
		?>
		</div> <!-- post-body-content -->
		</div> <!-- post-body -->
		</div> <!-- poststuff -->
		<?php
		
		echo '<div style="clear:both">&nbsp;</div>';
	}
	
	function pp_universal_hidden_input() {
	?>
		<input type="hidden" name="pp_universal" value="1" />
	<?php
	}
	
	function draw_settings_ui( $term, $box ) {
		if ( $tx = get_taxonomy( $term->taxonomy ) ) :?>
			<label for="pp_enable_taxonomy"><input type="checkbox" name="pp_enable_taxonomy" /> <?php printf( __( 'enable custom permissions for %s', 'pp' ), $tx->labels->name );?></label>
		<?php endif;
	}
	
	// wrapper function so we don't have to load item_roles_ui class just to register the metabox
	function draw_exceptions_ui( $term, $box ) {
		if ( empty($box['id']) )
			return;

		$this->init_item_exceptions_ui();
		$args = array( 'via_item_source' => 'term', 'for_item_source' => 'post', 'for_item_type' => $box['args']['for_item_type'], 'via_item_type' => $term->taxonomy, 'item_id' => $term->term_taxonomy_id );
		$this->item_exceptions_ui->draw_exceptions_ui( $box, $args );
	}
	
	function init_item_exceptions_ui() {
		if ( empty($this->item_exceptions_ui) ) {
			include_once( dirname(__FILE__).'/item-exceptions-ui_pp.php');
			$this->item_exceptions_ui = new PP_ItemExceptionsUI();
		}
	}
	
	function wp_scripts() {
		wp_enqueue_script( 'common' );
		wp_enqueue_script( 'postbox' );
		add_thickbox();
		wp_enqueue_script('media-upload');

		//require_once( dirname(__FILE__).'/item-edit-ui_pp.php' );
		pp_item_edit_js($_REQUEST['taxonomy']);
	}
} // end class
