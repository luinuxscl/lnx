<?php

namespace Database\Factories;

use App\Enums\NavigationItemType;
use App\Models\NavigationGroup;
use App\Models\NavigationItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NavigationItem>
 */
class NavigationItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'navigation_group_id' => NavigationGroup::factory(),
            'type' => NavigationItemType::Route,
            'route_name' => 'dashboard',
            'url' => null,
            'label_key' => null,
            'label_es' => null,
            'label_en' => null,
            'icon' => 'home',
            'position' => 0,
            'enabled' => true,
        ];
    }

    public function link(string $url, string $labelEs, string $labelEn): static
    {
        return $this->state(fn (): array => [
            'type' => NavigationItemType::Link,
            'route_name' => null,
            'url' => $url,
            'label_es' => $labelEs,
            'label_en' => $labelEn,
        ]);
    }
}
