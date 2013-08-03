<?php
/**
 * HEADER
 *
 * This file controls the HTML <head> and top graphical markup (including
 * Navigation) for each page in your theme. You can control what shows up where
 * using WordPress and PageLines PHP conditionals.
 *
 * @link        http://www.pagelines.com/
 *
 * @author      PageLines Inc.  http://www.pagelines.com/
 * @copyright   Copyright (c) 2008-2013, PageLines  hello@pagelines.com
 */

pagelines_register_hook('pagelines_before_html'); // Hook
?><!DOCTYPE html>
<html <?php language_attributes(); do_action('the_html_tag'); ?>>
<head>
<?php
		pagelines_register_hook('pagelines_head'); // Hook

		wp_head(); // Hook (WordPress)

		pagelines_register_hook('pagelines_head_last'); // Hook ?>

</head>
<?php

echo pl_source_comment('Start >> HTML Body', 1); ?>
<body <?php body_class( pagelines_body_classes() ); ?>>
<?php
pagelines_register_hook('pagelines_before_site'); // Hook

if(has_action('override_pagelines_body_output')):
	do_action('override_pagelines_body_output');

else:  ?>
<div id="site" class="site-wrap <?php echo pagelines_layout_mode();?>">

	<?php pagelines_register_hook('pagelines_before_page'); // Hook ?>
	<div  class="boxed-wrap site-translate">
		<div id="fixed-top" class="pl-fixed-top" data-region="fixed-top">
			<?php pagelines_template_area('pagelines_fixed_top', 'fixed_top'); // Hook ?>
		</div>
		<div class="fixed-top-pusher"></div>
		<script> jQuery('.fixed-top-pusher').height( jQuery('.pl-fixed-top').height() ) </script>
		
		<div class="pl-region-wrap">
			<div id="page" class="thepage page-wrap">

				<?php pagelines_register_hook('pagelines_page'); // Hook ?>
				<div class="page-canvas">
					<?php pagelines_register_hook('pagelines_before_header');?>
					<header id="header" class="header pl-region" data-region="header">
						<div class="outline pl-area-container">
							<?php pagelines_template_area('pagelines_header', 'header'); // Hook ?>
						</div>
					</header>
					<?php pagelines_register_hook('pagelines_before_main'); // Hook ?>
					<div id="page-main" class="pl-region" data-region="template">
						<div id="dynamic-content" class="outline template-region-wrap pl-area-container">
	<?php pagelines_special_content_wrap_top();

endif;

