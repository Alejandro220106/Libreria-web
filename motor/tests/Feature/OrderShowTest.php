<?php

use App\Models\Order;
use Database\Seeders\OrderSeeder;
use Tests\Support\RefreshDatabaseAsOwner;

uses(RefreshDatabaseAsOwner::class);

beforeEach(fn () => $this->seed(OrderSeeder::class));

it('returns one order in the exact contract shape', function () {
    $this->getJson('/api/v1/pedidos/1001')
        ->assertOk()
        ->assertExactJson([
            'id' => 1001,
            'estado' => 'processing',
            'moneda' => 'CRC',
            'total' => 16900,
            'creado_en' => '2026-11-10T20:02:55+00:00',
            'lineas' => [
                ['sku' => 'CONTRATO-001', 'nombre' => 'Cuentos de mi tía Panchita', 'cantidad' => 2, 'precio_unitario' => 5200, 'subtotal' => 10400],
                ['sku' => 'LIB-PRI-01', 'nombre' => 'El principito', 'cantidad' => 1, 'precio_unitario' => 6500, 'subtotal' => 6500],
            ],
            'direccion' => [
                'estado' => 'normalizada',
                'texto' => '100 metros norte de la iglesia de Ciudad Quesada, casa verde',
                'normalizada' => [
                    'provincia' => 'Alajuela',
                    'canton' => 'San Carlos',
                    'distrito' => 'Quesada',
                    'otras_senas' => '100 metros norte de la iglesia, casa verde',
                    'confianza' => 0.93,
                ],
            ],
            'conector' => [
                'estado' => 'exito',
                'detalle' => 'sent',
                'referencia_externa' => 'msg-88231',
                'actualizado_en' => '2026-11-10T20:02:55+00:00',
            ],
        ]);
});

it('shows an address pending review and a failed connector', function () {
    $this->getJson('/api/v1/pedidos/1002')
        ->assertOk()
        ->assertJsonPath('direccion.estado', 'pendiente_revision')
        ->assertJsonPath('direccion.normalizada', null)
        ->assertJsonPath('conector.estado', 'fallo');

    $this->getJson('/api/v1/pedidos/1003')
        ->assertOk()
        ->assertJsonPath('direccion.estado', 'sin_procesar')
        ->assertJsonPath('conector', ['estado' => 'pendiente', 'detalle' => null, 'referencia_externa' => null, 'actualizado_en' => null]);
});

it('finds the order by its WooCommerce id, not by the engine id', function () {
    $order = Order::create([
        'woo_id' => 5555,
        'status' => 'completed',
        'total' => 4900,
        'lines' => [['sku' => null, 'name' => 'Gallinas y gallos', 'quantity' => 1, 'unit_price' => '4900.00', 'subtotal' => '4900.00']],
        'address_text' => 'Barrio Lourdes, casa 4',
        'ordered_at' => '2026-11-11 10:00:00',
    ]);

    $this->getJson('/api/v1/pedidos/5555')
        ->assertOk()
        ->assertJsonPath('id', 5555)
        ->assertJsonPath('lineas.0.sku', null)
        ->assertJsonPath('lineas.0.precio_unitario', 4900);

    $this->getJson("/api/v1/pedidos/{$order->id}")->assertNotFound();
});

it('answers an unknown order with the contract 404', function () {
    $this->getJson('/api/v1/pedidos/999999')
        ->assertNotFound()
        ->assertExactJson([
            'error' => [
                'codigo' => 'no_encontrado',
                'mensaje' => 'No existe un pedido con id 999999.',
                'detalles' => [],
            ],
        ]);
});

it('answers a non-numeric id with the contract 404', function () {
    $this->getJson('/api/v1/pedidos/abc')
        ->assertNotFound()
        ->assertJsonPath('error.codigo', 'no_encontrado');
});
