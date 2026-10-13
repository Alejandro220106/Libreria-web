<?php
defined( 'ABSPATH' ) || exit;

/**
 * Describes the custom book field once. The admin screen, the public page
 * and the REST API all read this definition.
 */
class Libreria_Author_Field {

	/**
	 * Returns the field definition.
	 *
	 * @return array
	 */
	public static function definition() {
		return array(
			'meta_key'      => '_lib_author',
			'rest_property' => 'book_author',
			'label'         => __( 'Author', 'libreria-author' ),
			'type'          => 'text',
			'required'      => true,
			'max_length'    => 100,
		);
	}
}