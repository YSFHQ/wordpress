<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class PP_PostEditUI {
	var $item_roles_ui;

	function __construct() {
		wp_enqueue_style( 'pp-item-edit', PP_URLPATH . '/admin/css/pp-item-edit.css', array(), PPC_VERSION );
	
		add_action( 'admin_head', array(&$this, 'admin_head' ) );
	
		add_action( 'admin_menu', array(&$this, 'add_meta_boxes') );
		add_action( 'do_meta_boxes', array(&$this, 'act_prep_metaboxes') );
		
		//$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
		//wp_enqueue_script( 'pp_agent_search', PP_URLPATH . "/admin/js/listbox{$suffix}.js", array('jquery', 'jquery-form'), PPC_VERSION );   // this caused redundant loading
		
		require_once( dirname(__FILE__).'/item-edit-ui_pp.php' );
		add_action( 'admin_print_scripts', 'pp_item_edit_js' );
		
		add_action( 'admin_print_footer_scripts', array( &$this, 'edit_parent_link_js' ) );
		add_action( 'admin_print_footer_scripts', array( &$this, 'force_autosave_before_upload') );
		
		do_action( 'pp_post_edit_ui' );
	}

	function init_item_exceptions_ui() {
		if ( empty($this->item_exceptions_ui) ) {
			include_once( dirname(__FILE__).'/item-exceptions-ui_pp.php');
			$this->item_exceptions_ui = new PP_ItemExceptionsUI();
		}
	}
	
	function admin_head() {
		if ( current_user_can( 'pp_manage_settings' ) && ( ! defined('PPCE_VERSION') || ! defined('PPS_VERSION') ) && pp_get_option('display_extension_hints') ) {
			require_once( dirname(__FILE__).'/post-edit-ui-hints_pp.php');
			_pp_post_status_promo();
		}
	}
	
	function add_meta_boxes() {
		// ========= register WP-rendered metaboxes ============
		$post_type = pp_find_post_type();
		
		if ( ! current_user_can('pp_assign_roles') || apply_filters( 'pp_disable_exception_ui', false, 'post', 0, $post_type ) )
			return;

		$hidden_types = apply_filters( 'pp_hidden_post_types', array() );
		if ( ! empty( $hidden_types[$post_type] ) )
			return;

		if ( ! in_array( $post_type, pp_get_enabled_post_types() ) ) {
			if ( ! in_array( $post_type, array( 'revision' ) ) && pp_get_option('display_hints') ) {
				$type_obj = get_post_type_object( $post_type );
				if ( $type_obj->public ) {
					if ( ! in_array( $post_type, apply_filters( 'pp_unfiltered_post_types', array() ) ) )
						add_meta_box( "pp_enable_type", __( 'Press Permit Settings', 'pp' ), array(&$this, 'draw_settings_ui'), $post_type, 'advanced', 'default', array() );
				}
			}
			return;
		}
			
		//if ( ! $this->_roles_editable( $src_name, $object_type ) )
		//	return;
		
		$ops = _pp_can_set_exceptions( 'read', $post_type, array( 'via_item_source' => 'post', 'for_item_source' => 'post' ) ) ? array( 'read' => true ) : array();
		$operations = apply_filters( 'pp_item_edit_exception_ops', $ops, 'post', $post_type );
		
		foreach( array_keys($operations) as $op ) {
			if ( $op_obj = pp_get_op_object( $op, $post_type ) ) {
				// $screen = null, $context = 'advanced', $priority = 'default', $callback_args = null
				add_meta_box( "pp_{$op}_{$post_type}_exceptions", sprintf( __( '%s Exceptions', 'pp' ), $op_obj->noun_label ), array(&$this, 'draw_exceptions_ui'), $post_type, 'advanced', 'default', array( 'op' => $op ) );
			}
		}
	}
	
	function act_prep_metaboxes() {
		global $pagenow;
		
		if ( 'edit.php' == $pagenow )
			return;
		
		static $been_here;
		if ( isset($been_here) ) return;
		$been_here = true;

		global $typenow;	// pp_find_post_type();

		if ( ! in_array( $typenow, pp_get_enabled_post_types() ) || in_array( $typenow, array( 'revision' ) ) )
			return;
		
		//$can_admin_object = pp_is_user_administrator() || $pp_admin->user_can_admin_object('post', $object_id, $object_type); 
		
		if ( current_user_can( 'pp_assign_roles' ) ) {
			$this->init_item_exceptions_ui();
	
			$args = array( 'post_types' => (array) $typenow, 'hierarchical' => is_post_type_hierarchical($typenow) );	// via_src, for_src, via_type, item_id, args
			$this->item_exceptions_ui->data->load_exceptions( 'post', 'post', $typenow, pp_get_post_id(), $args );
		}
	}
	
	function draw_settings_ui( $object, $box ) {
		if ( $type_obj = get_post_type_object( $object->post_type ) ) :?>
			<label for="pp_enable_post_type"><input type="checkbox" name="pp_enable_post_type" id="pp_enable_post_type" /> <?php printf( __( 'enable custom permissions for %s', 'pp' ), $type_obj->labels->name );?></label>
		<?php endif;
	}
	
	// wrapper function so we don't have to load item_roles_ui class just to register the metabox
	function draw_exceptions_ui( $object, $box ) {
		if ( empty($box['id']) )
			return;

		$item_id = ( ! empty($object) && ( 'auto-draft' == $object->post_status ) ) ? 0 : $object->ID;

		$this->init_item_exceptions_ui();
		$args = array( 'via_item_source' => 'post', 'for_item_source' => 'post', 'for_item_type' => $object->post_type, 'via_item_type' => $object->post_type, 'item_id' => $item_id );
		$this->item_exceptions_ui->draw_exceptions_ui( $box, $args );
	}
	
	function edit_parent_link_js() {
		global $post;
		
		if ( empty( $post ) || ! is_post_type_hierarchical( $post->post_type ) || ! $post->post_parent || ! current_user_can( 'edit_post', $post->post_parent ) )
			return;
		?>
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready( function($) {
		$('#pageparentdiv div.inside p').first().wrapInner('<a href="post.php?post=<?php echo $post->post_parent;?>&amp;action=edit">');
});
/* ]]> */
</script>
<?php
	} // end function
	
	function force_autosave_before_upload() {  // under some configuration, it is necessary to pre-assign categories. Autosave accomplishes this by triggering save_post action handlers.
		if ( ! pp_unfiltered() ) : ?>
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready( function($) {
	$( '#wp-content-media-buttons a').click( function() {
		if ( $('#post-status-info span.autosave-message').html() == '&nbsp;' ) {
			autosave();
		}
	});
});
/* ]]> */
</script>
		<?php endif;
	} // end function

} // end class
