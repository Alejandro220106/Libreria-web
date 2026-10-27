<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            // woo_id and sku are unique, so the faker never repeats a value within a run.
            'woo_id' => fake()->unique()->numberBetween(1000, 999999),
            'sku' => 'TEST-'.fake()->unique()->numerify('######'),
            'name' => fake()->sentence(3),
            'type' => 'simple',
            'price' => fake()->numberBetween(2000, 20000),
            'stock' => fake()->numberBetween(0, 50),
            'categories' => ['Novela'],
            'author' => fake()->name(),
            'synced_at' => now(),
        ];
    }
}
