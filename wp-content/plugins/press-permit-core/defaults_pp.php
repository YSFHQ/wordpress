<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( is_admin() )
	require_once( dirname(__FILE__).'/admin/defaults-admin_pp.php');

/**
 * Establish array keys and default values for PP options 
 *
 * @package PP
 * @author Kevin Behrens <kevin@agapetry.net>
 * @copyright Copyright (c) 2011-2013, Agapetry Creations LLC
 * 
*/

function pp_default_options() {
	$def = array(
		'enabled_taxonomies' => array( 'category' => true, 'post_tag' => true ),
		'enabled_post_types' => array_fill_keys( array( 'post', 'page' ), true ),
		'define_create_posts_cap' => 0,
		'strip_private_caption' => 1,
		'display_user_profile_groups' => 0,
		'display_user_profile_roles' => 0,
		'new_user_groups_ui' => 1,
		'beta_updates' => false,
		'advanced_options' => 0,
		
		'support_key' => false,
		'supplemental_role_defs' => array(), // stored by Capability Manager Enhanced
		'customized_roles' => array(),	 	// stored by Capability Manager Enhanced
	);

	return apply_filters( 'pp_default_options', $def );
}
