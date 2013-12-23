<?php
/*
Template Name: Aircraft & Vehicles Page
*/
?>
<div style="font-size:1.4em">
    <p>There are many aircraft to fly in YSFlight, from fighter jets to biplanes, and from huge airliners to small aerobatic propeller planes. Each aircraft has a unique set of characteristics and is a blast to fly. There are also many ground vehicles in YSFlight, such as racecars, sedans, trucks, and airport vehicles. Not only that, but there are also a selection of military tanks, boats, and even people.</p>
    <p>What you see on this page is just a small selection of what YSFlight has to offer. To find more great addons, visit YSUpload or YSFinder.net. YSFlight has thousands of aircraft out there just waiting for someone to try out.</p>
</div>
<hr />

<div class="accordion" id="accordion-renders">
<?php
$dir = "media/renders/";
$folders = scandir($dir);
for ($i=2; $i<count($folders); $i++) {
    if (strpos($folders[$i], ".txt")===false) {
        //scan this folder
        $currfolder = $dir.$folders[$i]."/";
        $categoryname = $dir.$folders[$i].".txt";
        $categoryname = file_get_contents($categoryname);
        if ($categoryname==false) $categoryname = ""; else $categoryname = trim($categoryname);
        ?>
  <div class="accordion-group">
    <div class="accordion-heading">
      <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion-renders" href="#<?php echo $folders[$i]; ?>"><h4><?php echo strlen($categoryname)>0 ? $categoryname : $folders[$i]; ?></h4></a>
    </div>
    <div id="<?php echo $folders[$i]; ?>" class="accordion-body collapse">
      <div class="accordion-inner">
        <div class="row-fluid gallery">
          <ul class="thumbnails">
        <?php
        $files = scandir($currfolder);
        for ($j=2; $j<count($files); $j+=2) {
            $folderurl = $dir.$folders[$i]."/";
            $name = str_replace("_large.png", "", $files[$j]);
        ?>
            <li>
                <a class="thumbnail fancybox-button zoomer" data-rel="fancybox-button" title="<?php echo $name; ?>" href="/<?php echo $folderurl.$files[$j]; ?>">
                    <div class="overlay-zoom">
                        <img src="/<?php echo $folderurl.$files[$j+1]; ?>" alt="<?php echo $name; ?>">
                        <div class="zoom-icon"></div>
                    </div>
                </a>
            </li>
        <?php
        }
        ?>
          </ul>
        </div>
      </div>
    </div>
  </div>
<?php } else continue;
}
?>
</div>

<script type='text/javascript' src='/plugins/nextgen-gallery/products/photocrati_nextgen/modules/lightbox/static/fancybox/jquery.easing-1.3.pack.js'></script>
<script type='text/javascript' src='/plugins/nextgen-gallery/products/photocrati_nextgen/modules/lightbox/static/fancybox/jquery.fancybox-1.3.4.pack.js'></script>
<link rel="stylesheet" href="/assets/css/effects.css" media="all" type="text/css">
<link rel="stylesheet" href="/plugins/nextgen-gallery/products/photocrati_nextgen/modules/lightbox/static/fancybox/jquery.fancybox-1.3.4.css">
<style type="text/css">
.thumbnails>li, .thumbnails>li a {
  padding: 0 !important;
  margin: 0 !important;
}
.thumbnails>li a:hover {
  border: 0 !important;
}
#accordion-renders a.accordion-toggle {
  text-decoration: none !important;
}
#accordion-renders a.accordion-toggle h4 {
  color: #d62e32;
  font-weight: bold !important;
  margin: 0;
}
</style>
<script type="text/javascript">
$(function() {
  $("a.thumbnail").fancybox();
});
</script>