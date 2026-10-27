<?php

use App\Http\Requests\ListRequest;
use App\Http\Responses\PaginatedResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Throwaway listing of 40 numbers, paginated like a real endpoint.
    Route::get('api/v1/test/numbers', function (ListRequest $request) {
        $items = range(1, 40);
        $perPage = $request->perPage();
        $page = $request->page();

        $paginator = new LengthAwarePaginator(
            array_slice($items, ($page - 1) * $perPage, $perPage),
            count($items),
            $perPage,
            $page,
            ['path' => $request->url(), 'pageName' => 'pagina'],
        );

        return PaginatedResponse::make($paginator->withQueryString(), fn (int $n) => ['numero' => $n]);
    });
});

it('uses pagina 1 and por_pagina 15 by default', function () {
    $this->getJson('/api/v1/test/numbers')
        ->assertOk()
        ->assertJsonCount(15, 'datos')
        ->assertJsonPath('meta', ['pagina' => 1, 'por_pagina' => 15, 'total' => 40, 'total_paginas' => 3])
        ->assertJsonPath('enlaces.anterior', null);
});

it('respects pagina and por_pagina and keeps them in the links', function () {
    $response = $this->getJson('/api/v1/test/numbers?pagina=2&por_pagina=5')
        ->assertOk()
        ->assertJsonPath('datos.0.numero', 6)
        ->assertJsonPath('meta.pagina', 2)
        ->assertJsonPath('meta.por_pagina', 5);

    expect($response->json('enlaces.anterior'))
        ->toContain('pagina=1')
        ->toContain('por_pagina=5');
});

it('answers an empty page with 200 and no data', function () {
    $this->getJson('/api/v1/test/numbers?pagina=99')
        ->assertOk()
        ->assertJsonCount(0, 'datos');
});

it('rejects por_pagina above 100 with a 422 naming the field', function () {
    $this->getJson('/api/v1/test/numbers?por_pagina=101')
        ->assertUnprocessable()
        ->assertJsonPath('error.codigo', 'validacion')
        ->assertJsonPath('error.detalles.0.campo', 'por_pagina');
});

it('rejects pagina 0 with a 422 naming the field', function () {
    $this->getJson('/api/v1/test/numbers?pagina=0')
        ->assertUnprocessable()
        ->assertJsonPath('error.detalles.0.campo', 'pagina');
});