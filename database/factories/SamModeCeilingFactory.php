<?php

namespace Database\Factories;

use App\Ai\Sam\SamMode;
use App\Enums\Role;
use App\Models\SamModeCeiling;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SamModeCeiling>
 */
class SamModeCeilingFactory extends Factory
{
    protected $model = SamModeCeiling::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role' => Role::Admin->value,
            'mode' => SamMode::ReadOnly,
        ];
    }
}
