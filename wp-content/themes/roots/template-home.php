<?php
/*
Template Name: Home Template
*/
?>

<!--=== Video Promo ===-->
<div class="promo">
    <div class="container">
        <div class="row-fluid">
            <!-- What is YS -->
            <div class="span3">
                <div class="headline"><h3>What is YSFlight?</h3></div>
                <h4>Watch the video to find out.</h4>
                <hr class="hidden">
                <h4>Want to learn more? Check out these awesome videos:</h4>
                <ul id="moreVideos">
                    <li><a href="//www.youtube.com/embed/qo2mcV5EnME?rel=0&amp;autoplay=1&amp;wmode=transparent"><i class="icon-youtube-play"></i>Combat</a></li>
                    <li><a href="//www.youtube.com/embed/9WEo-j9mEZQ?rel=0&amp;autoplay=1&amp;wmode=transparent"><i class="icon-youtube-play"></i>Aerobatics</a></li>
                    <li><a href="//www.youtube.com/embed/yhGmXz0_L1s?rel=0&amp;autoplay=1&amp;wmode=transparent"><i class="icon-youtube-play"></i>Civilian</a></li>
                </ul>
            </div><!--/span3-->
            <!-- Video Promo -->
            <div class="span6">
                <iframe width="480" height="360" src="//www.youtube.com/embed/yyb85ANIHIM?rel=0&amp;wmode=transparent" seamless allowfullscreen></iframe>
            </div><!--/span6-->
            <!-- Featured Screenshots -->
            <div class="span3">
                <div class="headline"><h3>Featured Screenshots</h3></div>
                <div id="myCarousel" class="carousel slide">
                    <div class="carousel-inner">
                      <div class="item active">
                        <img src="http://placehold.it/973x615" alt="">
                        <div class="carousel-caption">
                          <p>Cras justo odio, dapibus ac facilisis in, egestas.</p>
                        </div>
                      </div>
                      <div class="item">
                        <img src="http://placehold.it/973x615" alt="">
                        <div class="carousel-caption">
                          <p>Cras justo odio, dapibus ac facilisis in, egestas.</p>
                        </div>
                      </div>
                      <div class="item">
                        <img src="http://placehold.it/973x615" alt="">
                        <div class="carousel-caption">
                          <p>Cras justo odio, dapibus ac facilisis in, egestas.</p>
                        </div>
                      </div>
                    </div>
                    
                    <div class="carousel-arrow">
                        <a class="left carousel-control" href="#myCarousel" data-slide="prev"><i class="icon-angle-left"></i></a>
                        <a class="right carousel-control" href="#myCarousel" data-slide="next"><i class="icon-angle-right"></i></a>
                    </div>
                </div>
                <!-- //End Featured Screenshots -->
            </div><!--/span4-->
        </div>
    </div>
</div>
<!--=== End Video Promo ===-->

<!--=== Download Block ===-->
<div class="row-fluid purchase margin-bottom-30">
    <div class="container">
        <div class="span9">
            <span><em>YSFlight is the only <strong>free</strong> flight simulator where anything is possible.</em> Download YSFlight and join our community today!</span>
            <p>YSFlight is a free flight simulator that places the user in control. <em>Basic avionics, forgiving flight models, and uncomplicated weapons systems make YSFlight easy to learn, while a vibrant modding and online flight community draws new fans in and holds veterans' attention.</em> Try this amazing free flight simulation today and you too will find that though this simulator may not have the world’s greatest graphics, it is like nothing else the world has ever seen - and it’s all FREE.</p>
        </div>
        <a href="#" class="btn-buy hover-effect"><i class="icon-download-alt"></i> Download YSFlight<br><small>For Windows, Mac OS X, and Linux</small></a>
    </div>
</div><!--/row-fluid-->
<!-- End Purchase Block -->

<!--=== Content Part ===-->
<div class="container"> 
    <!-- Pilot Blocks -->
    <div class="row-fluid blue">
        <div class="span4">
            <div class="service clearfix">
                <i class="icon-fighter-jet"></i>
                <div class="desc">
                    <h4>For combat pilots</h4>
                    <p>Donec id elit non mi porta gravida at eget metus. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus etiam sem...</p>
                    <div class="btn-u hover-effect">Visit Combat HQ</div>
                    <a href="<?php echo home_url(); ?>/community/combat-hq/"></a>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="service clearfix">
                <i class="icon-plane"></i>
                <div class="desc">
                    <h4>For civilian pilots</h4>
                    <p>Donec id elit non mi porta gravida at eget metus. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus etiam sem...</p>
                    <div class="btn-u hover-effect">Visit Civilian HQ</div>
                    <a href="<?php echo home_url(); ?>/community/civilian-hq/"></a>
                </div>
            </div>
        </div>

        <div class="span4" onclick='window.location.assign("<?php echo home_url(); ?>/community//")'>
            <div class="service clearfix">
                <i class="icon-trophy"></i>
                <div class="desc">
                    <h4>For aerobatic pilots</h4>
                    <p>Donec id elit non mi porta gravida at eget metus. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus etiam sem...</p>
                    <div class="btn-u hover-effect">Visit Aerobatic HQ</div>
                    <a href="<?php echo home_url(); ?>/community/aerobatic-hq/"></a>
                </div>
            </div>
        </div>
    </div><!--/row-fluid-->
    <!-- //End Pilot Blocks -->
    
    <!-- Information Blocks -->
    <div class="hero-unit">
        <div class="row-fluid">
            <!-- About YSFHQ -->
            <div class="headline"><h3>Welcome to YSFlight Headquarters!</h3></div>
            <p>Welcome to YSFlight Headquarters, the best English-speaking YSFlight fan community since 2010. Here, you can find add-ons, servers, and information for YSFlight, as well as a tight but large fan community. Visit the links on the top to have a look around, and enjoy your time on our website!</p>
            <!-- //End About YSFHQ -->
        </div>
        <div class="row-fluid">
            <!-- Link Blocks -->
            <div class="span4">
                <div class="service clearfix">
                    <i class="icon-comments"></i>
                    <div class="desc">
                        <h4><strong>YSFHQ Forums</strong></h4>
                        <p>Donec id elit non mi porta gravida at eget metus.</p>
                        <div class="btn-u hover-effect">Visit the forum</div>
                        <a href="http://forum.ysfhq.com/"></a>
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class="service clearfix">
                    <i class="icon-book"></i>
                    <div class="desc">
                        <h4>YSFlight Wiki</h4>
                        <p>Donec id elit non mi porta gravida at eget metus.</p>
                        <div class="btn-u hover-effect">Read the wiki</div>
                        <a href="http://ysflightsim.wikia.com/"></a>
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class="service clearfix">
                    <i class="icon-download-alt"></i>
                    <div class="desc">
                        <h4>YSUpload Addons</h4>
                        <p>Donec id elit non mi porta gravida at eget metus.</p>
                        <div class="btn-u hover-effect">Download some addons</div>
                        <a href="http://www.ysupload.com/"></a>
                    </div>
                </div>
            </div>
            <!-- //End Link Blocks -->
        </div>
        <div class="row-fluid">
            <!-- YSFHQ News -->
            <div class="headline"><h3>YSFlight HQ News</h3></div>
            <h4 class="title"><a href="#" class="read-more">A new YSFlight Headquarters is coming!</a></h4>
            <h5>Posted on <a href="#">June 29, 2013</a> by <a href="#">Eric</a></h5>
            <div class="story">
                <p><strong>CHICAGO, Illinois, U.S.A. (June 28, 2013)</strong> — Last Friday, YSFlight Headquarters' (YSFHQ) Chief Information Officer Eric Tendian launched <strong>Project Kaizen</strong>"Kaizen" is Japanese for improvement, and refers to the practice of continuous improvement in many industries. Project Kaizen aims to improve the YSFlight (YS) community through <a href="#" class="read-more">&hellip; READ MORE</a></p>
            </div>
            <!-- //End YSFHQ News -->
        </div>
    </div>

    <div class="row">
        <div class="span6">
            <!-- Featured Addons -->
            <div class="headline"><h3>Featured Addons</h3></div>
            <ul class="thumbnails">
                <li class="span3">
                    <div class="thumbnail-style thumbnail-kenburn">
                        <div class="thumbnail-img">
                            <div class="overflow-hidden"><img src="http://placehold.it/973x615" alt="" /></div>
                            <a class="btn-more hover-effect" href="#">download +</a>
                        </div>
                        <h3><a class="hover-effect" href="#">Aircraft Addon</a></h3>
                        <p>Donec id elit non mi porta gravida at eget metus. Fusce dapibus, justo sit amet risus etiam porta sem.</p>
                    </div>
                </li>
                <li class="span3">
                    <div class="thumbnail-style thumbnail-kenburn">
                        <div class="thumbnail-img">
                            <div class="overflow-hidden"><img src="http://placehold.it/973x615" alt="" /></div>
                            <a class="btn-more hover-effect" href="#">download +</a>
                        </div>
                        <h3><a class="hover-effect" href="#">Map Addon</a></h3>
                        <p>Donec id elit non mi porta gravida at eget metus. Fusce dapibus, justo sit amet risus etiam porta sem.</p>
                    </div>
                </li>
            </ul><!--/thumbnails-->
            <!-- //End Featured Addons -->
        </div>
        <div class="span6">
            <!-- Upcoming Events -->
            <div id="w" class="home">
                <div class="headline"><h3>Upcoming Events</h3></div> 
                <ul class="portfolio recent-work clearfix"> 
                    <li data-id="id-1">
                        <a href="portfolio_item.html">
                            <em class="overflow-hidden"><img src="http://placehold.it/270x171" alt="" /></em>
                            <span>
                                <strong>Happy New Year</strong>
                                <i>Anim pariatur cliche reprehenderit</i>
                            </span>
                        </a>
                    </li>
                    <li data-id="id-2">
                        <a href="portfolio_item.html">
                            <em class="overflow-hidden"><img src="http://placehold.it/270x171" alt="" /></em>
                            <span>
                                <strong>Happy New Year</strong>
                                <i>Anim pariatur cliche reprehenderit</i>
                            </span>
                        </a>
                    </li>
                </ul>
            </div>
            <!-- //End Upcoming Events -->
        </div>
    </div>
    
</div><!--/container--> 
<!-- End Content Part -->

<script type="text/javascript">
    jQuery(document).ready(function() {
        Index.initVideos();
    });
</script>