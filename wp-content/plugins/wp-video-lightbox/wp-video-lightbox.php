<?php
/*
Plugin Name: WP Video Lightbox
Version: 1.6.1
Plugin URI: http://www.tipsandtricks-hq.com/?p=2700
Author: Tips and Tricks HQ, Ruhul Amin
Author URI: http://www.tipsandtricks-hq.com/
Description: Simple video lightbox plugin to display videos in a nice overlay popup. It also supports images, flash, YouTube, iFrame.
*/
define('WP_LICENSE_MANAGER_VERSION', "1.6.1");
define('WP_VID_LIGHTBOX_URL', plugins_url('',__FILE__));

add_shortcode('video_lightbox_vimeo5', 'wp_vid_lightbox_vimeo5_handler');
add_shortcode('video_lightbox_youtube', 'wp_vid_lightbox_youtube_handler');

function wp_vid_lightbox_vimeo5_handler($atts) 
{
    extract(shortcode_atts(array(
            'video_id' => '',
            'width' => '',	
            'height' => '',
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
    $output .= '<a rel="wp-video-lightbox" href="'.$href_content.'" title="">'.$anchor_replacement.'</a>';	
    return $output;
}

function wp_vid_lightbox_youtube_handler($atts)
{
    extract(shortcode_atts(array(
            'video_id' => '',
            'width' => '',	
            'height' => '',
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
    $output = '<a rel="wp-video-lightbox" href="'.$href_content.'" title="">'.$anchor_replacement.'</a>';
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

function wp_vid_lightbox_init()
{
    if (!is_admin()) 
    {
        wp_enqueue_script('jquery');
        wp_register_script('jquery.prettyphoto', WP_VID_LIGHTBOX_URL.'/js/jquery.prettyPhoto.js', array('jquery'), '3.1.4');
        wp_enqueue_script('jquery.prettyphoto');
        wp_register_script('video-lightbox', WP_VID_LIGHTBOX_URL.'/js/video-lightbox.js', array('jquery'), '3.1.4');
        wp_enqueue_script('video-lightbox');
        wp_register_style('jquery.prettyphoto', WP_VID_LIGHTBOX_URL.'/css/prettyPhoto.css');
        wp_enqueue_style('jquery.prettyphoto');
        wp_register_style('video-lightbox', WP_VID_LIGHTBOX_URL.'/wp-video-lightbox.css');
        wp_enqueue_style('video-lightbox');
    }
}

add_action('init', 'wp_vid_lightbox_init');
