<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_filter( 'get_terms', '_pp_flt_administrator_pad_term_counts', 10, 3 );

function _pp_flt_administrator_pad_term_counts( $terms, $taxonomies, $args ) {
	if ( ! defined('XMLRPC_REQUEST') && ( 'all' == $args['fields'] ) && empty($args['pp_no_filter']) ) {
		global $pagenow;
		if ( ! is_admin() || ! in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) ) {
			require_once( PPC_ABSPATH.'/terms-query-lib_pp.php');

			// pp_tally_term_counts() is PP equivalent to WP _pad_term_counts()
			$args['required_operation'] = ( pp_is_front() && ! is_preview() ) ? 'read' : 'edit';
			PP_TermsQueryLib::tally_term_counts( $terms, reset($taxonomies), $args );
		}
	}
	return $terms;
}
