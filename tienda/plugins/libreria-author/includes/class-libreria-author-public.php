<?php
defined( 'ABSPATH' ) || exit;

/**
 * Public output: show the author on the product page.
 */
class Libreria_Author_Public {

	/**
	 * Registers the WordPress hooks for this concern.
	 */
	public function register() {
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_author' ), 6 );
	}

	/**
	 * Prints the author on the single product page.
	 */
	public function render_author() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$field  = Libreria_Author_Field::definition();
		$author = get_post_meta( $product->get_id(), $field['meta_key'], true );

		if ( '' === $author ) {
			return;
		}

		printf(
			'<p class="libreria-author"><strong>%1$s:</strong> %2$s</p>',
			esc_html( $field['label'] ),
			esc_html( $author )
		);
	}
}