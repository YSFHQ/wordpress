<?php
function _pp_file_filtering_promo() {
	if ( 0 === validate_plugin( "pp-file-url-filter/pp-file-url-filter.php" ) )
		$msg = __( 'To block direct URL access to attachments of unreadable posts, activate the PP File URL Filter plugin.', 'pp' );
	elseif( true == pp_key_status() )
		$msg = sprintf( __( 'To block direct URL access to attachments of unreadable posts, %1$sinstall%2$s the PP File URL Filter plugin.', 'pp' ), '<a href="admin.php?page=pp-settings&pp_tab=install">', '</a>' );
	else
		$msg = sprintf( __( 'To block direct URL access to attachments of unreadable posts, %1$senter%2$s or %3$spurchase%4$s a support key and install the PP File URL Filter plugin.', 'pp' ), '<a href="admin.php?page=pp-settings&pp_tab=install">', '</a>', '<a href="http://presspermit.com/purchase">', '</a>' );
	
	?>
	<script type="text/javascript">
	/* <![CDATA[ */
	jQuery(document).ready( function($) {
	$('#posts-filter').after('<a href="#" class="pp-file-filtering-promo" style="margin-left:5px"><?php _e('Block URL access', 'pp');?></a><span class="pp-ext-promo" style="display:none;"><?php echo $msg;?></span>');
	
	$('a.pp-file-filtering-promo').click(function() {
		$(this).hide().next('span').show();
		return false;
	});
	
	});
	/* ]]> */
	</script>
	<?php
}
