<?php get_template_part('templates/head'); ?>
<body <?php body_class(); ?>>

  <!--[if lt IE 7]><div class="alert">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">activate Google Chrome Frame</a> to improve your experience.</div><![endif]-->

  <?php
    get_template_part('templates/alert-message');
    do_action('get_header');
    // Use Bootstrap's navbar if enabled in config.php
    if (current_theme_supports('bootstrap-top-navbar')) {
      get_template_part('templates/header-custom');
    } else {
      get_template_part('templates/header');
    }
  ?>

<?php if (strpos(roots_template_path(), 'template-home.php')!==false) include roots_template_path();
else { ?>
  <?php get_template_part('templates/page', 'header'); ?>
  <div class="wrap container" role="document">
    <div class="content row-fluid">
      <div class="main <?php echo roots_main_class(); ?>" role="main">
        <?php include roots_template_path(); ?>
      </div><!-- /.main -->
      <?php if (roots_display_sidebar()) { ?>
      <aside class="sidebar <?php echo roots_sidebar_class(); ?>" role="complementary">
        <?php include roots_sidebar_path(); ?>
<?php if (!is_first_class()): ?><div>
<!-- ysfhq-wp-blog -->
<ins class="adsbygoogle"
     style="display:inline-block;width:300px;height:250px"
     data-ad-client="ca-pub-1211687588041162"
     data-ad-slot="7610455242"></ins>
<script>
(adsbygoogle = window.adsbygoogle || []).push({});
</script>
        </div><?php endif; ?>
      </aside><!-- /.sidebar -->
      <?php } else if (!is_first_class()) { ?>
      <aside class="sidebar span2" role="complementary">
        <!-- ysfhq-wp-vspacer -->
        <ins class="adsbygoogle"
             style="display:inline-block;width:160px;height:600px"
             data-ad-client="ca-pub-1211687588041162"
             data-ad-slot="1703522446"></ins>
        <script>
        (adsbygoogle = window.adsbygoogle || []).push({});
        </script>
      </aside><!-- /.sidebar -->
      <?php } ?>
    </div><!-- /.content -->
  </div><!-- /.wrap -->
<?php } ?>

  <?php get_template_part('templates/footer'); ?>

</body>
</html>
