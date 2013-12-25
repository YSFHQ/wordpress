<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<div class="wrap pp-groups">
<?php pp_icon(); ?>
<h2>
<?php
_e( 'User Permissions', 'pp' );
?>
</h2>

<p style="margin-top:50px;">
<h4><a href="admin.php?page=pp-edit-permissions&amp;action=edit&amp;agent_type=user">
<?php
_e( 'Bulk-Add User Permissions', 'pp' );
?>
</a>
</h4>
<?php if ( pp_get_option( 'display_hints' ) ) : ?>
<span class="pp-subtext">
<?php printf( __( '%1$snote%2$s: If you need to assign the same role or exception to multiple users, grouping them into a WP Role or custom Permission Group usually leads to a cleaner configuration.', 'pp' ), '<strong>', '</strong>' ); ?>
</span>
<?php endif; ?>
</p>

<div style="margin-top:50px;">
<h4>
<?php
_e( 'View / Edit User Permissions', 'pp' );
?>
</h4>

<div>
<?php
$url = "users.php";
printf( __( 'To assign supplemental roles and exceptions directly to a single user, click their "Site Role" cell on the %1$sUsers%2$s screen. The Users listing can be filtered by the following links:', 'pp' ), "<a href='$url'>", '</a>' );
?>
<br /><br />
<ul class="pp-notes">
<li><?php printf( __( '%1$sAll Users%2$s', 'pp' ), "<a href='$url'>", '</a>' );?></li>
</ul>
<br />
<ul class="pp-notes">
<li><?php printf( __( '%1$sUsers who have Supplemental Roles assigned directly%2$s', 'pp' ), "<a href='$url?pp_user_roles=1'>", '</a>' );?></li>
<li><?php printf( __( '%1$sUsers who have Exceptions assigned directly%2$s', 'pp' ), "<a href='$url?pp_user_exceptions=1'>", '</a>' );?></li>
<li><?php printf( __( '%1$sUsers who have Supplemental Roles or Exceptions directly%2$s', 'pp' ), "<a href='$url?pp_user_perms=1'>", '</a>' );?></li>
</ul>
<br />
<ul class="pp-notes">
<li><?php printf( __( '%1$sUsers who have Supplemental Roles (directly or via group)%2$s', 'pp' ), "<a href='$url?pp_has_roles=1'>", '</a>' );?></li>
<li><?php printf( __( '%1$sUsers who have Exceptions  (directly or via group)%2$s', 'pp' ), "<a href='$url?pp_has_exceptions=1'>", '</a>' );?></li>
<li><?php printf( __( '%1$sUsers who have Supplemental Roles or Exceptions  (directly or via group)%2$s', 'pp' ), "<a href='$url?pp_has_perms=1'>", '</a>' );?></li>
</ul>
</div>

<?php if ( pp_get_option( 'display_hints' ) ) : ?>
<span class="pp-subtext">
<?php printf( __( '%1$snote%2$s: If you don&apos;t see the Site Role column on the Users screen, make sure it is enabled in Screen Options. ', 'pp' ), '<strong>', '</strong>' ); ?>
</span>
<?php endif; ?>

</div>

</div>