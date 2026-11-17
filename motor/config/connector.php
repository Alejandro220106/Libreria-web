<?php

use App\Conectores\MailtrapConnector;
use App\Conectores\TelegramConnector;

return [

    // Active provider, chosen from .env (brief 6.6.2). It must be a key of "providers".
    'provider' => env('CONECTOR_PROVEEDOR') ?: 'telegram',

    // Registered connectors. Adding one means adding a line here.
    'providers' => [
        'telegram' => TelegramConnector::class,
        'mailtrap' => MailtrapConnector::class,
    ],

    // Calls to the provider.
    'timeout' => (int) env('CONECTOR_TIMEOUT', 10),
    'tries' => (int) env('CONECTOR_TRIES', 3),
    'backoff_ms' => (int) env('CONECTOR_BACKOFF_MS', 500),

    // Telegram Bot API: the message goes to the team's test chat, never to a customer.
    'telegram' => [
        'url' => env('CONECTOR_TELEGRAM_URL') ?: 'https://api.telegram.org',
        'token' => env('CONECTOR_TELEGRAM_TOKEN'),
        'chat_id' => env('CONECTOR_TELEGRAM_CHAT_ID'),
    ],

    // Mailtrap sandbox (previous provider, kept as an alternative): the email never reaches a real person.
    'mailtrap' => [
        'url' => env('MAILTRAP_URL', 'https://sandbox.api.mailtrap.io/api/send'),
        'token' => env('MAILTRAP_TOKEN'),
        'inbox_id' => env('MAILTRAP_INBOX_ID'),
        'from_email' => env('MAILTRAP_FROM_EMAIL', 'tienda@libreria.test'),
        'from_name' => env('MAILTRAP_FROM_NAME', 'Libreria Web'),
        'to_email' => env('MAILTRAP_TO_EMAIL', 'cliente@ejemplo.test'),
    ],

];
