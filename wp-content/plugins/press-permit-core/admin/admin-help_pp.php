<?php
class PP_AdminHelp {
	public static function register_contextual_help() {
		$screen_obj = get_current_screen();
		if ( is_object($screen_obj) )
			$screen = $screen_obj->id;
		
		if ( strpos( $screen, 'pp-' ) ) {
			$match = array();
			if ( ! preg_match( "/admin_page_pp-[^@]*-*/", $screen, $match ) )
				if ( ! preg_match( "/_page_pp-[^@]*-*/", $screen, $match ) )
					preg_match( "/pp-[^@]*-*/", $screen, $match );

			if ( $match )
				if ( $pos = strpos( $match[0], 'pp-' ) ) {
					$link_section = substr( $match[0], $pos + strlen('pp-') );
					$link_section = str_replace( '_t', '', $link_section );	
				}
					
		} elseif ( in_array( $screen_obj->base, array( 'post', 'page', 'upload', 'users', 'edit-tags', 'edit' ) ) ) {
			$link_section = $screen_obj->base;
		}
		
		if ( ! empty($link_section) ) {
			$screen_obj->add_help_tab( array( 
			   'id' => 'pp',            //unique id for the tab
			   'title' => __('Press Permit Help', 'pp'),      //unique visible title for the tab
			   'content' => '',  //actual help text
			   'callback' => array( 'PP_AdminHelp', '_pp_show_contextual_help' ), //optional function to callback
			) );
		}
	}
	
	public static function _pp_show_contextual_help() {
		$screen_obj = get_current_screen();
		
		$link_section = '';

		if ( is_object($screen_obj) )
			$screen = $screen_obj->id;
		
		if ( strpos( $screen, 'pp-' ) ) {
			$match = array();
			if ( ! preg_match( "/admin_page_pp-[^@]*-*/", $screen, $match ) )
				if ( ! preg_match( "/_page_pp-[^@]*-*/", $screen, $match ) )
					preg_match( "/pp-[^@]*-*/", $screen, $match );

			if ( $match )
				if ( $pos = strpos( $match[0], 'pp-' ) ) {
					$link_section = substr( $match[0], $pos + strlen('pp-') );
					$link_section = str_replace( '_t', '', $link_section );	
				}
					
		} elseif ( in_array( $screen_obj->base, array( 'post', 'page', 'upload', 'users', 'edit-tags', 'edit' ) ) ) {
			$link_section = $screen_obj->base;
		}

		if ( $link_section ) {
			$link_section = str_replace( '.php', '', $link_section);
			$link_section = str_replace( '/', '~', $link_section);
			
			$help = '';
			
			if ( in_array( $screen, array( 'post', 'page' ) ) ) {
				global $post;
				$id_arg = "&amp;post_id=" . $post->ID;
			} elseif ( in_array( $screen_obj->base, array( 'edit-tags' ) ) && ! empty( $_REQUEST['tag_ID'] ) ) {
				if ( $term = get_term( $_REQUEST['tag_ID'], $_REQUEST['taxonomy'] ) )
					$id_arg = "&amp;term_taxonomy_id=" . $term->term_taxonomy_id;
			} else
				$id_arg = '';
			
			$help .= '<ul><li>' . sprintf(__('%1$s Press Permit Documentation%2$s', 'pp'), "<a href='http://presspermit.com/docs/?pp_topic=$link_section' target='_blank'>", '</a>')
			. '</li><li>' . sprintf(__('%1$s Press Permit Support Forums%2$s', 'pp'), "<a href='http://presspermit.com/forums/?pp_topic=$link_section' target='_blank'>", '</a>')
			. '</li><li>' . sprintf(__('%1$s Press Permit Support Forums (with config data upload) *%2$s', 'pp'), "<a href='admin.php?page=pp-settings&amp;pp_support_forum=1&amp;pp_topic=$link_section{$id_arg}' target='_blank'>", '</a></li>')
			;
			
			$key = pp_get_option( 'support_key' );
			if ( ! $key || ( 1 != $key[0] ) )
				$help .= '<li>' . sprintf(__('%1$s Buy a Press Permit Support Key%2$s', 'pp'), "<a href='http://presspermit.com/purchase/'>", '</a></li>');
			
			$help .= '</ul>';
			
			$help .= '<div style="margin-left:20px">';
			$help .= __( '* to control which configuration data is uploaded, see Permissions > Settings > Install > Help', 'pp' );
			$help .= '</div>';
		}
		
		echo $help;
	}
}
