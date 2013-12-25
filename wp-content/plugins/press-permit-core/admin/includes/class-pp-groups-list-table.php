<?php
require_once( dirname(__FILE__).'/class-pp-groups-list-table-base.php' );
require_once( dirname(__FILE__).'/group-query_pp.php' );

do_action( 'pp_groups_list_table_load' );

class PP_Groups_List_Table extends PP_Groups_List_Table_Base {
	var $site_id;
	var $role_info;
	var $listed_ids = array();
	var $agent_type;
	
	function __construct() {
		global $_wp_column_headers;
		
		$screen = get_current_screen();

		// clear out empty entry from initial admin_header.php execution
		if ( isset( $_wp_column_headers[ $screen->id ] ) )
			unset( $_wp_column_headers[ $screen->id ] );
	
		parent::__construct( array(
			'singular' => 'group',
			'plural'   => 'groups'
		) );
		
		if ( empty( $_REQUEST['agent_type'] ) ) {
			if ( ! $this->agent_type = apply_filters( 'pp_query_group_type', '' ) )
				$this->agent_type = 'pp_group';
		} else 
			$this->agent_type = pp_sanitize_key($_REQUEST['agent_type']);
	}
	
	function ajax_user_can() {
		return current_user_can( 'pp_edit_groups' ) || _pp_any_group_manager();
	}

	function prepare_items() {
		global $groupsearch;
		
		$groupsearch = isset( $_REQUEST['s'] ) ? sanitize_text_field($_REQUEST['s']) : '';

		$groups_per_page = $this->get_items_per_page( 'groups_per_page' );

		$paged = $this->get_pagenum();

		$args = array(
			'number' => $groups_per_page,
			'offset' => ( $paged-1 ) * $groups_per_page,
			'search' => $groupsearch,
		);

		$args['search'] = '*' . $args['search'] . '*';

		if ( isset( $_REQUEST['orderby'] ) )
			$args['orderby'] = pp_sanitize_word($_REQUEST['orderby']);

		if ( isset( $_REQUEST['order'] ) )
			$args['order'] = pp_sanitize_word($_REQUEST['order']);

		// Query the user IDs for this page
		$args['agent_type'] = $this->agent_type;
		$pp_group_search = new PP_Group_Query( $args );

		$this->items = $pp_group_search->get_results();
		$this->listed_ids = array();
		
		foreach( $this->items AS $group ) {
			$this->listed_ids []= $group->ID;
		}
		
		$this->role_info = ppc_count_assigned_roles( $this->agent_type, array( 'query_agent_ids' => $this->listed_ids ) );
		$this->exception_info = ppc_count_assigned_exceptions( $this->agent_type, array( 'query_agent_ids' => $this->listed_ids ) );
		
		$this->set_pagination_args( array(
			'total_items' => $pp_group_search->get_total(),
			'per_page' => $groups_per_page,
		) );
	}

	function no_items() {
		_e( 'No matching groups were found.', 'pp' );
	}
	
	function get_views() {
		return array();
	}

	function get_bulk_actions() {
		$actions = array();

		if ( current_user_can( 'pp_delete_groups' ) && ( empty($_REQUEST['group_variant']) || 'wp_role' != $_REQUEST['group_variant'] ) )
			$actions['delete'] = __( 'Delete' );

		return $actions;
	}

	function get_columns() {
		$bulk_check_all = ( empty($_REQUEST['group_variant']) || 'wp_role' != $_REQUEST['group_variant'] ) ? '<input type="checkbox" />' : '';
		
		$c = array(
			'cb'       => $bulk_check_all,
			'ID' => __ppw( 'ID' ),
			'group_name'  => __ppw( 'Name' ),
			'num_users' => _x( 'Users', 'count', 'pp' ),
			'roles' => _x( 'Roles', 'count', 'pp' ),
			'exceptions' => _x( 'Exceptions', 'count', 'pp' ),
			'description' => __ppw( 'Description', 'pp' ),
		);

		$c = apply_filters( 'pp_manage_pp_groups_columns', $c );

		return $c;
	}

	function get_sortable_columns() {
		$c = array(
			'ID' => 'ID',
			'group_name' => 'group_name',
		);

		return $c;
	}

	function display_rows() {
		$style = '';
		
		foreach ( $this->items as $group_object ) {
			$style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
			echo "\n\t", $this->single_row( $group_object, $style );
		}
	}

	/**
	 * Generate HTML for a single row on the PP Role Groups admin panel.
	 *
	 * @param object $user_object
	 * @param string $style Optional. Attributes added to the TR element.  Must be sanitized.
	 * @param int $num_users Optional. User count to display for this group.
	 * @return string
	 */
	function single_row( $group_object, $style = '' ) {
		//$group_object = sanitize_user_object( $group_object, 'display' );
		global $pp_admin;
		
		static $base_url;
		static $members_cap;
		static $is_administrator;
		if ( ! isset($base_url) ) {
			$base_url = apply_filters( 'pp_groups_base_url', 'admin.php' );	  // @todo: filter based on menu usage

			$is_administrator = pp_is_user_administrator();
		}
		
		/*
		if ( ! $is_administrator ) {
			$members_cap = apply_filters( 'pp_edit_groups_reqd_caps', array('pp_manage_members'), 'edit-members' );
		}
		*/
		
		$group_id = $group_object->ID;
		
		if ( $group_object->metagroup_id ) {
			if ( ( 'rvy_notice' == $group_object->metagroup_type ) && ! defined( 'RVY_VERSION' ) )
				return;
		
			require_once( PPC_ABSPATH . '/groups-retrieval_pp.php' );
			$group_object->group_name = PP_GroupRetrieval::get_metagroup_name( $group_object->metagroup_type, $group_object->metagroup_id, $group_object->group_name );
			$group_object->group_description = PP_GroupRetrieval::get_metagroup_descript( $group_object->metagroup_type, $group_object->metagroup_id, $group_object->group_description );
		}

		$group_object->group_name = stripslashes($group_object->group_name);
		$group_object->group_description = stripslashes($group_object->group_description);
		
		// Set up the hover actions for this user
		$actions = array();
		$checkbox = '';
		
		$can_manage_group = $is_administrator || pp_has_group_cap( 'pp_edit_groups', $group_id, $this->agent_type );
		$agent_type_clause = ( ( $this->agent_type ) && ( 'pp_group' != $this->agent_type ) ) ? "&amp;agent_type=$this->agent_type" : '';
		
		// Check if the group for this row is editable
		if ( $can_manage_group ) {
			$edit_link = $base_url . "?page=pp-edit-permissions&amp;action=edit{$agent_type_clause}&amp;agent_id={$group_id}";
			$edit = "<strong><a href=\"$edit_link\">$group_object->group_name</a></strong><br />";
			$actions['edit'] = '<a href="' . $edit_link . '">' . __ppw( 'Edit' ) . '</a>';
		} else {
			$edit_link = '';
			$edit = '<strong>' . $group_object->group_name . '</strong>';
		}
		
		$can_delete_group = $is_administrator || current_user_can( 'pp_delete_groups', $group_id );
		
		if ( $can_delete_group && ! $group_object->metagroup_id ) {
			$actions['delete'] = "<a class='submitdelete' href='" . wp_nonce_url( $base_url . "?page=pp-groups&amp;pp_action=delete{$agent_type_clause}&amp;group=$group_id", 'bulk-groups' ) . "'>" . __( 'Delete' ) . "</a>";
		}

		$actions = apply_filters( 'pp_group_row_actions', $actions, $group_object );
		$edit .= $this->row_actions( $actions );

		// Set up the checkbox ( because the group or group members are editable, otherwise it's empty )
		if ( $actions && ! $group_object->metagroup_id )
			$checkbox = "<input type='checkbox' name='groups[]' id='group_{$group_id}' value='{$group_id}' />";
		else
			$checkbox = '';

		//$avatar = get_avatar( $user_object->ID, 32 );

		$r = "<tr id='group-$group_id'$style>";

		list( $columns, $hidden ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			$class = "class=\"$column_name column-$column_name\"";

			$style = '';
			if ( in_array( $column_name, $hidden ) )
				$style = ' style="display:none;"';

			$attributes = "$class$style";

			switch ( $column_name ) {
				case 'cb':
					$r .= "<th scope='row' class='check-column'>$checkbox</th>";
					break;
				case 'ID':
					$r .= "<td $attributes>$group_id</td>";
					break;
				case 'group_name':
					$r .= "<td $attributes>$edit</td>";
					break;
				case 'num_users':
					if ( 'wp_role' == $group_object->metagroup_type )
						$num_users = pp_count_role_users( $group_object->metagroup_id );
					else
						$num_users = pp_get_group_members( $group_id, $this->agent_type, 'count' );

					$attributes = 'class="posts column-num_users num"' . $style;
					$r .= "<td $attributes>";

					/*
					if ( $members_link )
						$r .= "<a href='$members_link'>$num_users</a>";
					else
					*/
					
					if ( 'wp_role' == $group_object->metagroup_type ) {
						if ( in_array( $group_object->metagroup_id, array( 'wp_anon', 'wp_all', 'wp_auth' ) ) ) {
							$r .= '';
						} else {
							$user_url = admin_url("users.php?role={$group_object->metagroup_id}");
							$r .= "<a href='$user_url'>$num_users</a>";
						}
					} else
						$r .= $num_users;
						
					$r .= "</td>";
					break;
				case 'roles':
				case 'exceptions':
					$r .= $this->single_row_role_column( $column_name, $group_id, $can_manage_group, $edit_link, $attributes );
					break;
				case 'description':
					$r .= "<td $attributes>$group_object->group_description</td>";
					break;
				default:
					$r .= "<td $attributes>";
					$r .= apply_filters( 'pp_manage_pp_groups_custom_column', '', $column_name, $group_id );
					$r .= "</td>";
			}
		}
		$r .= '</tr>';

		return $r;
	}
}

function pp_count_role_users( $role_name ) {
	static $role_count = array();
	
	if ( ! $role_count ) {
		$_user_count = count_users();
		foreach ( $_user_count['avail_roles'] as $role_name => $count )
			$role_count[$role_name] = $count;
	}

	return ( isset( $role_count[$role_name] ) ) ? $role_count[$role_name] : 0;
}

