<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\SamModeCeiling;
use Illuminate\Database\Eloquent\Factories\Factory;
use Luinuxscl\LnxSam\SamMode;

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
