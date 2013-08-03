<?php

if ( defined( 'MP6_COLOR_SCHEME' ) ) {
	add_action( 'wp_enqueue_scripts', 'mp6_colors_enqueue_adminbar_css' );
	add_action( 'admin_enqueue_scripts', 'mp6_colors_enqueue_adminbar_css' );
	add_action( 'admin_enqueue_scripts', 'mp6_colors_enqueue_colors_css' );
	add_action( 'login_enqueue_scripts', 'mp6_colors_enqueue_login_css' );
}

function mp6_colors_enqueue_adminbar_css() {
	if ( file_exists( plugin_dir_path(__FILE__) . 'schemes/' . MP6_COLOR_SCHEME .'/admin-bar.css' ) ) {
		wp_register_style( 'admin-bar-' . MP6_COLOR_SCHEME, plugins_url( 'schemes/' . MP6_COLOR_SCHEME . '/admin-bar.css', __FILE__ ), false, NULL );
		wp_enqueue_style( 'admin-bar-' . MP6_COLOR_SCHEME );
	}
}

function mp6_colors_enqueue_colors_css() {
	if ( file_exists( plugin_dir_path(__FILE__) . 'schemes/' . MP6_COLOR_SCHEME .'/colors-' . MP6_COLOR_SCHEME . '.css' ) ) {
		wp_register_style( 'colors-mp6-' . MP6_COLOR_SCHEME, plugins_url( 'schemes/' . MP6_COLOR_SCHEME . '/colors-' . MP6_COLOR_SCHEME . '.css', __FILE__ ), false, NULL );
		wp_enqueue_style( 'colors-mp6-' .MP6_COLOR_SCHEME );
	}
}

function mp6_colors_enqueue_login_css() {
	global $wp_styles;
	if ( file_exists( plugin_dir_path(__FILE__) . 'schemes/' . MP6_COLOR_SCHEME .'/colors-' . MP6_COLOR_SCHEME . '.css' ) ) {
		wp_register_style( 'colors-mp6-' . MP6_COLOR_SCHEME, plugins_url( 'schemes/' . MP6_COLOR_SCHEME . '/colors-' . MP6_COLOR_SCHEME . '.css', __FILE__ ), false, NULL );
		$wp_styles->do_item( 'colors-mp6-' . MP6_COLOR_SCHEME );
	}
}
