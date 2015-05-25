<?php
/*
 * Plugin Name: SF Archiver
 * Plugin URI: http://www.screenfeed.fr/archi/
 * Description: A simple way to manage archive pages for your Custom Post Types.
 * Version: 2.0
 * Author: Grégory Viguier
 * Author URI: http://www.screenfeed.fr/greg/
 * License: GPLv3
 * License URI: http://www.screenfeed.fr/gpl-v3.txt
 * Text Domain: sf-archiver
 * Domain Path: /languages/
*/

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

// !Check WordPress Version.
if ( version_compare( $GLOBALS['wp_version'], '3.5-alpha', '<' ) ) {
	return;
}

/*-----------------------------------------------------------------------------------*/
/* !CONSTANTS ====================================================================== */
/*-----------------------------------------------------------------------------------*/

define( 'SFAR_VERSION', '2.0' );
define( 'SFAR_FILE',    __FILE__ );


/*-----------------------------------------------------------------------------------*/
/* !INCLUDES ======================================================================= */
/*-----------------------------------------------------------------------------------*/

add_action( 'plugins_loaded', 'sfar_includes' );

function sfar_includes() {

	$plugin_dir = plugin_dir_path( SFAR_FILE );

	if ( doing_ajax() ) {

		include( $plugin_dir . 'inc/admin-and-ajax.php' );
		include( $plugin_dir . 'inc/ajax.php' );

	}
	elseif ( is_admin() ) {

		include( $plugin_dir . 'inc/admin-and-ajax.php' );
		include( $plugin_dir . 'inc/admin.php' );

	}
	elseif ( is_frontend() ) {

		include( $plugin_dir . 'inc/frontend.php' );

	}
}


/*-----------------------------------------------------------------------------------*/
/* !SETTINGS ======================================================================= */
/*-----------------------------------------------------------------------------------*/

// !Register settings.

add_action( 'plugins_loaded', 'sfar_register_settings', 9 );

function sfar_register_settings() {
	sf_register_setting( 'reading', 'sf_archiver', 'sfar_sanitize_settings' );
}


// !Sanitize settings.

function sfar_sanitize_settings( $value, $option_name = null, $context = null ) {
	$out     = array();
	$context = ( $context === 'get' ) ? 'get' : 'db';	// $context === 'db' means we're setting new values.

	if ( $context === 'db' ) {
		add_filter( 'sf_archiver_clear_options_cache', '__return_true' );
	}

	if ( ! is_array( $value ) || empty( $value ) ) {
		return $out;
	}

	foreach ( $value as $post_type => $atts ) {
		if ( is_array( $atts ) ) {
			$out[ $post_type ] = array(
				'posts_per_archive_page' => ! empty( $atts['posts_per_archive_page'] ) && (int) $atts['posts_per_archive_page'] > 0 ? (int) $atts['posts_per_archive_page'] : 0,
			);
		}
	}

	return apply_filters( 'sf_archiver_settings', $out, $value, $context );
}


// !Get settings.

function sfar_get_settings() {
	static $settings;

	if ( apply_filters( 'sf_archiver_clear_options_cache', false ) ) {
		$settings = null;
		remove_all_filters( 'sf_archiver_clear_options_cache' );
	}

	if ( ! isset( $settings ) ) {
		$settings = get_option( 'sf_archiver', array() );
		$settings = sfar_sanitize_settings( $settings, 'sf_archiver', 'get' );
	}

	return $settings;
}


/*-----------------------------------------------------------------------------------*/
/* !TOOLS ========================================================================== */
/*-----------------------------------------------------------------------------------*/

if ( ! function_exists( 'doing_ajax' ) ) :
function doing_ajax() {
	return defined( 'DOING_AJAX' ) && DOING_AJAX && is_admin();
}
endif;


if ( ! function_exists( 'is_frontend' ) ) :
function is_frontend() {
	return ! defined( 'XMLRPC_REQUEST' ) && ! defined( 'DOING_CRON' ) && ! is_admin();
}
endif;


// !register_setting() is not always defined...

if ( !function_exists( 'sf_register_setting' ) ) :
function sf_register_setting( $option_group, $option_name, $sanitize_callback = '' ) {
	global $new_whitelist_options;

	if ( function_exists( 'register_setting' ) ) {
		register_setting( $option_group, $option_name, $sanitize_callback );
		return;
	}

	$new_whitelist_options = isset( $new_whitelist_options ) && is_array( $new_whitelist_options ) ? $new_whitelist_options : array();
	$new_whitelist_options[ $option_group ] = isset( $new_whitelist_options[ $option_group ] ) && is_array( $new_whitelist_options[ $option_group ] ) ? $new_whitelist_options[ $option_group ] : array();
	$new_whitelist_options[ $option_group ][] = $option_name;

	if ( $sanitize_callback != '' ) {
		add_filter( "sanitize_option_{$option_name}", $sanitize_callback );
	}
}
endif;

/**/