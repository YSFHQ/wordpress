<?php
/*
Template Name: Home Template
*/
?>

<!--=== Video Promo ===-->
<div class="promo">
<?php
function random_pic() {
    $upload_dir = wp_upload_dir();
    $files = glob($upload_dir['path'].'/promo-bg' . '/*.*');
    $file = array_rand($files);
    return $upload_dir['url'].'/promo-bg/'.basename($files[$file]);
}
$pictures = array();
for ($i=0; $i<5; $i++) {
    $pictures[] = random_pic();
}
?>
    <div class="container">
        <div class="row-fluid">
            <!-- What is YS -->
            <div class="span3">
                <div class="headline"><h3>What is YSFlight?</h3></div>
                <h4>Watch the video to find out.</h4>
                <hr class="hidden">
                <h4>Want to learn more about YSFlight? Check out these awesome videos:</h4>
                <ul id="moreVideos">
                    <li><a href="//www.youtube.com/embed/qo2mcV5EnME?rel=0&amp;autoplay=1&amp;wmode=transparent"><i class="icon-youtube-play"></i>Combat</a></li>
                    <li><a href="//www.youtube.com/embed/yhGmXz0_L1s?rel=0&amp;autoplay=1&amp;wmode=transparent"><i class="icon-youtube-play"></i>Civilian</a></li>
                    <li><a href="//www.youtube.com/embed/v2ow5QA-ptc?rel=0&amp;autoplay=1&amp;wmode=transparent"><i class="icon-youtube-play"></i>Aerobatics</a></li>
                </ul>
            </div><!--/span3-->
            <!-- Video Promo -->
            <div class="span6">
                <!--<iframe width="480" height="360" src="//www.youtube.com/embed/OxeOJr9R5bs?rel=0&amp;wmode=transparent" allowfullscreen></iframe>-->
                <iframe width="560" height="360" src="//www.youtube.com/embed/wp37r4PgchE?rel=0&amp;wmode=transparent" allowfullscreen></iframe>
            </div><!--/span6-->
            <!-- Featured Screenshots -->
            <div class="span3">
                <div class="headline"><h3>Featured Screenshots</h3></div>
                <div id="myCarousel" class="carousel slide">
                    <div class="carousel-inner">
                      <div class="item active">
                        <img src="<?php echo $pictures[0]; ?>" alt="">
                        <!--<div class="carousel-caption">
                          <p></p>
                        </div>-->
                      </div>
                      <div class="item">
                        <img src="<?php echo $pictures[1]; ?>" alt="">
                        <!--<div class="carousel-caption">
                          <p></p>
                        </div>-->
                      </div>
                      <div class="item">
                        <img src="<?php echo $pictures[2]; ?>" alt="">
                        <!--<div class="carousel-caption">
                          <p></p>
                        </div>-->
                      </div>
                      <div class="item">
                        <img src="<?php echo $pictures[3]; ?>" alt="">
                        <!--<div class="carousel-caption">
                          <p></p>
                        </div>-->
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
    <div class="background" style="background-image: url(<?php echo $pictures[4]; ?>);"></div>
</div>
<!--=== End Video Promo ===-->

<!--=== Download Block ===-->
<div class="row-fluid purchase margin-bottom-30">
    <div class="container">
        <div class="span9">
            <p class="large"><em>YSFlight is the only <strong>free</strong> flight simulator where anything is possible.</em> Download YSFlight and join our community today!</p>
            <p>YSFlight is a free flight simulator that places the user in control. <em>Basic avionics, forgiving flight models, and uncomplicated weapons systems make YSFlight easy to learn, while a vibrant modding and online flight community draws new fans in and holds veterans' attention.</em> Try this amazing free flight simulation today and you too will find that though this simulator may not have the world’s greatest graphics, it is like nothing else the world has ever seen - and it’s all FREE.</p>
        </div>
        <a href="<?php echo home_url(); ?>/ysflight/download/" class="btn-buy hover-effect"><i class="icon-download-alt"></i> Download YSFlight<br><small>For Windows, Mac OS X, and Linux</small></a>
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
                    <p>Challenge enemies head-on with a variety of weapons.</p>
                    <ul>
                        <li>Take on your opponents in air combat, on singleplayer mode or online</li>
                        <li>Defend the airport against enemy bombers and attack aircraft</li>
                        <li>Fly low to eliminate hostile targets and clear a path for friendly forces</li>
                        <li>Rise to the top of the blacklist and become the most wanted player online</li>
                    </ul>
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
                    <p>Take a flight along the coast, or around the world.</p>
                    <ul>
                        <li>Earn your virtual airline money by accumulating flight hours</li>
                        <li>Take part in real Air Traffic Control sessions online</li>
                        <li>Take off in a small single-engine plane, or a massive jumbo jet</li>
                        <li>Battle weather effects as you fly towards your destination</li>
                    </ul>
                    <div class="btn-u hover-effect">Visit Civilian HQ</div>
                    <a href="<?php echo home_url(); ?>/community/civilian-hq/"></a>
                </div>
            </div>
        </div>

        <div class="span4" onclick='window.location.assign("<?php echo home_url(); ?>/community/aerobatic-hq/")'>
            <div class="service clearfix">
                <i class="icon-trophy"></i>
                <div class="desc">
                    <h4>For aerobatic pilots</h4>
                    <p>Perform maneuvers that no other simulator can achieve.</p>
                    <ul>
                        <li>Improve your formation flying skills offline or online</li>
                        <li>Compete in air races with fellow YSFlight pilots</li>
                        <li>Perform at airshows hosted throughout the year</li>
                        <li>Become the most skilled aerobatic pilot in YSFlight</li>
                    </ul>
                    <div class="btn-u hover-effect">Visit Aerobatic HQ</div>
                    <a href="<?php echo home_url(); ?>/community/aerobatic-hq/"></a>
                </div>
            </div>
        </div>
    </div><!--/row-fluid-->
    <!-- //End Pilot Blocks -->
<?php if (!is_first_class()): ?><div class="row-fluid"><div class="span12">
<!-- ysfhq-wp-hspacer -->
<ins class="adsbygoogle"
     style="display:block;width:970px;height:90px;margin:10px auto"
     data-ad-client="ca-pub-1211687588041162"
     data-ad-slot="1843123249"></ins>
<script>
(adsbygoogle = window.adsbygoogle || []).push({});
</script>
    </div></div><?php endif; ?>
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
                        <p>The heart of the YSFlight community is on the forum. Join in on the fun here.</p>
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
                        <p>Visit the official wiki to find all kinds of information on YSFlight. Tutorials, links, and more!</p>
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
                        <p>Download all kinds of YSFlight addons for free on YSUpload.com, the official addon host for YSFHQ.</p>
                        <div class="btn-u hover-effect">Download some addons</div>
                        <a href="http://www.ysupload.com/"></a>
                    </div>
                </div>
            </div>
            <!-- //End Link Blocks -->
        </div>
        <div class="row-fluid">
            <!-- YSFHQ News -->
            <div class="headline"><h3>YSFlight Headquarters News</h3></div>
            <?php
            $args = array('numberposts' => 2);
            $lastposts = get_posts($args);
            foreach ($lastposts as $post): setup_postdata($post); ?>
                <h4 class="title"><a href="<?php the_permalink(); ?>" class="read-more"><?php the_title(); ?></a></h4>
                <h5>Posted on <time class="updated" datetime="<?php echo get_the_time('c'); ?>"><?php echo get_the_date(); ?></time> by <a href="<?php echo get_author_posts_url(get_the_author_meta('ID')); ?>" rel="author" class="fn"><?php echo get_the_author(); ?></a></h5>
                <div class="story"><?php the_excerpt(); ?></div>
            <?php endforeach; ?>
            <!-- //End YSFHQ News -->
        </div>
    </div>
<?php if (!is_first_class()): ?><div class="row-fluid"><div class="span12">
<!-- ysfhq-wp-hspacer -->
<ins class="adsbygoogle"
     style="display:block;width:970px;height:90px;margin:0 auto"
     data-ad-client="ca-pub-1211687588041162"
     data-ad-slot="1843123249"></ins>
<script>
(adsbygoogle = window.adsbygoogle || []).push({});
</script>
    </div></div><?php endif; ?>
    <div class="row">
        <div class="span6">
            <!-- Featured Addons -->
            <div class="headline"><h3>Featured Addons</h3></div>
            <!--<h4><em>Coming Soon</em></h4>-->
            <ul class="thumbnails">
                <li class="span3">
                    <div class="thumbnail-style thumbnail-kenburn">
                        <div class="thumbnail-img">
                            <div class="overflow-hidden"><img src="http://i.imgur.com/bMDmMg4.png" alt="" /></div>
                            <a class="btn-more hover-effect" href="http://www.ysupload.com/download.php?id=1136">download +</a>
                        </div>
                        <h3><a class="hover-effect" href="http://www.ysupload.com/download.php?id=1136">Project Sunderland</a></h3>
                        <p>This pack contains Short Sunderlands from B Mk I all the way up to the GR Mk V. All aircraft have been created with panel lines and detailed cockpits as standard. Happy flying!</p>
                    </div>
                </li>
                <li class="span3">
                    <div class="thumbnail-style thumbnail-kenburn">
                        <div class="thumbnail-img">
                            <div class="overflow-hidden"><img src="http://i.imgur.com/nZkkScF.png" alt="" /></div>
                            <a class="btn-more hover-effect" href="http://www.ysupload.com/download.php?id=1097">download +</a>
                        </div>
                        <h3><a class="hover-effect" href="http://www.ysupload.com/download.php?id=1097">Mh3w Scenery Pack A</a></h3>
                        <p>Contains 15 maps including Chicago v3.0, Runway City, Chaos City, Red Planet, Raceway and etc.</p>
                    </div>
                </li>
            </ul><!--/thumbnails-->
            <!-- //End Featured Addons -->
        </div>
        <div class="span6">
            <!-- Upcoming Events -->
            <div id="w" class="home">
                <div class="headline"><h3>Upcoming Events</h3></div>
                <!--<br><h4><em>Coming Soon</em></h4>-->
                <ul class="portfolio recent-work clearfix"> 
                    <li data-id="id-1">
                        <a href="http://forum.ysfhq.com/viewtopic.php?f=163&t=5890">
                            <em class="overflow-hidden"><img src="http://i.imgur.com/i5qs4P7.png" alt="Flyer" height="500" width="270" /></em>
                            <span>
                                <strong>Cherokee Valley Airshow (3/15)</strong>
                                <i>Prepare for one of the best aerial events of the year... YS Air Shows is pleased to announce the Cherokee Valley Air Show on March 15th! Prepare for a virtual aviation spectacle unmatched by any other flight simulator community in the world!</i>
                            </span>
                        </a>
                    </li>
                    <li data-id="id-2">
                        <a href="http://forum.ysfhq.com/viewtopic.php?f=163&t=5829">
                            <em class="overflow-hidden"><img src="http://i.imgur.com/VR1CHY0.png" alt="Flyer" height="500" width="270" /></em>
                            <span>
                                <strong>YSFlight Phillip Island International Airshow (4/19-20)</strong>
                                <i>It is with great pleasure that Pacific Airshows brings you the YSFlight Phillip Island International Airshow 2014. Set on a small island south of Melbourne, Victoria, Phillip Island provides breath taking scenery, great fish, and an adreniline pumping Motorcycle Grand Prix every year.</i>
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
