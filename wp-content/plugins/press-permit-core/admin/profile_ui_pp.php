<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class PP_ProfileUI {
	static function abbreviated_exceptions_ui( $agent_type, $agent_id, $args = array() ) {
		$defaults = array( 'caption' => '', 'edit_url' => '', 'join_groups' => false, 'class' => 'pp-user-roles', 'new_permissions_link' => false, 'maybe_display_note' => true );
		$args = array_merge( $defaults, $args );
		extract( $args, EXTR_SKIP );
		
		//static $done = false;
		//if ( $done ) return;
		//$done = true;
		
		$args['show_link'] = false;
		$args['force_refresh'] = true;
		
		$new_permissions_link = $maybe_display_note && pp_is_user_administrator() && pp_bulk_roles_enabled() && current_user_can('list_users');
		
		if ( ! $exceptions_ui = ppc_list_agent_exceptions( $agent_type, $agent_id, $args ) ) {
			if ( ( 'user' == $agent_type ) && ( $join_groups != 'groups_only' ) && $new_permissions_link ) : ?>
				<div style="clear:both;"></div>
				<div id='pp_current_exceptions_ui' class='pp-group-box <?php echo $class;?>'>
				<h3>
				<?php 
				_e( 'Custom User Permissions', 'pp' );
				?>
				</h3>
				<p>
				<?php
				printf( __( 'Supplemental roles and exceptions assigned to a user\'s primary role or other Permission Groups are usually the cleanest way to customize permissions.  You can also %1$scustomize this user directly%2$s.', 'pp' ), "<a href='$edit_url'>", '</a>' );
				?>
				</p>
				</div>
			<?php endif;
				
			return;
		}
		?>
		<div style="clear:both;"></div>
		<div id='pp_current_exceptions_ui' class='pp-group-box <?php echo $class;?>'>
		<h3>
		<?php if ( $edit_url )
			echo "<a href='$edit_url'>$caption</a>";
		else
			echo $caption;
		?>
		</h3>
		<?php 
		echo $exceptions_ui;
		?>
		</div>
		<?php
	}

	public static function display_ui_user_assigned_roles($user) {
		$roles = array();
		
		$post_types = pp_get_enabled_post_types( array(), 'object' );
		$taxonomies = pp_get_enabled_taxonomies( array(), 'object' );
		
		$is_administrator = pp_is_user_administrator() && pp_bulk_roles_enabled() && current_user_can('list_users');
		$edit_url = ( $is_administrator ) ? "admin.php?page=pp-edit-permissions&amp;action=edit&amp;agent_id=$user->ID&amp;agent_type=user" : '';

		$roles = ppc_get_roles( 'user', $user->ID, compact( $post_types, $taxonomies ) );
		$has_user_roles = PP_GroupsUI::_current_roles_ui( $roles, array( 'read_only' => true, 'caption' => sprintf( __( 'Supplemental Roles %1$s(for this user)%2$s', 'pp' ), '<small>', '</small>'), 'class' => 'pp-user-roles', 'link' => $edit_url ) );

		$caption = sprintf( __( 'Exceptions %1$s(for user)%2$s', 'pp' ), '<small>', '</small>' );
		$new_permissions_link = true;
		$maybe_display_note = ! $has_user_roles;
		$display_limit = 12;
		self::abbreviated_exceptions_ui( 'user', $user->ID, compact( 'edit_url', 'caption', 'new_permissions_link', 'maybe_display_note', 'display_limit' ) );
	}

	public static function display_ui_user_roles($user) {
		$roles = array();
		
		$post_types = pp_get_enabled_post_types( array(), 'object' );
		$taxonomies = pp_get_enabled_taxonomies( array(), 'object' );
		
		$is_administrator = pp_bulk_roles_enabled() && current_user_can('pp_manage_groups');
		$edit_url = ( $is_administrator ) ? "admin.php?page=pp-edit-permissions&amp;action=edit&amp;agent_id=$user->ID&amp;agent_type=user" : '';
		
		$user->retrieve_extra_groups();
		
		foreach( array_keys( $user->groups ) as $agent_type ) {
			foreach( array_keys( $user->groups[$agent_type] ) as $agent_id ) {
				$roles = array_merge( $roles, ppc_get_roles( $agent_type, $agent_id, array( 'post_types' => $post_types, 'taxonomies' => $taxonomies, 'query_agent_ids' => array_keys( $user->groups[$agent_type] ) ) ) );
			}
		}
		$link = ( current_user_can( 'pp_assign_roles' ) ) ? "admin.php?page=pp-edit-permissions&amp;action=edit&agent_type=user&amp;agent_id=$user->ID" : '';
		PP_GroupsUI::_current_roles_ui( $roles, array( 'read_only' => true, 'link' => '', 'caption' => sprintf( __( 'Supplemental Roles %1$s(from primary role or group membership)%2$s', 'pp' ), '<small>', '</small>' ) ) );
		
		self::abbreviated_exceptions_ui( 'user', $user->ID, array( 'edit_url' => '', 'class' => 'pp-group-roles', 'caption' => __( 'Exceptions (from primary role or group membership)', 'pp' ), 'join_groups' => 'groups_only', 'display_limit' => 12 ) );
	}
	
	public static function display_ui_user_groups( $include_role_metagroups = false, $args = array() ) {
		$defaults = array( 'initial_hide' => false, 'selected_only' => false, 'hide_checkboxes' => false, 'force_display' => false, 'edit_membership_link' => false, 'user_id' => false );
		extract( array_merge( $defaults, $args ), EXTR_SKIP );
		
		require_once(dirname(__FILE__).'/permissions-ui_pp.php');

		if ( ! is_numeric($user_id) ) {
			global $profileuser;
			$user_id = ( ! empty($profileuser) ) ? $profileuser->ID : 0;
		}
		
		foreach( pp_get_group_types( array( 'editable' => true ) ) as $agent_type ) {
			//if ( ! $agent_type = apply_filters( 'pp_query_group_type', '' ) )
			//	$agent_type = 'pp_group';
			
			if ( ! pp_has_group_cap( 'pp_manage_members', 0, $agent_type ) )
				continue;
			
			if ( ! $all_groups = pp_get_groups( $agent_type ) )
				continue;
			
			$reqd_caps = (array) apply_filters( 'pp_edit_groups_reqd_caps', array('pp_edit_groups') );
			
			// @todo: reinstate?
			//$editable_ids = pp_get_groups( 'pp_group', FILTERED_PP, 'id', compact('reqd_caps') );
			
			if ( current_user_can( 'pp_manage_members' ) )
				$editable_ids = array_keys($all_groups);
			else
				$editable_ids = array();
			
			$stored_groups = pp_get_groups_for_user( $user_id, $agent_type, array( 'cols' => 'id' ) );
			
			//$addable_ids = array_diff( $editable_ids, array_keys($stored_groups) );
			
			$locked_ids = array_diff( array_keys($stored_groups), $editable_ids );
			
			// can't manually edit membership of WP Roles groups or other metagroups lacking _ed_ suffix
			$all_ids = array();
			foreach ( $all_groups as $key => $group ) {
				if ( $selected_only && ! isset( $stored_groups[$group->ID] ) ) {
					unset( $all_groups[$key] );
					continue;
				}
			
				$all_ids[]= $group->ID;

				if ( ! $include_role_metagroups && ! empty($group->metagroup_id) && ( 'wp_role' == $group->metagroup_type ) ) {
					$editable_ids = array_diff( $editable_ids, array($group->ID) );
					unset( $stored_groups[$group->ID] );
					unset( $all_groups[$key] );
					
				} elseif ( ! in_array($group->ID, $editable_ids) && ! in_array($group->ID, $locked_ids) ) {
					unset( $all_groups[$key] );
				}
			}

			$locked_ids = array_diff( array_keys($stored_groups), $editable_ids );
			
			// avoid incorrect eligible count if orphaned group roles are included in editable_ids
			$editable_ids = array_intersect( $editable_ids, $all_ids );

			if ( ! $all_groups && ! $force_display )
				continue;
				
			$style = ( $initial_hide ) ? "style='display:none'" : '';
				
			echo "<div id='userprofile_groupsdiv_pp' class='pp-group-box pp-group_members' $style>";
			echo "<h3>";
			
			if ( 'pp_group' == $agent_type ) {
				if ( defined( 'GROUPS_CAPTION_RS' ) )
					echo ( GROUPS_CAPTION_RS );
				else
					_e( 'Permission Groups', 'pp' );
			} else {
				$group_type_obj = pp_get_group_type_object( $agent_type );
				echo $group_type_obj->labels->name;
			}
			 
			echo "</h3>";
			
			$css_id = $agent_type;
			$args = array( 'eligible_ids' => $editable_ids, 'locked_ids' => $locked_ids, 'show_subset_caption' => false, 'hide_checkboxes' => $hide_checkboxes );
			
			require_once( dirname(__FILE__).'/agents_ui_pp.php');
			$pp_agents_ui = pp_init_agents_ui();
			$pp_agents_ui->agents_ui( $agent_type, $all_groups, $css_id, $stored_groups, $args);
			
			if ( $edit_membership_link || ( ! $all_groups && $force_display ) ) :?>
				<p>
				<?php if ( ! $all_groups && $force_display ) :
				_e( 'This user is not a member of any Permission Groups.', 'pp' );
				?>&nbsp;&bull;&nbsp;
				<?php endif; ?>
				<a href='user-edit.php?user_id=<?php echo $user_id;?>#userprofile_groupsdiv_pp' title='<?php echo( esc_attr( __( 'Edit this user&apos;s group membership' , 'pp' ) ) );?>'><?php _e( 'add / edit membership' );?></a>
				&nbsp;&nbsp;
				<span class="pp-subtext">
				<?php
				$note = apply_filters( 'pp_user_profile_groups_note', ( defined( 'BP_VERSION' ) ) ? __( 'note: BuddyPress Groups and other externally defined groups are not listed here, even if they modify permissions', 'pp' ) : '', $user_id, $args );
				echo $note;
				?>
				</span>
				</p>
			<?php endif;
			
			echo '</div>';

		}  // end foreach agent_type
		
		echo "<input type='hidden' name='pp_editing_user_groups' value='1' />";
	}
} // end class PP_ProfileUI