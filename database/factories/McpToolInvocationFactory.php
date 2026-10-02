<?php

namespace Database\Factories;

use App\Models\McpToolInvocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<McpToolInvocation>
 */
class McpToolInvocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_id' => User::factory(),
            'actor_name' => fake()->name(),
            'actor_email' => fake()->safeEmail(),
            'tool' => 'search-users',
            'correlation_id' => (string) Str::uuid(),
            'status' => 'success',
            'result_count' => 1,
            'context' => [],
            'recorded_at' => now(),
        ];
    }
}
