<!--=== Top ===-->
<div class="top">
    <div class="container"> 
        <ul class="loginbar pull-right">
            <li><i class="icon-globe"></i><a>Languages <i class="icon-sort-up"></i></a>
                <ul class="nav-list">
                    <li><a href="<?php echo get_site_url().$_SERVER["REQUEST_URI"]; ?>">English</a><!-- <i class="icon-ok"></i>--></li>
                    <li><a href="http://www.google.com/translate?hl=es&ie=UTF8&sl=en&tl=es&u=<?php echo rawurlencode(get_site_url().$_SERVER["REQUEST_URI"]); ?>" title="Spanish">Español</a></li>
                    <li><a href="http://www.google.com/translate?hl=pt&ie=UTF8&sl=en&tl=pt&u=<?php echo rawurlencode(get_site_url().$_SERVER["REQUEST_URI"]); ?>" title="Portuguese">português</a></li>
                    <li><a href="http://www.google.com/translate?hl=ja&ie=UTF8&sl=en&tl=ja&u=<?php echo rawurlencode(get_site_url().$_SERVER["REQUEST_URI"]); ?>" title="Japanese">日本語</a></li>
                    <li><a href="http://www.google.com/translate?hl=ko&amp;ie=UTF8&amp;sl=en&amp;tl=ko&amp;u=<?php echo rawurlencode(get_site_url().$_SERVER["REQUEST_URI"]); ?>" title="Korean">한국어</a></li>
                </ul>
            </li> 
            <li class="devider">&nbsp;</li>
            <li><a href="/about-us/" class="login-btn">About YSFHQ</a></li>
            <li class="devider">&nbsp;</li>
            <li><?php if (is_user_logged_in()) { ?><a href="<?php echo wp_logout_url( home_url() ); ?>" class="login-btn"><strong>Logout</strong></a><?php } else { ?><a href="<?php echo home_url().'/login/?redirect_to='.urlencode(get_permalink()); ?>" class="login-btn"><strong>Login</strong></a><?php } ?></li>
            <?php if (!is_user_logged_in() && strlen(wp_register('', '', false))>0) { ?><li class="devider">&nbsp;</li>
            <li><a href="<?php echo home_url().'/register/?redirect_to='.urlencode(get_permalink()); ?>" class="login-btn"><strong>Join</strong></a></li><?php } ?>
        </ul>
    </div>
</div><!--/top-->
<!--=== End Top ===-->

<!--=== Header ===-->
<div class="header"> 
    <div class="container"> 
        <!-- Logo --> 
        <div class="logo"> 
            <a href="<?php echo home_url(); ?>/"><img id="logo-header" src="<?php echo get_template_directory_uri(); ?>/assets/img/logo.png" alt="<?php bloginfo('name'); ?>"></a>
        </div><!-- /logo -->

        <!-- Menu -->
        <header class="banner navbar" role="banner">
          <div class="navbar-inner">
            <div class="container">
              <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
              </a>
              <nav class="nav-main nav-collapse collapse" role="navigation">
                <?php
                  if (has_nav_menu('primary_navigation')) :
                    wp_nav_menu(array('theme_location' => 'primary_navigation', 'menu_class' => 'nav top-2'));
                  endif;
                ?>
                <ul class="nav top-2"><li><a class="search"><i class="icon-search search-btn"></i></a></li></ul>
                <div class="search-open">
                  <div class="input-append">
                    <form role="search" method="get" id="searchform" class="searchform" action="<?php echo home_url(); ?>/">
                      <label class="screen-reader-text" for="s" style="display: none">Search for:</label>
                      <input type="text" value="" name="s" id="s" class="span3" placeholder="Search YSFHQ">
                      <input type="submit" id="searchsubmit" value="Go" class="btn-u">
                    </form>
                  </div>
                </div>
              </nav>
            </div>
          </div>
        </header>
    </div><!-- /container --> 
</div><!--/header -->
<!--=== End Header ===-->
