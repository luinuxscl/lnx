<?php

namespace Database\Factories;

use App\Enums\NavigationSource;
use App\Models\NavigationGroup;
use App\Models\NavigationMenu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NavigationGroup>
 */
class NavigationGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'navigation_menu_id' => NavigationMenu::factory(),
            'label_key' => 'Administration',
            'label_es' => null,
            'label_en' => null,
            'icon' => 'cog-6-tooth',
            'position' => 0,
            'enabled' => true,
            'source' => NavigationSource::Root,
            'overridden' => false,
        ];
    }

    public function catalog(): static
    {
        return $this->state(fn (): array => [
            'source' => NavigationSource::Catalog,
            'overridden' => false,
        ]);
    }
}
