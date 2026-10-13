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
				'get_callback' => array( $this, 'get_author' ),
				'schema'       => array(
					'description' => 'Author of the book.',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
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
}