<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 3);
        $price = fake()->numberBetween(2000, 20000);
        $normalized = fake()->boolean(70);

        return [
            'woo_id' => fake()->unique()->numberBetween(1000, 999999),
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'on-hold']),
            'currency' => 'CRC',
            'total' => $quantity * $price,
            'lines' => [[
                'sku' => 'TEST-'.fake()->numerify('######'),
                'name' => fake()->sentence(3),
                'quantity' => $quantity,
                'unit_price' => $price,
                'subtotal' => $quantity * $price,
            ]],
            // Never a real customer: no name, email or phone is stored (C8).
            'address_text' => fake()->streetAddress(),
            'address_status' => $normalized ? 'normalized' : 'pending_review',
            'address_normalized' => $normalized ? [
                'provincia' => 'Alajuela',
                'canton' => 'San Carlos',
                'distrito' => 'Quesada',
                'otras_senas' => fake()->sentence(4),
                'confianza' => 0.9,
            ] : null,
            'connector_status' => fake()->randomElement(['pending', 'success', 'failure']),
            'ordered_at' => fake()->dateTimeBetween('-30 days'),
        ];
    }
}
