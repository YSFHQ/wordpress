<?php
class PP_Groups_List_Table_Base extends WP_List_Table {
	var $role_info;
	var $exception_info;
	
	// moved out of class PP_Groups_List_Table to support sharing with other PPBP_Groups_List_Table and other subclasses
	function single_row_role_column( $column_name, $group_id, $can_manage_group, $edit_link, $attributes ) {
		global $pp_admin;
		
		$r = '';
		
		switch( $column_name ) {
		case 'roles':
			$role_str = '';
			
			if ( isset( $this->role_info[$group_id] ) ) {
				if ( isset( $this->role_info[$group_id]['roles'] ) ) {
					$display_limit = 3;
					
					$role_titles = array();
					$i=0;
					foreach( array_keys($this->role_info[$group_id]['roles']) as $role_title ) {
						$i++;
						$role_titles []= $role_title;
						if ( $i >= $display_limit )
							break;
					}
					
					$role_str = '<span class="pp-group-site-roles">' . implode( ',&nbsp; ', $role_titles ) . '</span>';
					
					if ( count($this->role_info[$group_id]['roles']) > $display_limit ) {
						$role_str = sprintf( __('%s, more...', 'pp'), $role_str );
					}
					
					if ( $can_manage_group )
						$role_str = "<a href=\"$edit_link\">$role_str</a><br />";
				}
			}
			$r .= "<td $attributes>$role_str</td>";
			break;
			
		case 'exceptions' :
			$exc_str = '';
			
			if ( isset( $this->exception_info[$group_id] ) ) {
				if ( isset( $this->exception_info[$group_id]['exceptions'] ) ) {
					$display_limit = 3;
					
					$exc_titles = array();
					$i=0;
					foreach( $this->exception_info[$group_id]['exceptions'] as $exc_title => $exc_count ) {
						$i++;
						$exc_titles []= sprintf( __('%1$s (%2$s)', 'pp'), $exc_title, $exc_count ); // ppc_get_role_title($role_name);
						if ( $i >= $display_limit )
							break;
					}
					
					$exc_str = '<span class="pp-group-site-roles">' . implode( ',&nbsp; ', $exc_titles ) . '</span>';
					
					if ( count($this->exception_info[$group_id]['exceptions']) > $display_limit ) {
						$exc_str = sprintf( __('%s, more...', 'pp'), $exc_str );
					}
					
					if ( $can_manage_group )
						$exc_str = "<a href=\"$edit_link\">$exc_str</a><br />";
				}
			}
			$r .= "<td $attributes>$exc_str</td>";
			break;
		}
		
		return $r;
	}
} // end class
