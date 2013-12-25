<?php
class PP_ItemExceptionsRenderUI {
	var $opt_labels = array();
	var $opt_class = array();
	var $options = array();
	var $base_url = '';
	
	function __construct() {
		$this->opt_labels = array( 
			'default' => __( '(default access)', 'pp' ),
			'default_yes' => __( '(default: Yes)', 'pp' ),
			'default_no' => __( '(default: No)', 'pp' ),
			'no_setting' => __( '(no setting)', 'pp' ),
			'enabled' => __( 'Enabled', 'pp' ),
			'blocked' => __( 'Blocked', 'pp' ),
			'default_blocked' => __( '(Blocked)', 'pp' ),
			'unblocked' => __( 'Unblocked', 'pp' ),
		);
		
		$this->opt_class = array( '' => " class='pp-def' ", 0 => " class='pp-no2' ", 1 => " class='pp-yes' ", 2 => " class='pp-yes2' " );
	}
	
	function set_options( $agent_type ) {
		global $pagenow;
		
		$this->options = array( 'includes' => array(), 'standard' => array() );

		$this->options['includes'][''] = $this->opt_labels['default_blocked'];
		$this->options['includes'][1] = $this->opt_labels['unblocked'];
		$this->options['includes'][2] = $this->opt_labels['enabled'];
	
		if ( in_array( $agent_type, array( 'wp_role', 'user' ) ) || defined( 'PP_GROUP_RESTRICTIONS' ) ) {
			$this->options['standard'][''] = (  ( 'user' == $agent_type ) || ( 'edit-tags.php' == $pagenow ) ) ? $this->opt_labels['no_setting'] : $this->opt_labels['default'];
			$this->options['standard'][0] = $this->opt_labels['blocked'];
			$this->options['standard'][2] = $this->opt_labels['enabled'];
		} else {
			$this->options['standard'][''] = $this->opt_labels['no_setting'];
			$this->options['standard'][2] = $this->opt_labels['enabled'];
		}
		
		switch ($agent_type) {
			case 'wp_role':
			case 'pp_group':
				$this->base_url = "admin.php?page=pp-edit-permissions&amp;action=edit&amp;agent_id=";
				break;

			default:
				$this->base_url = "admin.php?page=pp-edit-permissions&amp;action=edit&amp;agent_type=$agent_type&amp;agent_id=";
		}
	}
	
	function draw_row( $agent_type, $agent_id, $agent_exceptions, $inclusions_active, $agent_info, $args = array() ) {
		global $wp_roles;
		
		$defaults = array( 'reqd_caps' => false, 'hierarchical' => false, 'for_item_type' => '', 'op' => '', 'default_select' => false );
		$args = array_merge( $defaults, $args );
		extract( $args, EXTR_SKIP );
	
		$assignment_modes = array( 'item' );
		if ( $hierarchical )
			$assignment_modes []= 'children';
	
		$this->opt_class[''] = '';
		$disabled = '';
		
		if ( 'wp_role' == $agent_type ) {
			// also credit sitewide caps attached via supplemental role assignment to WP Role metagroup
			static $metagroup_caps;
			if ( ! isset( $metagroup_caps ) ) {
				$metagroup_caps = array();
			
				global $wpdb, $pp;
				$results = $wpdb->get_results( "SELECT g.metagroup_id AS wp_rolename, r.role_name AS supplemental_role FROM $wpdb->ppc_roles AS r INNER JOIN $wpdb->pp_groups AS g ON g.ID = r.agent_id AND r.agent_type = 'pp_group' WHERE g.metagroup_type = 'wp_role'" );

				foreach( $results as $row ) {
					$role_specs = explode( ':', $row->supplemental_role );
					if ( ! empty($role_specs[2]) && ( $for_item_type != $role_specs[2] ) )
						continue;

					if ( ! isset( $metagroup_caps[$row->wp_rolename] ) )
						$metagroup_caps[$row->wp_rolename] = array();

					$metagroup_caps[$row->wp_rolename] = array_merge( $metagroup_caps[$row->wp_rolename], array_fill_keys( $pp->get_role_caps( $row->supplemental_role ), true ) );
				}
			}
		
			$role_caps = isset( $wp_roles->role_objects[ $agent_info->metagroup_id ] ) ? array_intersect( $wp_roles->role_objects[ $agent_info->metagroup_id ]->capabilities, array( true, 1, '1' ) ) : array( 'read' => true, 'spectate' => true );
			
			if ( isset( $metagroup_caps[$agent_info->metagroup_id] ) )
				$role_caps = array_merge( $role_caps, $metagroup_caps[$agent_info->metagroup_id] );
			
			$is_unfiltered = ! empty( $role_caps['pp_administer_content'] ) || ! empty( $role_caps['pp_unfiltered'] );
			
			if ( $is_unfiltered )
				$disabled = ' disabled="disabled"';
	
			if ( $reqd_caps ) {
				if ( ! array_diff( $reqd_caps, array_keys($role_caps) ) || $is_unfiltered ) {
					$this->opt_class[''] = " class='pp-yes' ";
					$this->options['standard'][''] = $this->opt_labels['default_yes'];
				} else {
					$this->opt_class[''] = " class='pp-no' ";
					$this->options['standard'][''] = $this->opt_labels['default_no'];
				}
			}
		} else {
			$this->options['standard'][''] = ( in_array( $agent_type, array( 'wp_role', 'user' ) ) ) ? $this->opt_labels['default'] : $this->opt_labels['no_setting'];
		}

		$_inclusions_active = isset( $inclusions_active[$for_item_type][$op][$agent_type][$agent_id] );
		
		if ( 'wp_role' == $agent_type ) {
			require_once( PPC_ABSPATH . '/groups-retrieval_pp.php' );
			$title = " title='" . PP_GroupRetrieval::get_metagroup_descript( 'wp_role', $agent_info->metagroup_id, '' ) . "'";
		} elseif ( ( 'user' == $agent_type ) && ! empty($agent_info->display_name) && ( $agent_info->display_name != $agent_info->name ) ) {
			$title = " title='$agent_info->display_name'";
		} else {
			$title = '';
		}
		?>
<tr><td class='pp-exc-agent'><input type='hidden' value='<?php echo $agent_id;?>' /><a href='<?php echo "{$this->base_url}$agent_id";?>'<?php echo $title;?> target='_blank'><?php echo $agent_info->name;?></a></td>
<?php
		foreach( $assignment_modes as $assign_for ) {
			if ( ! empty( $agent_exceptions[$assign_for]['additional'] ) )
				$current_val = 2;
			elseif ( isset( $agent_exceptions[$assign_for]['include'] ) )
				$current_val = 1;
			else {
				if ( $default_select )
					$current_val = ( $_inclusions_active ) ? '1' : '2';	// default to "Unblocked" if available, otherwise Enabled
				else
					$current_val = ( isset( $agent_exceptions[$assign_for]['exclude'] ) ) ? 0 : '';
			}

			if ( $_inclusions_active ) {
				$option_set = 'includes';
				$this->opt_class[''] = " class='pp-no' ";
			} else {
				$option_set = 'standard';
				
				if ( ! $this->opt_class[''] )
					$this->opt_class[''] = " class='pp-def' ";
			}
			
			$disabled = $disabled || ( ( 'children' == $assign_for ) && apply_filters( 'pp_assign_for_children_locked', false, $for_item_type, array( 'operation' => $op ) ) ) ? ' disabled="disabled" ' : '';

			$for_type = ( $for_item_type ) ? $for_item_type : '(all)';
			?>
<td class="<?php echo ( 'children' == $assign_for ) ? 'pp-exc-children' : 'pp-exc-item';?>"><select name='pp_exceptions<?php echo "[$for_type][$op][$agent_type][$assign_for][$agent_id]'{$this->opt_class[$current_val]}";?><?php echo $disabled;?>>
<?php
foreach( $this->options[$option_set] as $val => $lbl ) :
	if ( ( 'wp_role' == $agent_type ) && in_array( $agent_info->metagroup_id, array( 'wp_anon', 'wp_all' ) ) && ( 2 == $val ) )
		continue;
?>
<option value='<?php echo "$val'{$this->opt_class[$val]}"; selected( $val, $current_val );?>><?php echo $lbl;?></option>
<?php endforeach; ?>
</select>
<?php
if ( $disabled ):?>
<input type="hidden" name='pp_exceptions<?php echo "[$for_type][$op][$agent_type][$assign_for][$agent_id]";?>' value="<?php echo $current_val;?>" />
<?php endif;?>

</td>
<?php
		}
		?>
</tr>
		<?php
	} // end function
} // end class
