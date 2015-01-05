<?php
add_shortcode('video_lightbox_vimeo5', 'wp_vid_lightbox_vimeo5_handler');
add_shortcode('video_lightbox_youtube', 'wp_vid_lightbox_youtube_handler');

function wp_vid_lightbox_vimeo5_handler($atts) 
{
    extract(shortcode_atts(array(
            'video_id' => '',
            'width' => '',	
            'height' => '',
            'description' => '',
            'anchor' => '',	
            'auto_thumb' => '',
    ), $atts));
    if(empty($video_id) || empty($width) || empty($height)){
            return "<p>Error! You must specify a value for the Video ID, Width, Height and Anchor parameters to use this shortcode!</p>";
    }
    if(empty($auto_thumb) && empty($anchor)){
    	return "<p>Error! You must specify an anchor parameter if you are not using the auto_thumb option.</p>";
    }
        
    $atts['vid_type'] = "vimeo";
    if (preg_match("/http/", $anchor)){ // Use the image as the anchor
        $anchor_replacement = '<img src="'.$anchor.'" class="video_lightbox_anchor_image" alt="" />';
    }
    else if($auto_thumb == "1")
    {
        $anchor_replacement = wp_vid_lightbox_get_auto_thumb($atts);
    }
    else    {
    	$anchor_replacement = $anchor;
    }    
    $href_content = 'http://vimeo.com/'.$video_id.'?width='.$width.'&amp;height='.$height;		
    $output = "";
    $output .= '<a rel="'.WPVL_PRETTYPHOTO_REL.'" href="'.$href_content.'" title="'.$description.'">'.$anchor_replacement.'</a>';	
    return $output;
}

function wp_vid_lightbox_youtube_handler($atts)
{
    extract(shortcode_atts(array(
            'video_id' => '',
            'width' => '',	
            'height' => '',
            'description' => '',
            'anchor' => '',
            'auto_thumb' => '',
    ), $atts));
    if(empty($video_id) || empty($width) || empty($height)){
            return "<p>Error! You must specify a value for the Video ID, Width, Height parameters to use this shortcode!</p>";
    }
    if(empty($auto_thumb) && empty($anchor)){
    	return "<p>Error! You must specify an anchor parameter if you are not using the auto_thumb option.</p>";
    }
    
    $atts['vid_type'] = "youtube";
    if(preg_match("/http/", $anchor)){ // Use the image as the anchor
        $anchor_replacement = '<img src="'.$anchor.'" class="video_lightbox_anchor_image" alt="" />';
    }
    else if($auto_thumb == "1")
    {
        $anchor_replacement = wp_vid_lightbox_get_auto_thumb($atts);
    }
    else{
    	$anchor_replacement = $anchor;
    }
    $href_content = 'https://www.youtube.com/watch?v='.$video_id.'&amp;width='.$width.'&amp;height='.$height;
    $output = '<a rel="'.WPVL_PRETTYPHOTO_REL.'" href="'.$href_content.'" title="'.$description.'">'.$anchor_replacement.'</a>';
    return $output;
}

function wp_vid_lightbox_get_auto_thumb($atts)
{
    $video_id = $atts['video_id'];
    $pieces = explode("&", $video_id);
    $video_id = $pieces[0];

    $anchor_replacement = "";
    if($atts['vid_type']=="youtube")
    {
        $anchor_replacement = '<div class="wpvl_auto_thumb_box_wrapper"><div class="wpvl_auto_thumb_box">';
        $anchor_replacement .= '<img src="https://img.youtube.com/vi/'.$video_id.'/0.jpg" class="video_lightbox_auto_anchor_image" alt="" />';
        $anchor_replacement .= '<div class="wpvl_auto_thumb_play"><img src="'.WP_VID_LIGHTBOX_URL.'/images/play.png" class="wpvl_playbutton" /></div>';
        $anchor_replacement .= '</div></div>';
    }
    else if($atts['vid_type']=="vimeo")
    {
        $VideoInfo = wp_vid_lightbox_getVimeoInfo($video_id);
        $thumb = $VideoInfo['thumbnail_medium'];
        //print_r($VideoInfo);
        $anchor_replacement = '<div class="wpvl_auto_thumb_box_wrapper"><div class="wpvl_auto_thumb_box">';
        $anchor_replacement .= '<img src="'.$thumb.'" class="video_lightbox_auto_anchor_image" alt="" />';
        $anchor_replacement .= '<div class="wpvl_auto_thumb_play"><img src="'.WP_VID_LIGHTBOX_URL.'/images/play.png" class="wpvl_playbutton" /></div>';
        $anchor_replacement .= '</div></div>';
    }
    else
    {
        wp_die("<p>no video type specified</p>");
    }
    return $anchor_replacement; 
}

function wp_vid_lightbox_getVimeoInfo($id) 
{
    if (!function_exists('curl_init')) die('CURL is not installed!');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://vimeo.com/api/v2/video/$id.php");
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $output = unserialize(curl_exec($ch));
    $output = $output[0];
    curl_close($ch);
    return $output;
}

function wp_vid_lightbox_enqueue_script()
{
    if(get_option('wpvl_enable_jquery')=='1')
    {
        wp_enqueue_script('jquery');
    }
    if(get_option('wpvl_enable_prettyPhoto')=='1')
    {
        $wpvl_prettyPhoto = WP_Video_Lightbox_prettyPhoto::get_instance();
        wp_register_script('jquery.prettyphoto', WP_VID_LIGHTBOX_URL.'/js/jquery.prettyPhoto.js', array('jquery'), WPVL_PRETTYPHOTO_VERSION);
        wp_enqueue_script('jquery.prettyphoto');
        wp_register_script('video-lightbox', WP_VID_LIGHTBOX_URL.'/js/video-lightbox.js', array('jquery'), WPVL_PRETTYPHOTO_VERSION);
        wp_enqueue_script('video-lightbox');
        wp_register_style('jquery.prettyphoto', WP_VID_LIGHTBOX_URL.'/css/prettyPhoto.css');
        wp_enqueue_style('jquery.prettyphoto');
        wp_register_style('video-lightbox', WP_VID_LIGHTBOX_URL.'/wp-video-lightbox.css');
        wp_enqueue_style('video-lightbox');

        wp_localize_script('video-lightbox', 'vlpp_vars', array(
                'prettyPhoto_rel' => WPVL_PRETTYPHOTO_REL,
                'animation_speed' => $wpvl_prettyPhoto->animation_speed,
                'slideshow' => $wpvl_prettyPhoto->slideshow,
                'autoplay_slideshow' => $wpvl_prettyPhoto->autoplay_slideshow,
                'opacity' => $wpvl_prettyPhoto->opacity,
                'show_title' => $wpvl_prettyPhoto->show_title,
                'allow_resize' => $wpvl_prettyPhoto->allow_resize,
                'allow_expand' => $wpvl_prettyPhoto->allow_expand,
                'default_width' => $wpvl_prettyPhoto->default_width,
                'default_height' => $wpvl_prettyPhoto->default_height,
                'counter_separator_label' => $wpvl_prettyPhoto->counter_separator_label,
                'theme' => $wpvl_prettyPhoto->theme,
                'horizontal_padding' => $wpvl_prettyPhoto->horizontal_padding,
                'hideflash' => $wpvl_prettyPhoto->hideflash,
                'wmode' => $wpvl_prettyPhoto->wmode,
                'autoplay' => $wpvl_prettyPhoto->autoplay,
                'modal' => $wpvl_prettyPhoto->modal,
                'deeplinking' => $wpvl_prettyPhoto->deeplinking,
                'overlay_gallery' => $wpvl_prettyPhoto->overlay_gallery,
                'overlay_gallery_max' => $wpvl_prettyPhoto->overlay_gallery_max,
                'keyboard_shortcuts' => $wpvl_prettyPhoto->keyboard_shortcuts,
                'ie6_fallback' => $wpvl_prettyPhoto->ie6_fallback
            )
        );
    }
}