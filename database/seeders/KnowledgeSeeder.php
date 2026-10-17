<?php

namespace Database\Seeders;

use App\Enums\KnowledgeAudience;
use App\Models\AiAssistant;
use App\Models\KnowledgeBase;
use Illuminate\Database\Seeder;

class KnowledgeSeeder extends Seeder
{
    /**
     * Seed the default knowledge base and assistant preset.
     *
     * Idempotent: safe to run on every deploy.
     */
    public function run(): void
    {
        KnowledgeBase::firstOrCreate(
            ['slug' => 'general'],
            [
                'name' => 'General',
                'description' => __('Default shared knowledge base.'),
                'audience' => KnowledgeAudience::User->value,
                'is_active' => true,
            ],
        );

        AiAssistant::firstOrCreate(
            ['slug' => 'general-assistant'],
            [
                'name' => __('General assistant'),
                'system_prompt' => null,
                'model' => null,
                'audience' => KnowledgeAudience::User->value,
                'is_active' => true,
            ],
        );
    }
}
