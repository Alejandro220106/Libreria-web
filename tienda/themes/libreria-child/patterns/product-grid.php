<?php
/**
 * Title: Libreria product grid
 * Slug: libreria-child/product-grid
 * Description: Results count, sorting and the book grid shared by the shop, category and search templates.
 * Inserter: no
 *
 * @package libreria-child
 */
?>
<!-- wp:group {"align":"wide","className":"libreria-toolbar","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide libreria-toolbar">
	<!-- wp:woocommerce/product-results-count /-->

	<!-- wp:woocommerce/catalog-sorting /-->
</div>
<!-- /wp:group -->

<!-- wp:woocommerce/product-collection {"queryId":0,"query":{"woocommerceAttributes":[],"woocommerceStockStatus":["instock","outofstock","onbackorder"],"taxQuery":{},"isProductCollectionBlock":true,"perPage":12,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","author":"","search":"","exclude":[],"sticky":"","inherit":true},"tagName":"div","dimensions":{"widthType":"fill","fixedWidth":""},"displayLayout":{"type":"flex","columns":4},"convertedFromProducts":false,"queryContextIncludes":["collection"],"align":"wide","className":"libreria-grid"} -->
<div class="wp-block-woocommerce-product-collection alignwide libreria-grid">
	<!-- wp:woocommerce/product-template -->
	<!-- wp:woocommerce/product-image {"showSaleBadge":false,"imageSizing":"single","aspectRatio":"2/3","isDescendentOfQueryLoop":true} -->
		<!-- wp:woocommerce/product-sale-badge {"isDescendentOfQueryLoop":true,"align":"right"} /-->
	<!-- /wp:woocommerce/product-image -->

	<!-- wp:post-title {"textAlign":"center","level":2,"isLink":true,"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

	<!-- wp:woocommerce/product-price {"textAlign":"center","isDescendentOfQueryLoop":true,"fontSize":"small"} /-->
	<!-- /wp:woocommerce/product-template -->

	<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
	<!-- wp:query-pagination-previous /-->

	<!-- wp:query-pagination-numbers /-->

	<!-- wp:query-pagination-next /-->
	<!-- /wp:query-pagination -->

	<!-- wp:woocommerce/product-collection-no-results -->
	<!-- wp:group {"layout":{"type":"flex","orientation":"vertical","justifyContent":"center","flexWrap":"wrap"}} -->
	<div class="wp-block-group">
		<!-- wp:pattern {"slug":"woocommerce/no-products-found-clear-filters"} /-->
	</div>
	<!-- /wp:group -->
	<!-- /wp:woocommerce/product-collection-no-results -->
</div>
<!-- /wp:woocommerce/product-collection -->
