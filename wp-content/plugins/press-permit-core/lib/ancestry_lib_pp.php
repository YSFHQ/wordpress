<?php
class PP_Ancestry {
	public static function get_post_path( $item_id ) {
		$title_caption = '';
		
		if ( $post = get_post( $item_id ) ) {
			if ( is_post_type_hierarchical( $post->post_type ) && $ancestors = self::get_page_ancestors($item_id) ) {
				$arr = array();
				foreach( $ancestors as $id ) {
					if ( $_ancestor = get_post( $id ) ) {
						$arr []= $_ancestor->post_title;
					}
				}
				$arr = array_reverse($arr);
				
				$arr[] = $post->post_title;
				$title_caption = implode( ' / ', $arr );
			} else {
				$title_caption = $post->post_title;
			}
		}
		
		return $title_caption;
	}
	
	public static function get_term_path( $term_id, $taxonomy ) {
		$title_caption = '';
		
		if ( $term = get_term( $term_id, $taxonomy ) ) {
			if ( isset($term->name) ) {
				if ( is_taxonomy_hierarchical( $taxonomy ) && $ancestors = self::get_term_ancestors($taxonomy, $term_id) ) {
					$arr = array();
					foreach( $ancestors as $id ) {
						if ( $_ancestor = get_term( $id, $taxonomy ) ) {
							$arr []= $_ancestor->name;
						}
					}
					$arr = array_reverse($arr);
					
					$arr[] = $term->name;
					$title_caption = implode( ' / ', $arr );
				} else {
					$title_caption = $term->name;
				}
			}
		}
		
		return $title_caption;
	}

	public static function _walk_ancestors($child_id, $ancestors, $parents) {
		if ( isset($parents[$child_id]) ) {
			if ( in_array( $parents[$child_id], $ancestors ) )  // prevent infinite recursion if a page has a descendant set as its parent page
			  return $ancestors;
	
			$ancestors []= $parents[$child_id];
			$ancestors = self::_walk_ancestors($parents[$child_id], $ancestors, $parents);
		}
		return $ancestors;
	}
	
	public static function get_page_ancestors( $object_id = 0 ) {
		static $ancestors;
		
		if ( ! isset($ancestors) || ! empty($_POST) )
			$ancestors = false;
	
		if ( is_array($ancestors) && ! $object_id )
			return $ancestors;
	
		if ( ! $ancestors ) {
			$ancestors = array();
			
			global $wpdb;

			$post_types = get_post_types( array( 'hierarchical' => true, 'public' => true ) );
			$where = "WHERE post_type IN ('" . implode( "','", $post_types ) . "') AND post_status != 'auto-draft'";

			if ( $pages = $wpdb->get_results("SELECT ID, post_parent FROM $wpdb->posts $where") ) {
				$parents = array();
				foreach ( $pages as $page )
					if ( $page->post_parent )
						$parents[$page->ID] = $page->post_parent;
		
				foreach ( $pages as $page ) {
					$ancestors[$page->ID] = self::_walk_ancestors($page->ID, array(), $parents);
					if ( empty( $ancestors[$page->ID] ) )
						unset( $ancestors[$page->ID] );
				}
			}
		}
		
		if ( $object_id ) {
			return ( isset($ancestors[$object_id]) ) ? $ancestors[$object_id] : array();
		}
		
		return $ancestors;
	}
	
	public static function get_term_ancestors($taxonomy, $term_id = 0) {
		static $ancestors;
		
		if ( ! isset($ancestors) || ! empty($_POST) )
			$ancestors = false;
	
		if ( is_array($ancestors) && ! $term_id )
			return $ancestors;
	
		if ( ! $ancestors ) {
			$ancestors = array();

			$terms = get_terms($taxonomy, array( 'pp_no_filter' => true ) );

			if ( $terms ) {
				$parents = array();
				
				foreach ( $terms as $term )
					if ( $term->parent )
						$parents[$term->term_id] = $term->parent;
		
				foreach ( $terms as $term ) {
					$_term_id = $term->term_id;
					$ancestors[$_term_id] = self::_walk_ancestors($_term_id, array(), $parents);
					if ( empty( $ancestors[$_term_id] ) )
						unset( $ancestors[$_term_id] );
				}
			}
		}
		
		if ( $term_id ) {
			return ( isset($ancestors[$term_id]) ) ? $ancestors[$term_id] : array();
		}
		
		return $ancestors;
	}
	
	public static function remap_tree( &$items, $ancestors, $args = array() ) {
		$defaults = array(
			'child_of' => 0, 			'parent' => -1,
			'orderby' => 'post_title',	'depth' => 0,
			'remap_parents' => true, 	'enforce_actual_depth' => true,
			'exclude' => '',			'remap_thru_excluded_parent' => false,
			'col_id' => 'ID',			'col_parent' => 'post_parent',
		);

		$args = wp_parse_args( $args, $defaults );
		extract($args, EXTR_SKIP);

		if ( 'ID' == $col_id ) {
			if ( ( $child_of && ! defined( 'PPC_FORCE_PAGE_REMAP' ) ) || defined( 'PPC_NO_PAGE_REMAP' ) )
				$remap_parents = false;
		} elseif ( ( $child_of && ! defined( 'PPC_FORCE_TERM_REMAP' ) ) || defined( 'PPC_NO_TERM_REMAP' ) ) {
			$remap_parents = false;
		}

		if ( $depth < 0 )
			$depth = 0;
		
		if ( $exclude )
			$exclude = wp_parse_id_list($exclude);
		
		$filtered_items_by_id = array();
		foreach ( $items as $item )
			$filtered_items_by_id[$item->$col_id] = true;

		$remapped_items = array();

		// temporary WP bug workaround
		$first_child_of_match = -1;

		// The desired "root" is included in the ancestor array if using $child_of arg, but not if child_of = 0
		$one_if_root = ( $child_of ) ? 0 : 1;

		foreach ( $items as $key => $item ) {
			$parent_id = $item->$col_parent;
			
			if ( $remap_parents ) {
				$id = $item->$col_id;
				
				if ( $parent_id && ( $child_of != $parent_id ) && isset($ancestors[$id]) ) {
					
					// Don't use any ancestors higher than $child_of
					if ( $child_of ) {
						$max_key = array_search( $child_of, $ancestors[$id] );
						if ( false !== $max_key )
							$ancestors[$id] = array_slice( $ancestors[$id], 0, $max_key + 1 );
					}
					
					// Apply depth cutoff here so Walker is not thrown off by parent remapping.
					if ( $depth && $enforce_actual_depth ) {
						if ( count($ancestors[$id]) > ( $depth - $one_if_root ) )
							unset( $items[$key]	);
					}

					if ( ! isset($filtered_items_by_id[$parent_id]) ) {
					
						// Remap to a visible ancestor, if any 
						if ( ! $depth || isset($items[$key]) ) {
							$visible_ancestor_id = 0;
	
							foreach( $ancestors[$id] as $ancestor_id ) {
								if ( isset($filtered_items_by_id[$ancestor_id]) || ($ancestor_id == $child_of) ) {
									// don't remap through a parent which was explicitly excluded
									if( $exclude && in_array( $items[$key]->$col_parent, $exclude ) && ! $remap_thru_excluded_parent )
										break;

									$visible_ancestor_id = $ancestor_id;
									break;
								}
							}
							
							if ( $visible_ancestor_id )
								$items[$key]->$col_parent = $visible_ancestor_id;

							elseif ( ! $child_of )
								$items[$key]->$col_parent = 0;
	
							// if using custom ordering, force remapped items to the bottom
							if ( ( $visible_ancestor_id == $child_of ) && ( false !== strpos( $orderby, 'order' ) ) ) {
								$remapped_items [$key]= $items[$key];
								unset( $items[$key]	);
							}
						}	
					}
				}
			} elseif ( $parent_id && ( $depth == 1 ) && ( $parent_id != $child_of ) && ! isset($filtered_items_by_id[$parent_id]) ) { // end if not skipping page parent remap
				unset($items[$key]); // Walker will not strip this item out based on wp_list_pages depth argument if its parent is missing

				continue;
			}
			
			// temporary WP bug workaround: need to keep track of parent, for reasons described below
			if (  $child_of && ! $remapped_items ) {
				if ( ( $first_child_of_match < 0 ) && ( $child_of == $items[$key]->$col_parent ) )
					$first_child_of_match = $key;
			}
		}
		
		// temporary WP bug workaround
		if ( $child_of && ( $parent < 0 ) && ( $first_child_of_match > 0 ) ) {
			if ( $first_item = reset($items) ) {
				if ( $child_of != $first_item->$col_parent ) {
					// As of WP 2.8.4, Walker class will botch this array because it assumes that the first element in the page array is a child of the display root
					// To work around, we must move first element with the desired child_of up to the top of the array
					$_items = array( $items[$first_child_of_match] );
					
					unset( $items[$first_child_of_match] );
					$items = array_merge( $_items, $items );
				}
			}
		}

		if ( $remapped_items )
			$items = array_merge($items, $remapped_items);

	} // end function remap_tree

}
