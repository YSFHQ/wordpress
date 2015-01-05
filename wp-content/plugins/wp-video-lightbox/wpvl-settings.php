<?php
class Video_Lightbox_Settings_Page 
{	
    
    function __construct() {
        //add_action( 'init', array( &$this, 'load_settings' ) );
        add_action( 'admin_menu', array( &$this, 'add_options_menu' ) );
    }

    function add_options_menu(){
        add_options_page('Video Lightbox Settings', 'Video Lightbox', 'manage_options', 'wp_video_lightbox', array(&$this, 'display_settings_page'));
    }
    
    function display_settings_page() 
    {
        $wpvl_plugin_tabs = array(
            'wp_video_lightbox' => 'General',
            'wp_video_lightbox&action=prettyPhoto' => 'prettyPhoto'
        );
        echo '<div class="wrap">'.screen_icon().'<h2>WP Video Lightbox v'.WP_VIDEO_LIGHTBOX_VERSION.'</h2>';;    
        echo '<div id="poststuff"><div id="post-body">';  

        if(isset($_GET['page'])){
            $current = $_GET['page'];
            if(isset($_GET['action'])){
                $current .= "&action=".$_GET['action'];
            }
        }
        $content = '';
        $content .= '<h2 class="nav-tab-wrapper">';
        foreach($wpvl_plugin_tabs as $location => $tabname)
        {
            if($current == $location){
                $class = ' nav-tab-active';
            } else{
                $class = '';    
            }
            $content .= '<a class="nav-tab'.$class.'" href="?page='.$location.'">'.$tabname.'</a>';
        }
        $content .= '</h2>';
        echo $content;

        if(isset($_GET['action']))
        { 
            switch ($_GET['action'])
            {
               case 'prettyPhoto':
                   $this->prettyPhoto_settings_section();
                   break;
            }
        }
        else
        {
            $this->general_settings_section();
        }

        echo '</div></div>';
        echo '</div>';
    }
    
    function general_settings_section() 
    {	
        if (isset($_POST['wpvl_general_settings_update']))
        {
            $nonce = $_REQUEST['_wpnonce'];
            if ( !wp_verify_nonce($nonce, 'wpvl_general_settings')){
                    wp_die('Error! Nonce Security Check Failed! Go back to general menu and save the settings again.');
            }
            
            update_option('wpvl_enable_jquery', ($_POST["enable_jquery"]=='1')?'1':'');
            
            echo '<div id="message" class="updated fade"><p><strong>';
            echo 'prettyPhoto Settings Updated!';
            echo '</strong></p></div>';
        }
        ?>

        <div style="background: none repeat scroll 0 0 #FFF6D5;border: 1px solid #D1B655;color: #3F2502;margin: 10px 0;padding: 5px 5px 5px 10px;text-shadow: 1px 1px #FFFFFF;">	
        <p><?php _e("For more information, updates, detailed documentation and video tutorial, please visit:", "WPVL"); ?><br />
        <a href="https://www.tipsandtricks-hq.com/wordpress-video-lightbox-plugin-display-videos-in-a-fancy-lightbox-overlay-2700" target="_blank"><?php _e("WP Video Lightbox Homepage", "WPVL"); ?></a></p>
        </div>

        <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
        <?php wp_nonce_field('wpvl_general_settings'); ?>

        <table class="form-table">
            
        <tbody>
        
        <tr valign="top">
        <th scope="row">Enable jQuery</th>
        <td> <fieldset><legend class="screen-reader-text"><span>Enable jQuery</span></legend><label for="enable_jquery">
        <input name="enable_jquery" type="checkbox" id="enable_jquery" <?php if(get_option('wpvl_enable_jquery')== '1') echo ' checked="checked"';?> value="1">
        Check this option if you want to enable jQuery library</label>
        </fieldset></td>
        </tr>
        
        </tbody>
        
        </table>

        <p class="submit"><input type="submit" name="wpvl_general_settings_update" id="wpvl_general_settings_update" class="button button-primary" value="Save Changes"></p></form>
 
        <?php
    }
    
    function prettyPhoto_settings_section()
    {
        if (isset($_POST['wpvl_prettyPhoto_update_settings']))
        {
            $nonce = $_REQUEST['_wpnonce'];
            if ( !wp_verify_nonce($nonce, 'wpvl_prettyPhoto_settings')){
                    wp_die('Error! Nonce Security Check Failed! Go back to prettyPhoto menu and save the settings again.');
            }            
            $wpvl_prettyPhoto = WP_Video_Lightbox_prettyPhoto::get_instance();
            update_option('wpvl_enable_prettyPhoto', ($_POST["enable_prettyPhoto"]=='1')?'1':'');
            $wpvl_prettyPhoto->animation_speed = trim($_POST["animation_speed"]);
            $wpvl_prettyPhoto->slideshow = trim($_POST["slideshow"]);
            $wpvl_prettyPhoto->autoplay_slideshow = ($_POST["autoplay_slideshow"]=='1')?'true':'false';
            $wpvl_prettyPhoto->opacity = trim($_POST["opacity"]);
            $wpvl_prettyPhoto->show_title = ($_POST["show_title"]=='1')?'true':'false';
            $wpvl_prettyPhoto->allow_resize = ($_POST["allow_resize"]=='1')?'true':'false';
            $wpvl_prettyPhoto->allow_expand = ($_POST["allow_expand"]=='1')?'true':'false';
            $wpvl_prettyPhoto->default_width = trim($_POST["default_width"]);
            $wpvl_prettyPhoto->default_height = trim($_POST["default_height"]);
            $wpvl_prettyPhoto->counter_separator_label = trim($_POST["counter_separator_label"]);
            $wpvl_prettyPhoto->theme = trim($_POST["theme"]);
            $wpvl_prettyPhoto->horizontal_padding = trim($_POST["horizontal_padding"]);
            $wpvl_prettyPhoto->hideflash = ($_POST["hideflash"]=='1')?'true':'false';
            $wpvl_prettyPhoto->wmode = trim($_POST["wmode"]);
            $wpvl_prettyPhoto->autoplay = ($_POST["autoplay"]=='1')?'true':'false';
            $wpvl_prettyPhoto->modal = ($_POST["modal"]=='1')?'true':'false';
            $wpvl_prettyPhoto->deeplinking = ($_POST["deeplinking"]=='1')?'true':'false';
            $wpvl_prettyPhoto->overlay_gallery = ($_POST["overlay_gallery"]=='1')?'true':'false';
            $wpvl_prettyPhoto->overlay_gallery_max = trim($_POST["overlay_gallery_max"]);
            $wpvl_prettyPhoto->keyboard_shortcuts = ($_POST["keyboard_shortcuts"]=='1')?'true':'false';
            $wpvl_prettyPhoto->ie6_fallback = ($_POST["ie6_fallback"]=='1')?'true':'false';
            
            WP_Video_Lightbox_prettyPhoto::save_object($wpvl_prettyPhoto);
            
            echo '<div id="message" class="updated fade"><p><strong>';
            echo 'prettyPhoto Settings Updated!';
            echo '</strong></p></div>';
        }
        $wpvl_prettyPhoto = WP_Video_Lightbox_prettyPhoto::get_instance();
        ?>

        <div style="background: none repeat scroll 0 0 #FFF6D5;border: 1px solid #D1B655;color: #3F2502;margin: 10px 0;padding: 5px 5px 5px 10px;text-shadow: 1px 1px #FFFFFF;">	
        <p><?php _e("For more information, updates, detailed documentation and video tutorial, please visit:", "WPVL"); ?><br />
        <a href="https://www.tipsandtricks-hq.com/wordpress-video-lightbox-plugin-display-videos-in-a-fancy-lightbox-overlay-2700" target="_blank"><?php _e("WP Video Lightbox Homepage", "WPVL"); ?></a></p>
        </div>

        <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
        <?php wp_nonce_field('wpvl_prettyPhoto_settings'); ?>

        <table class="form-table">
            
        <tbody>
        
        <tr valign="top">
        <th scope="row">Enable prettyPhoto</th>
        <td> <fieldset><legend class="screen-reader-text"><span>Enable prettyPhoto</span></legend><label for="enable_prettyPhoto">
        <input name="enable_prettyPhoto" type="checkbox" id="enable_prettyPhoto" <?php if(get_option('wpvl_enable_prettyPhoto')=='1') echo ' checked="checked"';?> value="1">
        Check this option if you want to enable prettyPhoto library</label>
        </fieldset></td>
        </tr>    
            
        <tr valign="top">
        <th scope="row"><label for="animation_speed">Animation speed</label></th>
        <td>
        <select name="animation_speed" id="animation_speed">
            <option <?php echo ($wpvl_prettyPhoto->animation_speed==='fast')?'selected="selected"':'';?> value="fast">Fast</option>
            <option <?php echo ($wpvl_prettyPhoto->animation_speed==='slow')?'selected="selected"':'';?> value="slow">Slow</option>
            <option <?php echo ($wpvl_prettyPhoto->animation_speed==='normal')?'selected="selected"':'';?> value="normal">Normal</option>
        </select>
        <!-- <span id="utc-time"><abbr title="Coordinated Universal Time">UTC</abbr> time is <code>2013-11-01 3:56:07</code></span> -->
        <p class="description">fast / slow / normal [default: fast]</p>
        </td>
        </tr>    
            
        <tr valign="top">
        <th scope="row"><label for="slideshow">Slideshow</label></th>
        <td><input name="slideshow" type="text" id="slideshow" value="<?php echo $wpvl_prettyPhoto->slideshow; ?>" class="regular-text">
        <p class="description">false OR interval time in ms [default: 5000]</p></td>
        </tr>
        
        <tr valign="top">
        <th scope="row">Autoplay slideshow</th>
        <td> <fieldset><legend class="screen-reader-text"><span>Autoplay slideshow</span></legend><label for="autoplay_slideshow">
        <input name="autoplay_slideshow" type="checkbox" id="autoplay_slideshow" <?php if($wpvl_prettyPhoto->autoplay_slideshow == 'true') echo ' checked="checked"';?> value="1">
        true / false [default: false]</label>
        </fieldset></td>
        </tr>
        
        <tr valign="top">
        <th scope="row"><label for="opacity">Opacity</label></th>
        <td><input name="opacity" type="text" id="opacity" value="<?php echo $wpvl_prettyPhoto->opacity; ?>" class="regular-text">
        <p class="description">Value between 0 and 1 [default: 0.8]</p></td>
        </tr>
        
        <tr valign="top">
        <th scope="row">Show title</th>
        <td> <fieldset><legend class="screen-reader-text"><span>Show title</span></legend><label for="show_title">
        <input name="show_title" type="checkbox" id="show_title" <?php if($wpvl_prettyPhoto->show_title == 'true') echo ' checked="checked"';?> value="1">
        true / false [default: true]</label>
        </fieldset></td>
        </tr>
        
        <tr valign="top">
        <th scope="row">Allow resize</th>
        <td> <fieldset><legend class="screen-reader-text"><span>Allow resize</span></legend><label for="allow_resize">
        <input name="allow_resize" type="checkbox" id="allow_resize" <?php if($wpvl_prettyPhoto->allow_resize == 'true') echo ' checked="checked"';?> value="1">
        Resize the photos bigger than viewport. true / false [default: true]</label>
        </fieldset></td>
        </tr>
        
        <tr valign="top">
        <th scope="row">Allow expand</th>
        <td> <fieldset><legend class="screen-reader-text"><span>Allow expand</span></legend><label for="allow_expand">
        <input name="allow_expand" type="checkbox" id="allow_expand" <?php if($wpvl_prettyPhoto->allow_resize == 'true') echo ' checked="checked"';?> value="1">
        Allow the user to expand a resized image. true / false [default: true]</label>
        </fieldset></td>
        </tr>
        
        <tr valign="top">
        <th scope="row"><label for="opacity">Default width</label></th>
        <td><input name="default_width" type="text" id="default_width" value="<?php echo $wpvl_prettyPhoto->default_width; ?>" class="regular-text">
        <p class="description">[default: 640]</p></td>
        </tr>
        
        <tr valign="top">
        <th scope="row"><label for="opacity">Default height</label></th>
        <td><input name="default_height" type="text" id="default_height" value="<?php echo $wpvl_prettyPhoto->default_height; ?>" class="regular-text">
        <p class="description">[default: 480]</p></td>
        </tr>
        
        <tr valign="top">
        <th scope="row"><label for="opacity">Counter separator label</label></th>
        <td><input name="counter_separator_label" type="text" id="counter_separator_label" value="<?php echo $wpvl_prettyPhoto->counter_separator_label; ?>" class="regular-text">
        <p class="description">The separator for the gallery counter 1 "of" 2 [default: /]</p></td>
        </tr>
        
        <tr valign="top">
        <th scope="row"><label for="theme">Theme</label></th>
        <td>
        <select name="theme" id="theme">
            <option selected="selected" value="pp_default">Default</option>
            <option <?php echo ($wpvl_prettyPhoto->theme==='light_rounded')?'selected="selected"':'';?> value="light_rounded">Light Rounded</option>
            <option <?php echo ($wpvl_prettyPhoto->theme==='dark_rounded')?'selected="selected"':'';?> value="dark_rounded">Dark Rounded</option>
            <option <?php echo ($wpvl_prettyPhoto->theme==='light_square')?'selected="selected"':'';?> value="light_square">Light Square</option>
            <option <?php echo ($wpvl_prettyPhoto->theme==='dark_square')?'selected="selected"':'';?> value="dark_square">Dark Square</option>
            <option <?php echo ($wpvl_prettyPhoto->theme==='facebook')?'selected="selected"':'';?> value="facebook">Facebook</option>
        </select>
        <!-- <span id="utc-time"><abbr title="Coordinated Universal Time">UTC</abbr> time is <code>2013-11-01 3:56:07</code></span> -->
        <p class="description">Select a theme for the lightbox window</p>
        </td>
        </tr>
        
        <tr valign="top">
        <th scope="row"><label for="opacity">Horizontal padding</label></th>
        <td><input name="horizontal_padding" type="text" id="horizontal_padding" value="<?php echo $wpvl_prettyPhoto->horizontal_padding; ?>" class="regular-text">
        <p class="description">The padding on each side of the picture [default: 20]</p></td>
        </tr>
        
        <tr valign="top">
        <th scope="row">Hide Flash</th>
        <td> <fieldset><legend class="screen-reader-text"><span>Hide Flash</span></legend><label for="hideflash">
        <input name="hideflash" type="checkbox" id="hideflash" <?php if($wpvl_prettyPhoto->hideflash == 'true') echo ' checked="checked"';?> value="1">
        Hides all the flash objects on a page, set to TRUE if flash appears over prettyPhoto [default: false]</label>
        </fieldset></td>
        </tr>
        
        <tr valign="top">
        <th scope="row"><label for="animation_speed">wmode</label></th>
        <td>
        <select name="wmode" id="wmode">
            <option selected="selected" value="opaque">opaque</option>
        </select>
        <!-- <span id="utc-time"><abbr title="Coordinated Universal Time">UTC</abbr> time is <code>2013-11-01 3:56:07</code></span> -->
        <p class="description">Set the flash wmode attribute [default: opaque]</p>
        </td>
        </tr>
        
        <tr valign="top">
        <th scope="row">Autoplay</th>
        <td> <fieldset><legend class="screen-reader-text"><span>Autoplay</span></legend><label for="autoplay">
        <input name="autoplay" type="checkbox" id="autoplay" <?php if($wpvl_prettyPhoto->autoplay == 'true') echo ' checked="checked"';?> value="1">
        Automatically start videos: true / false [default: true]</label>
        </fieldset></td>
        </tr>
        
        <tr valign="top">
        <th scope="row">Modal</th>
        <td> <fieldset><legend class="screen-reader-text"><span>Modal</span></legend><label for="modal">
        <input name="modal" type="checkbox" id="modal" <?php if($wpvl_prettyPhoto->modal == 'true') echo ' checked="checked"';?> value="1">
        If set to true, only the close button will close the window [default: false]</label>
        </fieldset></td>
        </tr>
        
        <tr valign="top">
        <th scope="row">Deeplinking</th>
        <td> <fieldset><legend class="screen-reader-text"><span>Deeplinking</span></legend><label for="deeplinking">
        <input name="deeplinking" type="checkbox" id="deeplinking" <?php if($wpvl_prettyPhoto->deeplinking == 'true') echo ' checked="checked"';?> value="1">
        Allow prettyPhoto to update the url to enable deeplinking. [default: true]</label>
        </fieldset></td>
        </tr>
        
        <tr valign="top">
        <th scope="row">Overlay gallery</th>
        <td> <fieldset><legend class="screen-reader-text"><span>Overlay gallery</span></legend><label for="overlay_gallery">
        <input name="overlay_gallery" type="checkbox" id="overlay_gallery" <?php if($wpvl_prettyPhoto->overlay_gallery == 'true') echo ' checked="checked"';?> value="1">
        If set to true, a gallery will overlay the fullscreen image on mouse over [default: true]</label>
        </fieldset></td>
        </tr>
        
        <tr valign="top">
        <th scope="row"><label for="opacity">Overlay gallery max</label></th>
        <td><input name="overlay_gallery_max" type="text" id="overlay_gallery_max" value="<?php echo $wpvl_prettyPhoto->overlay_gallery_max; ?>" class="regular-text">
        <p class="description">Maximum number of pictures in the overlay gallery [default: 30]</p></td>
        </tr>
        
        <tr valign="top">
        <th scope="row">Keyboard shortcuts</th>
        <td> <fieldset><legend class="screen-reader-text"><span>Keyboard shortcuts</span></legend><label for="keyboard_shortcuts">
        <input name="keyboard_shortcuts" type="checkbox" id="keyboard_shortcuts" <?php if($wpvl_prettyPhoto->keyboard_shortcuts == 'true') echo ' checked="checked"';?> value="1">
        Set to false if you open forms inside prettyPhoto [default: true]</label>
        </fieldset></td>
        </tr>
        
        <tr valign="top">
        <th scope="row">IE6 fallback</th>
        <td> <fieldset><legend class="screen-reader-text"><span>IE6 fallback</span></legend><label for="ie6_fallback">
        <input name="ie6_fallback" type="checkbox" id="ie6_fallback" <?php if($wpvl_prettyPhoto->ie6_fallback == 'true') echo ' checked="checked"';?> value="1">
        compatibility fallback for IE6 [default: true]</label>
        </fieldset></td>
        </tr>
        
        </tbody>
        
        </table>

        <p class="submit"><input type="submit" name="wpvl_prettyPhoto_update_settings" id="wpvl_prettyPhoto_update_settings" class="button button-primary" value="Save Changes"></p></form>
 
        <?php
    }

    function current_tab() {
            $tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->plugin_settings_page_key;
            return $tab;
    }

    /*
     * Renders our tabs in the plugin options page,
     * walks through the object's tabs array and prints
     * them one by one. Provides the heading for the
     * plugin_options_page method.
     */
    function plugin_options_tabs() 
    {
        $current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->plugin_settings_page_key;
        echo '<h2 class="nav-tab-wrapper">';
        foreach ( $this->plugin_settings_tabs as $tab_key => $tab_caption ) 
        {
            $active = $current_tab == $tab_key ? 'nav-tab-active' : '';
            echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->plugin_options_key . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';	
        }
        echo '</h2>';
    }
} //end class
