<?php
/**
 * Plugin Name: Libreria Author
 * Description: Adds an author field to WooCommerce books and exposes it in the REST API.
 * Version: 1.0.0
 * Requires PHP: 8.2
 * Requires Plugins: woocommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'LIBRERIA_AUTHOR_PATH', plugin_dir_path( __FILE__ ) );

spl_autoload_register(
	function ( $class_name ) {
		if ( ! str_starts_with( $class_name, 'Libreria_Author_' ) ) {
			return;
		}

		$file = LIBRERIA_AUTHOR_PATH . 'includes/class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

add_action( 'plugins_loaded', 'libreria_author_init' );

function libreria_author_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	// The plugin features will be loaded here in the next steps.
}
