<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Builds the contract listing shape (C2): datos, meta and enlaces.
 */
final class PaginatedResponse
{
    /**
     * @param  callable(mixed): array<string, mixed>  $transform
     */
    public static function make(LengthAwarePaginator $paginator, callable $transform): JsonResponse
    {
        return response()->json([
            'datos' => array_map($transform, $paginator->items()),
            'meta' => [
                'pagina' => $paginator->currentPage(),
                'por_pagina' => $paginator->perPage(),
                'total' => $paginator->total(),
                'total_paginas' => $paginator->lastPage(),
            ],
            'enlaces' => [
                'anterior' => $paginator->previousPageUrl(),
                'siguiente' => $paginator->nextPageUrl(),
            ],
        ]);
    }
}