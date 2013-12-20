=== WP Video Lightbox ===
Contributors: Tips and Tricks HQ, Ruhul Amin, wptipsntricks
Donate link: http://www.tipsandtricks-hq.com/
Tags: wordpress lightbox, wordpress video lightbox, video lightbox, wp video lightbox, wordpress video embed, add video to wordpress, gallery, image, images, lightbox, lightview, overlay, photo, photos, picture, video
Requires at least: 3.0
Tested up to: 3.8
Stable tag: 1.6.5
License: GPLv2 or later

Very easy to use WordPress lightbox plugin to display YouTube and Vimeo videos in an elegant lightbox overlay.

== Description ==

The WordPress Video Lightbox plugin allows you to embed videos on a page using lightbox overlay display. 

This plugin can be used to display images, flash, YouTube, Vimeo, iFrame etc in a lightbox overlay. The embedded videos can be viewed on iPhone and iPad too.

= Embedding Vimeo Video =

You can embed a vimeo video using the following shortcode in a WordPress post or page: 
 
`[video_lightbox_vimeo5 video_id="13562192" width="640" height="480" anchor="click here to open vimeo video"]`
`[video_lightbox_vimeo5 video_id="13562192" width="640" height="480" anchor="http://www.example.com/images/vimeo-thumb.jpg"]`
 
You need to replace the value of "video_id" with your actual vimeo video ID. When a user clicks on the anchor text/image your vimeo video will pop up in lightbox.

= Embedding YouTube Video =

You can embed a YouTube video using the following shortcode in a WordPress post or page: 
 
`[video_lightbox_youtube video_id="G7z74BvLWUg" width="640" height="480" anchor="click here to open YouTube video"]`
`[video_lightbox_youtube video_id="G7z74BvLWUg" width="640" height="480" anchor="http://www.example.com/images/youtube-thumb.jpg"]`

You need to replace the value of "video_id" with your actual YouTube video ID. You can also control the size of the lightbox window by customizing the width and height parameters.

= Features/Settings Configuration =

Once you have installed the plugin you can configure some options to customize the popup. The settings menu can be accessed from "Settings->Video Lightbox->prettyPhoto".

* Enable prettyPhoto: Check this option if you want to use the prettyPhoto library
* Animation speed: fast / slow / normal [default: fast]
* Autoplay slideshow: true / false [default: false]
* Opacity: Value between 0 and 1 [default: 0.8]
* Show title: true / false [default: true] 
* Allow resize: Resize the photos bigger than viewport. true / false [default: true]
* Allow expand: Allow the user to expand a resized image. true / false [default: true]
* Default width: default width of the lightbox window [default: 640, you can override it using the width parameter in the shortcode]
* Default height: default height of the lightbox window [default: 480, you can override it using the height parameter in the shortcode]
* Counter separator label: The separator for the gallery counter in lightbox [default: /]
* Theme: theme for the lightbox window - Default, Light Rounded, Dark Rounded, Light Square, Dark Square, Facebook
* Horizontal padding: The padding on each side of the lightbox window [default: 20]
* Hide Flash: Hides all the flash objects on a page, set to true if flash appears over prettyPhoto [default: false]
* wmode: the flash wmode attribute [default: opaque]
* Autoplay: Automatically start videos: true / false [default: true]
* Modal: If set to true, only the close button will close the window [default: false]
* Deeplinking: Allow prettyPhoto to update the url to enable deeplinking. [default: true]
* Overlay gallery: If this enabled, a gallery will overlay the fullscreen image on mouse over [default: true]
* Overlay gallery max: Maximum number of pictures in the overlay gallery [default: 30]
* Keyboard shortcuts: Set to false if you open forms inside prettyPhoto [default: true]
* IE6 fallback: compatibility fallback for IE6 [default: true]

For video tutorial, screenshots, detailed documentation, support and updates, please visit: [WP Video Lightbox plugin page](http://www.tipsandtricks-hq.com/wordpress-video-lightbox-plugin-display-videos-in-a-fancy-lightbox-overlay-2700)

== Usage ==

You need to embed the appropriate shortcode on a post/page to display the specific type of media (Youtube, Vimeo, Flash etc).
Instructions for using the shortcodes are available at the following URL: 
[WP Video Lightbox Details Usage Instruction](http://www.tipsandtricks-hq.com/wp-content/uploads/docs/WP-Video-Lightbox-Plugin-Usage-Guide.pdf)

== Installation ==

Upload the plugin to the plugins directory via WordPress Plugin Uploader (Plugins->Add New->Upload->Choose File->Install Now) and Activate it.

== Frequently Asked Questions ==

= Can this plugin be used to embed a YouTube video? =
Yes

= Can this plugin be used to embed a Vimeo video? =
Yes

= Can this plugin be used to do lightbox on images? =
Yes

= Can this plugin automatically create a thumbnail/anchor image from the YouTube video? =
Yes

== Screenshots ==

Please see this page for screenshots:
http://www.tipsandtricks-hq.com/wordpress-video-lightbox-plugin-display-videos-in-a-fancy-lightbox-overlay-2700

== Upgrade Notice ==

None

== Changelog ==

= 1.6.5 =
* fixed a bug in the plugin where default options were not set for automatic upgrade

= 1.6.4 =
* plugin is now compatible with WordPress 3.8.

= 1.6.3 =
* plugin now works with multisite install.

= 1.6.2 =
* Created a settings menu for the plugin
* Updated the prettyPhoto library to 3.1.5

= 1.6.1 =
* Added https support for YouTube video.

= 1.6 =
* Added an option to automatically create and use the thumbnail of the YouTube or Vimeo video as the anchor image.

= 1.5 = 
* Made some improvements as to how the JavaScript code is loaded in the plugin. WordPress 3.6 compatibility.

= 1.4 = 
* added a feature in the shortcode to turn off the related video display after the playback

= 1.3 = 
* First commit to the wordpress repository