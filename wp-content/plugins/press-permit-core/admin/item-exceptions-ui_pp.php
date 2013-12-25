<?php
class PP_ItemExceptionsUI {
	var $render;
	var $data;
	
	function __construct() {
		require_once( dirname(__FILE__).'/item-exceptions-data_pp.php' );
		$this->data = new PP_ItemExceptionsData();
	
		require_once( dirname(__FILE__).'/item-exceptions-render-ui_pp.php' );
		$this->render = new PP_ItemExceptionsRenderUI();
	}

	function draw_exceptions_ui( $box, $args ) {
		if ( ! isset( $box['args'] ) )
			return;

		extract( $box['args'], EXTR_SKIP );  // $op
		extract( $args, EXTR_SKIP );  // $for_item_source, for_item_type, via_item_source
		
		global $wp_roles, $pp_current_user;
		
		if ( 'post' == $via_item_source )
			$hierarchical = is_post_type_hierarchical( $via_item_type );
		else
			$hierarchical = is_taxonomy_hierarchical( $via_item_type );
		
		if ( $hierarchical = apply_filters( 'pp_do_assign_for_children_ui', $hierarchical, $via_item_type, $args ) )
			$type_obj = ( 'post' == $via_item_source ) ? get_post_type_object($via_item_type) : get_taxonomy($via_item_type);
		
		$agent_types['wp_role'] = (object) array( 'labels' => (object) array( 'name' => __('WP Roles', 'pp'), 'singular_name' => __('WP Role', 'pp') ) );
		$agent_types = apply_filters( 'pp_list_group_types', array_merge( $agent_types, pp_get_group_types( array(), 'object' ) ) );
		$agent_types['user'] = (object) array( 'labels' => (object) array( 'name' => __('Users'), 'singular_name' => __('User') ) );

		//if ( ! $skip_user_validation && ! $pp_admin->user_can_admin_role($role_name, $item_id, $src_name, $object_type) )
		//	return;

		static $drew_itemroles_marker;
		if ( empty( $drew_itemroles_marker ) ) {
			echo "<input type='hidden' name='pp_post_exceptions' value='true' />";
			$drew_itemroles_marker = true;
		}
		
		$current_exceptions = ( isset( $this->data->current_exceptions[$for_item_type] ) ) ? $this->data->current_exceptions[$for_item_type] : array();
		
		// ========== OBJECT / TERM EXCEPTION DROPDOWNS ============
		$toggle_agents = count($agent_types) > 1;
		if ( $toggle_agents ) {
			global $is_ID;
			$class_selected = 'agp-selected_agent agp-agent';
			$class_unselected = 'agp-unselected_agent agp-agent';
			$bottom_margin = ( ! empty( $is_IE ) ) ? '-0.7em' : 0;
			
			$default_agent_type = 'wp_role';

			echo "<div class='hide-if-not-js' style='margin:0 0 $bottom_margin 0'>"
				. "<ul class='pp-list_horiz' style='margin-bottom:-0.1em'>";
				
			foreach ( $agent_types as $agent_type => $gtype_obj ) {
				$label = ( ! empty($current_exceptions[$op][$agent_type]) ) ? sprintf( __( '%1$s (%2$s)', 'pp' ), $gtype_obj->labels->name, count($current_exceptions[$op][$agent_type]) ) : $gtype_obj->labels->name;
			
				$class = ( $default_agent_type == $agent_type ) ? "class='$class_selected'" : "class='$class_unselected'";
				echo "<li $class><a href='javascript:void(0)' class='{$op}-{$for_item_type}-{$agent_type}'>" . $label . '</a></li>';
			}

			echo '</ul></div>';
		}
		
		$class = "class='pp-agents pp-exceptions'";
		
		//need effective line break here if not IE
		echo "<div style='clear:both;margin:0 0 0.3em 0' $class>";
		
		$pp_agents_ui = pp_init_agents_ui();

		foreach ( array_keys($agent_types) as $agent_type ) {
			$hide_class = ( $toggle_agents && ( $agent_type != $default_agent_type ) ) ? ' class="hide-if-js"' : '';
			
			echo "\r\n<div id='{$op}-{$for_item_type}-{$agent_type}' $hide_class style='overflow-x:auto'>";

			$this->render->set_options( $agent_type );
			
			// list all WP roles
			if ( 'wp_role' == $agent_type ) {
				if ( ! isset( $current_exceptions[$op][$agent_type] ) )
					$current_exceptions[$op][$agent_type] = array();
				
				foreach( $this->data->agent_info['wp_role'] as $agent_id => $role ) {
					if ( in_array( $role->metagroup_id, array( 'wp_anon', 'wp_all' ) ) && ( ( 'read' != $op ) || pp_get_option( 'anonymous_unfiltered' ) ) )
						continue;

					if ( ! isset( $current_exceptions[$op][$agent_type][$agent_id] ) )
						$current_exceptions[$op][$agent_type][$agent_id] = array();
				}

				if ( ( 'post' == $via_item_source ) && ( 'attachment' != $via_item_type ) && in_array( $op, array( 'read', 'edit', 'delete' ) ) )
					$reqd_caps = map_meta_cap( "{$op}_post", 0, $item_id );
				else
					$reqd_caps = false;
			}
			
			global $wp_roles;
			?>
			
			<table class="pp-item-exceptions-ui pp-exc-<?php echo $agent_type;?>" style="width:100%"><tr>
			<?php if( 'wp_role' != $agent_type ) : ?>
				<td class="pp-select-exception-agents" style="display:none;">
				<?php
					// Select Groups / Users UI
					
				echo '<div>';
				echo '<div class="pp-agent-select">';
				
				$args = array_merge( $args, array(  
					'suppress_extra_prefix' => true, 
					'ajax_selection' => true,
					'display_stored_selections' => false,
					'create_dropdowns' => true,
					'op' => $op,
				) );

				$pp_agents_ui = pp_init_agents_ui();
				$pp_agents_ui->agents_ui( $agent_type, array(), "{$op}:{$for_item_type}:{$agent_type}", array(), $args );
				echo '</div>';
				echo '</div>';
					
				$colspan = 'colspan="2"'; 
				?>
				</td>
			<?php else:
				$colspan = '';	// for html5 compliance
			endif;?>
			
			<?php 
			$any_stored = empty( $current_exceptions[$op][$agent_type] ) ? 0 : count($current_exceptions[$op][$agent_type]); 
			?>
			<td class="pp-current-item-exceptions" style="width:100%">
				<div style="overflow:auto;max-height:250px;min-width:<?php echo ( $hierarchical ) ? '400px' : '250px' ?>">
				<table <?php if ( ! $any_stored ) echo 'style="display:none"'; ?>>
				<?php if ( $hierarchical ) : ?>
					<thead>
					<tr>
					<th></th>
					<th><?php printf( __('This %s', 'pp'), $type_obj->labels->singular_name ); ?></th>
					<th><?php 
						if ( $caption = apply_filters( 'pp_item_assign_for_children_caption', '', $via_item_type ) )
							printf( $caption );
						else
							printf( __('Sub-%s', 'pp'), $type_obj->labels->name ); 
					?></th>
					</tr>
					</thead>
				<?php endif; ?>
					<tbody>
					<?php					// @todo: why is agent_id=0 in current_exceptions array?
					if ( $any_stored ) {
						if ( 'wp_role' == $agent_type ) {
							foreach( $current_exceptions[$op][$agent_type] as $agent_id => $agent_exceptions ) {
								if ( $agent_id && isset( $this->data->agent_info[$agent_type][$agent_id] ) ) {
									$this->render->draw_row( $agent_type, $agent_id, $current_exceptions[$op][$agent_type][$agent_id], $this->data->inclusions_active, $this->data->agent_info[$agent_type][$agent_id], compact( 'for_item_type', 'op', 'reqd_caps', 'hierarchical' ) );
								}
							}
						} else {
							foreach( array_keys($this->data->agent_info[$agent_type]) as $agent_id ) {  // order by agent name
								if ( $agent_id && isset( $current_exceptions[$op][$agent_type][$agent_id] ) ) {
									$this->render->draw_row( $agent_type, $agent_id, $current_exceptions[$op][$agent_type][$agent_id], $this->data->inclusions_active, $this->data->agent_info[$agent_type][$agent_id], compact( 'for_item_type', 'op', 'reqd_caps', 'hierarchical' ) );
								}
							}
						}
					}
					?>
					</tbody>
					
					<tfoot<?php if ( $any_stored < 2 ) echo ' style="display:none;"';?>>
					<?php
						$link_caption = ( 'wp_role' == $agent_type ) ? __('default all', 'pp') : __('clear all', 'pp');
						?>
						<tr>
						<td></td><td style="text-align:center"><a href="#clear-item-exc"><?php echo $link_caption;?></a></td>
						<?php if( $hierarchical ) :?>
						<td style="text-align:center"><a href="#clear-sub-exc"><?php echo $link_caption;?></a></td>
						<?php endif; ?>
						</tr>
					</tfoot>
				
				</table>
				
				</div>
				
				<?php if ( ! $any_stored ) :?>
				<div class="pp-no-exceptions"><?php _e( 'No access customizations stored.', 'pp' );?></div>
				<?php endif; ?>
			</td>
			</tr>
			
			<tr>
			<td class="pp-exception-actions" <?php echo $colspan;?>>
			<?php if ( 'wp_role' != $agent_type ) :?>
				<a class="pp-select-exception-agents" href="#"><?php ( 'user' == $agent_type ) ? _e( 'select users', 'pp' ) : _e( 'select groups', 'pp' );?></a>
				<a class="pp-close-select-exception-agents" href="#" style="display:none;"><?php _e( 'close', 'pp' );?></a>
			<?php endif;
			if ( pp_group_type_editable( $agent_type ) && pp_has_group_cap( 'pp_create_groups', 0, $agent_type ) ) :
			?>
				&nbsp;&bull;&nbsp;<a class="pp-create-exception-agent" href="admin.php?page=pp-group-new" target="_blank"><?php _e( 'create group', 'pp' );?></a>
			<?php endif;
			?>
			</td>
			</tr>
			</table>
			
			</div>
			<?php
		} // end foreach group type caption
		
		echo '</div>'; // class pp-agents
		
		if ( ( 'read' == $op ) && pp_get_option('display_extension_hints') && ( ( ( 'attachment' == $for_item_type ) && ! defined( 'PPFF_VERSION' ) ) || ! defined( 'PPCE_VERSION' ) ) ) {
			require_once( dirname(__FILE__).'/item-exceptions-ui-hints_pp.php' );
			_ppc_item_ui_hints( $for_item_type );
		}
		
		if ( ( 'term' == $via_item_source ) && in_array( $op, array( 'read', 'edit' ) ) ) {
			$msg = __( 'To customize <strong>for a specific post status</strong>, edit the desired role / group / user permissions directly (Permissions > Groups or Users)', 'pp' );
			echo "<div class='pp-exc-notes'>$msg</div>";
		}
	}
	
} // end class	

