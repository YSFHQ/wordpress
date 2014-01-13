<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

pp_load_admin_api();

/**
 * PP_AdminFilters class
 * 
 * @package PP
 * @author Kevin Behrens <kevin@agapetry.net>
 * @copyright Copyright (c) 2011-2013, Agapetry Creations LLC
 * 
 */
class PP_AdminFilters
{
	var $last_post_status = array();
	
	function __construct() {
		global $pagenow;

		// =============== Post Maintenance =================
		add_action( 'save_post', array( &$this, 'act_save_post' ), 10, 2 );
		add_action( 'delete_post', array( &$this, 'act_delete_post' ) );
		
		add_action( 'edit_attachment', array( &$this, 'act_edit_attachment' ) );

		// log post status transition to recognize new posts and status change to/from private
		add_action( 'transition_post_status', array( &$this, 'act_log_post_status' ), 10, 3 );

		// =============== Term Maintenance =================
		add_action( 'created_term', array( &$this, 'act_save_term' ), 10, 3 );
		add_action( 'edited_term', array( &$this, 'act_save_term' ), 10, 3 );
		add_action( 'delete_term', array( &$this, 'act_delete_term' ), 10, 3 );
		
		// @todo: make this optional
		// include private posts in the post count for each term
		global $wp_taxonomies;
		foreach ( $wp_taxonomies as $key => $t ) {
			if ( isset($t->update_count_callback) && ( 'update_post_term_count' == $t->update_count_callback ) )
				$wp_taxonomies[$key]->update_count_callback = '_pp_update_post_term_count';
		}

		// =============== User Maintenance =================
		add_action( 'profile_update', array( &$this, 'act_sync_wproles' ) );
		add_action( 'profile_update',  array( &$this, 'act_update_user_groups' ) );
		add_action( 'set_user_role', array( &$this, 'act_schedule_role_sync' ) );

		if ( PP_MULTISITE ) {
			add_action( 'remove_user_from_blog', array( &$this, 'act_delete_users' ), 10, 2 );
			add_action( 'add_user_to_blog', array( &$this, 'act_schedule_user_sync' ), 10, 3 );	// as of WP 3.0, add_user_to_blog is fired too early for us
			add_action( 'deleted_user', array( &$this, 'act_delete_users_from_network' ) );
		} else {
			add_action( 'user_register', array( &$this, 'act_schedule_user_sync' ), 10, 3 );
			add_action( 'user_register', array( &$this, 'act_set_new_user_groups' ) );
			add_action( 'delete_user', array( &$this, 'act_delete_users' ) );
		}

		add_action( 'pp_deleted_group', array( &$this, 'act_deleted_group' ), 10, 2 );
		
		// Follow up on role creation / deletion by Capability Manager or other equivalent plugin
		// Capability Manager doesn't actually modify the stored role def until after the option update we're hooking on, so defer our maintenance operation
		global $wpdb;
		add_action( "update_option_{$wpdb->prefix}user_roles", array( &$this, 'act_schedule_role_sync' ) );

		// =============== Plugin Maintenance =================
		if ( 'update-core.php' == $pagenow ) {
			global $pp_filters_update_core;
			require_once( dirname(__FILE__).'/filters-update-core_pp.php' );
			$pp_filters_update_core = new PP_FiltersUpdateCore();
		}

		add_filter( 'pre_set_site_transient_update_plugins', array( &$this, 'flt_insert_pp_update_info' ) );
		
		global $wp_roles;	// fires on role creation / deletion
		if ( ! empty($wp_roles) )
			add_filter( "update_option_{$wp_roles->role_key}", array( &$this, 'flt_update_wp_roles' ) );
	} // end function __contruct
	
	function flt_update_wp_roles( $roles ) {
		global $wp_roles;
		wp_cache_delete( $wp_roles->role_key, 'options' );
		$wp_roles = new WP_Roles();
		
		$this->act_sync_wproles();
		return $roles;
	}

	// @todo: use new plugin api
	function flt_insert_pp_update_info( $plugin_updates ) {
		global $pp_extensions;
		
		static $busy;
		if ( defined( 'UPDATED_PP_PLUGIN' ) || ! empty($busy) ) { return $plugin_updates; }
		
		$busy = true;
		$pp_updates = pp_get_version_info();
		$busy = false;

		foreach( $pp_extensions as $ext ) {
			if ( ! empty( $pp_updates->response[$ext->basename]->package ) ) {
				$plugin_updates->response[$ext->basename] = (object) array( 'slug' => $ext->slug );	// Add this PP extension to the WP plugin update notification count
			}
		}
		
		return $plugin_updates;
	}

	function act_log_post_status( $new_status, $old_status, $post ) {
		$this->last_post_status[$post->ID] = $old_status;
	}

	function act_edit_attachment( $post_id ) {
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
	
		require_once( dirname(__FILE__).'/post-save_pp.php');
		PP_PostSave::act_save_item( 'post', $post_id, false );
	}
	
	function act_save_post( $post_id, $post ) {
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
			if ( ! pp_wp_ver( '3.8' ) || ( 'revision' == $post->post_type ) || ( 'draft' != $post->post_status ) )
				return;
		}
	
		require_once( dirname(__FILE__).'/post-save_pp.php');
		PP_PostSave::act_save_item( 'post', $post_id, $post );
	}

	function act_delete_post( $post_id ) {
		require_once( dirname(__FILE__).'/filters-admin-deletion_pp.php' );
		return _pp_mnt_delete_post( $post_id );
	}
	
	function act_save_term( $term_id, $tt_id, $taxonomy ) {
		require_once( dirname(__FILE__).'/term-save_pp.php');
		return PP_TermSave::act_save_item( $term_id, $tt_id, $taxonomy );
	}

	function act_delete_term( $term_id, $tt_id, $taxonomy ) {
		require_once( dirname(__FILE__).'/filters-admin-deletion_pp.php' );
		return _pp_mnt_delete_term( $term_id, $tt_id, $taxonomy );
	}
	
	function act_set_new_user_groups($user_id) {
		$this->do_group_update($user_id);
	}

	function act_update_user_groups($user_id) {
		if ( empty( $_POST['pp_editing_user_groups'] ) ) // otherwise we'd delete group assignments if another plugin calls do_action('profile_update') unexpectedly
			return;
			
		$this->do_group_update($user_id);
	}

	function do_group_update($user_id) {
		global $wpdb;
		$metagroup_ids = $wpdb->get_col( "SELECT ID FROM $wpdb->pp_groups WHERE metagroup_type = 'wp_role'" );
		
		require_once( dirname(__FILE__).'/user-groups-save_pp.php' );
		PP_UserGroupsSave::add_user_groups( $user_id, $metagroup_ids );
		PP_UserGroupsSave::remove_user_groups( $user_id, $metagroup_ids );
	}

	function act_delete_users( $user_ids, $blog_id_arg = 0 ) {
		require_once( dirname(__FILE__).'/groups-update_pp.php' );

		foreach ( (array) $user_ids as $user_id ) {
			PP_GroupsUpdate::delete_user_from_groups($user_id);
		}
		
		ppc_delete_agent_permissions( $user_ids, 'user' );
	}
	
	function act_delete_users_from_network( $user_ids ) {
		$user_ids = (array) $user_ids;  // as of WP 3.6, passed value is not an array, but allow for forward compat
		foreach( $user_ids as $id ) { 
			foreach ( get_blogs_of_user( $id ) as $blog ) {
				switch_to_blog( $blog->userblog_id );
				ppc_delete_agent_permissions( $id, 'user' );
				restore_current_blog();
			}
		}
	}
	
	function act_deleted_group( $group_id, $agent_type ) {
		if ( 'pp_group' == $agent_type )
			ppc_delete_agent_permissions( $group_id, $agent_type );
	}

	function act_schedule_user_sync( $user_id, $role_name = '', $blog_id = '' ) {
		$func = create_function( '', "PP_AdminFilters::act_sync_wproles('" . $user_id . "');" );
		add_action( 'shutdown', $func );
	}

	function act_sync_wproles( $user_ids = '', $role_name = '', $blog_id_arg = '' ) {
		require_once( dirname(__FILE__).'/update_pp.php');
		PP_Updated::sync_wproles( $user_ids, $role_name, $blog_id_arg );
	}

	function act_schedule_role_sync() {
		// Capability Manager doesn't actually create the role until after the option update we're hooking on, so defer our maintenance operation
		if ( ! has_action( 'shutdown', array( &$this, 'sync_all_wproles' ) ) )
			add_action( 'shutdown', array( &$this, 'sync_all_wproles' ) );
	}

	// simplifies attaching this function to hook which pass irrelevant argument
	function sync_all_wproles() {
		$this->act_sync_wproles();
	}

	// modifies WP core _update_post_term_count to include private posts in the count, since PP roles can grant access to them
	function update_post_term_count( $terms ) {
		global $wpdb;

		foreach ( (array) $terms as $term ) {
			$stati_csv = "'" . implode( "','", get_post_stati( array( 'public' => true, 'private' => true ), 'names', 'or' ) ) . "'";
			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts WHERE $wpdb->posts.ID = $wpdb->term_relationships.object_id AND post_status IN ($stati_csv) AND term_taxonomy_id = %d", $term ) );
			$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );
		}
	}
} // end class
