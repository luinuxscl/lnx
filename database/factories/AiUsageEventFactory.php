<?php

namespace Database\Factories;

use App\Models\AiUsageEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AiUsageEvent>
 */
class AiUsageEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $input = fake()->numberBetween(10, 500);
        $output = fake()->numberBetween(5, 300);

        return [
            'correlation_id' => (string) Str::uuid(),
            'attempt' => 1,
            'user_id' => User::factory(),
            'user_name' => fake()->name(),
            'user_email' => fake()->safeEmail(),
            'application' => 'default',
            'provider' => 'openrouter',
            'requested_model' => 'test/model',
            'effective_model' => 'test/model',
            'input_tokens' => $input,
            'output_tokens' => $output,
            'total_tokens' => $input + $output,
            'reported_cost' => null,
            'estimated_cost' => null,
            'cost_currency' => null,
            'cost_source' => AiUsageEvent::COST_UNKNOWN,
            'pricing_version' => null,
            'duration_ms' => fake()->numberBetween(50, 4000),
            'status' => AiUsageEvent::STATUS_SUCCESS,
            'error_category' => null,
            'recorded_at' => now(),
        ];
    }

    public function reportedCost(float $amount, string $currency = 'USD'): static
    {
        return $this->state(fn (): array => [
            'reported_cost' => $amount,
            'cost_currency' => $currency,
            'cost_source' => AiUsageEvent::COST_REPORTED,
        ]);
    }

    public function failed(string $category = 'unavailable'): static
    {
        return $this->state(fn (): array => [
            'status' => AiUsageEvent::STATUS_ERROR,
            'error_category' => $category,
            'input_tokens' => null,
            'output_tokens' => null,
            'total_tokens' => null,
        ]);
    }

    public function blocked(): static
    {
        return $this->state(fn (): array => [
            'status' => AiUsageEvent::STATUS_BLOCKED,
            'input_tokens' => null,
            'output_tokens' => null,
            'total_tokens' => null,
        ]);
    }
}
