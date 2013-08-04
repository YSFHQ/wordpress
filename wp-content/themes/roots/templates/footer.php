<!--=== Footer ===-->
<div class="footer">
    <div class="container">
        <div class="row-fluid">
            <div class="span4">
                <?php dynamic_sidebar('sidebar-footer-left'); ?>
            </div><!--/span4--> 
            
            <div class="span4">
                <?php dynamic_sidebar('sidebar-footer-center'); ?>
            </div><!--/span4-->

            <div class="span4">
                <?php dynamic_sidebar('sidebar-footer-right'); ?>
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
