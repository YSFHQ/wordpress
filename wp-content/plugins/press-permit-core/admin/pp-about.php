<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$opt_val = (array) pp_get_option( 'support_key' );
$show_pro_info = empty($opt_val[0]) || ! in_array( $opt_val[0], array( 1, -1 ) );
?>

<ul class="masthead"><li id="masthead-main" title="Agapetry Creations"><a class="agp-toplink" href="http://agapetry.net"> </a></li></ul>
<div id="wrap" style="height: 100%">

<div class="rc-about-dialog">
<p>
<a title="agape" href="http://en.wikipedia.org/wiki/Agape" target="_blank">a<small><small>&#8226;</small></small>ga<small><small>&#8226;</small></small>p&eacute;</a> (&alpha;&gamma;&alpha;&pi;&eta;): 
<?php _e('unselfish, benevolent love, born of the Spirit.', 'pp'); ?>
</p>

<p><?php _e('Agap&eacute; discerns needs and meets them unselfishly and effectively.', 'pp');?>
</p>

<p><?php printf(__('This WordPress plugin is part of my agap&eacute; try, %7$s a lifelong effort%8$s to love God and love people by rightly using the time and abilities He has leant me. As a husband, father, engineer, farmer and/or software developer, I have found this stewardship effort to be often fraught with contradiction. A wise and sustainable balancing of roles has seemed to elude me. Yet I want to keep trying, trusting that if God blesses and multiplies the effort, it will become agapetry, a creative arrangement motivated by benevolent love.  A fleeting childlike sketch of the beautiful %1$s chain-breaking agap&eacute;%2$s which %3$s Jesus Christ unleashed%4$s so %5$s freely%6$s and aptly on an enslaving, enslaved world.', 'pp'), '<a href="http://www.biblegateway.com/passage/?search=Isaiah%2059:1-60:3;Matthew%203:1-12;Luke%204:5-8;Matthew%205:1-48;Matthew%206:9-15;&version=NKJV" target="_blank">', '</a>', '<a href="http://www.biblegateway.com/passage/?search=Matthew%2020:20-28;Matthew%2026:36-49;John%2018:7-12;John%2019:1-30;Romans%208:11;1%20John%202:1-6;&version=ESV" target="_blank">', '</a>', '<a href="http://www.biblegateway.com/passage/?search=Isaiah%2055;John%207:37-51;&version=ESV" target="_blank">', '</a>', '<a href="http://www.biblegateway.com/passage/?search=eph%205:8-17&version=ESV" target="_blank">', '</a>');?>
</p>

<?php

// todo PP Core / Pro explanation if not activated

if ( $show_pro_info ) :?>
	<p class="agp-proceed">
	<?php
	printf(__( 'Over that last five years, I&apos;ve discovered that WordPress permissions customization is a complex moving target that&apos;s not well-suited to a purely free+consulting model. To ensure commensurate funding for plugin development, documentation and support, I&apos;ve introduced Press Permit Pro. Your purchase provides access and 12 months of updates to a package of extension plugins which provide these and other features:', 'pp' ), '<a href="http://presspermit.com/pp-rs-feature-grid">', '</a>' ); 
	?>
	</p>
	<ul class="agp-proceed"><li>
	<?php _e('Customize editing access for selected posts or terms', 'pp'); ?>
	</li><li>
	<?php _e('Custom Privacy Statuses, integrated with WP capabilities and editing', 'pp'); ?>
	</li><li>
	<?php _e('Custom Moderation Statuses, allowing multi-step moderation', 'pp'); ?>
	</li><li>
	<?php _e('Use BuddyPress groups as Permission Groups', 'pp'); ?>
	</li><li>
	<?php _e('Customize permissions for any bbPress Forum', 'pp'); ?>
	</li><li>
	<?php _e('Regulate direct file URL access', 'pp'); ?>
	</li><li>
	<?php _e('Hidden Content Teaser', 'pp'); ?>
	</li><li>
	<?php _e('Schedule or expire Permission Group membership (date or X days)', 'pp'); ?>
	</li></ul>
	
	<p class="agp-proceed">
	<?php _e( 'You also get access to the Pro Support Forums. If you would like to get a hold of this software or fund other development, there are several ways to proceed:', 'pp' );
	?>
	</p><ul class="agp-proceed"><li>
	<?php
	printf(__('%1$s by creating a 30 day evaluation site %2$sfor a nominal fee%3$s.', 'pp'), '<a href="http://try.presspermit.com/">Try Press&nbsp;Permit&nbsp;Pro</a>', '<a href="http://presspermit.com/purchase/press-permit-evaluation/">', '</a>');
	?>
	</li><li>
	<?php
	printf(__('%1$sBuy%2$s %3$s for 12 months of access to %4$sextension plugins%5$s and support resources.', 'pp'), '<a href="http://presspermit.com/purchase/">', '</a>', '<a href="http://presspermit.com/">Press&nbsp;Permit&nbsp;Pro</a>', '<a href="http://presspermit.com/pp-extensions/">', '</a>');
	?>
	</li><li>
	<?php
	printf(__('View Kevin&apos;s %1$s and submit a request.', 'pp'), '<a href="http://agapetry.net/availability/">Consulting&nbsp;Availability</a>');
	?>
	</li><li>
	<?php
	$paypal_button = '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" class="donate"><input type="hidden" name="cmd" value="_s-xclick" /> <input type="image" style="background:none" src="http://agapetry.net/btn_donate_SM.gif" name="submit" alt="PayPal - The safer, easier way to pay online!" /> <img alt="" border="0" src="http://agapetry.net/pixel.gif" width="1" height="1" style="opacity:0.01;" /> <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
	" /></form>';
	printf(__('If you just want to say thanks for past development and support, %s', 'pp'), $paypal_button);
	?>
	</li></ul>
<?php else : ?>
	<p><?php printf(__('Although Press Permit&apos;s development was a maniacal hermit-like effort, it was only possible because of the clean and extensible %1$s WordPress code base%2$s developed by hundreds of core contributors.  Thanks for appreciating and supporting my own contributions to this content management landscape.  I hope they will assist you toward resolution and purpose in building excellent websites with a thankful heart toward your own Creator, who IS agap&eacute;.', 'pp'), "<a href='http://codex.wordpress.org/Developer_Documentation' target='_blank'>", '</a>');?>
	</p>
	
	<p>
	<?php
	printf( __( 'If you need additional feature development or implementation assistance, I may be available for %1$spaid consulting%2$s.', 'pp' ), '<a href="http://agapetry.net/availability">', '</a>' );
	?>
	</p>
	
	<div>
	<?php _e('Thanks again for your support,', 'pp');?>
	</div>
	<div class="agp-signature">
	<?php echo('- Kevin Behrens');?>
	</div>
	
<?php endif; ?>

</div> <!-- rc-about-dialog -->

<a href="http://presspermit.com"><div class="pp-logo">&nbsp;</div></a>
<div class="madein">&nbsp;</div>
<div style="height: 150px;">&nbsp;</div>
	
</div> <!-- wrap -->