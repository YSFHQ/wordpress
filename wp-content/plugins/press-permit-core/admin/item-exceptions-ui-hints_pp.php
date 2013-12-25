<?php
function _ppc_item_ui_hints( $for_item_type ) {
	if ( ( 'attachment' == $for_item_type ) && ! defined( 'PPFF_VERSION' ) ) {
		if ( 0 === validate_plugin( "pp-file-url-filter/pp-file-url-filter.php" ) )
			$msg = __( 'To block direct access to unreadable files, activate the PP File URL Filter plugin.', 'pp' );
		elseif( true == pp_key_status() )
			$msg = sprintf( __( 'To block direct access to unreadable files, %1$sinstall%2$s the PP File URL Filter plugin.', 'pp' ), '<a href="admin.php?page=pp-settings&pp_tab=install">', '</a>' );
		else
			$msg = sprintf( __( 'To block direct access to unreadable files, %1$spurchase a support key%2$s and install the PP File URL Filter plugin.', 'pp' ), '<a href="http://presspermit.com/purchase">', '</a>' );

		echo "<div class='pp-ext-promo' style='padding:0.5em'>$msg</div>";
	}

	if ( ! defined( 'PPCE_VERSION' ) ) {	
		if ( 0 === validate_plugin( "pp-collaborative-editing/pp-collaborative-editing.php" ) )
			$msg = __( 'To customize editing permissions, activate the PP Collaborative Editing plugin.', 'pp' );
		elseif( true == pp_key_status() )
			$msg = sprintf( __( 'To customize editing permissions, %1$sinstall%2$s the PP Collaborative Editing plugin.', 'pp' ), '<a href="admin.php?page=pp-settings&pp_tab=install">', '</a>' );
		else
			$msg = sprintf( __( 'To customize editing permissions, %1$spurchase a support key%2$s and install the PP Collaborative Editing plugin.', 'pp' ), '<a href="http://presspermit.com/purchase">', '</a>' );
		
		echo "<div class='pp-ext-promo' style='padding:0.5em;margin-top:0'>$msg</div>";
	}
}
?>