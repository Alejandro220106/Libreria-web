<?php

use Database\Seeders\IntegrationLogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(IntegrationLogSeeder::class));

it('lists the log in the contract shape, newest first', function () {
    $this->getJson('/api/v1/bitacora')
        ->assertOk()
        ->assertJsonPath('meta', ['pagina' => 1, 'por_pagina' => 15, 'total' => 24, 'total_paginas' => 2])
        ->assertJsonPath('datos.0.id', 24)
        ->assertJsonPath('datos.0.direccion', 'entrante')
        ->assertJsonPath('datos.0.sistema', 'tienda')
        ->assertJsonPath('datos.0.estado', 'exito')
        ->assertJsonStructure(['datos' => [['id', 'fecha', 'direccion', 'sistema', 'operacion', 'estado', 'codigo_http', 'intentos', 'duracion_ms', 'correlacion', 'error']]]);
});

// C8: the Resource lists the fields one by one, so nothing else can appear.
it('only exposes the contract fields', function () {
    $entry = $this->getJson('/api/v1/bitacora')->json('datos.0');

    expect(array_keys($entry))->toBe(['id', 'fecha', 'direccion', 'sistema', 'operacion', 'estado', 'codigo_http', 'intentos', 'duracion_ms', 'correlacion', 'error']);
});

it('orders by date and then id, descending', function () {
    $ids = collect($this->getJson('/api/v1/bitacora?por_pagina=100')->json('datos'))->pluck('id')->all();

    expect($ids)->toBe(range(24, 1));
});

it('paginates', function () {
    $this->getJson('/api/v1/bitacora?pagina=2&por_pagina=10')
        ->assertOk()
        ->assertJsonCount(10, 'datos')
        ->assertJsonPath('datos.0.id', 14);
});

it('filters by each field', function (string $query, callable $matches) {
    $rows = $this->getJson('/api/v1/bitacora?por_pagina=100&'.$query)->assertOk()->json('datos');

    expect($rows)->not->toBeEmpty()
        ->and(collect($rows)->every($matches))->toBeTrue();
})->with([
    'sistema' => ['sistema=conector', fn ($r) => $r['sistema'] === 'conector'],
    'estado' => ['estado=firma_invalida', fn ($r) => $r['estado'] === 'firma_invalida'],
    'direccion' => ['direccion=saliente', fn ($r) => $r['direccion'] === 'saliente'],
    'correlacion' => ['correlacion=contrato-entrega-001', fn ($r) => $r['correlacion'] === 'contrato-entrega-001'],
]);

it('combines filters with and', function () {
    $rows = $this->getJson('/api/v1/bitacora?por_pagina=100&sistema=ia&estado=fallo')->assertOk()->json('datos');

    expect($rows)->not->toBeEmpty()
        ->and(collect($rows)->every(fn ($r) => $r['sistema'] === 'ia' && $r['estado'] === 'fallo'))->toBeTrue();
});

it('filters by date range and converts offsets to UTC', function () {
    // Newest entry is 2026-11-10 21:59 UTC. 17:59 at -04:00 is the same instant.
    $this->getJson('/api/v1/bitacora?'.http_build_query(['desde' => '2026-11-10T17:59:00-04:00']))
        ->assertOk()
        ->assertJsonPath('meta.total', 1);

    $this->getJson('/api/v1/bitacora?'.http_build_query(['hasta' => '2026-11-10T21:57:00Z']))
        ->assertOk()
        ->assertJsonPath('meta.total', 23);
});

it('rejects invalid filters with a 422 naming the field', function (string $query, string $field) {
    $this->getJson('/api/v1/bitacora?'.$query)
        ->assertStatus(422)
        ->assertJsonPath('error.codigo', 'validacion')
        ->assertJsonPath('error.detalles.0.campo', $field);
})->with([
    ['sistema=otro', 'sistema'],
    ['estado=success', 'estado'],
    ['direccion=incoming', 'direccion'],
    ['desde=ayer', 'desde'],
    ['por_pagina=101', 'por_pagina'],
]);
