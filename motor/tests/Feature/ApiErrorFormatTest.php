<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Throwaway routes that only exist during these tests.
    Route::prefix('api/v1/test')->group(function () {
        Route::get('validation', function (Request $request) {
            $request->validate(['por_pagina' => ['required', 'integer', 'between:1,100']]);
        });
        Route::get('forbidden', fn () => abort(403));
        Route::get('throttled', fn () => abort(429, '', ['Retry-After' => '30']));
        Route::get('crash', fn () => throw new RuntimeException('Failure in /var/www/motor/app/Secret.php'));
    });
});

it('answers an unknown API route with the contract 404', function () {
    $this->getJson('/api/v1/does-not-exist')
        ->assertNotFound()
        ->assertExactJson([
            'error' => [
                'codigo' => 'no_encontrado',
                'mensaje' => 'El recurso solicitado no existe.',
                'detalles' => [],
            ],
        ]);
});

it('lists validation errors per field with a 422', function () {
    $this->getJson('/api/v1/test/validation?por_pagina=500')
        ->assertUnprocessable()
        ->assertJsonPath('error.codigo', 'validacion')
        ->assertJsonPath('error.detalles.0.campo', 'por_pagina');
});

it('answers a forbidden action with the contract 403', function () {
    $this->getJson('/api/v1/test/forbidden')
        ->assertForbidden()
        ->assertJsonPath('error.codigo', 'prohibido');
});

it('keeps the Retry-After header on a 429', function () {
    $this->getJson('/api/v1/test/throttled')
        ->assertStatus(429)
        ->assertHeader('Retry-After', '30')
        ->assertJsonPath('error.codigo', 'limite_excedido');
});

it('hides internal details on a 500, even in debug mode', function () {
    config(['app.debug' => true]);

    $response = $this->getJson('/api/v1/test/crash');

    $response->assertStatus(500)
        ->assertExactJson([
            'error' => [
                'codigo' => 'error_interno',
                'mensaje' => 'Ocurrió un error interno.',
                'detalles' => [],
            ],
        ]);

    expect($response->getContent())
        ->not->toContain('Secret.php')
        ->not->toContain('trace');
});