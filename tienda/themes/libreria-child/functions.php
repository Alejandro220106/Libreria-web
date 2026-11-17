<?php
/**
 * Libreria child theme setup.
 *
 * Block themes do not load style.css on their own, so it is enqueued here.
 * Colors, fonts and spacing live in theme.json; style.css only holds the
 * rules theme.json cannot express (hover effects, sticky header, grids).
 *
 * @package libreria-child
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', 'libreria_child_enqueue_styles' );
add_action( 'after_setup_theme', 'libreria_child_editor_styles' );

/**
 * Loads the same style.css inside the Site Editor, so templates look the
 * same while they are edited.
 */
function libreria_child_editor_styles() {
	add_editor_style( 'style.css' );
}

/**
 * Loads style.css on the front end. The file modification time is the
 * version, so the browser fetches the file again whenever it changes.
 */
function libreria_child_enqueue_styles() {
	$path = get_stylesheet_directory() . '/style.css';

	wp_enqueue_style(
		'libreria-child-style',
		get_stylesheet_uri(),
		array(),
		(string) filemtime( $path )
	);
}

/*
 * Hardening: XML-RPC is turned off (brief 5.4.4). The store uses the REST API,
 * so nothing needs xmlrpc.php, and it is a common target for password guessing.
 */
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', '__return_empty_array' );
add_action( 'init', 'libreria_child_block_xmlrpc', 0 );
add_filter( 'wp_headers', 'libreria_child_remove_pingback_header' );
remove_action( 'wp_head', 'rsd_link' );

/**
 * Answers 403 to any request to xmlrpc.php before it reaches the XML-RPC server.
 */
function libreria_child_block_xmlrpc() {
	if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		status_header( 403 );
		exit( 'XML-RPC is disabled on this site.' );
	}
}

/**
 * Stops advertising the XML-RPC endpoint in the response headers.
 *
 * @param array $headers Response headers.
 * @return array
 */
function libreria_child_remove_pingback_header( $headers ) {
	unset( $headers['X-Pingback'] );
	return $headers;
}
