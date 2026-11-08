<?php

use App\Models\AiChatMessage;
use App\Models\AiConversation;
use App\Models\User;

/**
 * `latestOfMany()` aggregates the related key with `MAX`, and the messages of a
 * thread are keyed by UUID: PostgreSQL has no `max(uuid)`, so the relation only
 * resolved on SQLite and the Sam panel answered 500 in production.
 */
it('resolves the last message without aggregating the uuid key', function (): void {
    $conversation = AiConversation::query()->create([
        'user_id' => User::factory()->create()->getKey(),
        'title' => 'Hilo',
    ]);

    AiChatMessage::query()->create([
        'conversation_id' => $conversation->getKey(),
        'role' => 'user',
        'content' => 'Primera',
        'created_at' => now()->subMinutes(5),
    ]);

    AiChatMessage::query()->create([
        'conversation_id' => $conversation->getKey(),
        'role' => 'assistant',
        'content' => 'Segunda',
        'created_at' => now(),
    ]);

    $sql = $conversation->latestMessage()->toSql();

    expect($sql)->not->toContain('max(')
        ->and($conversation->latestMessage?->content)->toBe('Segunda')
        ->and(AiConversation::query()->with('latestMessage')->find($conversation->getKey())?->latestMessage?->content)
        ->toBe('Segunda');
});
