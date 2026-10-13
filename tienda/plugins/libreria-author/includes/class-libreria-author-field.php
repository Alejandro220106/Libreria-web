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

	/**
	 * Cleans a raw value: removes HTML tags and extra spaces.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return sanitize_text_field( (string) $value );
	}

	/**
	 * Checks the rule: required and no longer than the maximum length.
	 *
	 * @param string $value Sanitized value.
	 * @return true|WP_Error
	 */
	public static function validate( $value ) {
		$field  = self::definition();
		$length = mb_strlen( $value );

		if ( $field['required'] && 0 === $length ) {
			return new WP_Error( 'libreria_author_empty', 'The author is required.', array( 'status' => 400 ) );
		}

		if ( $length > $field['max_length'] ) {
			return new WP_Error(
				'libreria_author_too_long',
				sprintf( 'The author cannot be longer than %d characters.', $field['max_length'] ),
				array( 'status' => 400 )
			);
		}

		return true;
	}
}