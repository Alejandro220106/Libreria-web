<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductListRequest;
use App\Http\Resources\ProductResource;
use App\Http\Responses\PaginatedResponse;
use App\Models\Product;

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
