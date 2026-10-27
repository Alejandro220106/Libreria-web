<?php

use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(ProductSeeder::class));

it('lists products in the contract shape', function () {
    $this->getJson('/api/v1/productos')
        ->assertOk()
        ->assertJsonCount(14, 'datos')
        ->assertJsonPath('meta', ['pagina' => 1, 'por_pagina' => 15, 'total' => 14, 'total_paginas' => 1])
        ->assertJsonPath('datos.0.sku', 'CONTRATO-001')
        ->assertJsonPath('datos.0.id_tienda', 41)
        ->assertJsonPath('datos.0.precio', 5200)
        ->assertJsonPath('datos.0.categorias', ['Cuento'])
        ->assertJsonPath('datos.0.campo_propio', ['nombre' => 'autor', 'valor' => 'Carmen Lyra'])
        ->assertJsonStructure(['datos' => [['id', 'id_tienda', 'sku', 'nombre', 'tipo', 'precio', 'existencias', 'categorias', 'campo_propio', 'sincronizado_en']]]);
});

it('keeps null stock and a null custom field value', function () {
    $this->getJson('/api/v1/productos?sku=LIB-AGE-VAR')
        ->assertOk()
        ->assertJsonPath('datos.0.existencias', null)
        ->assertJsonPath('datos.0.campo_propio.valor', null);
});

it('paginates with pagina and por_pagina', function () {
    $this->getJson('/api/v1/productos?pagina=2&por_pagina=10')
        ->assertOk()
        ->assertJsonCount(4, 'datos')
        ->assertJsonPath('meta.total_paginas', 2)
        ->assertJsonPath('enlaces.siguiente', null);
});

it('filters by exact sku', function () {
    $this->getJson('/api/v1/productos?sku=LIB-MAM-01')
        ->assertOk()
        ->assertJsonCount(1, 'datos')
        ->assertJsonPath('datos.0.nombre', 'Mamita Yunai');
});

it('rejects an invalid por_pagina with a 422', function () {
    $this->getJson('/api/v1/productos?por_pagina=101')
        ->assertUnprocessable()
        ->assertJsonPath('error.detalles.0.campo', 'por_pagina');
});

it('never calls the store', function () {
    // Http::preventStrayRequests() (Pest.php) fails the test on any outgoing call.
    $this->getJson('/api/v1/productos')->assertOk();
});
