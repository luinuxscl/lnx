<?php

namespace Database\Factories;

use App\Enums\NavigationAudience;
use App\Models\NavigationMenu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NavigationMenu>
 */
class NavigationMenuFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'audience' => NavigationAudience::User,
        ];
    }

    public function forAudience(NavigationAudience $audience): static
    {
        return $this->state(fn (): array => ['audience' => $audience]);
    }
}
