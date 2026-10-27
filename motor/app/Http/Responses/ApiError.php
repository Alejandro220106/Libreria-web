<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Builds the single error format required by the contract (C3).
 */
final class ApiError
{
    /**
     * @param  array<int, array{campo: string, mensaje: string}>  $details
     * @param  array<string, string>  $headers
     */
    public static function response(
        int $status,
        string $code,
        string $message,
        array $details = [],
        array $headers = [],
    ): JsonResponse {
        return response()->json([
            'error' => [
                'codigo' => $code,
                'mensaje' => $message,
                'detalles' => $details,
            ],
        ], $status, $headers);
    }
}