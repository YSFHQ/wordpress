<div style="margin-top:5px">
<a href="#pp-pro-info"><?php _e( 'Show list of PP Pro features and screencasts', 'pp' );?></a>
</div>

<?php
$img_url = PP_URLPATH . '/admin/images/';
$lang_id = 'pp';
?>
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready( function($) {
	$('a[href=#pp-pro-info]').click( function() {
		$('#pp_features').show();
		$('ul.pro-pplinks').show();
		return false;
	});
	$('a[href=#pp-pro-hide]').click( function() {
		$('#pp_features').hide();
		$('ul.pro-pplinks').hide();
		return false;
	});
});
/* ]]> */
</script>
<style>
#pp_features {text-align:left;border:1px solid #eee;margin:10px 20px 20px 20px;background-color:white}
div.pp-logo, div.pp-logo img { text-align: left; clear:both }
ul.pp-features { list-style: none; padding-top:10px; text-align:left; margin-left: 50px; margin-top: 0; }
ul.pp-features li:before { content: "\2713\0020"; }
ul.pp-features li { padding-bottom: 5px }
img.cme-play { margin-bottom: -3px; margin-left: 5px;}
ul.pro-pplinks {
margin-top: 0;
margin-left:20px;
width:100%;
}
ul.pro-pplinks li{
display: inline;
margin: 0 3px 0 3px;
}
ul.pro-pplinks li.spacer{
font-size: 1.5em;
}
</style>

<div id="pp_features" style="display:none"><div class="pp-logo"><a href="http://presspermit.com"><img src="<?php echo $img_url;?>pp-logo.png" /></a>

<ul class="pp-features">

<li>
<?php _e( "Assign standard WP roles supplementally for a specific post type", $lang_id );?>
<a href="http://presspermit.com/tutorial/regulate-post-type-access" target="_blank"><img class="cme-play" src="<?php echo $img_url;?>play.png" /></a></li>

<li>
<?php _e( "Assign custom WP roles supplementally for a specific post type <em>(Pro)</em>", $lang_id );?>
<a href="http://presspermit.com/tutorial/custom-role-usage" target="_blank"><img class="cme-play" src="<?php echo $img_url;?>play.png" /></a></li>

<li>
<?php _e( "Customize reading permissions per-category or per-post", $lang_id );?>
<a href="http://presspermit.com/tutorial/category-exceptions" target="_blank"><img class="cme-play" src="<?php echo $img_url;?>play.png" /></a></li>

<li>
<?php _e( "Customize editing permissions per-category or per-post <em>(Pro)</em>", $lang_id );?>
<a href="http://presspermit.com/tutorial/page-editing-exceptions" target="_blank"><img class="cme-play" src="<?php echo $img_url;?>play.png" /></a></li>

<li>
<?php _e( "Custom Post Visibility statuses, fully implemented throughout wp-admin <em>(Pro)</em>", $lang_id );?>
<a href="http://presspermit.com/tutorial/custom-post-visibility" target="_blank"><img class="cme-play" src="<?php echo $img_url;?>play.png" /></a></li>

<li>
<?php _e( "Custom Moderation statuses for access-controlled, multi-step publishing workflow <em>(Pro)</em>", $lang_id );?>
<a href="http://presspermit.com/tutorial/multi-step-moderation" target="_blank"><img class="cme-play" src="<?php echo $img_url;?>play.png" /></a></li>

<li>
<?php _e( "Regulate permissions for Edit Flow post statuses <em>(Pro)</em>", $lang_id );?>
<a href="http://presspermit.com/tutorial/edit-flow-integration" target="_blank"><img class="cme-play" src="<?php echo $img_url;?>play.png" /></a></li>

<li>
<?php _e( "Customize the moderated editing of published content with Revisionary or Post Forking <em>(Pro)</em>", $lang_id );?>
<a href="http://presspermit.com/tutorial/published-content-revision" target="_blank"><img class="cme-play" src="<?php echo $img_url;?>play.png" /></a></li>

<li>
<?php _e( "Grant Spectator, Participant or Moderator access to specific bbPress forums <em>(Pro)</em>", $lang_id );?>
<a href="http://presspermit.com/tutorial/bbpress-forum-permissions" target="_blank"><img class="cme-play" src="<?php echo $img_url;?>play.png" /></a></li>

<li>
<?php _e( "Grant supplemental content permissions to a BuddyPress group <em>(Pro)</em>", $lang_id );?>
<a href="http://presspermit.com/tutorial/buddypress-content-permissions" target="_blank"><img class="cme-play" src="<?php echo $img_url;?>play.png" /></a></li>

<li>
<?php _e( "WPML integration to mirror permissions to translations <em>(Pro)</em>", $lang_id );?>
</li>

<li>
<?php _e( "Member support forum", $lang_id );?>
</li>
</ul>

<ul class="pro-pplinks" style="display:none">
<li><a class="pp-screencasts" href="http://presspermit.com/tutorial" target="_blank"><?php _e('Screencasts', 'pp');?></a></li>
<li class="spacer">&bull;</li>
<li><a href="http://presspermit.com/pp-rs-feature-grid" target="_blank"><?php _e('Feature Grid', 'pp');?></a></li>
<li class="spacer">&bull;</li>
<li><a href="http://presspermit.com/faqs" target="_blank"><?php _e('FAQs', 'pp');?></a></li>
<li class="spacer">&bull;</li>
<li><a href="http://presspermit.com/forums/forum/pre-sale-questions/" target="_blank"><?php _e('Pre-Sale Questions', 'pp');?></a></li>
<li class="spacer">&bull;</li>
<li><a href="http://presspermit.com/purchase/" target="_blank"><?php _e('Purchase', 'pp');?></a></li>
<li class="spacer">&bull;</li>
<li><a href="#pp-pro-hide"><?php _e('Hide', 'pp');?></a></li>
</ul>

</div>
