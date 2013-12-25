<?php
class PP_AdminRoles {
	// Regulates the ability to set exceptions for a specific post or term.  Note: exceptions bulk editor (Edit Permissions screen) requires edit_users capability.
	// * PP options determine whether non-Admins are checked for pp_set_*_exceptions cap or simply blocked
	// * pp_set_*_exceptions cap must be present in user's WP role.  It is *not* granted through Pattern Role assignment.
	// * If a non-Admin user has cap, they are still required to have edit_published and edit_others (if applicable) for the post type
	public static function can_set_exceptions( $operation, $for_item_type, $args ) {
		$defaults = array( 'item_id' => 0, 'via_item_source' => 'post', 'via_item_type' => '', 'for_item_source' => 'post' );
		extract( array_merge( $defaults, $args ), EXTR_SKIP );
		
		if ( ! $is_administrator = pp_is_user_administrator() ) {
			$enabled = ( 'read' == $operation ) ? pp_get_option( 'non_admins_set_read_exceptions' ) : pp_get_option( 'non_admins_set_edit_exceptions' );
			
			if ( ! $enabled )
				return false;
		}

		if ( in_array( $via_item_source, array( 'post', 'term' ) ) && ( 'read' == $operation ) )
			$can = $is_administrator || current_user_can( 'pp_set_read_exceptions' );
		else
			$can = false;
	
		// also filter for Administrators to account for non-applicable operations
		return apply_filters( 'pp_can_set_exceptions', $can, $operation, $for_item_type, array_merge( $args, compact( 'is_administrator' ) ) );
	}

	public static function user_can_admin_role( $role_name, $item_type, $user = '' ) {
		if ( pp_is_user_administrator() )
			return true;

		if ( ! current_user_can( 'pp_assign_roles' ) )
			return false;

		$can_do = false;

		if ( $type_obj = get_post_type_object($item_type) ) {
			if ( ! empty( $type_obj->cap->edit_published_posts ) )
				$can_do = current_user_can( $type_obj->cap->edit_published_posts );
		} elseif ( $tx_obj = get_taxonomy($item_type) ) {
			if ( ! empty( $tx_obj->cap->manage_categories ) )
				$can_do = current_user_can( $tx_obj->cap->manage_categories );
		}

		return apply_filters( 'pp_user_can_admin_role', $can_do, $role_name, $item_type, $user );
	}
	
	public static function get_role_title( $role_name, $args = array() ) {
		global $wp_roles, $pp_role_defs, $pp_cap_caster;

		$defaults = array( 'plural' => false, 'slug_fallback' => true, 'include_warnings' => false );
		$args = array_merge( $defaults, $args );
		extract( $args, EXTR_SKIP );
		
		pp_init_cap_caster();
		
		if ( strpos( $role_name, ':' ) ) {
			$arr_name = explode( ':', $role_name );
			if ( ! empty($arr_name[2]) ) {
				$caption_prop = ( $plural ) ? 'name' : 'singular_name';
				$warning = '';
				
				if ( isset($pp_role_defs->pattern_roles[ $arr_name[0] ]) ) {
					$role_caption = $pp_role_defs->pattern_roles[ $arr_name[0] ]->labels->$caption_prop;
					
					if ( $include_warnings && isset( $wp_roles->role_names[ $arr_name[0] ] ) && ! $pp_cap_caster->is_valid_pattern_role( $arr_name[0] ) ) {
						$warning = '<span class="pp-red"> ' . sprintf( __( '(using default capabilities due to invalid %s definition)', 'pp' ), $wp_roles->role_names[ $arr_name[0] ] ) . '</span>';
					}
				} elseif ( $slug_fallback )
					$role_caption = $arr_name[0];
				else
					return '';

				$type_caption = '';
				if ( $type_obj = pp_get_type_object($arr_name[1], $arr_name[2]) )
					$type_caption = $type_obj->labels->singular_name;
				else
					return ( $slug_fallback ) ? $role_name : '';

				$cond_caption = '';
				
				if ( isset( $arr_name[4] ) ) {
					$cond_caption = apply_filters( 'pp_condition_caption', ucwords( str_replace( '_', ' ', $arr_name[4] ) ), $arr_name[3], $arr_name[4] );
				}

				if ( $cond_caption )
					return trim( sprintf( __('%1$s&nbsp;%2$s&nbsp;<span class="pp_nolink">-&nbsp;%3$s</span>%4$s', 'pp'), $type_caption, str_replace( ' ', '&nbsp;', $role_caption ), str_replace( ' ', '&nbsp;', $cond_caption ), $warning ) );
				else
					return trim( sprintf( __('%1$s&nbsp;%2$s&nbsp;%3$s', 'pp'), $type_caption, $role_caption, $warning ) );
			}
		} elseif ( isset( $wp_roles->role_names[$role_name] ) )
			return $wp_roles->role_names[$role_name];
		else {
			return apply_filters( 'pp_role_title', $role_name, $args );
		}
	}
} // end class
