<?php

class PP_Group_Query {

	/**
	 * List of found group ids
	 *
	 * @access private
	 * @var array
	 */
	var $results;

	/**
	 * Total number of found groups for the current query
	 *
	 * @access private
	 * @var int
	 */
	var $total_groups = 0;

	var $agent_type = 'pp_group';
	
	// SQL clauses
	var $query_fields;
	var $query_from;
	var $query_join = '';
	var $query_where;
	var $query_orderby;
	var $query_limit;

	/**
	 *
	 * @param string|array $args The query variables
	 * @return WP_Group_Query
	 */
	function __construct( $query = null ) {
		if ( !empty( $query ) ) {
			global $blog_id;
			
			$this->query_vars = wp_parse_args( $query, array(
				'blog_id' => $blog_id,
				'include' => array(),
				'exclude' => array(),
				'search' => '',
				'orderby' => 'login',
				'order' => 'ASC',
				'offset' => '', 'number' => '',
				'count_total' => true,
				'fields' => 'all',
				'agent_type' => '',
			) );

			if ( ! empty( $query['agent_type'] ) )
				$this->agent_type = $query['agent_type'];
			
			$this->prepare_query();
			$this->query();
		}
	}

	/**
	 * Prepare the query variables
	 *
	 * @access private
	 */
	function prepare_query() {
		global $wpdb;

		$qv = &$this->query_vars;

		$groups_table = apply_filters( 'pp_use_groups_table', $wpdb->pp_groups, $this->agent_type );
		
		if ( is_array( $qv['fields'] ) ) {
			$qv['fields'] = array_unique( $qv['fields'] );

			$this->query_fields = array();
			foreach ( $qv['fields'] as $field )
				$this->query_fields[] = $groups_table . '.' . esc_sql( $field );
			$this->query_fields = implode( ',', $this->query_fields );
		} elseif ( 'all' == $qv['fields'] ) {
			$this->query_fields = "$groups_table.*";
		} else {
			$this->query_fields = "$groups_table.ID";
		}

		$this->query_from = "FROM $groups_table";
		$this->query_where = "WHERE 1=1";

		$group_variant = ( isset($_REQUEST['group_variant']) ) ? pp_sanitize_key($_REQUEST['group_variant']) : '';
		$group_variant = apply_filters( 'pp_query_group_variant', $group_variant );
		
		$require_meta_types = array();
		if ( 'wp_role' == $group_variant ) {
			$require_meta_types []= 'wp_role';
		}
		if ( $require_meta_types ) {
			$this->query_where .= " AND $groups_table.metagroup_type IN ('" . implode( "','", $require_meta_types ) . "')";
		}
		
		$skip_meta_types = array();
		if ( $group_variant && ( 'wp_role' != $group_variant ) ) {
			$skip_meta_types []= 'wp_role';
		} else {
			$pp_only_roles = (array) pp_get_option('supplemental_role_defs');
			
			if ( defined('CAPSMAN_ENH_VERSION') && version_compare( CAPSMAN_ENH_VERSION, '1.4.10', '<' ) ) { // version 1.4.9 and earlier stored redundant elements
				$_pp_only_roles = (array) $pp_only_roles;
				$pp_only_roles = array_unique( $pp_only_roles );
				if ( count($pp_only_roles) != count($_pp_only_roles) ) {
					pp_update_option( 'supplemental_role_defs', $pp_only_roles );
				}
			}
			
			if ( pp_get_option( 'anonymous_unfiltered' ) )
				$pp_only_roles = array_merge( $pp_only_roles, array( 'wp_anon', 'wp_all' ) );
			
			$pp_only_roles = implode( "','", $pp_only_roles );
			
			$this->query_where .= " AND ( ( $groups_table.metagroup_type != 'wp_role' ) OR ( $groups_table.metagroup_id NOT IN ( '$pp_only_roles' ) ) )";
		}

		if ( $skip_meta_types ) {
			$this->query_where .= " AND $groups_table.metagroup_type NOT IN ('" . implode( "','", $skip_meta_types ) . "')";
		}
		
		global $wp_roles;
		$admin_roles = array();
		
		if ( isset($wp_roles->role_objects) ) {
			foreach ( array_keys($wp_roles->role_objects) as $wp_role_name ) {
				if ( ! empty($wp_roles->role_objects[$wp_role_name]->capabilities['pp_administer_content']) || ! empty($wp_roles->role_objects[$wp_role_name]->capabilities['pp_unfiltered']) ) {
					$admin_roles[$wp_role_name] = true;
				}
			}
		}
		
		if ( $admin_roles )
			$this->query_where .= " AND $groups_table.metagroup_id NOT IN ('" . implode( "','", array_keys($admin_roles) ) . "')";
		
		$skip_meta_ids = array();
		if ( ! defined( 'RVY_VERSION' ) || defined('SCOPER_DEFAULT_MONITOR_GROUPS') || defined('PP_DEFAULT_MONITOR_GROUPS') ) {
			$skip_meta_ids = array_merge( $skip_meta_ids, array( 'rv_pending_rev_notice_ed_nr_', 'rv_scheduled_rev_notice_ed_nr_' ) );
		}
		if ( $skip_meta_ids ) {
			$this->query_where .= " AND $groups_table.metagroup_id NOT IN ('" . implode( "','", $skip_meta_ids ) . "')";
		}
		
		//$this->query_where .= "AND $groups_table.metagroup_id != 'wp_anon'";
		
		// sorting
		if ( 'ID' == $qv['orderby'] || 'id' == $qv['orderby'] ) {
			$orderby = 'ID';
		} else {
			$orderby = 'group_name';
		}

		$qv['order'] = strtoupper( $qv['order'] );
		if ( 'ASC' == $qv['order'] )
			$order = 'ASC';
		else
			$order = 'DESC';
		$this->query_orderby = "ORDER BY $orderby $order";

		// limit
		if ( $qv['number'] ) {
			if ( $qv['offset'] )
				$this->query_limit = $wpdb->prepare("LIMIT %d, %d", $qv['offset'], $qv['number']);
			else
				$this->query_limit = $wpdb->prepare("LIMIT %d", $qv['number']);
		}
		
		$search = trim( $qv['search'] );
		if ( $search ) {
			$leading_wild = ( ltrim($search, '*') != $search );
			$trailing_wild = ( rtrim($search, '*') != $search );
			if ( $leading_wild && $trailing_wild )
				$wild = 'both';
			elseif ( $leading_wild )
				$wild = 'leading';
			elseif ( $trailing_wild )
				$wild = 'trailing';
			else
				$wild = false;
			if ( $wild )
				$search = trim($search, '*');

			if ( is_numeric($search) )
				$search_columns = array('ID');
			else
				$search_columns = array('group_name');

			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}

		// if user cannot edit all groups, filter displayed groups based on group-specific role assignments
		if ( ! pp_is_user_administrator() ) {
			$reqd_caps = apply_filters( 'pp_edit_groups_reqd_caps', 'pp_manage_members', 'edit-group' );
			
			if ( ! current_user_can($reqd_caps) ) {
				global $wpdb, $pp_current_user;

				$exc_agent_type = ( in_array( $this->agent_type, array( 'pp_group', 'pp_net_group' ) ) ) ? 'pp_group' : $this->agent_type;
				
				$group_ids = ( isset( $pp_current_user->except['manage_' . $exc_agent_type][$exc_agent_type]['']['additional'][$exc_agent_type][''] ) ) ? $pp_current_user->except['manage_' . $exc_agent_type][$exc_agent_type]['']['additional'][$exc_agent_type][''] : array();
				$this->query_where .= " AND $groups_table.ID IN ('" . implode( "','", $group_ids ) . "')";
			}
		}
		
		$blog_id = absint( $qv['blog_id'] );

		if ( !empty( $qv['include'] ) ) {
			$ids = implode( ',', wp_parse_id_list( $qv['include'] ) );
			$this->query_where .= " AND $groups_table.ID IN ($ids)";
		} elseif ( !empty($qv['exclude']) ) {
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND $groups_table.ID NOT IN ($ids)";
		}

		do_action_ref_array( 'pp_pre_group_query', array( &$this ) );
	}

	/**
	 * Execute the query, with the current variables
	 *
	 * @access private
	 */
	function query() {
		global $wpdb;

		if ( is_array( $this->query_vars['fields'] ) || 'all' == $this->query_vars['fields'] ) {
			$this->results = $wpdb->get_results("SELECT $this->query_fields $this->query_from $this->query_join $this->query_where $this->query_orderby $this->query_limit");
		} else {
			$this->results = $wpdb->get_col("SELECT $this->query_fields $this->query_from $this->query_join $this->query_where $this->query_orderby $this->query_limit");
		}

		if ( $this->query_vars['count_total'] )
			$this->total_groups = $wpdb->get_var("SELECT COUNT(*) $this->query_from $this->query_join $this->query_where");

		if ( !$this->results )
			return;
	}

	/*
	 * Used internally to generate an SQL string for searching across multiple columns
	 *
	 * @access protected
	 *
	 * @param string $string
	 * @param array $cols
	 * @param bool $wild Whether to allow wildcard searches. Default is false for Network Admin, true for
	 *  single site. Single site allows leading and trailing wildcards, Network Admin only trailing.
	 * @return string
	 */
	function get_search_sql( $string, $cols, $wild = false ) {
		$string = esc_sql( $string );

		$searches = array();
		$leading_wild = ( 'leading' == $wild || 'both' == $wild ) ? '%' : '';
		$trailing_wild = ( 'trailing' == $wild || 'both' == $wild ) ? '%' : '';
		foreach ( $cols as $col ) {
			if ( 'ID' == $col )
				$searches[] = "$col = '$string'";
			else
				$searches[] = "$col LIKE '$leading_wild" . like_escape($string) . "$trailing_wild'";
		}

		return ' AND (' . implode(' OR ', $searches) . ')';
	}

	/**
	 * Return the list of groups
	 *
	 * @access public
	 *
	 * @return array
	 */
	function get_results() {
		return $this->results;
	}

	/**
	 * Return the total number of groups for the current query
	 *
	 * @access public
	 *
	 * @return array
	 */
	function get_total() {
		return $this->total_groups;
	}
}

