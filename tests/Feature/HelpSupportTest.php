<?php

use App\Ai\Tools\ProductHelpSearchTool;
use App\Enums\Role;
use App\Livewire\Support\Panel;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\Help\HelpRegistry;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Ai\Tools\Request;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('keeps the help topic as context when the request is sent', function () {
    $owner = User::factory()->create();
    $root = User::factory()->create();
    $root->assignRole(Role::Root);

    Livewire::actingAs($owner)->test(Panel::class)
        ->call('open', ['help_topic' => 'demo.user-management'])
        ->assertSet('helpTopic', 'demo.user-management')
        ->set('type', 'question')
        ->set('title', 'I need more detail')
        ->set('description', 'The guide did not cover my case.')
        ->call('submit')
        ->assertHasNoErrors();

    $ticket = SupportTicket::firstOrFail();

    expect($ticket->help_topic)->toBe('demo.user-management');

    $this->actingAs($owner)->get(route('support.show', $ticket))
        ->assertOk()
        ->assertDontSee('Tema de ayuda');

    $this->actingAs($root)->get(route('support.show', $ticket))
        ->assertOk()
        ->assertSee('Tema de ayuda')
        ->assertSee('demo.user-management');
});

it('searches the registry without leaking restricted entries to the chat tool', function () {
    app(HelpRegistry::class)->register('demo', [
        'demo.public' => [
            'type' => 'article',
            'group' => 'Help',
            'title' => 'Public guide',
            'summary' => 'Available to everyone.',
            'body' => 'Public content.',
            'order' => 1,
        ],
        'demo.admin' => [
            'type' => 'article',
            'group' => 'Help',
            'title' => 'Admin secret guide',
            'summary' => 'Only administrators can read this.',
            'body' => 'Sensitive admin content.',
            'roles' => ['admin'],
            'order' => 2,
        ],
    ]);

    $user = User::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);

    $tool = new ProductHelpSearchTool;

    $this->actingAs($user);
    $output = (string) $tool->handle(new Request(['query' => 'secret']));

    expect($output)->toContain('No help section matched')
        ->and($output)->not->toContain('Admin secret guide');

    $this->actingAs($admin);
    $output = (string) $tool->handle(new Request(['query' => 'secret']));

    expect($output)->toContain('Admin secret guide')
        ->and($output)->toContain('topic=demo.admin');
});

it('returns at most three results with their titles, summaries and links', function () {
    $entries = [];

    foreach (range(1, 4) as $index) {
        $entries["demo.guide-{$index}"] = [
            'type' => 'article',
            'group' => 'Help',
            'title' => "Guide {$index}",
            'summary' => "Summary {$index}",
            'body' => 'Body',
            'order' => $index,
        ];
    }

    app(HelpRegistry::class)->register('demo', $entries);

    $user = User::factory()->create();
    $this->actingAs($user);

    $output = (string) (new ProductHelpSearchTool)->handle(new Request(['query' => 'guide']));

    expect(substr_count($output, 'topic=demo.guide-'))->toBe(3)
        ->and($output)->toContain('Guide 1')
        ->and($output)->toContain('Summary 1')
        ->and($output)->not->toContain('Guide 4');
});
