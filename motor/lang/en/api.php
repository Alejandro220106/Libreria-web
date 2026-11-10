<?php

/*
| Messages of the engine's own API (contract C3). The error codes ("validacion",
| "no_encontrado", ...) are part of the contract and stay in the code; only the
| text the client reads lives here.
*/

return [

    'errors' => [
        'validation' => 'The request data is not valid.',
        'unauthenticated' => 'A valid token is required.',
        'forbidden' => 'You are not allowed to perform this action.',
        'not_found' => 'The requested resource does not exist.',
        'too_many_requests' => 'Too many requests. Please try again later.',
        'internal' => 'An internal error occurred.',
        'bad_request' => 'The request is not valid.',
        'order_not_found' => 'There is no order with id :id.',
        'invalid_signature' => 'The webhook signature is not valid.',
        'missing_delivery_header' => 'The delivery header is missing.',
        'invalid_order_id' => 'The order does not have a valid id.',
    ],

    'fields' => [
        'page' => 'Must be an integer greater than or equal to 1.',
        'per_page' => 'Must be an integer between 1 and :max.',
        'one_of' => 'Must be one of: :values.',
        'max_characters' => 'Must be at most :max characters long.',
        'iso_date' => 'Must be a date and time in ISO 8601 format.',
        'header_required' => 'The header is required.',
        'positive_integer' => 'Must be a positive integer.',
    ],

];
