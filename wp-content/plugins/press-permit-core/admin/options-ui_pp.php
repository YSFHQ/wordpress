<?php
class PP_OptionsUI {
	var $option_args;  // netwide / customize_defaults
	var $form_options;
	var $tab_captions;
	var $section_captions;
	var $option_captions;
	var $all_options;
	var $all_otype_options;
	var $display_hints;
	
	function __construct( $args = array() ) {
		if ( ! empty( $args['netwide'] ) )
			$args['customize_defaults'] = false;	// this is intended only for storing custom default values for site-specific options
		
		$this->option_args = $args;
	}
	
	function get_option( $option_basename ) {
		return pp_get_option( $option_basename, $this->option_args );
	}
	
	function get_option_array( $option_basename ) {
		$val = pp_get_option( $option_basename, $this->option_args );
		
		if ( ! $val || ! is_array($val) )
			$val = array();
			
		return $val;
	}
	
	function option_checkbox( $option_name, $tab_name, $section_name, $hint_text = '', $trailing_html = '', $args = array()) {
		$return = array( 'in_scope' => false, 'no_storage' => false, 'disabled' => false, 'title' => '' );
		
		if ( in_array( $option_name, $this->form_options[$tab_name][$section_name] ) ) {
			if ( empty($args['no_storage']) )
				$this->all_options []= $option_name;
			
			if ( isset( $args['val'] ) )
				$return['val'] = $args['val'];
			else
				$return['val'] = ( ! empty($args['no_storage']) ) ? 0 : pp_get_option($option_name, $this->option_args );
				
			$disabled_clause = ( ! empty($args['disabled']) || $this->hide_network_option($option_name) ) ? "disabled='disabled'" : '';
			$style = ( ! empty($args['style']) ) ? $args['style'] : '';
			
			$title = ( ! empty( $args['title'] ) ) ? " title='" . esc_attr($args['title']) . "'" : '';
			
			echo "<div class='agp-vspaced_input'>"
				. "<label for='$option_name'{$title}><input name='$option_name' type='checkbox' $disabled_clause $style id='$option_name' value='1' " . checked('1', $return['val'], false) . " /> "
				. $this->option_captions[$option_name]
				. "</label>";
				
				if ( $hint_text && $this->display_hints )
					echo "<div class='pp-subtext'>" . $hint_text . "</div>";
			echo "</div>";

			if ( $trailing_html )
				echo $trailing_html;
			
			$return['in_scope'] = true;	
		}
		
		return $return;
	}
	
	function hide_network_option( $option_name ) {
		if ( PP_MULTISITE ) {
			global $pp_netwide_options;
			return ( in_array( $option_name, $pp_netwide_options ) && ! is_network_admin() && ( 1 != get_current_blog_id() ) );
		} else
			return false;
	}
	
	function filter_network_options() {
		if ( PP_MULTISITE && ! is_network_admin() && ( 1 != get_current_blog_id() ) ) {
			global $pp_netwide_options;
			$this->all_options = array_diff( $this->all_options, $pp_netwide_options );
			$this->all_otype_options = array_diff( $this->all_otype_options, $pp_netwide_options );
		}
	}
}
