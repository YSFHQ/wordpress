<?php
/**
 * Edit user administration panel.
 *
 * @package WordPress
 * @subpackage Administration
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once( dirname(__FILE__).'/permissions-ui_pp.php');

$agent_type = ( ! empty($_REQUEST['agent_type']) ) ? pp_sanitize_key($_REQUEST['agent_type']) : 'pp_group';

if ( empty($_REQUEST['agent_id']) ) {
	$agent_id = 0;
	$agent = (object) array( 'metagroup_type' => '' );
	
	if ( 'user' != $agent_type )
		wp_die( __('No user/group specified.', 'pp') );
} else {
	$agent_id = (int) $_REQUEST['agent_id'];
	$agent = pp_get_agent( $agent_id, $agent_type );
}
	
$metagroup_type = ( ! empty($agent->metagroup_type) ) ? $agent->metagroup_type : '';

if ( 'user' == $agent_type ) {
	if ( ! current_user_can( 'pp_administer_content' ) || ! current_user_can( 'list_users' ) )
		wp_die( __( 'You are not permitted to do that.', 'pp' ) );

	if ( $agent_id && empty( $agent->ID ) )
		wp_die( __('Invalid user ID.', 'pp') );
} else {
	if ( ! pp_has_group_cap( 'pp_edit_groups', $agent_id, $agent_type ) )
		wp_die( __( 'You are not permitted to do that.', 'pp' ) );
		
	if ( ( 'wp_role' == $metagroup_type ) && ! current_user_can( 'pp_administer_content' ) )
		wp_die( __( 'You are not permitted to do that.', 'pp' ) );
		
	if ( ! $agent )
		wp_die( __('Invalid group ID.', 'pp') );
}

if ( $metagroup_type ) {  // metagroups cannot have name/description manually edited
	$agent->name = PP_GroupRetrieval::get_metagroup_name( $metagroup_type, $agent->metagroup_id, $agent->name );
	$agent->group_description = PP_GroupRetrieval::get_metagroup_descript( $metagroup_type, $agent->metagroup_id, $agent->group_description );
}

$url = apply_filters( 'pp_groups_base_url', 'admin.php' );

if ( isset( $_REQUEST['wp_http_referer'] ) )
	$wp_http_referer = $_REQUEST['wp_http_referer'];
elseif ( isset($_SERVER['HTTP_REFERER']) && ! strpos( $_SERVER['HTTP_REFERER'], 'page=pp-group-new' ) )
	$wp_http_referer = $_SERVER['HTTP_REFERER'];
else
	$wp_http_referer = '';
	
$wp_http_referer = remove_query_arg( array('update', 'delete_count'), stripslashes($wp_http_referer) );

// contextual help - choose Help on the top right of admin panel to preview this.
/*
add_contextual_help($current_screen,
    '<p>' . __('Your profile contains information about you (your &#8220;account&#8221;) as well as some personal options related to using WordPress.') . '</p>' .
    '<p>' . __('Required fields are indicated; the rest are optional. Profile information will only be displayed if your theme is set up to do so.') . '</p>' .
    '<p>' . __('Remember to click the Update Profile button when you are finished.') . '</p>' .
    '<p><strong>' . __('For more information:') . '</strong></p>' .
    '<p>' . __('<a href="http://codex.wordpress.org/Users_Your_Profile_Screen" target="_blank">Documentation on User Profiles</a>') . '</p>' .
    '<p>' . __('<a href="http://wordpress.org/support/" target="_blank">Support Forums</a>') . '</p>'
);
*/
?>

<?php if ( isset($_GET['updated']) ) : ?>
	<div id="message" class="updated"><p>
	
	<?php if( ! empty($_REQUEST['pp_roles']) ) : ?>
	<strong><?php _e('Roles updated.', 'pp') ?>&nbsp;</strong>

	<?php elseif( ! empty($_REQUEST['pp_exc']) ) : ?>
	<strong><?php _e('Exceptions updated.', 'pp') ?>&nbsp;</strong>

	<?php else : ?>
	<strong><?php _e('Group updated.', 'pp') ?>&nbsp;</strong>
	<?php 
	if ( $wp_http_referer ) : ?>
		<a href="<?php echo esc_url( $wp_http_referer ); ?>"><?php _e('Back to groups list', 'pp'); ?></a>
	<?php endif; ?>
	
	<?php endif; ?>
	
	</p></div>
	
<?php elseif ( isset($_GET['created']) ) : ?>
	<div id="message" class="updated"><p>
	<strong><?php _e('Group created.', 'pp') ?>&nbsp;</strong>
	<?php 
	if ( $wp_http_referer ) : ?>
		<a href="<?php echo esc_url( $wp_http_referer ); ?>"><?php _e('Back to groups list', 'pp'); ?></a>
	<?php endif; ?>
	</p></div>
	
<?php endif; ?>

<?php 
global $pp_admin;

if ( ! empty( $pp_admin->errors ) && is_wp_error( $pp_admin->errors ) ) : ?>
<div class="error"><p><?php echo implode( "</p>\n<p>", $pp_admin->errors->get_error_messages() ); ?></p></div>
<?php endif; ?>

<div class="wrap" id="group-profile-page">
<?php pp_icon(); ?>
<h2><?php 

if ( 'user' == $agent_type )
	( $agent_id ) ? _e('Edit User Permissions', 'pp' ) : _e('Add User Permissions', 'pp' );
	
elseif( 'wp_role' == $metagroup_type )
	_e('Edit Permission Group (WP Role)', 'pp' );
	
elseif ( 'pp_group' == $agent_type )
	_e('Edit Permission Group', 'pp' );

elseif ( $group_type_obj = pp_get_group_type_object( $agent_type ) )
	printf( __( 'Edit Permissions (%s)', 'pp' ), $group_type_obj->labels->singular_name );

?></h2>

<div id="pp_cred_wrap">
<form id="agent-profile" class="pp-admin <?php echo esc_attr($agent_type) . '-profile';?>" action="<?php echo esc_url($url); ?>" method="post"<?php do_action('pp_group_edit_form_tag'); ?>>
<?php wp_nonce_field('pp-update-group_' . $agent_id) ?>

<?php if ( $wp_http_referer ) : ?>
	<input type="hidden" name="wp_http_referer" value="<?php echo esc_url($wp_http_referer); ?>" />
<?php endif; ?>

<?php
$disabled = ( ! pp_group_type_editable( $agent_type ) || $agent->metagroup_id ) ? 'disabled="disabled"' : '';

// @todo: better html / css for update button pos
?>
<table class="pp-agent-profile">
<tr><td>
<table class="form-table">
<?php if ( ( 'user' == $agent_type ) && $agent_id && ( $agent->name != $agent->user_login ) ) : ?>
<tr>
	<th><label for="user_login"><?php echo __ppw('User Login:'); ?></label></th>
	<td><?php echo $agent->user_login;?>
	</td>
</tr>
<?php endif; ?>

<?php if ( $agent_id ) :?>
<tr>
	<th><label><!-- <label for="group_name"> --><?php echo __ppw('Name:'); ?></label></th>
	<td>
	<?php if ( ( 'user' == $agent_type ) ):
		echo "<a href='user-edit.php?user_id=$agent_id'>$agent->name</a>";
	else : ?>
	<input type="text" name="group_name" id="group_name" value="<?php echo esc_attr($agent->name); ?>" class="regular-text" <?php echo $disabled;?> /> 
	<?php endif; ?>
	</td>
</tr>
<?php endif;?>

<?php if ( ( 'user' == $agent_type ) && $agent_id ) :
	global $wp_roles;
	$user = new WP_User($agent_id);
	$primary_role = reset( $user->roles );
	if ( isset( $wp_roles->role_names[$primary_role] ) ) :?>
		<tr>
		<th><label><!-- <label for="user_login"> --><?php echo __('Primary Role:', 'pp'); ?></label></th>
		<td><?php 
		if ( $role_group_id = pp_get_metagroup( 'wp_role', $primary_role, array( 'cols' => 'id' ) ) )
			echo "<a href='admin.php?page=pp-edit-permissions&action=edit&agent_type=pp_group&agent_id=$role_group_id'>{$wp_roles->role_names[$primary_role]}</a>";
		else
			echo $wp_roles->role_names[$primary_role];
		?>
		</td>
		</tr>
	<?php endif; ?>
<?php elseif ($agent_id) : ?>
<tr>
	<th><label for="description"><?php echo __ppw('Description:', 'pp'); ?></label></th>
	<td><input type="text" name="description" id="description" value="<?php echo esc_attr($agent->group_description) ?>" class="regular-text" <?php echo $disabled;?> style="width:95%" /></td>
</tr>
<?php endif; ?>
</table>
</td>

<td>
<div class="pp-submit" style="text-align:right">
<?php 
if ( pp_group_type_editable( $agent_type ) && ( empty($agent->metagroup_type) || ! in_array( $agent->metagroup_type, array( 'wp_role', 'meta_role' ) ) || apply_filters( 'pp_metagroup_editable', false, $agent->metagroup_type, $agent_id ) ) ) { 
	submit_button( __('Update Group', 'pp') );
} 
?>
</div>
</td>
</tr>
</table>

<?php

do_action( 'pp_group_edit_form', $agent_type, $agent_id );

if ( $agent_id ) {
	if ( pp_group_type_editable($agent_type) && ! in_array( $agent->metagroup_type, array( 'wp_role', 'meta_role' ) ) ) {
		$member_types = array();

		if ( pp_has_group_cap( 'pp_manage_members', $agent_id, $agent_type ) )
			$member_types []= 'member';

		if ( $member_types )
			PP_GroupsUI::_draw_member_checklists( $agent_id, $agent_type, compact( 'member_types' ) );
	}
} elseif( 'user' == $agent_type ) {
	echo '<br />';
	PP_GroupsUI::_draw_member_checklists( 0, 'pp_group', array( 'suppress_caption' => true ) );
}

do_action( 'pp_edit_group_profile', $agent_type, $agent_id );
?>

<input type="hidden" name="action" value="update" />
<input type="hidden" name="agent_id" id="agent_id" value="<?php echo esc_attr($agent_id); ?>" />
<input type="hidden" name="agent_type" id="agent_type" value="<?php echo esc_attr($agent_type); ?>" />

</form>

<?php
if ( current_user_can('pp_assign_roles') && pp_bulk_roles_enabled() ) {
	PP_GroupsUI::_draw_group_permissions($agent_id, $agent_type, $url, $wp_http_referer, compact( 'agent' ) );
}


if ( 'user' == $agent_type ): ?>
	<div>
	<?php if ( $agent_id ) {
		$roles = array();
		$user = pp_get_user( $agent_id );
		$user->retrieve_extra_groups();
		
		$post_types = pp_get_enabled_post_types( array(), 'object' );
		$taxonomies = pp_get_enabled_taxonomies( array(), 'object' );

		foreach( array_keys( $user->groups ) as $agent_type ) {
			foreach( array_keys( $user->groups[$agent_type] ) as $_agent_id ) {
				$args = compact( $post_types, $taxonomies );
				$args['query_agent_ids'] = array_keys( $user->groups[$agent_type] );
				$roles = array_merge( $roles, ppc_get_roles( $agent_type, $_agent_id, $args ) );
			}
		}
		
		require_once( dirname(__FILE__).'/profile_ui_pp.php');
		PP_ProfileUI::display_ui_user_groups( false, array('initial_hide' => true, 'selected_only' => true, 'force_display' => true, 'edit_membership_link' => true, 'hide_checkboxes' => true, 'user_id' => $agent_id ) );

		$role_group_caption = sprintf( __( 'Supplemental Roles %1$s(from primary role or %2$sgroup membership%3$s)%4$s', 'pp' ), '<small>', "<a class='pp-show-groups' href='#'>", '</a>', '</small>' );
		PP_GroupsUI::_current_roles_ui( $roles, array( 'read_only' => true, 'class' => 'pp-group-roles', 'caption' => $role_group_caption ) );
		
		$exceptions = array();
		
		$args = array( 'assign_for' => '', 'inherited_from' => 0, 'extra_cols' => array('i.assign_for', 'i.eitem_id'), 'post_types' => array_keys($post_types), 'taxonomies' => array_keys($taxonomies), 'return_raw_results' => true );

		foreach( array_keys( $user->groups ) as $agent_type ) {
			$args['agent_type'] = $agent_type;
			$args['ug_clause'] = " AND e.agent_type = '$agent_type' AND e.agent_id IN ('" . implode( "','", array_keys( $user->groups[$agent_type] ) ) . "')";
			$args['query_agent_ids'] = array_keys( $user->groups[$agent_type] );

			$exceptions = array_merge( $exceptions, ppc_get_exceptions( $args ) );
		}
		PP_GroupsUI::_current_exceptions_ui( $exceptions, array( 'read_only' => true, 'class' => 'pp-group-roles', 'caption' => $role_group_caption ) );
	} else {
		?>
		<h4>
		<?php
		$url = "users.php";
		printf( __( 'View currently stored user permissions:', 'pp' ) );
		?>
		</h4>
		<ul class="pp-notes">
		<li><?php printf( __( '%1$sUsers who have Supplemental Roles assigned directly%2$s', 'pp' ), "<a href='$url?pp_user_roles=1'>", '</a>' );?></li>
		<li><?php printf( __( '%1$sUsers who have Exceptions assigned directly%2$s', 'pp' ), "<a href='$url?pp_user_exceptions=1'>", '</a>' );?></li>
		<li><?php printf( __( '%1$sUsers who have Supplemental Roles or Exceptions directly%2$s', 'pp' ), "<a href='$url?pp_user_perms=1'>", '</a>' );?></li>
		</ul>
		<?php 
	} // endif ($agent_id) ?>
	</div>
<?php endif;

if ( pp_bulk_roles_enabled() ) {
	echo '<div class="pp_exceptions_notes">';
	echo '<div><strong>' . __('Exceptions Explained:', 'pp') . '</strong>';
	echo "<ul>";
	echo "<li>" . __( 'Not These : Specified items are not accessible unless an "Also These" exception is also stored.', 'pp' ) . '</li>';
	echo "<li>" . __( 'Only These : Item access as defined by user / group role(s) is further limited by these selections. Users will still need sufficient capabilities in their primary or supplemental roles.', 'pp' ) . '</li>';
	echo "<li>" . __( 'Also These : Specified items are accessible regardless of role(s) and other exceptions.', 'pp' ) . '</li>';
	echo "</ul>";
	echo '</div>';

	echo '<div>';
	_e( 'Keep in mind that roles and exceptions can be assigned to WP Roles, BuddyPress Groups, Custom Groups and/or individual Users.  "Not These" and "Only These" exceptions are unavailable for groups in some contexts.', 'pp' );
	echo '</div>';
	echo '</div>';
}
?>

<?php if ( 'user' == $agent_type ): ?>
	<!-- </div> -->
<?php endif; ?>

</div></div> <?php // #pp_cred_wrap, #group-profile-page ?>
<?php
?>