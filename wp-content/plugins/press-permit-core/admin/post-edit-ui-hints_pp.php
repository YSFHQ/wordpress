<?php
function _pp_post_status_promo() {
	if ( ! defined( 'PPS_VERSION' ) ) {	
		if ( 0 === validate_plugin( "pp-custom-post-statuses/pp-custom-post-statuses.php" ) )
			$msg = __( 'To define custom privacy statuses, activate the PP Custom Post Statuses plugin.', 'pp' );
		elseif( true == pp_key_status() )
			$msg = sprintf( __( 'To define custom privacy statuses, %1$sinstall%2$s the PP Custom Post Statuses plugin.', 'pp' ), '<a href="admin.php?page=pp-settings&pp_tab=install">', '</a>' );
		else
			$msg = sprintf( __( 'To define custom privacy statuses, %1$senter%2$s or %3$spurchase%4$s a support key and install the PP Custom Post Statuses plugin.', 'pp' ), '<a href="admin.php?page=pp-settings&pp_tab=install">', '</a>', '<a href="http://presspermit.com/purchase">', '</a>' );
		
		?>
		<script type="text/javascript">
		/* <![CDATA[ */
		jQuery(document).ready( function($) {
		$('#visibility-radio-private').next('label').after('<a href="#" class="pp-custom-privacy-promo" style="margin-left:5px"><?php _e('define custom privacy', 'pp');?></a><span class="pp-ext-promo" style="display:none;"><br /><?php echo $msg;?></span>');
		
		$('a.pp-custom-privacy-promo').click(function() {
			$(this).hide().next('span').show();
			return false;
		});
		
		});
		/* ]]> */
		</script>
		<?php
	}
	
	if ( ! defined( 'PPS_VERSION' ) || ! defined( 'PPCE_VERSION' ) ) {
		$need_exts = array();
		if ( ! defined( 'PPCE_VERSION' ) )
			$need_exts []= 'PP Collaborative Editing Pack';
		
		if ( ! defined( 'PPS_VERSION' ) )
			$need_exts []= 'PP Custom Post Statuses';
			
		$need_exts = implode( ' and ', $need_exts );
			
		if( true == pp_key_status() )
			$msg = sprintf( __( 'To define custom moderation statuses, %1$sactivate%2$s %3$s.', 'pp' ), '<a href="admin.php?page=pp-settings&pp_tab=install">', '</a>', $need_exts );
		else
			$msg = sprintf( __( 'To define custom moderation statuses, %1$senter%2$s or %3$spurchase%4$s a Press Permit support key. Then install %5$s.', 'pp' ), '<a href="admin.php?page=pp-settings&pp_tab=install">', '</a>', '<a href="http://presspermit.com/purchase">', '</a>', $need_exts );
			
		?>
		<script type="text/javascript">
		/* <![CDATA[ */
		jQuery(document).ready( function($) {
		$('a.edit-post-status').after('<a href="#" class="pp-custom-moderation-promo" style="margin-left:5px"><?php _e('Customize', 'pp');?></a><span class="pp-ext-promo" style="display:none;"><br /><?php echo $msg;?></span>');
		
		$('a.pp-custom-moderation-promo').click(function() {
			$(this).hide().next('span').show();
			return false;
		});
		
		});
		/* ]]> */
		</script>
		<?php
	}
}
