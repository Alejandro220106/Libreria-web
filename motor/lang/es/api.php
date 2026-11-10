<?php

/*
| Messages of the engine's own API (contract C3). The error codes ("validacion",
| "no_encontrado", ...) are part of the contract and stay in the code; only the
| text the client reads lives here.
*/

return [

    'errors' => [
        'validation' => 'Los datos de la petición no son válidos.',
        'unauthenticated' => 'Se requiere un token válido.',
        'forbidden' => 'No tiene permiso para esta acción.',
        'not_found' => 'El recurso solicitado no existe.',
        'too_many_requests' => 'Demasiadas peticiones. Intente de nuevo más tarde.',
        'internal' => 'Ocurrió un error interno.',
        'bad_request' => 'La solicitud no es válida.',
        'order_not_found' => 'No existe un pedido con id :id.',
        'invalid_signature' => 'La firma del webhook no es válida.',
        'missing_delivery_header' => 'Falta la cabecera de la entrega.',
        'invalid_order_id' => 'El pedido no trae un id válido.',
    ],

    'fields' => [
        'page' => 'Debe ser un entero mayor o igual que 1.',
        'per_page' => 'Debe ser un entero entre 1 y :max.',
        'one_of' => 'Debe ser uno de: :values.',
        'max_characters' => 'Debe tener como máximo :max caracteres.',
        'iso_date' => 'Debe ser una fecha y hora en formato ISO 8601.',
        'header_required' => 'La cabecera es obligatoria.',
        'positive_integer' => 'Debe ser un entero positivo.',
    ],

];
