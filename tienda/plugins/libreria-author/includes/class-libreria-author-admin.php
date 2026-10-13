<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin screen: edit the author on the product screen.
 */
class Libreria_Author_Admin {

	/**
	 * Registers the WordPress hooks for this concern.
	 */
	public function register() {
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'render_field' ) );
	}

	/**
	 * Prints the author input in the General tab of the product data panel.
	 */
	public function render_field() {
		$field = Libreria_Author_Field::definition();

		woocommerce_wp_text_input(
			array(
				'id'                => $field['meta_key'],
				'label'             => $field['label'],
				'desc_tip'          => true,
				'description'       => 'Name of the book author.',
				'custom_attributes' => array(
					'maxlength' => $field['max_length'],
				),
			)
		);
	}
}