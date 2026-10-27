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
            return ApiError::response(422, 'validacion', 'Los datos de la petición no son válidos.', self::validationDetails($e));
        }

        if ($e instanceof AuthenticationException) {
            return ApiError::response(401, 'no_autenticado', 'Se requiere un token válido.');
        }

        if ($e instanceof HttpExceptionInterface) {
            return self::fromHttpException($e);
        }

        return ApiError::response(500, 'error_interno', 'Ocurrió un error interno.');
    }

    private static function fromHttpException(HttpExceptionInterface $e): JsonResponse
    {
        $status = $e->getStatusCode();
        $headers = $e->getHeaders();

        return match (true) {
            $status === 403 => ApiError::response(403, 'prohibido', 'No tiene permiso para esta acción.', [], $headers),
            $status === 404 => ApiError::response(404, 'no_encontrado', 'El recurso solicitado no existe.', [], $headers),
            $status === 429 => ApiError::response(429, 'limite_excedido', 'Demasiadas peticiones. Intente de nuevo más tarde.', [], $headers),
            $status >= 500 => ApiError::response(500, 'error_interno', 'Ocurrió un error interno.'),
            default => ApiError::response($status, 'solicitud_invalida', 'La solicitud no es válida.', [], $headers),
        };
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