<?php


class EditorColor{

	var $default_base = '#FFFFFF';
	var $default_text = '#000000';
	var $default_link = '#225E9B';
	var $background = '';

	function __construct( ){

		$this->background = pl_setting('page_background_image_url');

 		add_filter('pl_settings_array', array(&$this, 'add_settings'));
		add_filter('pless_vars', array(&$this, 'add_less_vars'));
		
		if($this->background && $this->background != '')
			add_filter('wp_enqueue_scripts', array(&$this, 'background_fit'));
		
//		add_filter('pagelines_body_classes', array(&$this, 'add_body_classes'));
		
		
	
	}
	
	function add_body_classes($classes){
		
		$classes[] = ( pl_setting('supersize_bg') ) ? 'fit-bg' : '';
	
		return $classes;
		
	}

	function add_less_vars( $vars ){
		$bg = pl_setting('bodybg');
		$base = ( $bg && $bg != '' ) ? $bg : $this->default_base;

		$text = ( pl_setting('text_primary') ) ? pl_setting('text_primary') : $this->default_text;
		$link = ( pl_setting('linkcolor') ) ? pl_setting('linkcolor') : $this->default_link;

		$vars['pl-base'] 				= $this->hash( $base );
		$vars['pl-text']				= $this->hash( $text );
		$vars['pl-link']				= $this->hash( $link );
		$vars['pl-background']			= $this->background( $vars['pl-base'] );
		
	//	plprint($vars['pl-background']);
		
		return $vars;
	}

	function background( $bg_color ){

		
		
		$fit = pl_setting('supersize_bg');
		
		$image = ($this->background && $this->background != '') ? $this->background : false;

		if($image && $fit){
			
			$background = $bg_color;
			
		} elseif($image && !$fit){

			$repeat = pl_setting('page_background_image_repeat');
			$pos_x = pl_setting('page_background_image_pos_hor');
			$pos_y = pl_setting('page_background_image_pos_vert');
			$attach = pl_setting('page_background_image_attach');

			$repeat = ($repeat) ? $repeat : 'no-repeat';
			$pos_x = ($pos_x) ? $pos_x.'%' : '50%';
			$pos_y = ($pos_y) ? $pos_y.'%' : '0%';
			$attach = ($attach) ? $attach : 'fixed';

			$background = sprintf('%s url("%s") %s %s %s %s', $bg_color, $image, $repeat, $pos_x, $pos_y, $attach);

		} else
			$background = $bg_color;

		return $background;
	}

	function background_fit(){
		
		if( !pl_setting('supersize_bg') )
			return; 
		
		wp_enqueue_script( 'pagelines-supersize' );
		add_action('pl_scripts_on_ready', array(&$this, 'run_background_fit'), 20);
	}

	function run_background_fit(){
	
		
		$image = $this->background;
		?>
		jQuery.supersized({ slides: [{ image : '<?php echo $image; ?>' }]})
<?php
	}


	function hash( $color ){

		$clean = str_replace('#', '', $color);

		if(preg_match('/^[a-f0-9]{6}$/i', $clean)){
			// IS A COLOR
		} elseif (preg_match('/^[a-f0-9]{3}$/i', $clean)){
			$clean = $clean.$clean;
		} else {
			$clean = 'FFFFFF';
		}

		

		return sprintf('#%s', $clean);

	}


	function add_settings( $settings ){

		$settings['color_control'] = array(
			'name' 	=> 'Color <span class="spamp">&amp;</span> Style',
			'icon'	=> 'icon-tint',
			'pos'	=> 3,
			'opts' 	=> $this->options()
		);

		return $settings;
	}

	function options(){

		$settings = array(
			array(
				'key'		=> 'canvas_colors',
				'type' 		=> 'multi',
				'title' 	=> __( 'Content Base Color', 'pagelines' ),
				'help' 		=> __( 'The "base" color is used as your background and as a basis for calculating contrast values in elements (like hover effects, etc.. ) Use it as your default background color and refine using custom CSS/LESS or a theme.' ),
				'opts'		=> array(
					array(
						'key'			=> 'bodybg',
						'type'			=> 'color',
						'label' 		=> __( 'Content Base Color', 'pagelines' ),
						'default'		=> $this->default_base,
					),
				)
			),
			array(
				'key'		=> 'text_colors',
				'type' 		=> 'multi',
				'label' 	=> __( 'Site Text Colors', 'pagelines' ),
				'title' 	=> __( 'Site Text Colors', 'pagelines' ),
				'help' 		=> __( 'Configure the basic text colors for your site', 'pagelines' ),
				'opts'		=> array(
					array(
						'key'			=> 'text_primary',
						'type'			=> 'color',
						'label' 		=> __( 'Main Text Color', 'pagelines' ),
						'default'		=> $this->default_text,
						'compile'		=> true,

					),
					array(
						'key'			=> 'linkcolor',
						'type'			=> 'color',
						'label' 		=> __( 'Link Color', 'pagelines' ),
						'default'		=> $this->default_link,
						'compile'		=> true,
					)
				)
			),
			array(
				'key'		=> 'background_image_settings',
				'type' 		=> 'multi',

				'title' 	=> __( 'Background Image Settings', 'pagelines' ),
				'help' 		=> __( '', 'pagelines' ),
				'opts'		=> array(
					array(
						'key'			=> 'page_background_image_url',
						'imgsize' 		=> 	'150',
						'sizemode'		=> 'height',
						'sizelimit'		=> 1224000,
						'type'			=> 'image_upload',
						'label' 		=> __( 'Page Background Image', 'pagelines' ),
						'default'		=> '',
						'compile'		=> true,

					),
					array(
						'key'			=> 'supersize_bg',
						'type'			=> 'check',
						'label' 		=> __( 'Fit image to page?', 'pagelines' ),
						'default'		=> true,
						'compile'		=> true,
						'help'			=> 'If you use this option the image will be fit "responsively" to the background of your page. This means the settings below will have no effect.'
						),
					array(
						'key'			=> 'page_background_image_repeat',
						'type'			=> 'select',
						'label' 		=> __( 'Background Repeat', 'pagelines' ),
						'default'		=> 'no-repeat',
						'opts'	=> array(
							'no-repeat' => array('name' => 'No Repeat'),
							'repeat'	=> array('name' => 'Repeat'),
							'repeat-x'	=> array('name' => 'Repeat Horizontally'),
							'repeat-y'	=> array('name' => 'Repeat Vertically')
						),
						'compile'		=> true,

					),
					array(
						'key'			=> 'page_background_image_pos_vert',
						'type'			=> 'count_select',
						'label' 		=> __( 'Vertical Background Position in Percent', 'pagelines' ),
						'default'		=> '0',
						'count_start'	=> 0,
						'count_number'	=> 100,
						'suffix'		=> '%',
						'compile'		=> true,

					),
					array(
						'key'			=> 'page_background_image_pos_hor',
						'type'			=> 'count_select',
						'label' 		=> __( 'Horizontal Background Position in Percent', 'pagelines' ),
						'default'		=> '50',
						'count_start'	=> 0,
						'count_number'	=> 100,
						'suffix'		=> '%',
						'compile'		=> true,

					),
					array(
						'key'			=> 'page_background_image_attach',
						'type'			=> 'select',
						'label' 		=> __( 'Set Background Attachment', 'pagelines' ),
						'default'		=> 'fixed',
						'opts'	=> array(
							'scroll'	=> array('name' => __( 'Scroll', 'pagelines' )),
							'fixed'		=> array('name' => __( 'Fixed', 'pagelines' )),
						),
						'compile'		=> true,

					)
				)
			)

		);


		return $settings;

	}

}





