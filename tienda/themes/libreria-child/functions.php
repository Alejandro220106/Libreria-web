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
