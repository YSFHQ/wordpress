<?php
add_filter( 'sseo_pp_group_items', '_pp_sseo_groups' );

function _pp_sseo_groups( $group_labels ) {
	$group_labels = array();
	
	$groups = pp_get_groups( 'pp_group', array( 'skip_meta_types' => array( 'wp_role' ) ) );
	foreach( $groups as $group )
		$group_labels[$group->ID] = $group->name;
	
	return $group_labels;
}
