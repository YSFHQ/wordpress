<?php
/**
 * Edit user administration panel.
 *
 * @package WordPress
 * @subpackage Administration
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once( dirname(__FILE__).'/permissions-ui_pp.php');

$url = apply_filters( 'pp_groups_base_url', 'admin.php' );

if ( isset( $_REQUEST['wp_http_referer'] ) )
	$wp_http_referer = $_REQUEST['wp_http_referer'];
elseif ( isset($_SERVER['HTTP_REFERER']) ) {
	if ( ! strpos( $_SERVER['HTTP_REFERER'], 'page=pp-group-new' ) )
		$wp_http_referer = $_SERVER['HTTP_REFERER'];
	else
		$wp_http_referer = '';

	$wp_http_referer = remove_query_arg( array('update', 'delete_count'), stripslashes($wp_http_referer) );
} else
	$wp_http_referer = '';
	
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

if ( ! current_user_can( 'pp_create_groups' ) )
	wp_die( __( 'You are not permitted to do that.', 'pp' ) );
?>

<?php 
global $pp_admin;

if ( isset($_GET['update']) && empty( $pp_admin->errors ) ) : ?>
	<div id="message" class="updated">
	<p><strong><?php _e('Group created.', 'pp') ?>&nbsp;</strong>
	<?php if ( $wp_http_referer ) : ?>
		<a href="<?php echo esc_url( $wp_http_referer ); ?>"><?php _e('Back to groups list', 'pp'); ?></a>
	<?php endif; ?>
	</p></div>
<?php endif; ?>

<?php 
if ( ! empty( $pp_admin->errors ) && is_wp_error( $pp_admin->errors ) ) : ?>
<div class="error"><p><?php echo implode( "</p>\n<p>", $pp_admin->errors->get_error_messages() ); ?></p></div>
<?php endif; ?>

<div class="wrap" id="group-profile-page">
<?php 
pp_icon(); 
?>
<h2><?php 
$agent_type = ( isset($_REQUEST['agent_type']) && pp_group_type_editable( $_REQUEST['agent_type'] ) ) ? pp_sanitize_key($_REQUEST['agent_type']) : 'pp_group';

if ( ( 'pp_group' == $agent_type ) || ! $group_type_obj = pp_get_group_type( $agent_type ) )
	_e('Create New Permission Group', 'pp' );
else
	printf( __('Create New %s', 'pp'), $group_type_obj->labels->singular_name );
?></h2>

<form action="" method="post" id="creategroup" name="creategroup" class="pp-admin">
<input name="action" type="hidden" value="creategroup" />
<input name="agent_type" type="hidden" value="<?php echo $agent_type;?>" />
<?php wp_nonce_field( 'pp-create-group', '_wpnonce_pp-create-group' ) ?>

<?php if ( $wp_http_referer ) : ?>
	<input type="hidden" name="wp_http_referer" value="<?php echo esc_url($wp_http_referer); ?>" />
<?php endif; ?>

<table class="form-table">
<tr class="form-field form-required">
	<th scope="row"><label for="group_name"><?php echo __ppw('Name'); ?></label></th>
	<td><input type="text" name="group_name" id="group_name" value="" class="regular-text" /> </td>
</tr>

<tr class="form-field">
	<th><label for="description"><?php echo __ppw('Description', 'pp'); ?></label></th>
	<td><input type="text" name="description" id="description" value="" class="regular-text" size="80" /></td>
</tr>
</table>

<?php
if ( pp_has_group_cap( 'pp_manage_members', 0, $agent_type ) ) {
	PP_GroupsUI::_draw_member_checklists( 0, $agent_type );
}

echo '<div style="clear:both;margin-top:10px;"><br />';
_e( 'Note: Supplemental Roles and other group settings can be configured here after the new group is created.', 'pp' );
echo '</div>';

do_action( 'pp_new_group_ui' );
?>

<?php 
submit_button( __('Create Group', 'pp'), 'primary large pp-submit' ); 
?>

</form>
</div>
<?php
?>
