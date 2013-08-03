<?php
/*
	Section: iBox
	Author: PageLines
	Author URI: http://www.pagelines.com
	Description: An easy way to create and configure several box type sections at once.
	Class Name: pliBox
	Filter: component
	Loading: active
*/


class pliBox extends PageLinesSection {

	var $default_limit = 4;

	function section_opts(){

		$options = array();

		$options[] = array(

			'title' => __( 'iBox Configuration', 'pagelines' ),
			'type'	=> 'multi',
			'opts'	=> array(
				array(
					'key'			=> 'ibox_count',
					'type' 			=> 'count_select',
					'count_start'	=> 1,
					'count_number'	=> 12,
					'default'		=> 4,
					'label' 	=> __( 'Number of iBoxes to Configure', 'pagelines' ),
				),
				array(
					'key'			=> 'ibox_cols',
					'type' 			=> 'count_select',
					'count_start'	=> 1,
					'count_number'	=> 12,
					'default'		=> '3',
					'label' 	=> __( 'Number of Columns for Each Box (12 Col Grid)', 'pagelines' ),
				),
				array(
					'key'			=> 'ibox_media',
					'type' 			=> 'select',
					'opts'		=> array(
						'icon'	 	=> array( 'name' => __( 'Icon Font', 'pagelines' ) ),
						'image'		=> array( 'name' => __( 'Images', 'pagelines' ) ),
						'text'		=> array( 'name' => __( 'Text Only, No Media', 'pagelines' ) )
					),
					'default'		=> 'icon',
					'label' 	=> __( 'Select iBox Media Type', 'pagelines' ),
				),
				array(
					'key'			=> 'ibox_format',
					'type' 			=> 'select',
					'opts'		=> array(
						'top'		=> array( 'name' => __( 'Media on Top', 'pagelines' ) ),
						'left'	 	=> array( 'name' => __( 'Media at Left', 'pagelines' ) ),
					),
					'default'		=> 'top',
					'label' 	=> __( 'Select the iBox Media Location', 'pagelines' ),
				),
			)

		);

		$slides = ($this->opt('ibox_count')) ? $this->opt('ibox_count') : $this->default_limit;
		$media = ($this->opt('ibox_media')) ? $this->opt('ibox_media') : 'icon';

		for($i = 1; $i <= $slides; $i++){

			$opts = array(

				array(
					'key'		=> 'ibox_title_'.$i,
					'label'		=> __( 'iBox Title', 'pagelines' ),
					'type'		=> 'text'
				),
				array(
					'key'		=> 'ibox_text_'.$i,
					'label'	=> __( 'iBox Text', 'pagelines' ),
					'type'	=> 'textarea'
				),
				array(
					'key'		=> 'ibox_link_'.$i,
					'label'		=> __( 'iBox Link (Optional)', 'pagelines' ),
					'type'		=> 'text'
				),
			);

			if($media == 'icon'){
				$opts[] = array(
					'key'		=> 'ibox_icon_'.$i,
					'label'		=> __( 'iBox Icon', 'pagelines' ),
					'type'		=> 'select_icon',
				);
			} elseif($media == 'image'){
				$opts[] = array(
					'key'		=> 'ibox_image_'.$i,
					'label'		=> __( 'iBox Image', 'pagelines' ),
					'type'		=> 'image_upload',
				);
			}


			$options[] = array(
				'title' 	=> __( 'iBox ', 'pagelines' ) . $i,
				'type' 		=> 'multi',
				'opts' 		=> $opts,

			);

		}

		return $options;
	}



   function section_template( ) {

		$boxes = ($this->opt('ibox_count')) ? $this->opt('ibox_count') : $this->default_limit;
		$cols = ($this->opt('ibox_cols')) ? $this->opt('ibox_cols') : 3;

		$media_type = ($this->opt('ibox_media')) ? $this->opt('ibox_media') : 'icon';
		$media_format = ($this->opt('ibox_format')) ? $this->opt('ibox_format') : 'top';

		$width = 0;
		$output = '';

		for($i = 1; $i <= $boxes; $i++):

			// TEXT
			$text = ($this->opt('ibox_text_'.$i)) ? $this->opt('ibox_text_'.$i) : 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean id lectus sem. Cras consequat lorem.';

			$text = sprintf('<div data-sync="ibox_text_%s">%s</div>', $i, $text );

			$title = ($this->opt('ibox_title_'.$i)) ? $this->opt('ibox_title_'.$i) : __('iBox '.$i, 'pagelines');
			$title = sprintf('<h4 data-sync="ibox_title_%s">%s</h4>', $i, $title );

			// LINK
			$link = $this->opt('ibox_link_'.$i);
			$media_link = ($link) ? sprintf('href="%s"', $link) : '';
			$text_link = ($link) ? sprintf('<div class="ibox-link"><a href="%s">%s <i class="icon-angle-right"></i></a></div>', $link, __('More', 'pagelines')) : '';


			$format_class = ($media_format == 'left') ? 'media left-aligned' : 'top-aligned';
			$media_class = 'media-type-'.$media_type;

			$media_bg = '';
			$media_html = '';

			if( $media_type == 'icon' ){
				$media = ($this->opt('ibox_icon_'.$i)) ? $this->opt('ibox_icon_'.$i) : false;
				if(!$media){
					$icons = pl_icon_array();
					$media = $icons[ array_rand($icons) ];
				}
				$media_html = sprintf('<i class="icon-3x icon-%s"></i>', $media);

			} elseif( $media_type == 'image' ){

				$media = ($this->opt('ibox_image_'.$i)) ? $this->opt('ibox_image_'.$i) : false;

				$media_html = '';

				$media_bg = ($media) ? sprintf('background-image: url(%s);', $media) : '';

			}



			if($width == 0)
				$output .= '<div class="row fix">';



			$output .= sprintf(
				'<div class="span%s ibox %s fix">
					<div class="ibox-media img">
						<span class="ibox-icon-border pl-animation pl-appear pl-contrast %s" style="%s" %s>
							%s
						</span>
					</div>
					<div class="ibox-text bd">
						%s
						<div class="ibox-desc">
							%s
							%s
						</div>
					</div>
				</div>',
				$cols,
				$format_class,
				$media_class,
				$media_bg,
				$media_link,
				$media_html,
				$title,
				$text,
				$text_link
			);

			$width += $cols;

			if($width >= 12 || $i == $boxes){
				$width = 0;
				$output .= '</div>';
			}


		 endfor;

		printf('<div class="ibox-wrapper pl-animation-group">%s</div>', $output);

	}


}