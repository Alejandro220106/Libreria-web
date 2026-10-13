<?php
/**
 * Plugin Name: Libreria Author
 * Description: Adds an author field to WooCommerce books and exposes it in the REST API.
 * Version: 1.0.0
 * Requires PHP: 8.2
 * Requires Plugins: woocommerce
 */

defined( 'ABSPATH' ) || exit;

add_action( 'plugins_loaded', 'libreria_author_init' );

function libreria_author_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	// The plugin features will be loaded here in the next steps.
}
