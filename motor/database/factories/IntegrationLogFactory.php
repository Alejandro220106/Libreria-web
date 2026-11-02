<?php

namespace Database\Factories;

use App\Domain\IntegrationLog\LogDirection;
use App\Domain\IntegrationLog\LogStatus;
use App\Domain\IntegrationLog\LogSystem;
use App\Models\IntegrationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntegrationLog>
 */
class IntegrationLogFactory extends Factory
{
    public function definition(): array
    {
        $failed = fake()->boolean(15);

        return [
            'direction' => LogDirection::Outgoing,
            'system' => fake()->randomElement([LogSystem::Store, LogSystem::Connector, LogSystem::Ai]),
            'operation' => fake()->randomElement(['get_products', 'send_email', 'normalize_address']),
            'status' => $failed ? LogStatus::Failure : LogStatus::Success,
            'http_code' => $failed ? 500 : 200,
            'attempts' => $failed ? 3 : 1,
            'duration_ms' => fake()->numberBetween(50, 2500),
            'correlation_id' => (string) fake()->numberBetween(1000, 999999),
            'error' => $failed ? 'Illuminate\Http\Client\RequestException' : null,
            'logged_at' => fake()->dateTimeBetween('-30 days'),
        ];
    }
}
