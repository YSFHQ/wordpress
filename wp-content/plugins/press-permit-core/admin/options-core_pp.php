<?php
class PP_Options_Core {
	
	function __construct() {
		add_filter( 'pp_option_tabs', array(&$this, 'option_tabs'), 2 );
		add_filter( 'pp_section_captions', array(&$this, 'section_captions' ) );
		add_filter( 'pp_option_captions', array(&$this, 'option_captions' ) );
		add_filter( 'pp_option_sections', array(&$this, 'option_sections' ) );
	
		add_action( 'pp_core_options_pre_ui', array( &$this, 'options_pre_ui' ) );

		add_action( 'pp_core_options_ui', array( &$this, 'options_ui' ) );
	}
	
	function option_tabs( $tabs ) {
		$tabs['core'] = __( 'Core', 'pp' );
		return $tabs;
	}

	function section_captions( $sections ) {
		$new = array(
			'taxonomies' =>		__('Filtered Taxonomies', 'pp'),
			'post_types' => 	__('Filtered Post Types', 'pp'),
			'front_end' 	=> 	__('Front End', 'pp'),
			'user_profile' => 	__('User Management / Profile', 'pp'),
			'db_maint' =>		__('Database Maintenance', 'pp'),
		);
		
		$key = 'core';
		$sections[$key] = ( isset($sections[$key]) ) ? array_merge( $sections[$key], $new ) : $new;
		
		return $sections;
	}

	function option_captions( $captions ) {
		$opt =  array(
			'enabled_taxonomies' => 		 	__('Filtered Taxonomies', 'pp' ),
			'enabled_post_types' => 		 	__('Filtered Post Types', 'pp' ),
			'define_create_posts_cap' => 		__('Use create_posts capability', 'pp' ),
			'strip_private_caption' => 		 	__('Suppress "Private:" Caption', 'pp'),
			'display_user_profile_groups' => 	__('Permission Groups on User Profile', 'pp'),
			'display_user_profile_roles' =>  	__('Supplemental Roles on User Profile', 'pp'),
			'new_user_groups_ui' =>				__('Select Permission Groups at User creation', 'pp'),
			'do_group_index_drop' =>			__('Drop old PP database indexes for better performance', 'pp'),
		);
		
		return array_merge($captions, $opt);
	}

	function option_sections( $sections ) {
		$new = array(
			'taxonomies' 	=> 	array( 'enabled_taxonomies' ),
			'post_types' 	=> 	array( 'enabled_post_types', 'define_create_posts_cap' ),
			'front_end' 	=> 	array( 'strip_private_caption' ),
			'user_profile' =>	array( 'new_user_groups_ui', 'display_user_profile_groups', 'display_user_profile_roles' ),
			'db_maint' 		 => array( 'do_group_index_drop' ),
		);
		
		$key = 'core';
		$sections[$key] = ( isset($sections[$key]) ) ? array_merge( $sections[$key], $new ) : $new;
		return $sections;
	}
	
	function options_pre_ui() {
		global $pp_options_ui;

		if ( $pp_options_ui->get_option('display_hints') ) {
			echo '<div class="pp-optionhint">';
			_e("Basic settings for content filtering, management and presentation.", 'pp');
			do_action( 'pp_options_form_hint' );
			echo '</div>';
		}
	}
	
	function options_ui() {
		global $pp_default_options, $pp_options_ui;
		$ui = $pp_options_ui;
		$tab = 'core';
		
									// --- FILTERED TAXONOMIES / POST TYPES SECTION ---
		foreach ( array( 'object' => 'post_types', 'term' => 'taxonomies' ) as $scope => $section ) {
			if ( empty( $ui->form_options[$tab][$section] ) )
				continue;
			?>
			<tr><th scope="row"><?php echo $ui->section_captions[$tab][$section];?></th><td>
			<?php
			if ( 'term' == $scope ) {
				$option_name = 'enabled_taxonomies';
				_e('Modify permissions for these Taxonomies:', 'pp');
				echo '<br />';
				$types = get_taxonomies( array( 'public' => true ), 'object' );
				
				if ( $omit_types = apply_filters( 'pp_unfiltered_taxonomies', array( 'post_status', 'topic-tag' ) ) )	// avoid confusion with Edit Flow administrative taxonomy
					$types = array_diff_key( $types, array_fill_keys( (array) $omit_types, true ) );
					
				$hidden_types = apply_filters( 'pp_hidden_taxonomies', array() );
				$types = _pp_order_types( $types );
			} else {
				$option_name = 'enabled_post_types';		
				_e('Modify permissions for these Post Types:', 'pp');
				$types = get_post_types( array( 'public' => true ), 'object' );
				
				if ( $omit_types = apply_filters( 'pp_unfiltered_post_types', array() ) )
					$types = array_diff_key( $types, array_fill_keys( (array) $omit_types, true ) );

				$hidden_types = apply_filters( 'pp_hidden_post_types', array() );
				$types = _pp_order_types( $types );
			}
			
			$ui->all_otype_options []= $option_name;

			if ( isset($pp_default_options[$option_name]) ) {
				if ( ! $enabled = $ui->get_option( $option_name ) )
					$enabled = array();

				foreach ( $types as $key => $obj ) {
					if ( ! $key )
						continue;
					
					$id = $option_name . '-' . $key;
					$name = $option_name . "[$key]";
					?>
					
					<?php if ( 'nav_menu' == $key ) :?>
						<input name="<?php echo($name);?>" type="hidden" id="<?php echo($id);?>" value="1" />
					<?php else: ?>
						<?php if ( isset( $hidden_types[$key] ) ) :?>
							<input name="<?php echo($name);?>" type="hidden" value="<?php echo $hidden_types[$key];?>" />
						<?php else:?>
							<div class="agp-vtight_input">
							<input name="<?php echo($name);?>" type="hidden" value="0" />
							<label for="<?php echo($id);?>" title="<?php echo($key);?>">
							<input name="<?php echo($name);?>" type="checkbox" id="<?php echo($id);?>" value="1" <?php checked('1', ! empty($enabled[$key]) );?> />
							
							<?php 
							if ( isset( $obj->labels_pp ) )
								echo $obj->labels_pp->name;
							elseif ( isset( $obj->labels->name ) )
								echo $obj->labels->name;
							else
								echo $key;

							echo ('</label></div>');
						endif;
					endif;  // displaying checkbox UI
					
				} // end foreach src_otype
			} // endif default option isset

			if ( 'object' == $scope ) {
				if ( pp_get_option( 'display_hints' ) ) {
					if ( $types = get_post_types( array( 'public' => true, '_builtin' => false ) ) ) :?>
						<div class="pp-subtext">
						<?php
						printf( __('<span class="pp-important">Note</span>: Type-specific capability requirements (i.e. edit_things instead of edit_posts) will be imposed. If PP filters Media or a custom type, non-Administrators <span class="pp-important">will need a corresponding %1$ssupplemental role%2$s for editing</span>. Adding the type-specific capabilities directly to a WP role definition also works.'), "<a href='" . admin_url('?page=pp-groups') . "'>", '</a>');
						?>
						</div>
						
						<?php if ( in_array( 'forum', $types ) && ! defined( 'PPP_VERSION' ) && pp_get_option( 'display_extension_hints' ) ) :?>
							<div class="pp-subtext" style="margin-top:10px">
							<?php
							if ( pp_key_active() )
								_e( 'To customize bbPress forum permissions, activate PP Compatibility Pack.', 'pp' );
							else
								_e( 'To customize bbPress forum permissions, activate your Press Permit Pro support key.', 'pp' );
							?>
							</div>
						<?php endif;?>
					<?php endif;
				}
				
				if ( pp_wp_ver( '3.5' ) ) {
					echo '<br /><div>';
					$hint = __('If enabled, the create_posts, create_pages, etc. capabilities will be enforced for all Filtered Post Types.  <strong>NOTE: You will also need to use a WordPress Role Editor</strong> such as Capability Manager Enhanced to add the create_posts capability to desired roles.', 'pp' );
					$ret = $ui->option_checkbox( 'define_create_posts_cap', $tab, $section, $hint, '' );
					echo '</div>';
				}
			}
			?>
			</td></tr>
			<?php
		} // end foreach scope
		
		$section = 'front_end';									// --- FRONT END SECTION ---
		if ( ! empty( $ui->form_options[$tab][$section] ) ) :?>
			<tr><th scope="row"><?php echo $ui->section_captions[$tab][$section];?></th><td>
			<?php
			$hint = __('Remove the "Private:" and "Protected" prefix from Post, Page titles', 'pp');
			$ui->option_checkbox( 'strip_private_caption', $tab, $section, $hint );
			?>
			</td></tr>
		<?php endif; // any options accessable in this section
	

		$section = 'user_profile';								// --- USER PROFILE SECTION ---
		if ( ! empty( $ui->form_options[$tab][$section] ) ) : ?>
			<tr><th scope="row"><?php echo $ui->section_captions[$tab][$section];?></th><td>
			<?php
			$hint = '';

			if ( ! defined( 'PP_MULTISITE' ) )
				$ui->option_checkbox( 'new_user_groups_ui', $tab, $section, $hint, '<br />' );
			
			$hint = __('note: Groups and Roles are always displayed in "Edit User"', 'pp');
			$ui->option_checkbox( 'display_user_profile_groups', $tab, $section );
			$ui->option_checkbox( 'display_user_profile_roles', $tab, $section, $hint );
			?>
				
			</td></tr>
		<?php endif; // any options accessable in this section
		
		
		$section = 'db_maint';
		if ( get_option('pp_need_group_index_drop') ) :?>
			<tr><th scope="row"><?php echo $ui->section_captions[$tab][$section];?></th><td>
			<?php
			$hint = '';
			$ui->option_checkbox( 'do_group_index_drop', $tab, $section, $hint, '<br />', array( 'no_storage' => true ) );
			?>

			</td></tr>
		<?php endif;
		
	} // end function options_ui()
}

