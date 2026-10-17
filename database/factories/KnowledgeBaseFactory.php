<?php

namespace Database\Factories;

use App\Enums\KnowledgeAudience;
use App\Models\KnowledgeBase;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<KnowledgeBase>
 */
class KnowledgeBaseFactory extends Factory
{
    protected $model = KnowledgeBase::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'description' => fake()->sentence(),
            'audience' => KnowledgeAudience::User->value,
            'is_active' => true,
            'created_by' => null,
        ];
    }

    /**
     * Set the base audience.
     */
    public function audience(KnowledgeAudience $audience): static
    {
        return $this->state(fn (): array => ['audience' => $audience->value]);
    }

    public function root(): static
    {
        return $this->audience(KnowledgeAudience::Root);
    }

    public function admin(): static
    {
        return $this->audience(KnowledgeAudience::Admin);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
