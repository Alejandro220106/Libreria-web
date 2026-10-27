<?php

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    // The listing is served from the engine database: any call to the store fails the test.
    Http::fake();
    Http::preventStrayRequests();

    Product::factory()->count(25)->create();
});

afterEach(fn () => Http::assertNothingSent());

it('returns the contract pagination keys', function () {
    $response = $this->getJson('/api/v1/productos')->assertOk();

    expect(array_keys($response->json()))->toBe(['datos', 'meta', 'enlaces'])
        ->and(array_keys($response->json('meta')))->toBe(['pagina', 'por_pagina', 'total', 'total_paginas'])
        ->and(array_keys($response->json('enlaces')))->toBe(['anterior', 'siguiente'])
        ->and($response->json('meta'))->toBe(['pagina' => 1, 'por_pagina' => 15, 'total' => 25, 'total_paginas' => 2]);
});

it('has no previous link on the first page', function () {
    $this->getJson('/api/v1/productos?por_pagina=10')
        ->assertOk()
        ->assertJsonCount(10, 'datos')
        ->assertJsonPath('enlaces.anterior', null)
        ->assertJsonPath('enlaces.siguiente', fn ($url) => str_contains($url, 'pagina=2') && str_contains($url, 'por_pagina=10'));
});

it('has both links on a middle page', function () {
    $this->getJson('/api/v1/productos?pagina=2&por_pagina=10')
        ->assertOk()
        ->assertJsonCount(10, 'datos')
        ->assertJsonPath('meta.pagina', 2)
        ->assertJsonPath('enlaces.anterior', fn ($url) => str_contains($url, 'pagina=1'))
        ->assertJsonPath('enlaces.siguiente', fn ($url) => str_contains($url, 'pagina=3'));
});

it('answers 200 with no data when the page is out of range', function () {
    $this->getJson('/api/v1/productos?pagina=9&por_pagina=10')
        ->assertOk()
        ->assertJsonCount(0, 'datos')
        ->assertJsonPath('meta.total', 25);
});
