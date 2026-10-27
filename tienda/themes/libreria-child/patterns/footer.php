<?php
/**
 * Title: Libreria footer
 * Slug: libreria-child/footer
 * Description: Site footer with a headline, shop links and account links.
 * Inserter: no
 *
 * @package libreria-child
 */

$libreria_has_woo = function_exists( 'wc_get_page_permalink' );

// Shop column: the shop page plus the same top-level categories as the header menu.
$libreria_shop_links = array(
	array(
		'label' => __( 'Todos los libros', 'libreria-child' ),
		'url'   => $libreria_has_woo ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
	),
);
foreach ( array( 'ficcion', 'infantil', 'academicos', 'historia' ) as $libreria_slug ) {
	$libreria_term = get_term_by( 'slug', $libreria_slug, 'product_cat' );
	$libreria_link = $libreria_term ? get_term_link( $libreria_term ) : false;
	if ( $libreria_link && ! is_wp_error( $libreria_link ) ) {
		$libreria_shop_links[] = array(
			'label' => $libreria_term->name,
			'url'   => $libreria_link,
		);
	}
}

// Account column: only pages WooCommerce creates by default.
$libreria_account_links = array();
if ( $libreria_has_woo ) {
	$libreria_account_links = array(
		array(
			'label' => __( 'Mi cuenta', 'libreria-child' ),
			'url'   => wc_get_page_permalink( 'myaccount' ),
		),
		array(
			'label' => __( 'Carrito', 'libreria-child' ),
			'url'   => wc_get_cart_url(),
		),
		array(
			'label' => __( 'Finalizar compra', 'libreria-child' ),
			'url'   => wc_get_checkout_url(),
		),
	);
}

/**
 * Prints a list block with one link per item.
 *
 * @param array[] $items Each item has a label and a url.
 */
$libreria_print_links = static function ( $items ) {
	echo '<!-- wp:list {"className":"libreria-footer-list"} -->' . "\n";
	echo '<ul class="wp-block-list libreria-footer-list">';
	foreach ( $items as $item ) {
		echo '<!-- wp:list-item --><li><a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a></li><!-- /wp:list-item -->';
	}
	echo '</ul>' . "\n";
	echo '<!-- /wp:list -->' . "\n";
};
?>
<!-- wp:group {"align":"full","className":"libreria-footer","backgroundColor":"contrast","textColor":"base","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull libreria-footer has-base-color has-contrast-background-color has-text-color has-background">
	<!-- wp:columns {"align":"wide"} -->
	<div class="wp-block-columns alignwide">
		<!-- wp:column {"width":"50%"} -->
		<div class="wp-block-column" style="flex-basis:50%">
			<!-- wp:heading {"level":2,"fontSize":"xx-large"} -->
			<h2 class="wp-block-heading has-xx-large-font-size"><?php echo esc_html__( 'Un libro para cada lector.', 'libreria-child' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p><?php echo esc_html__( 'Novelas, clásicos, libros infantiles y académicos, a un clic de distancia.', 'libreria-child' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:heading {"level":3,"fontSize":"medium"} -->
			<h3 class="wp-block-heading has-medium-font-size"><?php echo esc_html__( 'Tienda', 'libreria-child' ); ?></h3>
			<!-- /wp:heading -->

			<?php $libreria_print_links( $libreria_shop_links ); ?>
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:heading {"level":3,"fontSize":"medium"} -->
			<h3 class="wp-block-heading has-medium-font-size"><?php echo esc_html__( 'Mi compra', 'libreria-child' ); ?></h3>
			<!-- /wp:heading -->

			<?php $libreria_print_links( $libreria_account_links ); ?>
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:paragraph {"align":"wide","fontSize":"small"} -->
	<p class="alignwide has-small-font-size">&copy; <?php echo esc_html( gmdate( 'Y' ) . ' ' . get_bloginfo( 'name' ) ); ?></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
