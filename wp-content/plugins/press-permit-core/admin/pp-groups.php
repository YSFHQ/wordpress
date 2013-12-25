<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 
require_once( dirname(__FILE__).'/pp-permits-helper.php' );
 
global $pp_admin;

if ( ! $agent_type = apply_filters( 'pp_query_group_type', ( isset($_REQUEST['agent_type']) ) ? pp_sanitize_key($_REQUEST['agent_type']) : '' ) )
	$agent_type = 'pp_group';

$group_variant = ( isset($_REQUEST['group_variant']) ) ? pp_sanitize_key($_REQUEST['group_variant']) : '';
$group_variant = apply_filters( 'pp_query_group_variant', $group_variant );

$pp_groups_list_table = apply_filters( 'pp_groups_list_table', false, $agent_type ); 

if ( false == $pp_groups_list_table ) {
	global $pp_groups_list_table;

	if ( empty($pp_groups_list_table) ) {
		require_once( dirname(__FILE__).'/includes/class-pp-groups-list-table.php' );
		$pp_groups_list_table = new PP_Groups_List_Table();
	}
}

$pagenum = $pp_groups_list_table->get_pagenum();

// contextual help - choose Help on the top right of admin panel to preview this.
/*
add_contextual_help($current_screen,
    '<p>' . __('This screen lists all the existing users for your site. Each user has one of five defined roles as set by the site admin: Site Administrator, Editor, Author, Contributor, or Subscriber. Users with roles other than Administrator will see fewer options in the dashboard navigation when they are logged in, based on their role.') . '</p>' .
    '<p>' . __('You can customize the display of information on this screen as you can on other screens, by using the Screen Options tab and the on-screen filters.') . '</p>' .
    '<p>' . __('To add a new user for your site, click the Add New button at the top of the screen or Add New in the Users menu section.') . '</p>' .
    '<p><strong>' . __('For more information:') . '</strong></p>' .
    '<p>' . __('<a href="http://codex.wordpress.org/Users_Screen" target="_blank">Documentation on Managing Users</a>') . '</p>' .
    '<p>' . __('<a href="http://codex.wordpress.org/Roles_and_Capabilities" target="_blank">Descriptions of Roles and Capabilities</a>') . '</p>' .
    '<p>' . __('<a href="http://wordpress.org/support/" target="_blank">Support Forums</a>') . '</p>'
);
*/

$url = $referer = $redirect = $update = '';
PP_Permits_Helper::get_url_properties( $url, $referer, $redirect );

if ( ! empty( $_REQUEST['action2'] ) && ! is_numeric($_REQUEST['action2']) )
	$action = $_REQUEST['action2'];
elseif ( ! empty( $_REQUEST['action'] ) && ! is_numeric($_REQUEST['action']) )
	$action = $_REQUEST['action'];
elseif ( ! empty( $_REQUEST['pp_action'] ) )
	$action = $_REQUEST['pp_action'];
else
	$action = '';

switch ( $action ) {
//switch ( $pp_groups_list_table->current_action() ) {

case 'delete':
case 'bulkdelete':
	if ( empty($_REQUEST['groups']) )
		$groupids = array(intval($_REQUEST['group']));
	else
		$groupids = (array) $_REQUEST['groups'];
?>
<form action="" method="post" name="updategroups" id="updategroups">
<?php wp_nonce_field('delete-groups') ?>
<?php echo $referer; ?>

<div class="wrap">
<?php pp_icon(); ?>
<h2><?php _e('Delete Groups'); ?></h2>
<p><?php echo _n( 'You have specified this group for deletion:', 'You have specified these groups for deletion:', count( $groupids ), 'pp' ); ?></p>
<ul>
<?php
	$go_delete = 0;
	
	if ( ! $agent_type = apply_filters( 'pp_query_group_type', '' ) )
		$agent_type = 'pp_group';
	
	foreach ( $groupids as $id ) {
		$id = (int) $id;
		if ( $group = pp_get_group( $id, $agent_type ) ) {
			echo "<li><input type=\"hidden\" name=\"users[]\" value=\"" . esc_attr($id) . "\" />" . sprintf(__('ID #%1s: %2s'), $id, $group->name) . "</li>\n";
			$go_delete++;
		}
	}
	?>
	</ul>
<?php if ( $go_delete ) : ?>
	<input type="hidden" name="action" value="dodelete" />
	<?php submit_button( __('Confirm Deletion'), 'secondary' ); ?>
<?php else : ?>
	<p><?php _e('There are no valid groups selected for deletion.', 'pp'); ?></p>
<?php endif; ?>
</div>
</form>
<?php

break;

default:
	$pp_groups_list_table->prepare_items();
	$total_pages = $pp_groups_list_table->get_pagination_arg( 'total_pages' );

	$messages = array();
	if ( isset($_GET['update']) ) :
		switch($_GET['update']) {
		case 'del':
		case 'del_many':
			$delete_count = isset($_GET['delete_count']) ? (int) $_GET['delete_count'] : 0;
			$messages[] = '<div id="message" class="updated"><p>' . sprintf(_n('%s group deleted', '%s groups deleted', $delete_count, 'pp'), $delete_count) . '</p></div>';
			break;
		case 'add':
			$messages[] = '<div id="message" class="updated"><p>' . __('New group created.', 'pp') . '</p></div>';
			break;
		}
	endif; ?>

<?php if ( isset($pp_admin->errors) && is_wp_error( $pp_admin->errors ) ) : ?>
	<div class="error">
		<ul>
		<?php
			foreach ( $pp_admin->get_error_messages() as $err )
				echo "<li>$err</li>\n";
		?>
		</ul>
	</div>
<?php endif;

if ( ! empty($messages) ) {
	foreach ( $messages as $msg )
		echo $msg;
} ?>

<div class="wrap pp-groups">
<?php pp_icon(); ?>
<h2>
<?php
if ( ( 'pp_group' == $agent_type ) || ! $group_type_obj = pp_get_group_type_object( $agent_type ) )
	$groups_caption = ( defined( 'PP_GROUPS_CAPTION' ) ) ? PP_GROUPS_CAPTION : __('Permission Groups', 'pp');
else
	$groups_caption = $group_type_obj->labels->name;

echo esc_html( $groups_caption );

/*
if ( current_user_can( 'pp_edit_groups' ) ) {
	if ( PP_MULTISITE && pp_get_option('ms_netwide_groups') )
		$url = 'users.php';
	else
		$url = 'admin.php';
}
*/
$url = 'admin.php';

if ( pp_group_type_editable( $group_variant ) && current_user_can('pp_create_groups') ) { 
?>
	<a href="<?php echo add_query_arg( array( 'agent_type' => $agent_type, 'page' => 'pp-group-new' ), $url );?>" class="add-new-h2"><?php echo esc_html( __ppw( 'Add New' ) ); ?></a>
<?php }

echo '</h2>';

if ( pp_get_option('display_hints') ) {
	echo '<div class="pp-hint">';
	
	if ( defined( 'PP_GROUPS_HINT' ) ) {
		echo esc_html( PP_GROUPS_HINT );
	} else {
		echo esc_html( __( 'Permission Groups are sets of users to which you may assign supplemental roles or exceptions. To customize permissions for an individual user instead, click their Role in the Users listing.', 'pp' ) );
	}

	echo '</div><br />';
}

/* if ( current_user_can( 'create_users' ) ) { ?> */

$group_types = array( 'pp_group' => (object) array( 'labels' => (object) array( 'singular_name' => __('Custom Group', 'pp') ) ) );

if ( current_user_can( 'pp_administer_content' ) )
	$group_types['wp_role'] = (object) array( 'labels' => (object) array( 'singular_name' => __('WP Role', 'pp') ) );

$group_types = apply_filters( 'pp_list_group_types', array_merge( $group_types, pp_get_group_types( array(), 'object' ) ) );   // currently faking WP Role as a "group type", but want it listed before BuddyPress Group

$links = array();
foreach( $group_types as $_group_type => $gtype_obj ) {
	$agent_type_str = ( 'wp_role' == $_group_type ) ? "&agent_type=pp_group" : "&agent_type=$_group_type";
	$gvar_str = "&group_variant=$_group_type";
	$class = strpos( $agent_type_str, $agent_type ) && ( ! $group_variant || strpos( $gvar_str, $group_variant ) ) ? 'class="current"' : '';
	$links[]= "<li><a href='admin.php?page=pp-groups{$agent_type_str}{$gvar_str}' $class>{$gtype_obj->labels->singular_name}</a></li>";
}

echo '<ul class="subsubsub">';
printf( __( '%1$sGroup Type:%2$s %3$s', 'pp' ), '<li class="pp-gray"><strong>', '</strong></li>', implode( '&nbsp;|&nbsp;', $links ) );
echo '</ul>';

if ( ! empty($groupsearch) )
	printf( '<span class="subtitle">' . __('Search results for &#8220;%s&#8221;') . '</span>', esc_html( $groupsearch ) ); ?>
</h2>

<?php $pp_groups_list_table->views(); ?>

<form action="<?php echo "$url"?>" method="get">
<input type="hidden" name="page" value="pp-groups" />
<input type="hidden" name="agent_type" value="<?php echo $agent_type ?>" />
<?php 
//echo esc_html( __( 'Posts with a custom Visibility or Editability require a corresponding Permission Group role assignment.', 'pp' ) );
$pp_groups_list_table->search_box( __( 'Search Groups', 'pp' ), 'group' ); 
?>

<?php $pp_groups_list_table->display(); ?>

</form>

<br class="clear" />

<?php
if ( defined( 'BP_VERSION') && ! defined( 'PPCE_VERSION' ) && pp_get_option('display_extension_hints') ) {	
	if ( 0 === validate_plugin( "pp-buddypress-role-groups/pp-buddypress-role-groups.php" ) )
		$msg = __( 'To assign roles or exceptions to BuddyPress groups, activate the PP BuddyPress Role Groups', 'pp' );
	elseif( true == pp_key_status() )
		$msg = sprintf( __( 'To assign roles or exceptions to BuddyPress groups, %1$sinstall%2$s the PP BuddyPress Role Groups plugin.', 'pp' ), '<a href="admin.php?page=pp-settings&pp_tab=install">', '</a>' );
	else
		$msg = sprintf( __( 'To assign roles or exceptions to BuddyPress groups, %1$enter%2$s or %3$spurchase%4$s a support key and install the PP BuddyPress Role Groups plugin.', 'pp' ), '<a href="admin.php?page=pp-settings&pp_tab=install">', '</a>', '<a href="http://presspermit.com/purchase">', '</a>' );
	
	echo "<div class='pp-ext-promo'>$msg</div>";
}
?>

</div>
<?php

break;

} // end of the $doaction switch
