<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * PP_AgentsUI class
 * 
 * @package PP
 * @author Kevin Behrens <kevin@agapetry.net>
 * @copyright Copyright (c) 2011-2013, Agapetry Creations LLC
 * 
 */

class PP_AgentsUI {
	var $agents_js_queue = false;
	
	function agents_ui( $agent_type, $all_agents, $id_suffix = '', $item_assignments = array(), $args = array()) {
		$defaults = array( 'role_name' => '', 'ajax_selection' => false, 'width' => '', 'hide_checkboxes' => false );
		extract( array_merge( $defaults, $args ), EXTR_SKIP );
		
		echo '<div class="pp_agents_wrapper">';
		
		if ( $ajax_selection ) {
			global $pp_plugin_page;
		
			$pp_agents_ajax = $this->init_ajax();
		
			if ( 'pp-edit-permissions' == $pp_plugin_page ) {
				$args['width'] = 180;
			}
		
			echo '<div class="pp_agents_ajax_wrapper">';
			$pp_agents_ajax->display( $agent_type, $id_suffix, $item_assignments, $args );
			echo '</div>';
		} else {
			require_once( dirname(__FILE__).'/agents-checklist_pp.php' );
		
			echo "<div class='pp_agents_ui_wrapper'>";
			
			if ( $item_assignments ) {
				PP_Agents_Checklist::display( 'current', $agent_type, $all_agents, $id_suffix, $item_assignments, $args ); 
			}
			
			PP_Agents_Checklist::display( 'eligible', $agent_type, $all_agents, $id_suffix, $item_assignments, $args ); 

			echo '<div style="clear:both; height:1px; margin:0">&nbsp;</div>';
			
			echo '</div>'; // pp_agents_ui_wrapper
		}
		
		echo '</div>'; // pp_agents_wrapper
	}
	
	function init_ajax() {
		global $pp_agents_ajax;
		require_once( dirname(__FILE__).'/agents-ajax_pp.php' );
		$pp_agents_ajax = new PP_Agents_Ajax();
		return $pp_agents_ajax;
	}
} // end class
