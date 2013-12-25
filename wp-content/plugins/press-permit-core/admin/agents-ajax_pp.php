<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * PP_Agents_Ajax class
 * 
 * @package PP
 * @author Kevin Behrens <kevin@agapetry.net>
 * @copyright Copyright (c) 2011-2013, Agapetry Creations LLC
 * 
 */

class PP_Agents_Ajax {
	var $agents_js_queue = false;
	
	// output seach textbox & button, results list, "Add All" button
	function display( $agent_type, $id_suffix, $current_selections = array(), $args = array() ) {
		$defaults = array( 'agent_id' => 0, 'context' => '',
		'label_select' => _x( 'Select &gt;', 'user', 'pp'), 'label_unselect' => _x( '&lt; Unselect', 'user', 'pp'), 'label_selections' => __( 'Current Selections:', 'pp' ),
		'display_stored_selections' => true, 'create_dropdowns' => false, 'width' => '', 'width_current' => '', 'label_headline' => true, 'multi_select' => true, 'use_selection_js' => true,
		);
		
		$args = apply_filters( 'pp_agents_selection_ui_args', array_merge( $defaults, $args ), $agent_type, $id_suffix );
		extract( $args, EXTR_SKIP );

		$width = ( $width ) ? "width:{$width}px;" : '';
		
		$this->register_ajax_js( $agent_type, $id_suffix, $context, $agent_id, $args );

		if ( true === $label_headline )
			$label_headline = ( 'user' == $agent_type ) ? __( 'Select Users', 'pp' ) : __( 'Select Groups', 'pp' );
?>
		<table id="pp-agent-selection_<?php echo $id_suffix;?>-wrapper" class="pp-agents-selection">
		<tr><td id="pp-agent-selection_<?php echo $id_suffix;?>">
		<h4><?php echo $label_headline;?></h4>
		<input id="agent_search_text_<?php echo $id_suffix;?>" type="text" size="8" />
		<button type="button" class="pp-agent-search-submit" id="agent_submit_<?php echo $id_suffix;?>"><?php echo __ppw("Search")?></button>
		<img class="waiting" style="display:none;" src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) )?>" alt="" />
		
		<?php if ( ( 'user' == $agent_type ) && pp_get_option( 'user_search_by_role' ) ) :?>
			<br /><select id="pp_search_role_<?php echo $id_suffix;?>">
			<option value=""><?php _e( '(any WP role)', 'pp' );?></option>
			<?php wp_dropdown_roles(); ?>
			</select>
		<?php endif;?>
		
		</td>
		
		<?php if( $display_stored_selections) : ?>
		<td style="vertical-align:bottom" class="pp-members-current"><h4><?php echo $label_selections;?></h4></td>
		<?php endif; ?>
		
		</tr>
		
		<tr><td>
		<select id="agent_results_<?php echo $id_suffix;?>" class="pp_agent_results" <?php if( $multi_select) :?>multiple="multiple" style="height:160px;<?php else:?>style="display:none;<?php endif;?><?php echo $width;?>"></select>
		<span id="agent_msg_<?php echo $id_suffix;?>"></span>
		</td>

		<?php if( $display_stored_selections) : 
			if ( $width_current )
				$width = "width:{$width_current}px;";
		?>
		<td class="pp-members-current">
		<select id='<?php echo $id_suffix;?>' name='<?php echo $id_suffix;?>[]' multiple='multiple' style='height:160px;<?php echo $width;?>float:right'>
		
<?php 
	$display_property = ( 'user' == $agent_type ) ? 'user_login' : 'display_name'; 

	foreach ( $current_selections as $agent ) : 
		$attribs = ( isset($agent->display_name) && ( $agent->user_login != $agent->display_name ) ) ? 'title="' . esc_attr($agent->display_name) . '"' : '';
		?>
		<?php $data = apply_filters( 'pp_agents_selection_ui_attribs', array( 'attribs' => $attribs, 'user_caption' => $agent->$display_property ), $agent_type, $id_suffix, $agent ); 
		?>
		<option value="<?php echo $agent->ID;?>" <?php echo $data['attribs'];?>><?php echo $data['user_caption'];?></option>
<?php endforeach;?>

		</select><br />
		</td>
		<?php endif; ?>
		
		</tr>
		
		<?php do_action( '_pp_agents_selection_ui_select_pre', $id_suffix ); ?>
		<tr>
		<?php do_action( 'pp_agents_selection_ui_select_pre', $id_suffix ); ?>		

		<td><button type="button" id="select_agents_<?php echo $id_suffix;?>" class="pp_add" style="float:right<?php if( ! $multi_select ):?>;display:none;<?php endif;?>"><?php echo $label_select;?></button></td>
		
		<?php if( $display_stored_selections) : ?>
		<td class="pp-members-current"><button type="button" id="unselect_agents_<?php echo $id_suffix;?>" class="pp_remove"><?php echo $label_unselect;?></button></td>
		<?php endif; ?>
		
		</tr>
		</table>	
<?php
		if ( ! defined( 'PPM_VERSION' ) && pp_get_option('display_extension_hints') ) {	
			if ( 0 === validate_plugin( "pp-membership/pp-membership.php" ) )
				$msg = __( 'To set date limits on group membership, activate the PP Membership plugin.', 'pp' );
			elseif( true == pp_key_status() )
				$msg = sprintf( __( 'To set date limits on group membership, %1$sinstall%2$s the PP Membership plugin.', 'pp' ), '<a href="admin.php?page=pp-settings&pp_tab=install">', '</a>' );
			else
				$msg = sprintf( __( 'To set date limits on group membership, %1$senter%2$s or %3$spurchase%4$s a support key and install the PP Membership plugin.', 'pp' ), '<a href="admin.php?page=pp-settings&pp_tab=install">', '</a>', '<a href="http://presspermit.com/purchase">', '</a>' );
			
			echo "<div class='pp-ext-promo'>$msg</div>";
		}

		$csv = ( $current_selections ) ? implode( ',', array_keys($current_selections) ) : '';
		$csv = apply_filters( 'pp_agents_selection_ui_csv', $csv, $id_suffix, $current_selections );
?>
		<input type="hidden" id="<?php echo $id_suffix;?>_csv" name="<?php echo $id_suffix;?>_csv" value="<?php echo $csv;?>" />
		<?php
	} // end function ajax_selection_ui

	function register_ajax_js( $agent_type, $id_sfx, $context = '', $agent_id = 0, $args = array() ) {
		global $wp_scripts;
		
		// note: this is also done in PP_AdminFiltersItemUI() constructor
		$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
		wp_enqueue_script( 'pp_listbox', PP_URLPATH . "/admin/js/listbox{$suffix}.js", array('jquery', 'jquery-form'), PPC_VERSION, true );
		$wp_scripts->in_footer []= 'pp_listbox';  // otherwise it will not be printed in footer, as of WP 3.2.
		
		if ( ! empty($args['create_dropdowns']) ) {
			wp_localize_script( 'pp_listbox', 'ppListbox', array( 'omit_admins' => '1', 'metagroups' => 1 ) );
		
			wp_enqueue_script( 'pp_agent_select', PP_URLPATH . "/admin/js/agent-exception-select_pp{$suffix}.js", array('jquery', 'jquery-form'), PPC_VERSION, true );

			$arr = array_merge( $args, array( 'agent_type' => $agent_type, 'ajaxurl' => admin_url('') ) );
			wp_localize_script( 'pp_agent_select', 'ppException', $arr );
		} else {
			wp_localize_script( 'pp_listbox', 'ppListbox', array( 'omit_admins' => '0', 'metagroups' => 0 ) );
			
			if ( ! do_action( 'pp_override_agent_select_js', false ) )
				wp_enqueue_script( 'pp_agent_select', PP_URLPATH . "/admin/js/agent-select_pp{$suffix}.js", array('jquery', 'jquery-form'), PPC_VERSION, true );
		}
	
		$wp_scripts->in_footer []= 'pp_agent_select'; // otherwise it will not be printed in footer, as of WP 3.2.1
		
		$ajaxhandler = ( ! empty($args['create_dropdowns']) ) ? 'got_ajax_dropdowns' : 'got_ajax_listbox';
		wp_localize_script( 'pp_agent_select', 'PPAgentSelect', array( 'adminurl' => admin_url(''), 'ajaxhandler' => $ajaxhandler ) );

		if ( ! $this->agents_js_queue ) {
			$this->agents_js_queue = array();
			add_action( 'admin_print_footer_scripts', array( &$this, 'ajax_selection_js' ), 30 );
		}
		
		$suppress_selection_js = ! empty($args['suppress_selection_js']);
		$this->agents_js_queue []= compact( 'agent_type', 'id_sfx', 'context', 'agent_id', 'suppress_selection_js' );
	}
	
	function ajax_selection_js() {
		// $args: agent_id supports contextual filtering of search results (e.g. group search omits groups which specified user is already a member of)
		
		// todo: clean up js loading logic
		if ( $this->agents_js_queue ) {
			$author_selection_only = false;
			
			if ( ! do_action( 'pp_override_agent_select_js', false ) && ! wp_script_is( 'pp_agent_select', 'done' ) ) {
				global $wp_scripts;
				$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
				wp_enqueue_script( 'pp_agent_select', PP_URLPATH . "/admin/js/agent-select_pp{$suffix}.js", array('jquery', 'jquery-form'), PPC_VERSION, true );
				$wp_scripts->do_item('pp_agent_select');
				$author_selection_only = true;
			}
			
			?>
<script type="text/javascript">
/* <![CDATA[ */
<?php foreach( $this->agents_js_queue as $args ) : ?>
pp_load_agents_js( '<?php echo($args['id_sfx']);?>', '<?php echo($args['agent_type']);?>', '<?php echo($args['context']);?>', '<?php echo($args['agent_id']);?>', '<?php echo($args['suppress_selection_js']);?>', <?php echo ( $author_selection_only ) ? 'true' : 'false';?> );	
<?php endforeach; ?>
/* ]]> */
</script>
<?php
		}	
	} // end function ajax_selection_js
	
} // end class
