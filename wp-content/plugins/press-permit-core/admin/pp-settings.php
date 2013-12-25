<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once( dirname(__FILE__).'/options-ui_pp.php' );

require_once( dirname(__FILE__).'/options-install_pp.php' );
$options_install = new PP_Options_Install();

require_once( dirname(__FILE__).'/options-core_pp.php' );
$options_core = new PP_Options_Core();

require_once( dirname(__FILE__).'/options-advanced_ppx.php' );
$options_advanced = new PP_Options_Advanced();

// enqueue JS for footer
global $wp_scripts;
$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
wp_enqueue_script( 'pp_settings', PP_URLPATH . "/admin/js/pp_settings{$suffix}.js", array('jquery', 'jquery-form'), PPC_VERSION, true );
$wp_scripts->in_footer []= 'pp_settings';  // otherwise it will not be printed in footer, as of WP 3.2.1

function pp_options( $args = array() ) {
	if ( ! current_user_can('pp_manage_settings') )
		wp_die(__ppw('Cheatin&#8217; uh?'));

	do_action( 'pp_options_ui' );

	global $pp, $pp_admin, $pp_options_ui;

	$pp->load_config();
	//$pp->load_user_config();

	$pp_options_ui = new PP_OptionsUI( $args );
	$ui = $pp_options_ui; // shorten syntax
	
	$ui->all_options = array();

	$ui->tab_captions = apply_filters( 'pp_option_tabs', array() );
	$ui->section_captions = apply_filters( 'pp_section_captions', array() );
	$ui->option_captions = apply_filters( 'pp_option_captions', array() );
	$ui->form_options = apply_filters( 'pp_option_sections', array() );
	$ui->display_hints = pp_get_option( 'display_hints' );
	
	if ( $_hidden = apply_filters( 'pp_hide_options', array() ) ) {
		$hidden = array();
		foreach( array_keys( $_hidden ) as $option_name ) {
			if ( ! is_array($_hidden[$option_name]) && strlen($option_name) > 3 )
				$hidden[] = substr( $option_name, 3 );
		}

		foreach( array_keys( $ui->form_options ) as $tab ) {
			foreach( array_keys( $ui->form_options[$tab] ) as $section )
				$ui->form_options[$tab][$section] = array_diff( $ui->form_options[$tab][$section], $hidden );
		}
	}

	?>
	<div class='wrap'>
	<?php
	echo '<form id="pp_settings_form" action="" method="post">';
	wp_nonce_field( 'pp-update-options' );

	do_action( 'pp_options_form' );
	?>
	<?php pp_icon(); ?>

	<div class="submit pp-submit" style="border:none;position:absolute;right:20px;top:25px;">
	<input type="submit" name="pp_submit" class="button-primary" value="<?php _e('Save Changes', 'pp');?>" />
	</div>
	<h2>
	<?php
	$title = apply_filters( 'pp_options_form_title', _e('Press Permit Settings', 'pp') );
	_e( $title );
	?>
	</h2>
	
	<?php

	if ( $subheading = apply_filters( 'pp_options_form_subheading', '' ) )
		echo $subheading;

	$color_class = apply_filters( 'pp_options_form_color_class', 'pp-backtan' );

	$class_selected = "agp-selected_agent agp-agent $color_class";
	$class_unselected = "agp-unselected_agent agp-agent";

	?>
	<script type="text/javascript">
	/* <![CDATA[ */
	jQuery(document).ready( function($) {
		$('li.agp-agent a').click(function() {
			$('li.agp-agent').removeClass( 'agp-selected_agent <?php echo $color_class;?>' );
			$('li.agp-agent').addClass( 'agp-unselected_agent' );
			$(this).parent().addClass( 'agp-selected_agent <?php echo $color_class;?>' );
			$('.pp-options-wrapper > div').hide();
			$('#' + $(this).attr('class') ).show();
		});
	});
	/* ]]> */
	</script>
	<?php
	$default_tab = ( isset ($_REQUEST['pp_tab']) && isset($ui->tab_captions[$_REQUEST['pp_tab']]) ) ? $_REQUEST['pp_tab'] : 'install';
	$default_tab = apply_filters( 'pp_options_default_tab', $default_tab );
	
	// @todo: prevent line breaks in these links
	echo "<ul class='pp-list_horiz' style='margin-bottom:-0.1em'>";

	foreach( $ui->tab_captions as $tab => $caption ) {
		if ( ! empty( $ui->form_options[$tab] ) ) {
			$class = ( $default_tab == $tab ) ? $class_selected : $class_unselected;  // @todo: return to last tab
			echo "<li class='$class'><a class='pp-{$tab}' href='javascript:void(0)'>" . $ui->tab_captions[$tab] . '</a></li>';
		}
	}
	echo '</ul>';

	echo '<div class="pp-options-wrapper">';
	$table_class = 'form-table pp-form-table pp-options-table';

	if ( isset($_REQUEST['pp_submit']) ) : ?>
		<div id="message" class="updated"><p>
		<strong><?php _e('Settings were updated.', 'pp'); ?>&nbsp;</strong>
		</p></div>
	<?php elseif ( isset($_REQUEST['pp_defaults']) ) : ?>
		<div id="message" class="updated"><p>
		<strong><?php _e('Settings were reset to defaults.', 'pp'); ?>&nbsp;</strong>
		</p></div>
	<?php endif;
	
	foreach( array_keys($ui->tab_captions) as $tab ) {
		$display = ( $default_tab == $tab ) ? '' : 'display:none';
		echo "<div id='pp-{$tab}' style='clear:both;margin:0;{$display}' class='pp-options $color_class'>";
		do_action( "pp_{$tab}_options_pre_ui" );
		echo "<table class='$table_class' id='pp-{$tab}_table'>";
		do_action( "pp_{$tab}_options_ui" );
		echo '</table></div>';
	}

	echo '</div>'; // pp-options-wrapper

	$pp_options_ui->filter_network_options();
	
	echo "<input type='hidden' name='all_options' value='" . implode(',', $ui->all_options) . "' />";
	echo "<input type='hidden' name='all_otype_options' value='" . implode(',', $ui->all_otype_options) . "' />";

	echo "<input type='hidden' name='pp_submission_topic' value='options' />";
	?>

	<p class="submit pp-submit" style="border:none;">
	<input type="submit" name="pp_submit" class="button-primary" value="<?php _e('Save Changes', 'pp');?>" />
	</p>

	<?php
	$msg = __( "All settings in this form (including those on undisplayed tabs) will be reset to DEFAULTS.  Are you sure?", 'pp' );
	$js_call = "javascript:if (confirm('$msg')) {return true;} else {return false;}";
	?>
	<p class="submit pp-submit-alternate" style="border:none;float:left">
	<input type="submit" name="pp_defaults" value="<?php _e('Revert to Defaults', 'pp') ?>" onclick="<?php echo $js_call;?>" />
	</p>
	</form>
	<p style='clear:both'>
	</p>
	</div>

	<?php
} // end function
