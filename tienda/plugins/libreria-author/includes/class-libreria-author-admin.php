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
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_field' ) );
	}

	/**
	 * Prints the author input in the General tab of the product data panel.
	 */
	public function render_field() {
		$field = Libreria_Author_Field::definition();

		echo '<div class="options_group">';

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

		wp_nonce_field( 'libreria_author_save', 'libreria_author_nonce' );

		echo '</div>';
	}

	/**
	 * Saves the author when the product is saved.
	 *
	 * @param int $post_id Product ID.
	 */
	public function save_field( $post_id ) {
		$field = Libreria_Author_Field::definition();

		if ( ! isset( $_POST['libreria_author_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['libreria_author_nonce'] ) ), 'libreria_author_save' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST[ $field['meta_key'] ] ) ) {
			return;
		}

		$value = Libreria_Author_Field::sanitize( wp_unslash( $_POST[ $field['meta_key'] ] ) );
		$valid = Libreria_Author_Field::validate( $value );

		if ( is_wp_error( $valid ) ) {
			WC_Admin_Meta_Boxes::add_error( $valid->get_error_message() );
			return;
		}

		update_post_meta( $post_id, $field['meta_key'], $value );
	}
}