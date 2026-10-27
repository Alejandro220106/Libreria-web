<?php
/**
 * Title: Libreria header
 * Slug: libreria-child/header
 * Description: Site header with title, category menu, product search, account and cart.
 * Inserter: no
 *
 * @package libreria-child
 */

// Top-level categories shown in the menu. A slug that does not exist is skipped.
$libreria_menu_slugs = array( 'ficcion', 'infantil', 'academicos', 'historia' );
?>
<!-- wp:group {"align":"full","className":"libreria-header","backgroundColor":"base","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull libreria-header has-base-background-color has-background">
	<!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:site-title {"level":0} /-->

		<!-- wp:navigation {"overlayMenu":"mobile","overlayBackgroundColor":"base","overlayTextColor":"contrast","layout":{"type":"flex","justifyContent":"center"},"metadata":{"ignoredHookedBlocks":["woocommerce/mini-cart","woocommerce/customer-account"]}} -->
		<?php
		foreach ( $libreria_menu_slugs as $libreria_slug ) {
			$libreria_term = get_term_by( 'slug', $libreria_slug, 'product_cat' );
			if ( ! $libreria_term ) {
				continue;
			}
			$libreria_link = get_term_link( $libreria_term );
			if ( is_wp_error( $libreria_link ) ) {
				continue;
			}
			$libreria_attrs = array(
				'label' => $libreria_term->name,
				'type'  => 'product_cat',
				'id'    => $libreria_term->term_id,
				'url'   => $libreria_link,
				'kind'  => 'taxonomy',
			);
			echo '<!-- wp:navigation-link ' . wp_json_encode( $libreria_attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ' /-->' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
		<!-- /wp:navigation -->

		<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
		<div class="wp-block-group">
			<!-- wp:search {"label":"<?php echo esc_attr_x( 'Buscar', 'search label', 'libreria-child' ); ?>","showLabel":false,"buttonPosition":"button-only","buttonUseIcon":true,"query":{"post_type":"product"}} /-->

			<!-- wp:woocommerce/customer-account {"displayStyle":"icon_only","iconStyle":"line"} /-->

			<!-- wp:woocommerce/mini-cart {"hasHiddenPrice":true} /-->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
