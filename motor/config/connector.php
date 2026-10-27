<?php

use App\Conectores\MailtrapConnector;

return [

    // Active provider, chosen from .env (brief 6.6.2). It must be a key of "providers".
    'provider' => env('CONECTOR_PROVEEDOR') ?: 'mailtrap',

    // Registered connectors. Adding one means adding a line here.
    'providers' => [
        'mailtrap' => MailtrapConnector::class,
    ],

    // Calls to the provider.
    'timeout' => (int) env('CONECTOR_TIMEOUT', 10),
    'tries' => (int) env('CONECTOR_TRIES', 3),
    'backoff_ms' => (int) env('CONECTOR_BACKOFF_MS', 500),

    // Mailtrap sandbox: the email never reaches a real person.
    'mailtrap' => [
        'url' => env('MAILTRAP_URL', 'https://sandbox.api.mailtrap.io/api/send'),
        'token' => env('MAILTRAP_TOKEN'),
        'inbox_id' => env('MAILTRAP_INBOX_ID'),
        'from_email' => env('MAILTRAP_FROM_EMAIL', 'tienda@libreria.test'),
        'from_name' => env('MAILTRAP_FROM_NAME', 'Libreria Web'),
        'to_email' => env('MAILTRAP_TO_EMAIL', 'cliente@ejemplo.test'),
    ],

];
