<?php

namespace Database\Factories;

use App\Enums\KnowledgeAudience;
use App\Models\AiAssistant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AiAssistant>
 */
class AiAssistantFactory extends Factory
{
    protected $model = AiAssistant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = implode(' ', [fake()->word(), fake()->word()]);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'system_prompt' => fake()->paragraph(),
            'model' => null,
            'audience' => KnowledgeAudience::User->value,
            'is_active' => true,
            'created_by' => null,
        ];
    }

    /**
     * Set the preset audience.
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
