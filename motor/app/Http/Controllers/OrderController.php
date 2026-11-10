<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Http\Responses\ApiError;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    /**
     * The id in the URL is the WooCommerce order id, not the engine's own id (C5).
     */
    public function show(int $id): JsonResponse
    {
        $order = Order::where('woo_id', $id)->first();

        if ($order === null) {
            return ApiError::response(404, 'no_encontrado', __('api.errors.order_not_found', ['id' => $id]));
        }

        return response()->json(OrderResource::make($order)->resolve());
    }
}
