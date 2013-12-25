<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class PP_Updated {
	public static function populate_roles( $reload_user = false ) {
		if ( $role = @get_role( 'administrator' ) ) {
			$role->remove_cap( 'pp_bulk_assign_roles' );
			$role->add_cap( 'pp_manage_settings' );
			$role->add_cap( 'pp_administer_content' );
			$role->add_cap( 'pp_create_groups' );
			$role->add_cap( 'pp_edit_groups' );
			$role->add_cap( 'pp_delete_groups' );
			$role->add_cap( 'pp_manage_members' );
			$role->add_cap( 'pp_assign_roles' );
			$role->add_cap( 'pp_set_read_exceptions' );
		}
		
		if ( $role = @get_role( 'editor' ) ) {
			$role->remove_cap( 'pp_bulk_assign_roles' );
			$role->add_cap( 'pp_create_groups' );
			$role->add_cap( 'pp_edit_groups' );
			$role->add_cap( 'pp_delete_groups' );
			$role->add_cap( 'pp_manage_members' );
			$role->add_cap( 'pp_assign_roles' );
			$role->add_cap( 'pp_set_read_exceptions' );
			$role->add_cap( 'pp_moderate_any' );
		}

		update_option( 'ppperm_added_role_caps_10beta', true );
		
		if ( $reload_user ) {
			global $wp_roles; 
			$wp_roles = new WP_Roles();

			// force full menu display after activation
			global $current_user, $wpdb;
			if ( ! empty($current_user) ) {
				wp_cache_delete( $current_user->ID, 'user_meta' );
				$current_user = new WP_User( $current_user->ID );
				
				global $pp_current_user;
				if ( isset( $pp_current_user ) ) {
					$pp_current_user->allcaps = array_merge( $pp_current_user->allcaps, $current_user->allcaps );
				}
			}
		}
	}
		
	public static function version_updated( $prev_version ) {
		// single-pass do loop to easily skip unnecessary version checks
		do {
			if ( ! $prev_version ) {
				if ( get_option('pp_version') && ! get_option('pp_group_index_drop_done') ) {  // previous installation of PP < 2.0 ?
					update_option( 'pp_need_group_index_drop', true ); // flag groups index drop to be launched from PP Options
				}
	
				break;  // no need to run through version comparisons if no previous version
			}
			
			if ( version_compare( $prev_version, '2.1.35', '<') ) {
				require_once( dirname(__FILE__).'/update-exceptions_pp.php' );
				
				// Previously, page exceptions were propagated to all descendents, including attachments (but this is unnecessary and potentially undesirable)
				_ppc_delete_propagated_attachment_exceptions();
				_ppc_expose_attachment_exception_items();  // "include" exceptions for Read/Edit operations are exposed but not deleted, since they affect access to other media 

				// Previously, propagated exceptions were not removed when parent exception assign_for was changed to item only.  Expose them by setting inherited_from to 0
				_ppc_expose_orphaned_exception_items();
			} else break;

			if ( version_compare( $prev_version, '2.1.33', '<') ) {
				if ( $enabled_taxonomies = get_option( 'pp_enabled_taxonomies' ) ) {
					// previously, post_tag was disabled by default but implicitly enabled for front-end filtering
					$enabled_taxonomies['post_tag'] = true;
					update_option( 'pp_enabled_taxonomies', $enabled_taxonomies );
				}
			} else break;
			
			if ( version_compare( $prev_version, '2.1.16-beta', '<') ) {
				global $wpdb;
				$wpdb->query( "UPDATE $wpdb->ppc_exceptions SET for_item_source = 'post' WHERE for_item_source = 'all'" );
			} else break;
			
			if ( version_compare( $prev_version, '2.1.10-beta', '<') ) {
				// this was defaulted to true in past versions
				update_site_option( 'pp_beta_updates', false );
			} else break;
			
			if ( version_compare( $prev_version, '2.1.6-beta', '<') ) {
				// added pp caps for exception assignment from Post/Term edit screen by non-Administrators
				if ( ! get_option( 'ppperm_added_role_caps_21beta' ) )
					pp_populate_roles();
			} else break;
			
			if ( version_compare( $prev_version, '2.1.1-beta', '<') ) {
				global $wpdb;
				// ancestors are no longer buffered in this manner
				$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name = 'pp_page_ancestors'" );
				$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%_ancestors_pp'" );
			} else break;
		
			if ( version_compare( $prev_version, '2.0.29-beta', '<') ) {
				global $wpdb;
				// change from [Anonymous] to {Anonymous} for consistency with {Authenticated} and to move to bottom of groups listing
				$wpdb->update( $wpdb->pp_groups, array( 'group_name' => '{Anonymous}' ), array( 'metagroup_type' => 'wp_role', 'metagroup_id' => 'wp_anon' ) );
				$wpdb->update( $wpdb->pp_groups, array( 'group_name' => '{All}' ), array( 'metagroup_type' => 'wp_role', 'metagroup_id' => 'wp_all' ) );
			} else break;
		
			if ( version_compare( $prev_version, '2.0.16-beta', '<') ) {
				// move existing support key from options table to sitemeta (checking current site and main site)
				if ( PP_MULTISITE && ! get_site_option( 'pp_network_key_set' ) ) {
					if ( ! $key_data = get_option( 'pp_support_key' ) ) {
						global $blog_id;
						if ( $blog_id != 1 ) {
							$restore_blog_id = $blog_id;
							switch_to_blog( 1 );
						}
					
						$key_data = get_option( 'pp_support_key' );
						
						if ( ! empty($restore_blog_id) )
							switch_to_blog( $restore_blog_id );
					}
					
					if ( $key_data ) {
						update_site_option( 'pp_support_key', $key_data );
						update_site_option( 'pp_network_key_set', true );
					}
				}
			} else break;

			if ( version_compare( $prev_version, '2.0.3-beta3', '<') ) {
				global $wpdb;
				$wpdb->update( $wpdb->ppc_exceptions, array( 'for_item_status' => '' ), array( 'via_item_source' => 'post' ) );	  // fix importer glitch
				
				// delete orphaned exceptions
				if ( $orphan_ids = $wpdb->get_col( 
					"SELECT eitem_id FROM $wpdb->ppc_exception_items AS i INNER JOIN $wpdb->ppc_exceptions AS e ON e.exception_id = i.exception_id " 
					. "WHERE ( e.via_item_source = 'post' AND i.item_id NOT IN ( SELECT ID FROM $wpdb->posts ) ) OR ( e.via_item_source = 'term' AND i.item_id NOT IN ( SELECT term_taxonomy_id FROM $wpdb->term_taxonomy ) )"
				) ) {
					$wpdb->query( "DELETE FROM $wpdb->ppc_exception_items WHERE eitem_id IN ('" . implode( "','", $orphan_ids ) . "')" );
				}
				
				// force regen of buffered ancestors array (prev versions botched it when tt_id != term_id)
				$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%_ancestors_pp'" );
			} else break;
		
			if ( version_compare( $prev_version, '2.0.2-beta', '<') ) {
				global $wpdb;

				// added "Authenticated" metagroup
				self::sync_wproles();
				
				if ( ! get_option('pp_group_index_drop_done') ) {
					update_option( 'pp_need_group_index_drop', true );  // flag groups index drop to be launched from PP Options
				}
			}
		} while ( 0 ); // end single-pass version check loop
		
		/*
		if ( $prev_version && is_admin() ) {
			if ( preg_match( "/dev|alpha|beta|rc/i", PPC_VERSION ) && ! preg_match( "/dev|alpha|beta|rc/i", $prev_version ) ) {
				ppc_notice( __( 'You have installed a development / beta version of Press Permit Core. If this is a concern, see Permissions > Settings > Install > Beta Updates.', 'pp' ), 'updated' );
			}
		}
		*/
	}

	public static function drop_group_indexes_sql() {
		global $wpdb;
	
		return array( "DROP INDEX `pp_group_meta_id` ON $wpdb->pp_groups",
					  "DROP INDEX `pp_group_metaid` ON $wpdb->pp_groups",
					  "DROP INDEX `pp_statuskey` ON $wpdb->pp_group_members",
					  "DROP INDEX `pp_status_key` ON $wpdb->pp_group_members",
					  "DROP INDEX `pp_datekey` ON $wpdb->pp_group_members",
					  "DROP INDEX `pp_date_key` ON $wpdb->pp_group_members",
					);
	}
	
	public static function do_index_drop( $arr_sql, $option_key, $prevent_retry = true ) {
		global $wpdb;
		
		// in case of failure, don't do this more than once
		if ( $prevent_retry ) {
			if ( get_option( $option_key ) && ! $arr_sql )
				return;
		}

		update_option( $option_key, true );
		
		$wpdb->suppress_errors = true;
		foreach( $arr_sql as $sql )
			@$wpdb->query( $sql );

		$wpdb->suppress_errors = false;
	}
	
	public static function sync_wproles($user_ids = '', $role_name_arg = '', $blog_id_arg = '' ) {
		global $wpdb, $wp_roles;
		
		if ( empty($wp_roles->role_objects) ) { return; }
		
		if ( $user_ids )
			$user_ids = (array) $user_ids;
		
		$metagroups = $stored_metagroups = $all_role_metagroups = $stored_role_metagroups = $insert_sql_rows = $delete_clauses = array();
		
		if ( $blog_id_arg )
			$members_table = ( $blog_id_arg > 1 ) ? $wpdb->base_prefix . $blog_id_arg . '_' . 'pp_group_members' : $wpdb->base_prefix . 'pp_group_members';
		else
			$members_table = $wpdb->pp_group_members;

		// sync WP Role metagroups
		foreach ( array_keys($wp_roles->role_objects) as $role_name ) {
			$metagroup_id = trim(substr($role_name, 0, 40));
			
			// if the name is too long and its truncated ID already taken, just exclude it from eligible metagroups
			if ( ! isset( $metagroups[ $metagroup_id ] ) )
				$metagroups[$metagroup_id] = (object) array( 'type' => 'wp_role', 'name' => sprintf( '[WP %s]', $role_name ), 'descript' => sprintf( 'All users with a WordPress %s role', $role_name ) );
		}

		// add a metagroup for anonymous users
		$metagroups['wp_anon'] = (object) array( 'type' => 'wp_role', 'name' => '{Anonymous}', 'descript' => 'Anonymous users (not logged in)' );

		// add a metagroup for authenticated users
		$metagroups['wp_auth'] = (object) array( 'type' => 'wp_role', 'name' => '{Authenticated}', 'descript' => 'All users who are logged in and have a role on the site' );

		// add a metagroup for all users
		$metagroups['wp_all'] = (object) array( 'type' => 'wp_role', 'name' => '{All}', 'descript' => 'All users (including anonymous)' );
		
		// add metagroups for Revisionary notification recipients
		$metagroups['rvy_pending_rev_notice'] = (object) array( 'type' => 'rvy_notice', 'name' => '[Pending Revision Monitors]', 'descript' => 'Administrators / Publishers to notify (by default) of pending revisions' );
		$metagroups['rvy_scheduled_rev_notice'] = (object) array( 'type' => 'rvy_notice', 'name' => '[Scheduled Revision Monitors]', 'descript' => 'Administrators / Publishers to notify when any scheduled revision is published' );

		if ( $results = $wpdb->get_results( "SELECT * FROM $wpdb->pp_groups WHERE metagroup_id != ''" ) ) {
			$delete_metagroup_ids = array();

			foreach ( $results as $row ) {
				if ( ! isset( $metagroups[$row->metagroup_id] ) )
					$delete_metagroup_ids[] = $row->ID;
				else {
					$stored_metagroups[$row->metagroup_id] = true;
					
					if ( 'wp_role' == $row->metagroup_type )
						$all_role_metagroups[$row->metagroup_id] = $row->ID;
				}
			}

			if ( $delete_metagroup_ids ) {
				$id_in = implode( "','", $delete_metagroup_ids );
				$wpdb->query( "DELETE FROM $wpdb->pp_groups WHERE ID IN ('$id_in')" );
				$wpdb->query( "DELETE FROM $members_table WHERE group_id IN ('$id_in')" );
			}
		}
		
		if ( $insert_metagroups = array_diff_key( $metagroups, $stored_metagroups ) ) {
			foreach ( $insert_metagroups as $metagroup_id => $metagroup ) {
				$wpdb->insert( $wpdb->pp_groups, array( 'metagroup_id' => $metagroup_id, 'metagroup_type' => $metagroup->type, 'group_name' => $metagroup->name, 'group_description' => $metagroup->descript ) );

				if ( ( 'wp_role' == $metagroup->type ) && $wpdb->insert_id )
					$all_role_metagroups[$metagroup_id] = $wpdb->insert_id;
			}
		}

		$user_clause = ( $user_ids ) ? 'AND user_id IN (' . implode(', ', array_map( 'intval', $user_ids ) ) . ')' : ''; 
		
		// which user roles are already represented by PP metagroup membership?
		$results = $wpdb->get_results( "SELECT group_id, user_id FROM $members_table WHERE group_id IN ('" . implode( "','", $all_role_metagroups) . "') $user_clause" );

		foreach ( $results as $key => $row ) {
			$stored_role_metagroups[$row->user_id][] = $row->group_id;
		}

		// Now step through every WP usermeta capabilities record, synchronizing PP metagroup membership with WP role and custom caps
		$usermeta = $wpdb->get_results( "SELECT user_id, meta_value FROM $wpdb->usermeta WHERE meta_key = '{$wpdb->prefix}capabilities' $user_clause" );

		foreach ( array_keys($usermeta) as $key ) {
			$user_caps = maybe_unserialize($usermeta[$key]->meta_value);
			if ( empty($user_caps) || ! is_array($user_caps) )
				continue;
			
			// Filter out caps that are not role names
			$user_role_metagroups = array_intersect_key( $all_role_metagroups, array_diff( $user_caps, array('', 0, false) ) );

			$user_id = $usermeta[$key]->user_id;
			
			if ( isset( $stored_role_metagroups[$user_id] ) ) {
				if ( $delete_role_metagroups = array_diff( $stored_role_metagroups[$user_id], $user_role_metagroups ) )
					$delete_clauses []= "user_id = '$user_id' AND group_id IN ('" . implode( "','", $delete_role_metagroups ) . "')";
			}
			
			if ( isset($stored_role_metagroups[$user_id]) )
				$user_role_metagroups = array_diff( $user_role_metagroups, $stored_role_metagroups[$user_id] );

			foreach( $user_role_metagroups as $group_id )
				$insert_sql_rows []= "('$user_id', '$group_id', 'member', 'active')";
		}
		
		if ( $delete_clauses ) {
			$wpdb->query( "DELETE FROM $members_table WHERE ( " . implode( ' ) OR ( ', $delete_clauses ) . " )" );
		}
		
		if ( $insert_sql_rows ) {
			$wpdb->query( "INSERT INTO $members_table (user_id, group_id, member_type, status ) VALUES " . implode( ',', $insert_sql_rows ) );
		}
		
		update_option( 'pp_wp_role_sync', true );
	} // end sync_wproles function
} // end class