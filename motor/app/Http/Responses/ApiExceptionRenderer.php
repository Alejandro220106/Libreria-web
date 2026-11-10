<?php

namespace App\Http\Responses;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Turns any exception thrown on an API route into the contract's error format (C3).
 */
final class ApiExceptionRenderer
{
    public static function render(Throwable $e): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return ApiError::response(422, 'validacion', __('api.errors.validation'), self::validationDetails($e));
        }

        if ($e instanceof AuthenticationException) {
            return ApiError::response(401, 'no_autenticado', __('api.errors.unauthenticated'));
        }

        if ($e instanceof HttpExceptionInterface) {
            return self::fromHttpException($e);
        }

        return ApiError::response(500, 'error_interno', __('api.errors.internal'));
    }

    private static function fromHttpException(HttpExceptionInterface $e): JsonResponse
    {
        $status = $e->getStatusCode();
        $headers = $e->getHeaders();

        return match (true) {
            $status === 403 => ApiError::response(403, 'prohibido', __('api.errors.forbidden'), [], $headers),
            $status === 404 => ApiError::response(404, 'no_encontrado', __('api.errors.not_found'), [], $headers),
            $status === 429 => ApiError::response(429, 'limite_excedido', self::tooManyRequestsMessage($headers), [], $headers),
            $status >= 500 => ApiError::response(500, 'error_interno', __('api.errors.internal')),
            default => ApiError::response($status, 'solicitud_invalida', __('api.errors.bad_request'), [], $headers),
        };
    }

    /**
     * Uses Retry-After when it holds seconds, so the client knows how long to wait (C3).
     *
     * @param  array<string, mixed>  $headers
     */
    private static function tooManyRequestsMessage(array $headers): string
    {
        $retryAfter = $headers['Retry-After'] ?? null;

        return is_numeric($retryAfter)
            ? __('api.errors.too_many_requests_seconds', ['seconds' => (int) $retryAfter])
            : __('api.errors.too_many_requests');
    }

    /**
     * @return array<int, array{campo: string, mensaje: string}>
     */
    private static function validationDetails(ValidationException $e): array
    {
        $details = [];

        foreach ($e->errors() as $field => $messages) {
            foreach ($messages as $message) {
                $details[] = ['campo' => $field, 'mensaje' => $message];
            }
        }

        return $details;
    }
}