<?php
/**
 *
 *
 *  PageLines Default/Standard Options Lib
 *
 *
 *  @package PageLines Framework
 *  @since 3.0.0
 *
 *
 */
class EditorSettings {

	public $settings = array( );


	function __construct(){
		$this->settings['configuration'] = array(
			'name' 	=> 'Admin Shortcuts',
			'icon'	=> 'icon-cog',
			'pos'	=> 1,
			'opts' 	=> $this->config()
		);
	
		$this->settings['basic_settings'] = array(
			'name' 	=> 'Site Images',
			'icon'	=> 'icon-picture',
			'pos'	=> 2,
			'opts' 	=> $this->basic()
		);

		$this->settings['social_media'] = array(
			'name' 	=> 'Social <span class="spamp">&amp;</span> Local',
			'icon'	=> 'icon-comments',
			'pos'	=> 5,
			'opts' 	=> $this->social()
		);
		
		$this->settings['advanced'] = array(
			'name' 	=> 'Advanced',
			'icon'	=> 'icon-wrench',
			'pos'	=> 50,
			'opts' 	=> $this->advanced()
		);

		$this->settings['resets'] = array(
			'name' 	=> 'Resets',
			'icon'	=> 'icon-undo',
			'pos'	=> 55,
			'opts' 	=> $this->resets()
		);
	}
	



	function get_set( ){

		$settings =  apply_filters('pl_settings_array', $this->settings);

		$default = array(
			'icon'	=> 'icon-edit',
			'pos'	=> 100
		);

		foreach($settings as $key => &$info){
			$info = wp_parse_args( $info, $default );
		}
		unset($info);

		uasort($settings, array(&$this, "cmp_by_position") );

		return apply_filters('pl_sorted_settings_array', $settings);
	}

	function cmp_by_position($a, $b) {

		if( isset( $a['pos'] ) && is_int( $a['pos'] ) && isset( $b['pos'] ) && is_int( $b['pos'] ) )
			return $a['pos'] - $b['pos'];
		else
			return 0;
	}
	
	function config(){
	
			$settings = array(

				array(
					'key'			=> 'set_homepage',
					'label'			=> '<i class="icon-home"></i> Set Site Homepage',
					'type' 			=> 	'link',
					'classes'		=> 'btn-primary btn-block',
					'url'			=> admin_url( 'options-reading.php' ), 
					'title' 		=> 	__( 'Site Homepage', 'pagelines' ),
				),


				array(
					'key'			=> 'manage_menus',
					'label'			=> '<i class="icon-reorder"></i> Manage Menus',
					'type' 			=> 	'link',
					'classes'		=> 'btn-primary btn-block',
					'url'			=> admin_url( 'nav-menus.php' ), 
					'title' 		=> 	__( 'Manage Menus', 'pagelines' ),
				),
				
				array(
					'key'			=> 'edit_widgets',
					'label'			=> '<i class="icon-retweet"></i> Manage Widgets',
					'type' 			=> 	'link',
					'classes'		=> 'btn-primary btn-block',
					'url'			=> admin_url( 'widgets.php' ), 
					'title' 		=> 	__( 'Manage Widgetized Areas', 'pagelines' ),
				),
				
				array(
					'key'			=> 'manage_profile',
					'label'			=> '<i class="icon-user"></i> User Profile',
					'type' 			=> 	'link',
					'classes'		=> 'btn-primary btn-block',
					'url'			=> admin_url( 'profile.php' ), 
					'title' 		=> 	__( 'Manage Your Profile', 'pagelines' ),
				),
				array(
					'key'			=> 'site_settings_admin',
					'label'			=> '<i class="icon-cog"></i> Site Settings',
					'type' 			=> 	'link',
					'classes'		=> 'btn-primary btn-block',
					'url'			=> admin_url( 'options-general.php' ), 
					'title' 		=> 	__( 'Site Settings', 'pagelines' ),
				),
				array(
					'key'			=> 'plugins_management',
					'label'			=> '<i class="icon-download"></i> Plugins Admin',
					'type' 			=> 	'link',
					'classes'		=> 'btn-primary btn-block',
					'url'			=> admin_url( 'plugins.php' ), 
					'title' 		=> 	__( 'Manage Extensions', 'pagelines' ),
				),
				
				array(
					'key'			=> 'perm_management',
					'label'			=> '<i class="icon-link"></i> Permalinks',
					'type' 			=> 	'link',
					'classes'		=> 'btn-primary btn-block',
					'url'			=> admin_url( 'options-permalink.php' ), 
					'title' 		=> 	__( 'Manage Permalinks', 'pagelines' ),
				)
			);

			if( pl_setting( 'enable_debug' ) ) {
				$settings[] = array(
						'key'			=> 'debug_info',
						'label'			=> '<i class="icon-ambulance"></i> View Debug Info',
						'type' 			=> 	'link',
						'classes'		=> 'btn-important btn-block',
						'url'			=> site_url( '?pldebug=1' ), 
						'title' 		=> 	__( 'Debug Enabled', 'pagelines' ),
				);
			}

			return $settings;
		
	}

	function basic(){

		$settings = array(

			array(
				'key'			=> 'pagelines_favicon',
				'label'			=> 'Upload Favicon (32px by 32px)',
				'type' 			=> 	'image_upload',
				'imgsize' 			=> 	'16',
				'title' 		=> 	__( 'Favicon Image', 'pagelines' ),
				'help' 			=> 	__( 'Enter the full URL location of your custom <strong>favicon</strong> which is visible in browser favorites and tabs.<br/> <strong>Must be .png or .ico file - 32px by 32px</strong>.', 'pagelines' ),
				'default'		=>  '[pl_parent_url]/images/default-favicon.png'
			),


			array(
				'key'			=> 'pl_login_image',
				'type' 			=> 	'image_upload',
				'label'			=> 'Upload Login Image (160px Height)',
				'imgsize' 			=> 	'80',
				'sizemode'		=> 'height',
				'title' 		=> __( 'Login Page Image', 'pagelines' ),
				'default'		=> '[pl_parent_url]/images/default-login-image.png',
				'help'			=> __( 'This image will be used on the login page to your admin. Use an image that is approximately <strong>80px</strong> in height.', 'pagelines' )
			),

			array(
				'key'			=> 'pagelines_touchicon',
				'label'			=> 'Upload Touch Image (144px by 144px)',
				'type' 			=> 	'image_upload',
				'imgsize' 			=> 	'72',
				'title' 		=> __( 'Mobile Touch Image', 'pagelines' ),
				'default'		=> '[pl_parent_url]/images/default-touch-icon.png',
				'help'			=> __( 'Enter the full URL location of your Apple Touch Icon which is visible when your users set your site as a <strong>webclip</strong> in Apple Iphone and Touch Products. It is an image approximately 144px by 144px in either .jpg, .gif or .png format.', 'pagelines' )
			),

		);

		return $settings;

	}


	function social(){



		$settings = array(
			array(
				'key'		=> 'twittername',
				'type' 		=> 'text',
				'label' 	=> __( 'Your Twitter Username', 'pagelines' ),
				'title' 	=> __( 'Twitter Integration', 'pagelines' ),
				'help' 		=> __( 'This places your Twitter feed on the site. Leave blank if you want to hide or not use.', 'pagelines' )
			),
			array(
				'key'		=> 'fb_multi',
				'type'		=> 'multi', 
				'title'		=> 'Facebook',
				'opts'		=> array(
					array(
						'key'		=> 'facebook_name',
						'type' 		=> 'text',
						'label' 	=> __( 'Your Facebook Page Name', 'pagelines' ),
						'title' 	=> __( 'Facebook Page', 'pagelines' ),
						'help' 		=> __( 'Enter the name component of your Facebook page URL. (For example, what comes after the facebook url: www.facebook.com/[name])', 'pagelines' )
					),
					array(
						'key'		=> 'facebook_app_id',
						'type' 		=> 'text',
						'label' 	=> __( 'Your Facebook App ID', 'pagelines' ),
						'title' 	=> __( 'Facebook App ID', 'pagelines' ),
						'help' 		=> __( 'Add your Facebook Application ID here.', 'pagelines' )
					),
				)
			),
			
			array(
				'key'		=> 'site-hashtag',
				'type' 		=> 'text',
				'label' 	=> __( 'Your Website Hashtag', 'pagelines' ),
				'title' 	=> __( 'Website Hashtag', 'pagelines' ),
				'help'	 	=> __( 'This hashtag will be used in social media (e.g. Twitter) and elsewhere to create feeds.', 'pagelines' )
			),

		);


		return $settings;

	}





	function advanced(){

		$settings = array(
			array(
					'key'		=> 'load_prettify_libs',
					'type'		=> 'check',
					'label'		=> __( 'Enable Code Prettify?', 'pagelines' ),
					'title'		=> __( 'Google Prettify Code', 'pagelines' ),
					'help'		=> __( "Add a class of 'prettyprint' to code or pre tags, or optionally use the [pl_codebox] shortcode. Wrap the codebox shortcode using [pl_raw] if Wordpress inserts line breaks.", 'pagelines' )
			),
			array(
					'key'		=> 'partner_link',
					'type'		=> 'text',
					'label'		=> __( 'Enter Partner Link', 'pagelines' ),
					'title'		=> __( 'PageLines Affiliate/Partner Link', 'pagelines' ),
					'help'		=> __( "If you are a <a target='_blank' href='http://www.pagelines.com'>PageLines Partner</a> enter your link here and the footer link will become a partner or affiliate link.", 'pagelines' )
			),
			array(
					'key'		=> 'special_body_class',
					'type'		=> 'text',
					'label'		=> __( 'Install Class', 'pagelines' ),
					'title'		=> __( 'Current Install Class', 'pagelines' ),
					'help'		=> __( "Use this option to add a class to the &gt;body&lt; element of the website. This can be useful when using the same child theme on several installations or sub domains and can be used to control CSS customizations.", 'pagelines' )
			),
			array(
					'key'		=> 'enable_debug',
					'type'		=> 'check',
					'label'		=> __( 'Enable debug?', 'pagelines' ),
					'title'		=> __( 'PageLines debug', 'pagelines' ),
					'help'		=> sprintf( __( 'This information can be useful in the forums if you have a problem. %s', 'pagelines' ),
								   sprintf( '%s', ( pl_setting( 'enable_debug' ) ) ?
								   sprintf( '<br /><a href="%s">Click here</a> for your debug info.', site_url( '?pldebug=1' ) ) : '' ) )								  
			),
			array(
					'key'	=> 'v2_upgrading', 
					'type'	=> 'multi',
					'title'	=> 'Framework V2 Upgrade (MUST PUBLISH)', 
					'opts'	=> array(
						array(
								'key'		=> 'v2_upgrade_help',
								'type'		=> 'help',
								'help'		=> __( 'PL has added some settings that may help you upgrade from Framework (v2). To use these, select them and then publish. <br/><strong>Note</strong> that due to the substantial amount of changes in DMS, there may still be issues. These features will be removed with DMS v3.1.', 'pagelines' ),		  
						),
						array(
								'key'		=> 'enable_v2',
								'type'		=> 'check',
								'label'		=> __( 'Enable v2 Compatibility Mode?<br/>(Refresh to see changes.)', 'pagelines' ),
								'title'		=> __( 'v2 Compatibility Mode', 'pagelines' ),
								'help'		=> __( 'Note: you must publish this setting.', 'pagelines' ),
								'ref'		=> __( 'Use this option to enable v2 interfaces and options. Not all v2 options work in DMS due to specificity; but it allows you to reference your old settings as you are rebuilding your site using the DMS system.', 'pagelines' ),				  
						), 
						array(
								'key'		=> 'v2_sections_live',
								'type'		=> 'check',
								'label'		=> __( 'Only Show v2 Sections "Live"<br/>(Compatibility Mode Req.)', 'pagelines' ),
								'title'		=> __( 'Only Show v2 Sections "Live"', 'pagelines' ),
								'help'		=> __( 'Note: you must publish this setting.', 'pagelines' ),
								'ref'		=> __( 'Enabling this options keeps all DMS sections from showing on your "live" site. This allows you to go through every page in draft mode, and duplicate your layout using DMS sections. When you are ready, disable compatibility mode and your v2 sections disappear sitewide.', 'pagelines' ),				  
						)
					)
			), 
			
		);
		return $settings;
	}

	function resets(){

		$settings = array(
			array(
					'key'		=> 'reset_global',
					'type'		=> 'action_button',
					'classes'	=> 'btn-important',
					'label'		=> __( '<i class="icon-undo"></i> Reset Global Settings', 'pagelines' ),
					'title'		=> __( 'Reset Global Site Settings', 'pagelines' ),
					'help'		=> __( "Use this button to reset all global settings to their default state. <br/><strong>Note:</strong> Once you've completed this action, you may want to publish these changes to your live site.", 'pagelines' )
			),
			array(
					'key'		=> 'reset_local',
					'type'		=> 'action_button',
					'classes'	=> 'btn-important',
					'label'		=> __( '<i class="icon-undo"></i> Reset Current Page Settings', 'pagelines' ),
					'title'		=> __( 'Reset Current Page Settings', 'pagelines' ),
					'help'		=> __( "Use this button to reset all settings on the current page back to their default state. <br/><strong>Note:</strong> Once you've completed this action, you may want to publish these changes to your live site.", 'pagelines' )
			),
			array(
					'key'		=> 'reset_cache',
					'type'		=> 'action_button',
					'classes'	=> 'btn-info',
					'label'		=> __( '<i class="icon-trash"></i> Flush Caches', 'pagelines' ),
					'title'		=> __( 'Clear all CSS/LESS cached data.', 'pagelines' ),
					'help'		=> __( "Use this button to purge the stored LESS/CSS data. This will also clear cached pages if wp-super-cache or w3-total-cache are detected.", 'pagelines' )
			),
		);
		
		
		return $settings;
	}
}

function pl_standard_section_options( ){
	$options = array();

	$options['standard'] = array(

		'key'			=> 'pl_section_styling',
		'type' 			=> 'multi',
		'label' 	=> __( 'Standard Options', 'pagelines' ),
		'opts'	=> array(
			array(

				'key'		=> 'pl_area_class',
				'type' 		=> 'text',
				'label' 	=> __( 'Styling Classes', 'pagelines' ),
				'help'		=> __( 'Separate with a space " "', 'pagelines' ),
			)
			// , array(
			// 
			// 					'key'		=> 'pl_disabled_section',
			// 					'type' 		=> 'check',
			// 					'label' 	=> __( 'Hide On Current Page?', 'pagelines' ),
			// 					'scope'		=> 'local'
			// 				)
		),
		

	);
	
	return $options;
}