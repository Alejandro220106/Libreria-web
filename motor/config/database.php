<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection
    |--------------------------------------------------------------------------
    |
    | The engine runs on PostgreSQL 18 in every environment (local, tests and
    | the demo), so what passes locally also passes in the demo.
    |
    */

    'default' => env('DB_CONNECTION', 'pgsql'),

    'connections' => [

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'libreria_motor'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('DB_SSLMODE', 'prefer'),
            // Same zone as the app, so now() in SQL matches now() in PHP.
            'timezone' => env('APP_TIMEZONE', 'America/Costa_Rica'),
        ],

        // Schema owner: only for `php artisan migrate --database=pgsql_admin`, never for requests.
        // With DB_ADMIN_USERNAME empty (single-user mode) it is the same user as the app, so a
        // fresh clone runs `php artisan migrate --seed` with no extra step (brief 6.2.2).
        'pgsql_admin' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'libreria_motor'),
            'username' => env('DB_ADMIN_USERNAME') ?: env('DB_USERNAME', 'postgres'),
            'password' => env('DB_ADMIN_USERNAME') ? env('DB_ADMIN_PASSWORD', '') : env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('DB_SSLMODE', 'prefer'),
            // Same zone as the app, so now() in SQL matches now() in PHP.
            'timezone' => env('APP_TIMEZONE', 'America/Costa_Rica'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

];
