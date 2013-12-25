<?php
class PP_FiltersUpdateCore {
	function __construct() {
		add_filter( 'site_transient_update_plugins', array( &$this, 'bulk_update_remove_pp' ) );
	}
	
	function bulk_update_remove_pp( $plugin_updates ) {
		global $pagenow, $pp_extensions;
		
		if ( is_object($plugin_updates) && ! empty($plugin_updates->response) ) {
			$num_pp_updates = ( isset( $plugin_updates->response[ constant('PPC_BASENAME') ] ) ) ? 1 : 0;

			foreach( $pp_extensions as $ext_slug => $ext ) {
				if ( isset( $plugin_updates->response[ $ext->basename ] ) )
					$num_pp_updates++;
			}
			
			if ( $num_pp_updates && ( 'update-core.php' == $pagenow && did_action('admin_notices') ) ) {  // prevent interaction with "update core" mechanism or its menu indicator
				unset( $plugin_updates->response[ constant('PPC_BASENAME') ] );

				foreach( $pp_extensions as $ext_slug => $ext ) {
					unset( $plugin_updates->response[ $ext->basename ] );
				}
				
				if ( 'update-core.php' == $pagenow && did_action('admin_notices') ) {
					$this->num_pp_updates = $num_pp_updates;
					add_action( 'admin_footer', array( &$this, 'pp_update_links' ) );
				}
			}
		}

		return $plugin_updates;
	}

	function pp_update_links() {
		?>
	<script type="text/javascript">
	/* <![CDATA[ */
	jQuery(document).ready( function($) {
	$('#upgrade-plugins-2').parent().after('<br /><div id="pp_updates"><?php printf( _n('%1$sPress Permit%2$s: %3$s%4$s update available%5$s', '%1$sPress Permit%2$s: %3$s%4$s updates available%5$s', $this->num_pp_updates, 'pp'), '<strong>', '</strong>', '<a href="admin.php?page=pp-settings&tab=install">', $this->num_pp_updates, '</a>');?></div><br />');
	});
	/* ]]> */
	</script>
		<?php
	}
}
