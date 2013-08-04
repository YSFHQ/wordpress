<!--=== Footer ===-->
<div class="footer">
    <div class="container">
        <div class="row-fluid">
            <div class="span4">
                <!-- About -->
                <div class="headline"><h3>About</h3></div>
                <p class="margin-bottom-25">YSFlight Headquarters has been the best English-speaking YSFlight fan community since 2010.</p>

                <!-- Monthly Newsletter -->
                <div class="headline"><h3>Monthly Newsletter</h3></div> 
                <p>Subscribe to our newsletter and stay up to date with the latest news, events, addons, and much more!</p>
                <form class="form-inline">
                    <div class="input-append">
                        <input type="text" placeholder="Email Address" class="input-medium">
                        <button class="btn-u">Subscribe</button>
                    </div>
                </form> 
            </div><!--/span4--> 
            
            <div class="span4">
                <div class="posts">
                    <div class="headline"><h3>Servers Currently Online</h3></div>
                    <dl class="dl-horizontal">
                        <dt><a href="#"><img src="http://placehold.it/150x160" alt="" /></a></dt>
                        <dd>
                            <p><a href="#"><strong>The Best Server</strong><br>There are <em>9001 players</em> currently flying<br>Join at <strong>192.168.1.100</strong>:7915</a></p> 
                        </dd>
                    </dl>
                    <dl class="dl-horizontal">
                    <dt><a href="#"><img src="http://placehold.it/150x160" alt="" /></a></dt>
                        <dd>
                            <p><a href="#"><strong>Decent Server</strong><br>There are <em>2 players</em> currently flying<br>Join at <strong>192.168.1.100</strong>:7915</a></p> 
                        </dd>
                    </dl>
                    <dl class="dl-horizontal">
                    <dt><a href="#"><img src="http://placehold.it/150x160" alt="" /></a></dt>
                        <dd>
                            <p><a href="#"><strong>Complete Madness</strong><br>There are <em>66 players</em> currently flying<br>Join at <strong>192.168.1.100</strong>:7915</a></p> 
                        </dd>
                    </dl>
                </div>
            </div><!--/span4-->

            <div class="span4">
                <!-- Contact Us -->
                <div class="headline"><h3>Contact Us</h3></div>
                <p>
                    We'd love to hear from you! You can contact the YSFHQ staff at <a href="mailto:team@ysfhq.com" class="">team@ysfhq.com</a>. You can also fill out our contact form <a href="#">here</a>. For all legal matters, please email <a href="mailto:legal@ysfhq.com" class="">legal@ysfhq.com</a>. <a href="http://forum.ysfhq.com/">Visit us on the forums!</a>
                </p>

                <!-- Stay Connected -->
                <div class="headline"><h3>Stay Connected</h3></div> 
                <ul class="social-icons">
                    <li><a href="<?php echo home_url(); ?>/feed/" data-original-title="Feed" class="social_rss" target="_blank"></a></li>
                    <li><a href="http://www.facebook.com/ysfhq" data-original-title="Facebook" class="social_facebook" target="_blank"></a></li>
                    <li><a href="http://www.twitter.com/YSFHQ" data-original-title="Twitter" class="social_twitter" target="_blank"></a></li>
                    <li><a href="http://www.youtube.com/YSFlightHeadquarters" data-original-title="YouTube" class="social_youtube" target="_blank"></a></li>
                </ul>
            </div><!--/span4-->
        </div><!--/row-fluid--> 
    </div><!--/container--> 
</div><!--/footer-->
<!--=== End Footer ===-->

<!--=== Copyright ===-->
<div class="copyright">
    <div class="container">
        <div class="row-fluid">
            <div class="span8">
                <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. All Rights Reserved. <a href="<?php echo home_url(); ?>/terms-of-service-and-privacy-policy/#privpolicy">Privacy Policy</a> | <a href="<?php echo home_url(); ?>/terms-of-service-and-privacy-policy/">Terms of Service</a></p>
            </div>
            <div class="span4">
                <a href="<?php echo home_url(); ?>/"><img id="logo-footer" src="<?php echo get_template_directory_uri(); ?>/assets/img/logo_text.png" alt="<?php bloginfo('name'); ?>" class="pull-right"></a>
            </div>
        </div><!--/row-fluid-->
    </div><!--/container--> 
</div><!--/copyright--> 
<!--=== End Copyright ===-->

<script type="text/javascript">
    jQuery(document).ready(function() {
        App.init();
        App.initSliders();
    });
</script>

<?php wp_footer(); ?>
