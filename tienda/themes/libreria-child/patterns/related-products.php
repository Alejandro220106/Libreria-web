<?php
/**
 * Title: Libreria related products
 * Slug: libreria-child/related-products
 * Description: Related books for the product page, using the same book card as the rest of the store.
 * Inserter: no
 *
 * @package libreria-child
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:woocommerce/product-collection {"align":"wide","queryId":20,"query":{"perPage":4,"pages":1,"offset":0,"postType":"product","order":"asc","orderBy":"title","search":"","exclude":[],"inherit":false,"taxQuery":{},"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[],"filterable":false,"relatedBy":{"categories":true,"tags":true}},"tagName":"div","displayLayout":{"type":"flex","columns":4,"shrinkColumns":false},"dimensions":{"widthType":"fill"},"collection":"woocommerce/product-collection/related","hideControls":["inherit"],"queryContextIncludes":["collection"],"className":"libreria-grid"} -->
<div class="wp-block-woocommerce-product-collection alignwide libreria-grid">
	<!-- wp:heading {"className":"libreria-related-title"} -->
	<h2 class="wp-block-heading libreria-related-title"><?php echo esc_html__( 'También le puede interesar', 'libreria-child' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:woocommerce/product-template -->
	<!-- wp:woocommerce/product-image {"showSaleBadge":false,"imageSizing":"single","aspectRatio":"2/3","isDescendentOfQueryLoop":true} /-->

	<!-- wp:post-title {"textAlign":"center","level":3,"isLink":true,"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

	<!-- wp:woocommerce/product-price {"textAlign":"center","isDescendentOfQueryLoop":true,"fontSize":"small"} /-->
	<!-- /wp:woocommerce/product-template -->
</div>
<!-- /wp:woocommerce/product-collection -->
