<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_title' => fake()->company(),
            'site_tagline' => fake()->sentence(4),
            'meta_description' => fake()->sentence(),
            'admin_email' => fake()->unique()->safeEmail(),
            'logo_path' => null,
            'logo_dark_path' => null,
            'favicon_path' => null,
            'apple_touch_icon_path' => null,
            'footer_text' => fake()->sentence(),
            'analytics_id' => null,
            'maintenance_mode' => false,
            'maintenance_message' => null,
        ];
    }
}
