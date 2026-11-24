<?php

namespace Src\Catalog\Infrastructure\Http\Controllers;

use Src\Shared\Infrastructure\Http\Controllers\Controller;
use Src\Catalog\Infrastructure\Http\Requests\ProductListRequest;
use Src\Catalog\Infrastructure\Http\Resources\ProductResource;
use Src\Shared\Infrastructure\Http\Responses\PaginatedResponse;
use Src\Catalog\Infrastructure\Persistence\Models\Product;

class ProductController extends Controller
{
    /**
     * Served from the engine's database; the store is never queried here.
     */
    public function index(ProductListRequest $request)
    {
        $paginator = Product::query()
            ->when($request->query('sku'), fn ($query, $sku) => $query->where('sku', $sku))
            ->orderBy('id')
            ->paginate($request->perPage(), ['*'], 'pagina', $request->page());

        return PaginatedResponse::make(
            $paginator->withQueryString(),
            fn (Product $product) => ProductResource::make($product)->resolve(),
        );
    }
}
