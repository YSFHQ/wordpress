<?php
class PP_Options_Advanced {
	var $enabled;

	function __construct() {
		$this->enabled = pp_get_option( 'advanced_options' );  // if disabled, will show only available option will be "enable"
	
		add_filter( 'pp_option_tabs', array(&$this, 'option_tabs'), 6 );
		add_filter( 'pp_section_captions', array(&$this, 'section_captions' ) );
		add_filter( 'pp_option_captions', array(&$this, 'option_captions' ) );
		add_filter( 'pp_option_sections', array(&$this, 'option_sections' ) );
		
		add_action( 'pp_advanced_options_pre_ui', array( &$this, 'options_pre_ui' ) );
		add_action( 'pp_advanced_options_ui', array( &$this, 'options_ui' ) );
	}
	
	function option_tabs( $tabs ) {
		$tabs['advanced'] = __( 'Advanced', 'pp' );
		return $tabs;
	}

	function section_captions( $sections ) {
		$new = array(			
			'enable' =>			__('Enable Advanced', 'pp'),
			'file_filtering' => __('File Filtering', 'pp'),
			'network' =>		__('Network-Wide Settings', 'pp'),		
		);
		
		if ( $this->enabled ) {
			$new = array_merge( $new, array(
				'anonymous' =>			 __('Anonymous Users', 'pp'),
				'permissions_admin' =>   __('Permissions Admin', 'pp'),
				'capabilities' =>		 __('PP Capabilities', 'pp'),
				'role_integration' =>	 __('Role Integration', 'pp'),
				'misc' =>				 __('Miscellaneous', 'pp'),
			) );
		}

		$key = 'advanced';
		$sections[$key] = ( isset($sections[$key]) ) ? array_merge( $sections[$key], $new ) : $new;
		return $sections;
	}

	function option_captions( $captions ) {
		$opt = array( 'advanced_options' => 		__('Enable advanced settings', 'pp') );
	
		if ( $this->enabled ) {
			$opt = array_merge( $opt, array(
				'anonymous_unfiltered' =>			sprintf( __('%1$sDisable%2$s all filtering for anonymous users', 'pp'), '<strong>', '</strong>' ),
				'user_search_by_role' =>			__('User Search: Filter by WP role', 'pp' ),
				'display_hints' => 					__('Display Administrative Hints', 'pp'),
				'display_extension_hints' => 		__('Display Extension Hints', 'pp'),
				'dynamic_wp_roles' =>				__('Detect Dynamically Mapped WP Roles', 'pp'),
				'non_admins_set_read_exceptions' => __('Non-Administrators can set Reading Exceptions for their editable posts', 'pp'),
			) );
		}
		
		return array_merge($captions, $opt);
	}

	function option_sections( $sections ) {
		$new = array( 'enable' =>		array( 'advanced_options' ) );
		
		if ( $this->enabled ) {
			$new = array_merge( $new, array(		
				'anonymous' =>			array( 'anonymous_unfiltered' ),
				'permissions_admin' =>  array( 'non_admins_set_read_exceptions' ),
				'role_integration' =>	array( 'dynamic_wp_roles' ),
				'misc' =>				array( 'user_search_by_role', 'display_hints', 'display_extension_hints' ),
			) );
		}
		
		$key = 'advanced';
		$sections[$key] = ( isset($sections[$key]) ) ? array_merge( $sections[$key], $new ) : $new;
		return $sections;
	}
	
	function options_pre_ui() {
		global $pp_options_ui;
		
		if ( $pp_options_ui->display_hints ) {
			echo '<div class="pp-optionhint">';
			
			if ( pp_get_option( 'advanced_options' ) ) {
				if ( defined( 'PPCE_VERSION' ) )
					_e("<strong>Note:</strong> if you disable these settings, the stored values (including Role Usage adjustments) are retained but ignored.", 'pp');
			} else {
				_e( 'Most sites don\'t need advanced settings. But enable them if you need to work with custom WP Roles or apply performance tweaks.', 'pp' );
			}
			echo '</div>';
		}
	}
	
	function options_ui() {
		global $pp_options_ui;
		$ui = $pp_options_ui;  // shorten syntax
		$tab = 'advanced';
		
		$section = 'enable';									// --- ENABLE SECTION ---
		if ( ! empty( $ui->form_options[$tab][$section] ) ) : ?>
			<tr><th scope="row"><?php echo $ui->section_captions[$tab][$section];?></th><td>
			<?php
			$hint = '';
			$ui->option_checkbox( 'advanced_options', $tab, $section, $hint );
			?>
			</td></tr>
		<?php endif; // any options accessable in this section
		

		$section = 'file_filtering';
		
		if ( $this->enabled || true || PP_MULTISITE ):?>
		<tr><th scope="row"><?php echo $ui->section_captions[$tab][$section];?></th><td>
		<?php
		if ( defined( 'PPFF_VERSION' ) ) {
			do_action( 'ppff_pp_option_hint' );
		} else {
			_e( 'To filter access to attached files, install the Press Permit File URL Filter extension.', 'pp' );
			echo '<br />';
		}
		?>
		</td></tr>
		<?php endif;
		

		if ( $this->enabled ) {
			$section = 'anonymous';									// --- ANONYMOUS USERS SECTION ---
			if ( ! empty( $ui->form_options[$tab][$section] ) ) : ?>
				<tr><th scope="row"><?php echo $ui->section_captions[$tab][$section];?></th><td>
				<?php
				$hint =  sprintf( __('Disable PP filtering for users who are not logged in. %1$sNote that this performance enhancement will make reading exceptions ineffective%2$s.', 'pp'), '<span class="pp-warning"><strong>', '</strong></span>' );
				$ui->option_checkbox( 'anonymous_unfiltered', $tab, $section, $hint );

				do_action( 'pp_options_ui_insertion', $tab, $section );
				?>
				</td></tr>
			<?php endif; // any options accessable in this section
		
			$section = 'custom_statuses';							// --- CUSTOM POST STATUSES SECTION ---
			if ( ! empty( $ui->form_options[$tab][$section] ) ) : ?>
				<tr><th scope="row"><?php echo $ui->section_captions[$tab][$section];?></th><td>
				<?php
				do_action( 'pp_options_ui_insertion', $tab, $section );
				?>
				</td></tr>
			<?php endif; // any options accessable in this section
		
		
			$section = 'permissions_admin';							// --- PERMISSIONS ADMIN SECTION ---
			if ( ! empty( $ui->form_options[$tab][$section] ) ) : ?>
				<tr><th scope="row"><?php echo $ui->section_captions[$tab][$section];?></th><td>
				<?php
				$hint =  __('If enabled, users with the pp_set_read_exceptions capability in the WP role can set reading exceptions for their editable posts.', 'pp');
				$ui->option_checkbox( 'non_admins_set_read_exceptions', $tab, $section, $hint );

				do_action( 'pp_options_ui_insertion', $tab, $section );
				?>
				</td></tr>
			<?php endif; // any options accessable in this section

			
			$section = 'capabilities';								// --- PP CAPABILITIES SECTION ---
			?>
				<tr><td scope="row" colspan="2"><span style="font-weight:bold"><?php echo $ui->section_captions[$tab][$section];?></span>
				<span style="margin-left:125px">
				<?php
				
				if ( pp_get_option( 'display_hints' ) ) :?>
					<span class="pp-subtext">
					<?php
					if ( ppc_is_plugin_active( 'capsman-enhanced' ) ) {
						$url = 'admin.php?page=capsman';
						printf( __('You can customize Press Permit administration capabilities %1$s for any WP role%2$s:', 'pp' ), '<a href="' . $url . '">', '</a>' );
					} else {
						printf( __('You can customize Press Permit administration capabilities by using a WP role editor such as %1$s:', 'pp'), '<span class="plugins update-message"><a href="' . pp_plugin_info_url('capability-manager-enhanced') . '" class="thickbox" title=" Capability Manager Enhanced">Capability&nbsp;Manager&nbsp;Enhanced</a></span>' );
					}
					?>
					</span>
				<?php endif;?>
				</span>
				
	
				<table id="pp_cap_descripts">
				<thead>
				<tr>
				<th class="cap-name"><?php _e('Capability Name', 'pp');?></th>
				<th><?php echo __ppw('Description', 'pp');?></th>
				</tr>
				</thead>
				<tbody>
				
				<?php
				$pp_caps = array( 
					'pp_manage_settings'	=> __( 'Modify these Press Permit settings', 'pp' ),
					'pp_unfiltered'			=> __( 'Press Permit does not apply any supplemental roles or exceptions to limit or expand viewing or editing access', 'pp' ),
					'pp_administer_content'	=> __( 'PP implicitly grants capabilities for all post types and statuses, but does not apply exceptions', 'pp' ),
					'pp_create_groups'		=> __( 'Can create Permission Groups', 'pp' ),
					'pp_edit_groups'		=> __( 'Can edit all Permission Groups (barring Exceptions)', 'pp' ),
					'pp_delete_groups'		=> __( 'Can delete Permission Groups', 'pp' ),
					'pp_manage_members'		=> __( 'If group editing is allowed, can also modify group membership', 'pp' ),
					'pp_assign_roles'		=> __( 'Assign supplemental Roles or Exceptions. Other capabilities may also be required.', 'pp' ),
					'pp_set_read_exceptions'=> __( 'Set Reading Exceptions for editable posts on Edit Post/Term screen (for non-Administrators lacking edit_users capability; may be disabled by PP option)', 'pp' ), 
				);
				
				if ( ! defined( 'PPCE_VERSION' ) && ! pp_key_active() ) {
					if ( class_exists('Fork') )
						$pp_caps['pp_set_fork_exceptions'] = __( '(PP Pro capability)', 'pp' );
					
					if ( defined('RVY_VERSION') )
						$pp_caps['pp_set_revise_exceptions'] = __( '(PP Pro capability)', 'pp' );
					
					$pp_caps = array_merge( $pp_caps, array( 
						'pp_set_edit_exceptions'			=> __( '(PP Pro capability)', 'pp' ),
						'pp_set_associate_exceptions'		=> __( '(PP Pro capability)', 'pp' ),
						'pp_set_term_assign_exceptions'		=> __( '(PP Pro capability)', 'pp' ),
						'pp_set_term_manage_exceptions'		=> __( '(PP Pro capability)', 'pp' ),
						'pp_set_term_associate_exceptions'	=> __( '(PP Pro capability)', 'pp' ),
						'list_others_unattached_files' 		=> __( '(PP Pro capability)', 'pp' ),
						'edit_own_attachments' 				=> __( '(PP Pro capability)', 'pp' ),
						)
					);
				}
				
				if ( ! defined( 'PPS_VERSION' ) && ! pp_key_active() ) {
					$pp_caps = array_merge( $pp_caps, array( 
						'pp_define_post_status'			=> __( '(PP Pro capability)', 'pp' ),
						'pp_define_moderation'			=> __( '(PP Pro capability)', 'pp' ),
						'pp_define_privacy'				=> __( '(PP Pro capability)', 'pp' ),
						'set_posts_status'				=> __( '(PP Pro capability)', 'pp' ),
						'pp_moderate_any'				=> __( '(PP Pro capability)', 'pp' ),
						)
					);
				}

				$pp_caps = apply_filters( 'pp_cap_descriptions', $pp_caps );
				
				foreach( $pp_caps as $cap_name => $descript ) :?>
				<tr>
				<td class="cap-name"><?php echo $cap_name;?></td>
				<td><?php echo $descript;?></td>
				</tr>
				<?php endforeach;?>
				</tbody>
				</table>

	
				</td></tr>
			<?php
			
			$section = 'misc';										// --- MISC SECTION ---
			if ( ! empty( $ui->form_options[$tab][$section] ) ) : ?>
				<tr><th scope="row"><?php echo $ui->section_captions[$tab][$section];?></th><td>
				<?php
				$hint =  __('Display a role dropdown alongside the user search input box to narrow results.', 'pp');
				$ui->option_checkbox( 'user_search_by_role', $tab, $section, $hint );
				
				$hint =  __('Display additional descriptions in role assignment and options UI.', 'pp');
				$ui->option_checkbox( 'display_hints', $tab, $section, $hint );
				
				$hint =  __('Display descriptive captions for additional functionality provided by missing or deactivated extension plugins (Press Permit Pro package).', 'pp');
				$ui->option_checkbox( 'display_extension_hints', $tab, $section, $hint );
				?>
				</td></tr>
			<?php endif; // any options accessable in this section
		
		
			$section = 'role_integration';							// --- ROLE INTEGRATION SECTION ---
			if ( ! empty( $ui->form_options[$tab][$section] ) ) : ?>
				<tr><th scope="row"><?php echo $ui->section_captions[$tab][$section];?></th><td>
				<?php
				$hint =  __('Detect user roles which are appended dynamically but not stored to the WP database. May be useful for sites that sync with Active Directory or other external user registration systems.', 'pp');
				$args = ( defined( 'PP_FORCE_DYNAMIC_ROLES' ) ) ? array( 'val' => 1, 'no_storage' => true, 'disabled' => true ) : array();
				$ui->option_checkbox( 'dynamic_wp_roles', $tab, $section, $hint, '', $args );
				?>
				</td></tr>
			<?php endif; // any options accessable in this section
			
		
		} // endif advanced options enabled
		
		if ( PP_MULTISITE ) {
			$section = 'network';
			?>
			<tr><th scope="row"><?php echo $ui->section_captions[$tab][$section];?></th><td>

			<div id="pp_modify_default_settings" style="max-width:700px">
			<?php
			_e( 'To modify one or more default settings network-wide, <strong>copy</strong> the following code into your theme&apos;s <strong>functions.php</strong> file (or some other file which is always executed and not auto-updated) and modify as desired:', 'pp' );
			?>
			<textarea rows='10' cols='150' readonly='readonly' style="margin-top:5px">
// Use this filter if you want to change the default, but still allow manual setting
add_filter( 'pp_default_options', 'my_pp_default_options', 99 );

function my_pp_default_options( $def_options ) {
	// Array key corresponds to name attributes of checkboxes, dropdowns and input boxes. Modify for desired default settings.

	$def_options['new_user_groups_ui'] = 0;
	
	return $def_options;
}
			</textarea>
			</div>
			<br />
			
			<div id="pp_force_settings" style="max-width:700px">
			<?php
			_e( 'To force the value of one or more settings network-wide, <strong>copy</strong> the following code into your theme&apos;s <strong>functions.php</strong> file (or some other file which is always executed and not auto-updated) and modify as desired:', 'pp' );
			?>
			<textarea rows='13' cols='150' readonly='readonly' style="margin-top:5px">
// Use this filter if you want to force an option, blocking/disregarding manual setting
add_filter( 'pp_options', 'my_pp_options', 99 );

// Use this filter if you also want to hide an option from the PP settings screen (works for most options)
add_filter( 'pp_hide_options', 'my_pp_options', 99 );

function my_pp_options( $options ) {
	// Array key corresponds to pp_prefixed name attributes of checkboxes, dropdowns and input boxes. Modify for desired settings.

	$options['pp_new_user_groups_ui'] = 1;
	$options['pp_display_hints'] = 0;	// note: advanced options can be forced here even if advanced settings are disabled
	
	return $options;
}
			</textarea>
			</div>
			
			</td></tr>
		<?php
		} // endif multisite
	} // end function options_ui()
}
