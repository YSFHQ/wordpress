<?php
	// output restriction attributes JS and (if user can administer roles) role assignment js
	function pp_item_edit_js($object_type = '') {	
		if ( ! $object_type )
			$object_type = pp_find_post_type();
		
		if ( in_array( $object_type, array( 'revision' ) ) )
			return;
		
		$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
		wp_enqueue_script( 'pp-item-edit', PP_URLPATH . "/admin/js/pp-item-edit{$suffix}.js", array(), PPC_VERSION );
		
		if ( taxonomy_exists($object_type) )
			$type_obj = get_taxonomy($object_type);
		else
			$type_obj = get_post_type_object( $object_type );
		
		if ( $type_obj ) {
			//$arr = array( 'hierarchical' => $type_obj->hierarchical );
			//wp_localize_script( 'pp_attributes', 'ppItem', $arr );

?>
<script type="text/javascript">
/* <![CDATA[ */
var pp_hier_type='<?php echo $type_obj->hierarchical;?>';
/* ]]> */
</script>
<?php
		}
	}
