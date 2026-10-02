<?php

namespace Database\Factories;

use App\Models\AiQuotaCounter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiQuotaCounter>
 */
class AiQuotaCounterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'scope_type' => AiQuotaCounter::SCOPE_USER,
            'scope_id' => (string) fake()->unique()->numberBetween(1, 1000),
            'window' => 'hour',
            'window_start' => now()->startOfHour(),
            'requests' => 0,
            'tokens' => 0,
            'reserved_tokens' => 0,
        ];
    }
}
