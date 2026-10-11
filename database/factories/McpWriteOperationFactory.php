<?php

namespace Database\Factories;

use App\Models\McpWriteOperation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<McpWriteOperation>
 */
class McpWriteOperationFactory extends Factory
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
            'tool' => fake()->unique()->slug(2),
            'resource' => 'document',
            'operation' => 'create',
            'resource_id' => (string) fake()->numberBetween(1, 1000),
            'idempotency_key' => null,
            'request_hash' => hash('sha256', Str::random(16)),
            'status' => McpWriteOperation::STATUS_SUCCESS,
            'diff' => null,
            'response' => null,
            'correlation_id' => (string) Str::uuid(),
            'recorded_at' => now(),
        ];
    }
}
