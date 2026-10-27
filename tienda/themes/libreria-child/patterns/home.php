<?php
/**
 * Title: Libreria home
 * Slug: libreria-child/home
 * Description: Home page sections: hero, category tiles, new arrivals, featured band and best sellers.
 * Inserter: no
 *
 * @package libreria-child
 */

defined( 'ABSPATH' ) || exit;

$libreria_shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );

// Category tiles: slug, colors (palette slugs) and a short line of our own copy.
$libreria_tiles = array(
	array(
		'slug' => 'ficcion',
		'bg'   => 'teal',
		'fg'   => 'base',
		'text' => __( 'Novelas y cuentos para perderse un rato.', 'libreria-child' ),
	),
	array(
		'slug' => 'infantil',
		'bg'   => 'accent-1',
		'fg'   => 'contrast',
		'text' => __( 'Historias para los lectores más jóvenes.', 'libreria-child' ),
	),
	array(
		'slug' => 'academicos',
		'bg'   => 'navy',
		'fg'   => 'base',
		'text' => __( 'Para estudiar, entender y aprender.', 'libreria-child' ),
	),
	array(
		'slug' => 'autores-costarricenses',
		'bg'   => 'accent-3',
		'fg'   => 'base',
		'text' => __( 'Voces de casa, para leer en familia.', 'libreria-child' ),
	),
);

// The home rows only show books that belong to a real category, so uncategorized
// drafts and test products never appear on the home page.
$libreria_category_ids = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
		'fields'     => 'ids',
	)
);
$libreria_tax_query    = ( ! is_wp_error( $libreria_category_ids ) && $libreria_category_ids )
	? array( 'product_cat' => array_map( 'intval', $libreria_category_ids ) )
	: array();

/**
 * Prints a product collection block that shows book cards without a buy button.
 *
 * @param int    $query_id Unique id of the collection on the page.
 * @param string $order_by Query order field (date, popularity, title...).
 * @param int    $per_page Number of books.
 * @param int    $columns  Columns on large screens.
 * @param string $class    Extra CSS classes for the wrapper.
 * @param bool   $titles   Whether to show title and price under the cover.
 * @param int    $offset   Books to skip, so two rows never repeat the same books.
 * @param string $order    Sort direction (desc or asc).
 * @param array  $tax      Optional tax query that replaces the default one.
 */
$libreria_collection = static function ( $query_id, $order_by, $per_page, $columns, $class, $titles = true, $offset = 0, $order = 'desc', $tax = null ) use ( $libreria_tax_query ) {
	$attrs = array(
		'queryId'              => $query_id,
		'query'                => array(
			'perPage'                      => $per_page,
			'pages'                        => 0,
			'offset'                       => $offset,
			'postType'                     => 'product',
			'order'                        => $order,
			'orderBy'                      => $order_by,
			'search'                       => '',
			'exclude'                      => array(),
			'inherit'                      => false,
			'taxQuery'                     => null !== $tax ? $tax : $libreria_tax_query,
			'isProductCollectionBlock'     => true,
			'woocommerceOnSale'            => false,
			'woocommerceStockStatus'       => array( 'instock', 'outofstock', 'onbackorder' ),
			'woocommerceAttributes'        => array(),
			'woocommerceHandPickedProducts' => array(),
		),
		'tagName'              => 'div',
		'dimensions'           => array(
			'widthType'  => 'fill',
			'fixedWidth' => '',
		),
		'displayLayout'        => array(
			'type'    => 'flex',
			'columns' => $columns,
		),
		'queryContextIncludes' => array( 'collection' ),
		'align'                => 'wide',
		'className'            => $class,
	);

	// serialize_block_attributes() escapes the characters that could break out of a block comment.
	echo '<!-- wp:woocommerce/product-collection ' . serialize_block_attributes( $attrs ) . ' -->' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<div class="wp-block-woocommerce-product-collection alignwide ' . esc_attr( $class ) . '">' . "\n";
	echo '<!-- wp:woocommerce/product-template -->' . "\n";
	echo '<!-- wp:woocommerce/product-image {"showSaleBadge":false,"imageSizing":"single","aspectRatio":"2/3","isDescendentOfQueryLoop":true} /-->' . "\n";
	if ( $titles ) {
		echo '<!-- wp:post-title {"textAlign":"center","level":3,"isLink":true,"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->' . "\n";
		echo '<!-- wp:woocommerce/product-price {"textAlign":"center","isDescendentOfQueryLoop":true,"fontSize":"small"} /-->' . "\n";
	}
	echo '<!-- /wp:woocommerce/product-template -->' . "\n";
	echo '</div>' . "\n";
	echo '<!-- /wp:woocommerce/product-collection -->' . "\n";
};
?>
<!-- wp:group {"align":"full","className":"libreria-hero","backgroundColor":"accent-5","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull libreria-hero has-accent-5-background-color has-background">
	<!-- wp:columns {"verticalAlignment":"center","align":"wide"} -->
	<div class="wp-block-columns alignwide are-vertically-aligned-center">
		<!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%">
			<!-- wp:paragraph {"className":"libreria-eyebrow"} -->
			<p class="libreria-eyebrow"><?php echo esc_html__( 'Librería en línea', 'libreria-child' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":1,"fontSize":"xx-large"} -->
			<h1 class="wp-block-heading has-xx-large-font-size"><?php echo esc_html__( 'Encuentre su próxima lectura', 'libreria-child' ); ?></h1>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p><?php echo esc_html__( 'Novelas, clásicos, libros infantiles y académicos, reunidos en un solo lugar.', 'libreria-child' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button -->
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $libreria_shop_url ); ?>"><?php echo esc_html__( 'Ver todos los libros', 'libreria-child' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%">
			<?php $libreria_collection( 11, 'date', 3, 3, 'libreria-hero-covers libreria-grid', false ); ?>
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","className":"libreria-tiles","layout":{"type":"grid","columnCount":4}} -->
<div class="wp-block-group alignwide libreria-tiles">
	<?php
	foreach ( $libreria_tiles as $libreria_tile ) :
		$libreria_term = get_term_by( 'slug', $libreria_tile['slug'], 'product_cat' );
		$libreria_link = $libreria_term ? get_term_link( $libreria_term ) : false;
		if ( ! $libreria_link || is_wp_error( $libreria_link ) ) {
			continue;
		}
		?>
	<!-- wp:group {"className":"libreria-tile","backgroundColor":"<?php echo esc_attr( $libreria_tile['bg'] ); ?>","textColor":"<?php echo esc_attr( $libreria_tile['fg'] ); ?>"} -->
	<div class="wp-block-group libreria-tile has-<?php echo esc_attr( $libreria_tile['fg'] ); ?>-color has-<?php echo esc_attr( $libreria_tile['bg'] ); ?>-background-color has-text-color has-background">
		<!-- wp:heading {"level":2} -->
		<h2 class="wp-block-heading"><a href="<?php echo esc_url( $libreria_link ); ?>"><?php echo esc_html( $libreria_term->name ); ?></a></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph -->
		<p><?php echo esc_html( $libreria_tile['text'] ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
	<?php endforeach; ?>
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","className":"libreria-section-head","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
<div class="wp-block-group alignwide libreria-section-head">
	<!-- wp:group {"layout":{"type":"constrained"}} -->
	<div class="wp-block-group">
		<!-- wp:heading {"level":2} -->
		<h2 class="wp-block-heading"><?php echo esc_html__( 'Novedades', 'libreria-child' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph -->
		<p><?php echo esc_html__( 'Los últimos libros que llegaron a la tienda.', 'libreria-child' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:paragraph {"className":"libreria-see-all"} -->
	<p class="libreria-see-all"><a href="<?php echo esc_url( $libreria_shop_url ); ?>"><?php echo esc_html__( 'Ver todo', 'libreria-child' ); ?><span class="screen-reader-text"><?php echo esc_html__( ' el catálogo', 'libreria-child' ); ?></span></a></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<?php $libreria_collection( 12, 'date', 4, 4, 'libreria-grid', true, 3 ); // Skips the 3 books already shown in the banner. ?>

<!-- wp:group {"align":"full","className":"libreria-band","backgroundColor":"accent-3","textColor":"base","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull libreria-band has-base-color has-accent-3-background-color has-text-color has-background">
	<!-- wp:group {"align":"wide","layout":{"type":"constrained","contentSize":"640px"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":2,"textAlign":"center"} -->
		<h2 class="wp-block-heading has-text-align-center"><?php echo esc_html__( 'Compre sin salir de casa', 'libreria-child' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center"} -->
		<p class="has-text-align-center"><?php echo esc_html__( 'Explore el catálogo completo y arme su pedido en pocos pasos.', 'libreria-child' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
		<div class="wp-block-buttons">
			<!-- wp:button {"backgroundColor":"accent-1","textColor":"contrast"} -->
			<div class="wp-block-button"><a class="wp-block-button__link has-contrast-color has-accent-1-background-color has-text-color has-background wp-element-button" href="<?php echo esc_url( $libreria_shop_url ); ?>"><?php echo esc_html__( 'Explorar la tienda', 'libreria-child' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<?php
// Last row: one real category, so it never repeats the books of the rows above.
// The books live in the subcategory: a collection filtered by a parent category only finds books assigned to it directly.
$libreria_kids_term = get_term_by( 'slug', 'clasicos-infantiles', 'product_cat' );
$libreria_kids_link = $libreria_kids_term ? get_term_link( $libreria_kids_term ) : false;
if ( $libreria_kids_term && $libreria_kids_link && ! is_wp_error( $libreria_kids_link ) ) :
	?>
<!-- wp:group {"align":"wide","className":"libreria-section-head","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
<div class="wp-block-group alignwide libreria-section-head">
	<!-- wp:group {"layout":{"type":"constrained"}} -->
	<div class="wp-block-group">
		<!-- wp:heading {"level":2} -->
		<h2 class="wp-block-heading"><?php echo esc_html__( 'Para los más pequeños', 'libreria-child' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph -->
		<p><?php echo esc_html__( 'Cuentos y clásicos para leer en voz alta.', 'libreria-child' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:paragraph {"className":"libreria-see-all"} -->
	<p class="libreria-see-all"><a href="<?php echo esc_url( $libreria_kids_link ); ?>"><?php echo esc_html__( 'Ver todo', 'libreria-child' ); ?><span class="screen-reader-text"><?php echo esc_html__( ' los clásicos infantiles', 'libreria-child' ); ?></span></a></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

	<?php $libreria_collection( 13, 'title', 4, 4, 'libreria-grid', true, 0, 'asc', array( 'product_cat' => array( (int) $libreria_kids_term->term_id ) ) ); ?>
<?php endif; ?>
