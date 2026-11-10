<?php

declare(strict_types=1);

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

it('uses Spanish with English as the fallback', function () {
    expect(app()->getLocale())->toBe('es')
        ->and(app()->getFallbackLocale())->toBe('en');
});

it('returns validation messages in Spanish', function () {
    $validator = Validator::make(['precio' => null], ['precio' => 'required']);

    expect($validator->errors()->first('precio'))->toBe('El campo precio es obligatorio.');
});

it('returns the API error messages in Spanish', function () {
    $this->getJson('/api/v1/pedidos/999999')
        ->assertNotFound()
        ->assertJsonPath('error.mensaje', 'No existe un pedido con id 999999.');
});

/*
| Arr::dot() flattens nested keys ("between.numeric", "max.string"), so a
| missing message inside a group is caught too, not only a missing group.
*/
it('has the same keys in Spanish and English', function (string $file) {
    $english = array_keys(Arr::dot(require lang_path("en/{$file}.php")));
    $spanish = array_keys(Arr::dot(require lang_path("es/{$file}.php")));

    expect(array_values(array_diff($english, $spanish)))->toBe([], "Missing in lang/es/{$file}.php")
        ->and(array_values(array_diff($spanish, $english)))->toBe([], "Extra in lang/es/{$file}.php");
})->with(['auth', 'pagination', 'passwords', 'validation', 'api']);
