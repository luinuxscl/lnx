<?php

namespace Database\Factories;

use App\Enums\AdministrativeAction;
use App\Models\AdministrativeActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdministrativeActivity>
 */
class AdministrativeActivityFactory extends Factory
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
            'action' => AdministrativeAction::UserUpdated,
            'resource_type' => 'user',
            'resource_id' => 1,
            'resource_name' => fake()->name(),
            'resource_email' => fake()->safeEmail(),
            'changes' => ['name' => ['old' => 'Before', 'new' => 'After']],
            'occurred_at' => now(),
        ];
    }
}
