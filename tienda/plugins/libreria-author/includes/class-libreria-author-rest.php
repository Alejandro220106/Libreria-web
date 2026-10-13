<?php
defined( 'ABSPATH' ) || exit;

/**
 * REST API: expose and update the author.
 */
class Libreria_Author_Rest {

	/**
	 * Registers the WordPress hooks for this concern.
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_field' ) );
	}

	/**
	 * Adds the author as its own property of the product in the REST API.
	 */
	public function register_field() {
		$field = Libreria_Author_Field::definition();

		register_rest_field(
			'product',
			$field['rest_property'],
			array(
				'get_callback'    => array( $this, 'get_author' ),
				'update_callback' => array( $this, 'update_author' ),
				'schema'          => array(
					'description' => 'Author of the book.',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'validate_callback' => array( $this, 'validate_author' ),
					),
				),
			)
		);
	}

	/**
	 * Returns the author of a product for REST responses.
	 *
	 * @param array $response_data Product data prepared for the response.
	 * @return string
	 */
	public function get_author( $response_data ) {
		$field = Libreria_Author_Field::definition();

		return (string) get_post_meta( $response_data['id'], $field['meta_key'], true );
	}

	/**
	 * Validates the author sent in a request, before anything is saved.
	 *
	 * @param mixed $value Value sent by the client.
	 * @return true|WP_Error
	 */
	public function validate_author( $value ) {
		if ( ! is_string( $value ) ) {
			return new WP_Error( 'libreria_author_invalid', 'The author must be a text value.', array( 'status' => 400 ) );
		}

		return Libreria_Author_Field::validate( Libreria_Author_Field::sanitize( $value ) );
	}

	/**
	 * Saves the author sent in a POST or PUT request.
	 *
	 * @param mixed      $value   Value sent by the client.
	 * @param WC_Product $product Product being saved.
	 * @return true|WP_Error
	 */
	public function update_author( $value, $product ) {
		$field = Libreria_Author_Field::definition();
		$value = Libreria_Author_Field::sanitize( $value );
		$valid = Libreria_Author_Field::validate( $value );

		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		update_post_meta( $product->get_id(), $field['meta_key'], $value );

		return true;
	}
}