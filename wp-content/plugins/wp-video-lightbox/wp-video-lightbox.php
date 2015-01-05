<?php
/*
Plugin Name: WP Video Lightbox
Version: 1.7.1
Plugin URI: https://www.tipsandtricks-hq.com/?p=2700
Author: Tips and Tricks HQ, Ruhul Amin
Author URI: https://www.tipsandtricks-hq.com/
Description: Simple video lightbox plugin to display videos in a nice overlay popup. It also supports images, flash, YouTube, iFrame.
*/
if (!defined('ABSPATH')) exit;

if (!class_exists('WP_Video_Lightbox'))
{
    class WP_Video_Lightbox
    {
        var $version = '1.7.1';
        var $db_version = '1.0';
        var $plugin_url;
        var $plugin_path;
        var $settings_obj;

        function __construct() 
        {
            $this->define_constants();
            $this->includes();
            $this->loader_operations();
            //Handle any db install and upgrade task

            add_action( 'init', array( &$this, 'plugin_init' ), 0 );
            add_action( 'wp_enqueue_scripts', array( &$this, 'plugin_scripts' ), 0 );
            add_action( 'wp_footer', array( &$this, 'plugin_footer' ), 0 );
        }

        function define_constants(){
            define('WP_VIDEO_LIGHTBOX_VERSION', $this->version);
            define('WP_VID_LIGHTBOX_URL', $this->plugin_url());
            define('WP_VIDEO_LIGHTBOX_PATH', $this->plugin_path());
            define('WP_VIDEO_LIGHTBOX_DB_VERSION', $this->db_version);
            define('WPVL_PRETTYPHOTO_REL', 'wp-video-lightbox');
            define('WPVL_PRETTYPHOTO_VERSION', '3.1.5');
        }

        function includes() {
                include_once('class-prettyphoto.php');
                include_once('wpvl-settings.php');
                include_once('misc_functions.php');
        }

        function loader_operations(){
                register_activation_hook( __FILE__, array(&$this, 'activate_handler') );    //activation hook
                add_action('plugins_loaded',array(&$this, 'plugins_loaded_handler'));   //plugins loaded hook
        }

        function plugins_loaded_handler()  //Runs when plugins_loaded action gets fired
        {
            $this->do_db_upgrade_check();
            $this->settings_obj = new Video_Lightbox_Settings_Page();   //Initialize settins menus
        }
        
        function activate_handler(){//Will run when the plugin activates only
            //initialize settings
            add_option('wpvl_plugin_version', $this->version);
            add_option('wpvl_enable_jquery', '1');
            add_option('wpvl_enable_prettyPhoto', '1');
            $wpvl_prettyPhoto = WP_Video_Lightbox_prettyPhoto::get_instance();
            WP_Video_Lightbox_prettyPhoto::save_object($wpvl_prettyPhoto);
        }

        function do_db_upgrade_check(){
            //NOP
            if(is_admin())
            {
                $wpvl_version = get_option('wpvl_plugin_version');
                if(!isset($wpvl_version) || $wpvl_version != $this->version)
                {
                    $this->activate_handler();
                    update_option('wpvl_plugin_version', $this->version);
                }
            }
        }

        function plugin_init(){
            //add_filter('ngg_render_template',array(&$this, 'ngg_render_template_handler'),10,2);
            if(!is_admin())
            {

            }
        }

        function plugin_scripts()
        {
            if (!is_admin()) 
            {
                wp_vid_lightbox_enqueue_script();
            }
        }

        function plugin_footer()
        {

        }

        function plugin_url(){ 
            if ( $this->plugin_url ) return $this->plugin_url;
            return $this->plugin_url = plugins_url( basename( plugin_dir_path(__FILE__) ), basename( __FILE__ ) );
        }

        function plugin_path(){ 	
            if ( $this->plugin_path ) return $this->plugin_path;		
            return $this->plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );
        }

    }

}//End of class not exists check

$GLOBALS['wp_video_lightbox'] = new WP_Video_Lightbox();
