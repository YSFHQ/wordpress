<?php // avoid bombing out if the actual debug file is not loaded
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! function_exists('d_echo') ) {
function d_echo($str) {
	return;
}
}

if ( ! function_exists('pp_errlog') ) {
	function pp_errlog($message, $line_break = true) {
		return;
	}
}

if ( ! function_exists('ppc_errlog') ) {
	function ppc_errlog($message, $line_break = true) {
		return;
	}
}

if ( ! function_exists('agp_bt_die') ) {
function agp_bt_die() {
	return;
}
}

if ( ! function_exists('_pp_memory_new_usage') ) {
function _pp_memory_new_usage () {
	return;
}
}

if ( ! function_exists('pp_log_mem_usage') ) {
function pp_log_mem_usage( $label, $display_total = true ) {
	return;
}
}

if ( ! function_exists('dump') ) {
function dump(&$var, $info = FALSE, $display_objects = true) { 
	return; 
}
}
