<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class PP_GroupsUI {
	public static function _draw_member_checklists( $group_id, $agent_type, $args = array() ) {
		$defaults = array( 'member_types' => array( 'member' ), 'suppress_caption' => false );
		$args = array_merge( $defaults, $args );
		extract( $args, EXTR_SKIP );
		
		$captions['member'] = apply_filters( 'pp_group_members_caption', __('Group Members', 'pp') );

		echo '<div class="pp-group-box pp-group_members" style="display:none;float:left;margin-right:20px;">';
		
		if ( ! $suppress_caption ) {
			echo '<h3>';

			// note: member_type other than 'member' is never invoked as of PP Core 2.1-beta		

			$i = 0;
			foreach( $member_types as $member_type ) {
				$link_class = ( $i ) ? 'agp-unselected_agent' : 'agp-selected_agent';
				?>
				<span class="<?php echo "$link_class pp-member-type pp-$member_type";?>"><a href="#" class="<?php echo "pp-$member_type";?>"><?php echo $captions[$member_type]; ?></a></span>
				<?php
				$i++;
				if ( $i < count($member_types) ) :?>
					<span> | </span>
				<?php endif;
			}

			echo '</h3>';
		}

		$i = 0;
		foreach( $member_types as $member_type ) {
			$style = ( $i ) ? ' style="display:none"' : '';
			?>
			<div class="<?php echo "pp-member-type pp-$member_type";?>"<?php echo $style;?>>
			<?php
			self::user_selection_ui( $group_id, $agent_type, $member_type );
			?>
			</div>
			<?php
			$i++;
		}

		echo '</div>';
		
		do_action( 'pp_group_members_ui', $group_id, $agent_type );
	}
	
	public static function draw_type_options( $type_objects, $args = array() ) {
		$defaults = array( 'option_any' => false, 'option_na' => false );
		extract( array_merge( $defaults, $args ), EXTR_SKIP );
		
		if ( ! $type_objects ) {
			//echo "<option>" . __('(none enabled)', 'pp') . '</option>';
			return;
		}
		
		echo "<option class='pp-opt-none' value=''>" . __('select...', 'pp') . '</option>';
		
		foreach( $type_objects as $_type => $type_obj ) {
			echo "<option value='$type_obj->name'>" . $type_obj->labels->singular_name . '</option>';
		}
		
		if ( $option_any )
			echo "<option value='(all)'>" . __( '(all)', 'pp' ) . '</option>';
			
		if ( $option_na ) {
			global $pp_role_defs;
			if ( $pp_role_defs->direct_roles )
				echo "<option value='-1'>" . __( 'n/a', 'pp' ) . '</option>';
		}
	}
	
	public static function _select_clone_ui( $agent ) {
		global $wp_roles;
		_e('Copy Roles and Exceptions from:', 'pp');
		
		$pp_only = (array) pp_get_option( 'supplemental_role_defs' );
	?>
		<select name="pp_select_role">
		
		<?php
		foreach( $wp_roles->role_names as $role_name => $role_caption ) {
			if ( ! in_array( $role_name, $pp_only ) && ( $role_name != $agent->metagroup_id ) && empty( $wp_roles->role_objects[$role_name]->capabilities['activate_plugins'] ) && empty( $wp_roles->role_objects[$role_name]->capabilities['pp_administer_content'] ) && empty( $wp_roles->role_objects[$role_name]->capabilities['pp_unfiltered'] ) )
				echo "<option value='$role_name'>$role_caption</option>";
		}
		?>
		
		</select>
		
		<br />
		<div>
		<input id="pp_clone_permissions" class="button button-primary" type="submit" name="pp_clone_permissions" value="<?php _e('Do Clone');?>">
		</div>
	<?php
	}
	
	public static function _select_roles_ui( $type_objects, $taxonomy_objects ) {
	?>
		<img id="pp_add_role_waiting" class="waiting" style="display:none;position:absolute" src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) )?>" alt="" />
		<table id="pp_add_role">
		<thead>
		<tr>
		<th><?php _e('Post Type', 'pp');?></th>
		<th class="pp-select-role" style="display:none"><?php echo __ppw('Role');?></th>
		<th class="pp-select-cond" style="display:none"><?php _e('for Statuses', 'pp');?></th>
		<th class="pp-add-site-role" style="display:none"></th>
		</tr>
		</thead>
		<tbody>
		<tr>
		<td>
		<select name="pp_select_type">
		<?php 
		self::draw_type_options( $type_objects, array( 'option_na' => true ) );
		do_action( 'pp_role_types_dropdown' );
		?></select></td>
		
		<td class="pp-select-role" style="display:none"><select name="pp_select_role"></select></td>
		
		<td class="pp-select-cond" id="pp_cond_ui" style="display:none">
		<p class="pp-checkbox">
		<input type="checkbox" id="pp_select_cond_" name="pp_select_cond[]" checked="checked" value="" /><label id="lbl_pp_select_cond_" for="pp_select_cond_"> <?php _e('standard statuses', 'pp');?></label>
		</p>
		<p class="pp-checkbox pp_select_private_status" style="display:none">
		<input type="checkbox" id="pp_select_cond_post_status_private" name="pp_select_cond[]" value="post_status:private" style="display:none" /><label for="pp_select_cond_post_status_private"> <?php _e('Status: Private', 'pp');?></label>
		</p>
		</td>
		
		<td class="pp-add-site-role" style="display:none">
		<input id="pp_add_site_role" class="button-secondary" type="submit" name="pp_add_site_role" value="<?php _e('Add Role', 'pp');?>" />
		</td>
		
		</tr>
		</tbody>
		</table>
		
		<div class='pp-ext-promo'>
		<?php
		if ( ! defined( 'PPCE_VERSION' ) && pp_get_option('display_extension_hints') ) {	
			if ( 0 === validate_plugin( "pp-custom-post-statuses/pp-custom-post-statuses.php" ) )
				$msg = __( 'To assign roles for custom post statuses, activate the PP Custom Post Statuses plugin.', 'pp' );
			elseif( true == pp_key_status() )
				$msg = sprintf( __( 'To assign roles for custom post statuses, %1$sinstall%2$s the PP Custom Post Statuses plugin.', 'pp' ), '<a href="admin.php?page=pp-settings&pp_tab=install">', '</a>' );
			else
				$msg = sprintf( __( 'To assign roles for custom post statuses, %1$senter%2%s or %3$spurchase%4$s a support key and install the PP Custom Post Statuses plugin.', 'pp' ), '<a href="admin.php?page=pp-settings&pp_tab=install">', '</a>', '<a href="http://presspermit.com/purchase">', '</a>' );
			
			echo "<div>$msg</div>";
		}
		?>
		
		<?php
		if ( function_exists('bbp_get_version') && ! defined( 'PPP_VERSION' ) && pp_get_option('display_extension_hints') ) {	
			if ( 0 === validate_plugin( "pp-compatibility/pp-compatibility.php" ) )
				$msg = __( 'To assign roles for bbPress forums, activate the PP Compatibility Pack plugin.', 'pp' );
			elseif( true == pp_key_status() )
				$msg = sprintf( __( 'To assign roles for bbPress forums, %1$sinstall%2$s the PP Compatibility Pack plugin.', 'pp' ), '<a href="admin.php?page=pp-settings&pp_tab=install">', '</a>' );
			else
				$msg = sprintf( __( 'To assign roles for bbPress forums, %1$senter%2$s or %3$spurchase%4$s a support key and install the PP Compatibility Pack plugin.', 'pp' ), '<a href="admin.php?page=pp-settings&pp_tab=install">', '</a>', '<a href="http://presspermit.com/purchase">', '</a>' );
			
			echo "<div>$msg</div>";
		}
		?>
		
		<?php
		if ( defined('RVY_VERSION') && ( ! defined( 'PPCE_VERSION' ) || ! defined('PPP_VERSION') ) && pp_get_option('display_extension_hints') ) {	
			if ( 0 === validate_plugin( "pp-collaborative-editing/pp-collaborative-editing.php" ) )
				$msg = __( 'To assign Revisionary exceptions, activate the PP Collaborative Editing and PP Compatibility Pack plugins.', 'pp' );
			elseif( true == pp_key_status() )
				$msg = sprintf( __( 'To assign Revisionary exceptions, %1$sinstall%2$s the PP Collaborative Editing and PP Compatibility Pack plugins.', 'pp' ), '<a href="admin.php?page=pp-settings&pp_tab=install">', '</a>' );
			else
				$msg = sprintf( __( 'To assign Revisionary exceptions, %1$senter%2$s or %3$spurchase%4$s a support key and install the PP Collaborative Editing and PP Compatibility Pack plugins.', 'pp' ), '<a href="admin.php?page=pp-settings&pp_tab=install">', '</a>', '<a href="http://presspermit.com/purchase">', '</a>' );
			
			echo "<div>$msg</div>";
		}
		?>
		</div>
	<?php
	}
	
	public static function _select_exceptions_ui( $type_objects, $taxonomy_objects, $args = array() ) {
		// Discourage anon/all metagroups having read exceptions for specific posts. Normally, that's what post visibility is for.
		$is_all_anon = ( isset( $args['agent'] ) && ! empty($args['agent']->metagroup_id) && in_array( $args['agent']->metagroup_id, array( 'wp_anon', 'wp_all' ) ) );
	?>
		<img id="pp_add_exception_waiting" class="waiting" style="display:none;position:absolute" src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) )?>" alt="" />
		<table id="pp_add_exception">
		<thead>
		<tr>
		<th><?php _e('Post Type', 'pp');?></th>
		<th class="pp-select-x-operation" style="display:none"><?php _e('Post Operation', 'pp');?></th>
		<th class="pp-select-x-mod-type" style="display:none"><?php _e('Adjustment', 'pp');?></th>
		<th class="pp-select-x-via-type" style="display:none"><?php _e('Qualification', 'pp');?></th>
		<th class="pp-select-x-status" style="display:none"><?php _e('Statuses', 'pp');?></th>
		<th class="pp-add-exception" style="display:none"></th>
		</tr>
		</thead>
		<tbody>
		<tr>
		<td>

		<select name="pp_select_x_for_type">
		<?php
		unset( $type_objects['attachment'] ); // may be re-added by extension
		$type_objects = apply_filters( 'pp_append_exception_types', _pp_order_types( apply_filters( 'pp_exception_types', $type_objects ) ) );
		
		if ( ! empty($args['external']) )
			$type_objects = array_merge( $type_objects, $args['external'] );
		
		self::draw_type_options( $type_objects, array( 'option_any' => true ) );
		do_action( 'pp_exception_types_dropdown', $args );

		?></select></td>
		
		<td class="pp-select-x-operation" style="display:none"><select name="pp_select_x_operation"></select></td>
		<td class="pp-select-x-mod-type" style="display:none"><select name="pp_select_x_mod_type"></select></td>
		
		<td class="pp-select-x-via-type" style="display:none"><select name="pp_select_x_via_type"></select>
		
		<div class="pp-select-x-assign-for" id="pp_select_x_assign_for" style="display:none">
		<p class="pp-checkbox">
		<input type="checkbox" name="pp_select_x_item_assign" id="pp_select_x_item_assign" checked="checked" value="1"><label for="pp_select_x_item_assign"><?php _e('for item', 'pp');?></label>
		</p>
		<?php /* </p> */?>
		</div>
		
		</td>
		
		<td class="pp-select-x-status" style="display:none">
		<p class="pp-checkbox"  >
		<input type="checkbox" id="pp_select_x_cond_" name="pp_select_x_cond[]" checked="checked" value="" /><label id="lbl_pp_select_x_cond_" for="pp_select_x_cond_"> <?php _e('(all)', 'pp');?></label>
		</p>
		</td>

		<td class="pp-select-items" style="display:none;padding-right:0"><?php self::_item_select_ui( array_merge( $type_objects, $taxonomy_objects ) );?></td>
	
		</tr>
		</tbody>
		</table>
		
		<?php if ( $is_all_anon ):?>
		<div id="pp-all-anon-warning" class="pp-red" style="display:none;margin-top:10px;margin-bottom:10px">
		<?php _e( 'Warning: Content hidden by exceptions will be displayed if PP is deactivated. Consider setting a private Visibility on Edit Post screen instead.', 'pp' ); ?>
		</div>

		<script type="text/javascript">
		/* <![CDATA[ */
		jQuery(document).ready( function($) {
			$(document).on('change','select[name="pp_select_x_for_type"]',function(){
				$('#pp-all-anon-warning').hide();
			});

			var handle_anon_warning = function() {
				if ( ( 'read' == $('select[name="pp_select_x_operation"]').val() ) && ( 'additional' != $('select[name="pp_select_x_mod_type"]').val() ) && ( 'pp-post-object' == $('select[name="pp_select_x_via_type"] option:selected').attr('class') ) ) {
					$('#pp-all-anon-warning').show();
				} else {
					$('#pp-all-anon-warning').hide();
				}
			}
			
			$(document).on( 'pp_exceptions_ui', handle_anon_warning );
			$(document).on('change','select[name="pp_select_x_via_type"]', handle_anon_warning );
		});
		</script>
		<?php endif; ?>
		
		<div class='pp-ext-promo'>
		<?php
		if ( ! defined( 'PPCE_VERSION' ) && pp_get_option('display_extension_hints') ) {	
			if ( 0 === validate_plugin( "pp-collaborative-editing/pp-collaborative-editing.php" ) )
				$msg = __( 'To assign exceptions for editing, parent selection or term assignment, activate the PP Collaborative Editing plugin.', 'pp' );
			elseif( true == pp_key_status() )
				$msg = sprintf( __( 'To assign exceptions for editing, parent selection or term assignment, %1$sinstall%2$s the PP Collaborative Editing plugin.', 'pp' ), '<a href="admin.php?page=pp-settings&pp_tab=install">', '</a>' );
			else
				$msg = sprintf( __( 'To assign exceptions for editing, parent selection or term assignment, %1$senter%2$s or %3$spurchase%4$s a support key and install the PP Collaborative Editing plugin.', 'pp' ), '<a href="admin.php?page=pp-settings&pp_tab=install">', '</a>', '<a href="http://presspermit.com/purchase">', '</a>' );
			
			echo "<div>$msg</div>";
		}
		?>
		
		<?php
		if ( function_exists('bbp_get_version') && ! defined( 'PPP_VERSION' ) && pp_get_option('display_extension_hints') ) {	
			if ( 0 === validate_plugin( "pp-compatibility/pp-compatibility.php" ) )
				$msg = __( 'To assign exceptions for bbPress forums, activate the PP Compatibility Pack plugin.', 'pp' );
			elseif( true == pp_key_status() )
				$msg = sprintf( __( 'To assign exceptions for bbPress forums, %1$sinstall%2$s the PP Compatibility Pack plugin.', 'pp' ), '<a href="admin.php?page=pp-settings&pp_tab=install">', '</a>' );
			else
				$msg = sprintf( __( 'To assign exceptions for bbPress forums, %1$senter%2$s or %3$spurchase%4$s a support key and install the PP Compatibility Pack plugin.', 'pp' ), '<a href="admin.php?page=pp-settings&pp_tab=install">', '</a>', '<a href="http://presspermit.com/purchase">', '</a>' );
			
			echo "<div>$msg</div>";
		}
		?>
		</div>
	<?php
	}
	
	static function _item_select_ui( $type_objects ) {
		add_filter( 'get_terms_args', array( 'PP_GroupsUI', '_term_select_no_paging' ), 50, 2 );
		add_action( 'admin_print_footer_scripts', array( 'PP_GroupsUI', '_hide_term_select_paging' ) );
		
		foreach( $type_objects as $type_obj ) {
			if ( defined( 'PP_' . strtoupper($type_obj->name) . '_NO_EXCEPTIONS' ) )
				continue;
	
			$type_obj = apply_filters( 'pp_permit_items_meta_box_object', $type_obj );	
			
			//if ( ! defined( 'PP_' . strtoupper($type_obj->name) . '_TRUNCATE_EXCEPTIONS_UI' ) )
			//	$type_obj->_default_query['posts_per_page'] = 999;	// @todo: support paging in item selection metabox

			if ( post_type_exists( $type_obj->name ) )
				$metabox_function = "pp_nav_menu_item_post_type_meta_box";
			elseif( taxonomy_exists( $type_obj->name ) )
				$metabox_function = "pp_nav_menu_item_taxonomy_meta_box";
			elseif ( in_array( $type_obj->name, array( 'pp_group', 'pp_net_group' ) ) )
				$metabox_function = "pp_nav_menu_item_group_meta_box";
			elseif ( ! $metabox_function = apply_filters( 'pp_item_select_metabox_function', '', $type_obj ) )
				continue;

			add_meta_box( "select-exception-{$type_obj->name}", sprintf( __('Select %s', 'pp'), $type_obj->labels->name), $metabox_function, 'edit-exceptions', 'side', 'default', $type_obj );
		}
		?>
		<div id="nav-menus-frame">
		<div id="menu-settings-column" class="metabox-holder pp-menu-settings-column">
			<form id="nav-menu-meta" class="nav-menu-meta" method="post" enctype="multipart/form-data">
				<!-- <input type="hidden" name="action" value="add-menu-item" /> -->
				<?php 
				wp_nonce_field( 'add-exception_item', 'menu-settings-column-nonce' );
				?>
				<?php do_meta_boxes( 'edit-exceptions', 'side', null ); ?>
			</form>
		</div><?php //#menu-settings-column ?>
			<div class="nav-tabs-wrapper" style="display:n-one">
			<div class="nav-tabs">
				<span class="nav-tab menu-add-new nav-tab-active"></span>
				<ul id="menu-to-edit" class="menu ui-sortable"> </ul>
			</div><?php //#nav-tabs ?>
		</div><?php //#nav-tabs-wrapper ?>
		</div><?php //#nav-menus-frame ?>
		<?php
	}
	
	public static function _term_select_no_paging( $args, $taxonomies ) {
		$args['number'] = 999;
		return $args;
	}
	
	public static function _hide_term_select_paging() {
		?>
		<script type="text/javascript">
		/* <![CDATA[ */
		jQuery(document).ready( function($) {
			//$('.add-menu-item-pagelinks').hide();
		});
		/* ]]> */
		</script>
		<?php
	}
	
	public static function _selected_roles_ui() {
	?>
		<div id="pp_site_selection_msg" class="pp-error-note pp-edit-msg" style="display:none"></div>
	
		<div id="pp_review_roles" class="pp-save-roles" style="display:none">
		<!-- <form name="pp_role_selections"> -->
		<table id="pp_tbl_role_selections">
		<thead>
		<tr>
		<th><?php _e('Post Type', 'pp');?></th>
		<th><?php echo __ppw('Role');?></th>
		<th><?php _e('Status', 'pp');?></th>
		<th></th>
		</tr>
		</thead>
		<tbody>
		</tbody>
		</table>
		<!--</form>-->
		<div id="pp_save_roles"><?php 
		//submit_button( __('Save Roles', 'pp'), 'primary' ); 
		?>
		<p class="submit">
		<input id="submit_roles" class="button button-primary" type="submit" value="<?php _e('Save Roles', 'pp');?>" name="submit">
		</p>
		
		</div>
		</div>
		<div id="pp_new_submission_msg" style="display:none"></div>
	<?php
	}

	public static function _selected_exceptions_ui() {
	?>
		<div id="pp_item_selection_msg" class="pp-error-note pp-edit-msg" style="display:none"></div>
	
		<div id="pp_review_exceptions" class="pp-save-exceptions" style="display:none">
		<!-- <form name="pp_role_selections"> -->
		<table id="pp_tbl_exception_selections">
		<thead>
		<tr>
		<th><?php _e('Post Type', 'pp');?></th>
		<th><?php _e('Operation', 'pp');?></th>
		<th><?php _e('Adjustment', 'pp');?></th>
		<th><?php _e('Qualification', 'pp');?></th>
		<th></th>
		<th><?php _e('Status', 'pp');?></th>
		<th></th>
		</tr>
		</thead>
		<tbody>
		</tbody>
		</table>
		<!--</form>-->
		<div id="pp_save_exceptions"><?php 
		//submit_button( __('Save Exceptions', 'pp'), 'primary' ); 
		?>
		<p class="submit">
		<input id="submit_exc" class="button button-primary" type="submit" value="<?php _e('Save Exceptions', 'pp');?>" name="submit">
		</p>
		
		</div>
		</div>
		<div id="pp_new_x_submission_msg" style="display:none"></div>
	<?php
	}

	public static function _draw_group_permissions( $agent_id, $agent_type, $url, $wp_http_referer = '', $args = array() ) {
		global $current_user;
		
		//$defaults = array( 'agent' => (object) array() );
		
		$post_types = _pp_order_types( pp_get_enabled_post_types( array(), 'object' ) );
		$taxonomies = _pp_order_types( pp_get_enabled_taxonomies( array( 'object_type' => false ), 'object' ) );
		//$taxonomies ['link_category'] = (object) array( 'name' => 'link_category', 'labels' => (object) array( 'name' => __ppw('Link Categories'), 'singular_name' => __ppw('Link Categories') ) );
		
		$perms = array();
		
		if ( ( 'pp_group' == $agent_type ) && ( $group = pp_get_group( $agent_id ) ) )
			$is_wp_role = ( 'wp_role' == $group->metagroup_type );
		
		if ( empty($group) || ! in_array( $group->metagroup_id, array( 'wp_anon', 'wp_all' ) ) )
			$perms['roles'] = __('Add Supplemental Roles', 'pp');
		
		$perms['exceptions'] = __('Add Exceptions', 'pp');

		if ( ! isset( $perms['roles'] ) )
			$current_tab = 'pp-add-exceptions';
		elseif ( ! isset( $perms['roles'] ) )
			$current_tab = 'pp-add-roles';
		elseif ( ! $current_tab = get_user_option( 'pp-permissions-tab' ) )
			$current_tab = ( isset($perms['roles']) ) ? 'pp-add-roles' : 'pp-add-exceptions';
		
		if ( ( $args['agent']->metagroup_type == 'wp_role' ) && ! in_array( $args['agent']->metagroup_id, array( 'wp_anon', 'wp_all' ) ) ) {
			$perms['clone'] = __('Clone', 'pp');
		}

		// --- add permission tabs ---
		echo "<div style='clear:both'></div><ul id='pp_add_permission_tabs' class='pp-list_horiz' style='margin-bottom:-3px'>";
		foreach( $perms as $perm_type => $_caption ) {
			$class = ( "pp-add-$perm_type" == $current_tab ) ? 'agp-selected_agent' : 'agp-unselected_agent';

			echo "<li class='agp-agent pp-add-$perm_type pp-add-permissions $class'><a class='pp-add-{$perm_type}' href='javascript:void(0)'>" . $_caption . '</a></li>';
		}
		echo '</ul>';

		// --- divs for add Roles / Exceptions ---
		$arr = array_keys($perms);
		$first_perm_type = reset( $arr );
		foreach( array_keys($perms) as $perm_type ) {
			$display_style = ( "pp-add-$perm_type" == $current_tab ) ? '' : ';display:none';
			echo "<div class='pp-group-box pp-add-permissions pp-add-$perm_type' style='clear:both{$display_style}'>";
			echo '<div>';
			
			if ( 'roles' == $perm_type ) {
				// temp workaround for bbPress
				self::_select_roles_ui( array_diff_key( $post_types, array_fill_keys( array( 'topic', 'reply' ), true ) ), $taxonomies );
			} elseif ( 'exceptions' == $perm_type ) {
				if ( ! isset( $args['external'] ) )
					$args['external'] = array();

				self::_select_exceptions_ui( array_diff_key( $post_types, array_fill_keys( array( 'topic', 'reply' ), true ) ), $taxonomies, $args );
			}
			?>
			<form id="group-<?php echo $perm_type;?>-selections" action="<?php echo esc_url($url); ?>" method="post"<?php do_action('pp_group_edit_form_tag'); ?>>
			<?php wp_nonce_field("pp-update-{$perm_type}_" . $agent_id, "_pp_nonce_$perm_type" ) ?>
			
			<?php
			if ( 'clone' == $perm_type ) {
				self::_select_clone_ui( $args['agent'] );
			}
			?>
			<?php if ( $wp_http_referer ) : ?>
				<input type="hidden" name="wp_http_referer" value="<?php echo esc_url($wp_http_referer); ?>" />
			<?php endif; ?>
			<input type="hidden" name="action" value="pp_update<?php echo $perm_type;?>" />
			<input type="hidden" name="agent_id" value="<?php echo esc_attr($agent_id); ?>" />
			<input type="hidden" name="agent_type" value="<?php echo esc_attr($agent_type); ?>" />
			<input type="hidden" name="member_csv" value="-1" />
			<input type="hidden" name="group_name" value="-1" />
			<input type="hidden" name="description" value="-1" />
			<?php
			if ( 'roles' == $perm_type )
				self::_selected_roles_ui();
			elseif ( 'exceptions' == $perm_type )
				self::_selected_exceptions_ui(); 
			?>
			</form>
			<?php
			
			echo '</div></div>';
		} // end foreach perm_type (roles, exceptions)
		
		$args['agent_type'] = $agent_type;		

		$roles = ppc_get_roles( $agent_type, $agent_id, compact( $post_types, $taxonomies ) );
		$args['class'] = ( 'user' == $agent_type ) ? 'pp-user-roles' : 'pp-group-roles';
		$args['agent_type'] = $agent_type;
		self::_current_roles_ui( $roles, $args );

		$post_types[''] = ''; // also retrieve exceptions for (all) post type
		
		$_args = array( 'assign_for' => '', 'extra_cols' => array('i.assign_for', 'i.eitem_id'), 'agent_type' => $agent_type, 'agent_id' => $agent_id, 'post_types' => array_keys($post_types), 'taxonomies' => array_keys($taxonomies), 'return_raw_results' => true );
		if ( empty( $_REQUEST['show_propagated'] ) )
			$_args['inherited_from'] = 0;
		else
			$_args['extra_cols'] []= 'i.inherited_from';
		
		$exc = ppc_get_exceptions( $_args );
		$args['class'] = ( 'user' == $agent_type ) ? 'pp-user-roles' : 'pp-group-roles';
		
		self::_current_exceptions_ui( $exc, $args );
		
		do_action( 'pp_group_roles_ui', $agent_type, $agent_id );
	}
	
	public static function _current_roles_ui( $roles, $args = array() ) {
		global $pp_admin, $pp_role_defs;

		$defaults = array( 'read_only' => false, 'caption' => '', 'class' => 'pp-group-roles', 'link' => '', 'agent_type' => '' );
		$args = array_merge( $defaults, $args );
		extract( $args );
		
		if ( ! $caption )
			$caption = ( 'user' == $agent_type ) ? sprintf( __('Supplemental Roles %1$s(for user)%2$s', 'pp'), '<small>', '</small>' ) : __('Supplemental Roles', 'pp');
		
		$type_roles = array();
		
		if ( ! $roles )
			return;
		
		// @todo: still necessary?
		$has_roles = false;
		foreach( array_keys($roles) as $key ) {
			if ( ! empty($roles[$key]) )
				$has_roles = true;
		}
		
		if ( ! $has_roles )
			return;
		
		$can_assign = current_user_can('pp_assign_roles') && pp_bulk_roles_enabled();
		
		echo '<div style="clear:both;"></div>'
			. "<div id='pp_current_roles_header' class='pp-group-box $class'>"
			. '<h3>';

		if ( $link )
			echo "<a href='$link'>$caption</a>";
		else
			echo $caption;
		
		echo '</h3>';
		echo '<div>';
		
		$_class = ( $read_only ) ? ' class="pp-readonly"' : '';
		echo '<div id="pp_current_roles"' . $_class . '>';
		
		foreach( array_keys($roles) as $role_name ) {
			if ( strpos( $role_name, ':' ) ) {
				$arr = explode( ':', $role_name );
				//$pattern_role_name = $arr[0];
				$src_name = $arr[1];
				$object_type = $arr[2];
			} else {
				$object_type = '';
				
				$src_name = ( 0 === strpos( $role_name, 'pp_' ) && strpos( $role_name, '_manager' ) ) ? 'term' : 'post';
			}

			$type_roles[$src_name][$object_type][$role_name] = true;
		}

		//ksort( $type_roles );
		
		foreach( array_keys($type_roles) as $src_name ) {
			ksort( $type_roles[$src_name] );
		
			foreach( array_keys($type_roles[$src_name]) as $object_type ) {
				if ( $type_obj = pp_get_type_object( $src_name, $object_type ) ) {
					$type_caption = $type_obj->labels->singular_name; 
				} elseif ( 'term' == $src_name ) {
					if ( ! defined( 'PPCE_VERSION' ) )  // term management roles will not be applied without collaborative editing extension, so do not display
						continue;
					
					$type_caption = __( 'Term Management', 'pp' );
				} else {
					$_role_name = key( $type_roles[$src_name][$object_type] );
					if ( false === strpos( $_role_name, ':' ) ) {
						$type_obj = (object) array( 'labels' => (object) array( 'name' => __( 'objects', 'pp' ), 'singular_name' => __( 'objects', 'pp' ) ) );
						$type_caption = __( 'Direct-Assigned', 'pp' );
					} else  {
						$type_obj = (object) array( 'labels' => (object) array( 'name' => __( 'objects', 'pp' ), 'singular_name' => __( 'objects', 'pp' ) ) );
						$type_caption = __( 'Disabled Type', 'pp' );
					}
				}
				
				echo "<div class='type-roles-wrapper'>";
				echo '<h4 style="margin-bottom:0.3em">' . sprintf( __( '%s Roles', 'pp' ), $type_caption ) . '</h4>';
				echo "<div class='pp-current-type-roles'>";
				
				// site roles
				if ( isset( $type_roles[$src_name][$object_type] ) ) {
					echo "<div id='pp_current_{$src_name}_{$object_type}_site_roles' class='pp-current-roles pp-current-site-roles'>";
					$inputs = array();
				
					$_arr = $type_roles[$src_name][$object_type];
					ksort( $_arr );
					foreach( array_keys($_arr) as $role_name ) {
						$role_title = ppc_get_role_title( $role_name, array( 'include_warnings' => true ) );
						
						if ( $read_only ) {
							$inputs []= "<label>$role_title</label>";
						} else {
							$ass_id = $roles[$role_name];
							$cb_id = 'pp_edit_role_' . str_replace( ',', '_', $ass_id );
							$inputs []= "<label for='$cb_id'><input id='$cb_id' type='checkbox' name='pp_edit_role[]' value='$ass_id'>&nbsp;" . $role_title . '</label>';
						}
					}
					
					$sep = ( $read_only ) ? ',&nbsp; ' : ' ';
					echo implode( $sep, $inputs );	
					
					echo '</div>';
				}
				
				echo '</div></div>';
				
			} // end foreach object_type
		} // end foreach src_name

		echo '<br /><div class="pp-role-bulk-edit" style="display:none">';
		echo "<select><option value=''>" . __ppw('Bulk Actions', 'pp') . "</option><option value='remove'>" . __ppw('Remove', 'pp') . '</option></select>';
		//submit_button( __ppw('Apply'), 'button-secondary submit-edit-item-role', '', false );
		?>
		<input type="submit" name="" class="button submit-edit-item-role" value="<?php _e('Apply', 'pp');?>" />
		<?php

		echo '<img class="waiting" style="display:none;" src="' . esc_url( admin_url( 'images/wpspin_light.gif' ) ) . '" alt="" />';
		echo '</div>';
		
		echo '</div>';
		
		echo '</div></div>';
		
		return true;
	}
	
	public static function _current_exceptions_ui( $exc_results, $args = array() ) {
		global $pp_admin, $pp_role_defs, $pp_data_sources;

		$defaults = array( 'read_only' => false, 'class' => 'pp-group-roles', 'item_links' => false, 'caption' => '', 'link' => '', 'agent_type' => '' );
		$args = array_merge( $defaults, $args );
		extract( $args );
		
		if ( ! $exc_results )
			return;
		
		if ( ! $caption )
			$caption = ( 'user' == $agent_type ) ? sprintf( __('Exceptions %1$s(for user)%2$s', 'pp'), '<small>', '</small>' ) : __('Exceptions', 'pp');
		
		require_once( PPC_ABSPATH . '/lib/ancestry_lib_pp.php' );
		
		$can_assign = current_user_can('pp_assign_roles') && pp_bulk_roles_enabled();

		$exceptions = array_fill_keys( array_merge( array( 'term', 'post' ), pp_get_group_types() ), array() );
		
		$item_paths = array_fill_keys( array_keys($exceptions), array( __('(none)', 'pp') ) );	// support imported include exception with no items included

		$post_types = pp_get_enabled_post_types( array(), 'names' );
		$taxonomies = pp_get_enabled_taxonomies( array( 'object_type' => false ), 'names' );
		
		foreach( $exc_results as $row ) {			// object_type not strictly necessary here, included for consistency with term role array
			switch( $row->via_item_source ) {
				case 'term' :
					if ( $row->item_id ) {
						$taxonomy = '';	
						$term_id = (int) pp_ttid_to_termid( $row->item_id, $taxonomy );

						if ( $row->item_id )
							$item_paths['term'][$row->item_id] = PP_Ancestry::get_term_path( $term_id, $taxonomy );
						
						$via_type = $taxonomy;
					} else
						$via_type = $row->via_item_type;
						
					break;
					
				case 'post':
					if ( $row->item_id )
						$item_paths['post'][$row->item_id] = PP_Ancestry::get_post_path( $row->item_id );
					// no break
					
				default:
					if ( pp_group_type_exists($row->via_item_source) ) {
						static $groups_by_id;
						
						if ( ! isset($groups_by_id) )
							$groups_by_id = array();

						if ( ! isset($groups_by_id[$row->via_item_source]) ) {
							$groups_by_id[$row->via_item_source] = array();
							
							foreach( pp_get_groups( $row->via_item_source, array( 'skip_meta_types' => 'wp_role' ) ) as $group ) {
								$groups_by_id[$row->via_item_source][$group->ID] = $group->name;
							}
						}
						
						if ( isset( $groups_by_id[$row->via_item_source][$row->item_id] ) )
							$item_paths[$row->via_item_source][$row->item_id] = $groups_by_id[$row->via_item_source][$row->item_id];
							
						$via_type = $row->via_item_source;
					} else
						$via_type = ( $row->via_item_type ) ? $row->via_item_type : $row->for_item_type;
			}
			
			if ( ! isset( $exceptions[$row->via_item_source][$via_type] ) )
				$exceptions[$row->via_item_source][$via_type] = array();
				
			if ( ! isset( $exceptions[$row->via_item_source][$via_type][$row->for_item_type] ) )
				$exceptions[$row->via_item_source][$via_type][$row->for_item_type] = array();
				
			if ( ! isset( $exceptions[$row->via_item_source][$via_type][$row->for_item_type][$row->operation] ) )
				$exceptions[$row->via_item_source][$via_type][$row->for_item_type][$row->operation] = array();
			
			if ( ! isset( $exceptions[$row->via_item_source][$via_type][$row->for_item_type][$row->operation][$row->mod_type] ) )
				$exceptions[$row->via_item_source][$via_type][$row->for_item_type][$row->operation][$row->mod_type] = array();
			
			if ( ! isset( $exceptions[$row->via_item_source][$via_type][$row->for_item_type][$row->operation][$row->mod_type][$row->for_item_status] ) )
				$exceptions[$row->via_item_source][$via_type][$row->for_item_type][$row->operation][$row->mod_type][$row->for_item_status] = array();

			if ( ! isset( $exceptions[$row->via_item_source][$via_type][$row->for_item_type][$row->operation][$row->mod_type][$row->for_item_status][$row->item_id] ) )
				$exceptions[$row->via_item_source][$via_type][$row->for_item_type][$row->operation][$row->mod_type][$row->for_item_status][$row->item_id] = array();
				
			$exceptions[$row->via_item_source][$via_type][$row->for_item_type][$row->operation][$row->mod_type][$row->for_item_status][$row->item_id][$row->assign_for] = $row->eitem_id;
			
			if ( ! empty($row->inherited_from) )
				$exceptions[$row->via_item_source][$via_type][$row->for_item_type][$row->operation][$row->mod_type][$row->for_item_status][$row->item_id]['inherited_from'] = $row->inherited_from;
		}
		
		echo '<div style="clear:both;"></div>'
			. "<div id='pp_current_exceptions' class='pp-group-box $class'>"
			. '<h3>';

		if ( $link )
			echo "<a href='$link'>$caption</a>";
		else
			echo $caption;

		echo '</h3>';
		echo '<div>';
		
		echo '<div id="pp_current_exceptions_inner">';
		
		//ksort( $type_roles );
		
		if ( empty( $_REQUEST['all_types'] ) && ! empty( $exceptions['post'] ) ) {
			$all_types = array_fill_keys( array_merge( $post_types, $taxonomies, array( '' ) ), true );
			$all_types = array_diff_key( $all_types, array( 'topic' => true, 'reply' => true ) );  // hide topic, reply assignments even if they are somehow saved/imported without inherited_from value
			
			$exceptions['post'] = array_intersect_key( $exceptions['post'], $all_types );
			
			foreach( array_keys( $exceptions['post'] ) as $key ) {
				$exceptions['post'][$key] = array_intersect_key( $exceptions['post'][$key], $all_types );
			}
		}
		
		foreach( array_keys($exceptions) as $via_src ) {
			ksort( $exceptions[$via_src] );
			
			foreach( array_keys($exceptions[$via_src]) as $via_type ) {
				if ( $via_type_obj = pp_get_type_object( $via_src, $via_type ) ) {
					$via_type_caption = $via_type_obj->labels->singular_name; 
				} else
					continue;

				$any_redundant = false;

				echo "<div id='pp_current_{$via_src}_{$via_type}_roles' class='pp-current-exceptions'>";
				
				/*
				if ( 'term' == $via_src )
					echo '<h4>' . __( 'Per-Term:', 'pp' ) . '</h4>';
				else {
					if ( $object_type )
						echo '<h4>' . sprintf( __( 'Per-%s:', 'pp' ), $type_caption ) . '</h4>';
					else
						echo '<h4>' . sprintf( __( 'Per-object:', 'pp' ), $type_caption ) . '</h4>';
				}

				echo '<h4>' . sprintf( __( '%s Exceptions', 'pp' ), $via_type_caption ) . '</h4>';
				*/
				
				ksort( $exceptions[$via_src][$via_type] );
				
				foreach( array_keys($exceptions[$via_src][$via_type]) as $for_type ) {
					if ( pp_group_type_exists($for_type) )
						$for_src = $for_type;
					else
						$for_src = ( taxonomy_exists($for_type) || ! $for_type ) ? 'term' : 'post';

					if ( ! $for_type )
						$for_type_obj = (object) array( 'labels' => (object) array( 'singular_name' => __('(all post types)', 'pp') ) );
					elseif ( ! $for_type_obj = pp_get_type_object( $for_src, $for_type ) )
						continue;
					
					foreach( array_keys($exceptions[$via_src][$via_type][$for_type]) as $operation ) {
						if ( ! $operation_obj = pp_get_op_object( $operation, $for_type ) )
							continue;
	
						if ( 'assign' == $operation )
							$op_caption = ( $for_type ) ? sprintf( __('%1$s (%2$s: %3$s)', 'pp'), $operation_obj->label, $for_type_obj->labels->singular_name, $via_type_caption ) : sprintf( __('%1$s %2$s %3$s', 'pp'), $operation_obj->label, $via_type_caption, $for_type_obj->labels->singular_name );
						elseif ( in_array( $operation, array( 'manage', 'associate' ) ) )
							$op_caption = sprintf( __('%1$s %2$s', 'pp'), $operation_obj->label, $via_type_caption );
						else
							$op_caption = sprintf( __('%1$s %2$s', 'pp'), $operation_obj->label, $for_type_obj->labels->singular_name );
						
						echo "<div class='type-roles-wrapper'>";
						echo '<h4>' . $op_caption . '</h4>';
						echo "<div class='pp-current-type-roles'>";
						
						echo '<div class="pp-current-roles-tbl-wrapper"><table>';
					
						// fill table body (item assignments for each role)
						echo '<tbody>';
						
						foreach( array_keys($exceptions[$via_src][$via_type][$for_type][$operation]) as $mod_type ) {
							if ( ! $mod_type_obj = pp_get_mod_object( $mod_type ) )
								continue;
							
							foreach( array_keys($exceptions[$via_src][$via_type][$for_type][$operation][$mod_type]) as $status ) {
								if ( $status ) {
									$_status = explode( ':', $status );
									if ( count( $_status ) > 1 ) {
										$attrib = $_status[0];
										$_status = $_status[1];
									} else {
										$attrib = 'post_status';
										$_status = $status;
									}
									
									if ( 'post_status' == $attrib ) {
										if ( $status_obj = get_post_status_object( $_status ) ) {
											$status_label = $status_obj->label;
										} elseif ( '{unpublished}' == $_status ) {	// @todo: API
											$status_label = __( 'unpublished', 'pp' );
										} else {
											$status_label = $status;
										}
									} else
										$status_label = $status;
									
									$mod_caption = sprintf( __('%1$s (%2$s)', 'pp'), $mod_type_obj->label, $status_label );
								} else {
									$mod_caption = $mod_type_obj->label;
								}
						
								if ( ( 'exclude' == $mod_type ) && ! empty( $exceptions[$via_src][$via_type][$for_type][$operation]['include'] ) ) {
									$tr_class = ' class="pp_faded"';
									$mod_caption = sprintf( __( '* %s', 'pp' ), $mod_caption );
									$any_faded = true;
								} else
									$tr_class = '';
						
								echo "<tr{$tr_class}><td class='pp_item_role_caption'>$mod_caption</td>";
								echo '<td>';
								
								//if ( $item_links ) {
								//	if ( 'term' != $scope )
								//		$edit_url_base = $pp_data_sources->member_property( $item_source, '_edit_link' );
								//}

								echo "<div class='pp-role-terms-wrapper pp-role-terms-{$via_type}'>";

								if ( ( 'term' == $via_src ) && ! in_array( $operation, array( 'manage', 'associate' ) ) ) {
									if ( taxonomy_exists( $via_type ) ) {
										// "Categories:"
										$tx_obj = get_taxonomy($via_type);
										$tx_caption = $tx_obj->labels->name;
									} else
										$tx_caption = '';
									
									echo '<div class="pp-taxonomy-caption">' . sprintf( __('%s:', 'pp'), $tx_caption ) . '</div>';

									//$edit_url_base = ( isset($tx_obj->_edit_link) ) ? $tx_obj->_edit_link : '';
								}
								
								echo '<div class="pp-role-terms">';
								
								$tx_item_paths = array_intersect_key( $item_paths[$via_src], $exceptions[$via_src][$via_type][$for_type][$operation][$mod_type][$status] );
		
								uasort( $tx_item_paths, 'strnatcasecmp' );  // sort by array values, but maintain keys );
								
								foreach( $tx_item_paths as $item_id => $item_path ) {
									//$assignment = $roles[$scope][$role_name][$item_source][$item_type][$item_id];
									$assignment = $exceptions[$via_src][$via_type][$for_type][$operation][$mod_type][$status][$item_id];
									
									$classes = array();
									
									if ( isset($assignment['children']) ) {
										if ( isset($assignment['item']) ) {
											$ass_id = $assignment['item'] . ',' . $assignment['children'];
											$classes []= 'role_both';
											$any_both = true;
										} else {
											$ass_id = '0,' . $assignment['children'];
											$classes []= 'role_ch';
											$any_child_only = true;
										}
									} else {
										$ass_id = $assignment['item'];
									}
									
									$class = ( $classes ) ? "class='" . implode( ' ', $classes ) . "'" : '';
								
									if ( $read_only ) {
										if ( $item_links ) {
											//$item_edit_url = sprintf($edit_url_base, $item_id);
											$item_edit_url = '';
											echo "<div><a href='$item_edit_url' $class>$item_path</a></div>";
										} else 
											echo "<div><span $class>$item_path</span></div>";
									} else {
										$cb_id = 'pp_edit_exception_' . str_replace( ',', '_', $ass_id );
										
										if ( ! empty($assignment['inherited_from']) ) {
											$classes []= 'inherited';
											$classes []= "from_{$assignment['inherited_from']}";
										}
										
										if ( $tr_class ) // apply fading for redundantly stored exclusions
											$classes []= $tr_class;
										
										$lbl_class = ( $classes ) ? "class='" . implode( ' ', $classes ) . "'" : '';
										
										if ( 'term' == $via_src )
											$edit_url = admin_url( "edit-tags.php?taxonomy={$via_type}&action=edit&tag_ID=" . pp_ttid_to_termid( $item_id, $via_type ) . "&post_type=$for_type" );
										else
											$edit_url = admin_url( "post.php?post=$item_id&action=edit" );
										echo "<div><label for='$cb_id' $lbl_class><input id='$cb_id' type='checkbox' name='pp_edit_exception[]' value='$ass_id' $class> " . $item_path . '</label><a href="' . $edit_url . '">' . __('edit') . '</a></div>';
									}
								} // end foreach item
								
								if ( ( count($tx_item_paths) > 3 ) && ! $read_only ) {
									$cb_id = "pp_check_all_{$via_src}_{$via_type}_{$for_type}_{$operation}_{$status}";
									echo "<div><label for='$cb_id'><input type='checkbox' id='$cb_id' class='pp_check_all'> " . __('(all)', 'pp')  . '</label></div>';
								}
								
								echo '</div></div>';   // pp-role-terms, pp-role-terms-wrapper
								
								echo '</td></tr>';
							} // end foreach status
						} // end foreach mod_type
						echo '</tbody>';
					
						echo '</table></div>';  // pp-current-roles-tbl-wrapper
					
						echo '<div class="pp-exception-bulk-edit" style="display:none">';
						echo "<select><option value=''>" . __ppw('Bulk Actions', 'pp') . "</option><option value='remove'>" . __ppw('Remove', 'pp') . '</option>';
						
						if ( ( 'post' == $via_src ) && ( ! $via_type || $via_type_obj->hierarchical ) ) {
							echo "<option value='propagate'>" . sprintf( __('Assign for selected and sub-%s', 'pp'), $via_type_obj->labels->name ) . '</option>';
							echo "<option value='unpropagate'>" . sprintf( __('Assign for selected %s only', 'pp'), $via_type_obj->labels->singular_name ) . '</option>';
							echo "<option value='children_only'>" . sprintf( __('Assign for sub-%s only', 'pp'), $via_type_obj->labels->name ) . '</option>';
						} elseif ( 'term' == $via_src && $via_type_obj->hierarchical ) {
							echo "<option value='propagate'>" . __('Assign for selected and sub-terms', 'pp') . '</option>';
							echo "<option value='unpropagate'>" . __('Assign for selected term only', 'pp') . '</option>';
							echo "<option value='children_only'>" . __('Assign for sub-terms only', 'pp') . '</option>';
						}
						
						echo '</select>';
						//submit_button( __ppw('Apply'), 'button-secondary submit-edit-item-exception', '', false );
						?>
						<input type="submit" name="" class="button submit-edit-item-exception" value="<?php _e('Apply', 'pp');?>" />
						<?php
						echo '<img class="waiting" style="display:none;" src="' . esc_url( admin_url( 'images/wpspin_light.gif' ) ) . '" alt="" />';
						echo '</div>';  // pp-exception-bulk-edit
						
						echo '</div></div>';  // type-roles-wrapper, pp-current-type-roles
					} // end foreach operation
				} // end foreach for_type
				
				if ( $any_redundant ) {
					echo '<div class="pp-current-roles-note">' . __('* = exceptions redundant due to a corresponding &quot;only these&quot; entry', 'pp') . '</div>';
				}
				
				if ( ! empty($via_type_obj->hierarchical) ) {
					$_caption = strtolower($via_type_obj->labels->name);
					
					if ( ! empty($any_both) || ! empty($any_child_only) ) :?>
						<div class="pp-current-roles-note">

						<?php 
						if ( ! empty( $any_both ) )
							echo '<span class="role_both" style="padding-right:20px">' . sprintf( __('... = assigned for %1$s and sub-%1$s', 'pp'), $_caption ) . '</span>';
					
						if ( ! empty( $any_child_only ) )
							echo '<span>' . sprintf( __('* = assigned for sub-%s only', 'pp'), $_caption ) . '</span>';
						?>
						</div>
					<?php endif;
						
					$show_all_url = esc_url( add_query_arg( 'show_propagated', '1', $_SERVER['REQUEST_URI'] ) );
					$show_all_link = "&nbsp;&nbsp;<a href='$show_all_url'>";
					
					if ( empty( $_REQUEST['show_propagated'] ) ) {
						if ( 'term' == $via_src )
							echo '<div class="pp-current-roles-note">' . sprintf( __('note: Exceptions inherited from parent %1$s are not displayed. %2$sshow all%3$s', 'pp'), $_caption, $show_all_link, '</a>' ) . '</div>';
						else
							echo '<div class="pp-current-roles-note">' . sprintf( __('note: Exceptions inherited from parent %1$s or terms are not displayed. %2$sshow all%3$s', 'pp'), $_caption, $show_all_link, '</a>' ) . '</div>';
					}
				}
				
				echo '</div>';  // pp-current-exceptions

			} // end foreach via_type
			
		} // end foreach via_src

		echo '</div>';  // pp_current_exceptions_inner
		
		echo '</div>';  // no class
		
		echo '</div>';  // pp_current_exceptions
	}
	
	// Called once each for members checklist, managers checklist in admin UI.
	// In either case, current (checked) members are at the top of the list.
	public static function user_selection_ui( $group_id = 0, $agent_type, $user_class = 'member', $all_users = '' ) {
		global $pp;
		
		// This is only needed for checkbox selection
		if ( ! $all_users )
			$all_users = self::get_all_users( 'id_name' );

		$current_users = ( $group_id ) ? pp_get_group_members( $group_id, $agent_type, 'all', array( 'member_type' => $user_class, 'status' => 'any' ) ) : array();

		$args = array( 
			'suppress_extra_prefix' => true, 
			'ajax_selection' => true,
			'agent_id' => $group_id,
		);

		echo '<div>';

		echo '<div class="pp-agent-select">';
 		$pp_agents_ui = pp_init_agents_ui();
		$pp_agents_ui->agents_ui( 'user', $all_users, $user_class, $current_users, $args );
		echo '</div>';
		
		echo '</div>';
	}
	
	public static function get_all_users( $cols = 'id', $args = array() ) {
		require_once( PPC_ABSPATH.'/users_pp.php' );
		$args['cols'] = $cols;
		return PP_Users::get_users( $args );
	}
}
