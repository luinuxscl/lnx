<?php

use App\Enums\Role;
use App\Livewire\Ai\Usage;
use App\Models\AiUsageEvent;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function usageRoot(): User
{
    $root = User::factory()->create();
    $root->assignRole(Role::Root->value);

    return $root;
}

it('redirects guests from the AI consumption panel', function () {
    $this->get(route('admin.ai.usage'))->assertRedirect(route('login'));
});

it('forbids users without the root role from the consumption panel', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.ai.usage'))->assertForbidden();

    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $this->actingAs($admin)->get(route('admin.ai.usage'))->assertForbidden();

    Livewire::actingAs($admin)->test(Usage::class)->assertForbidden();
});

it('shows recorded consumption to root and filters it', function () {
    $root = usageRoot();
    AiUsageEvent::factory()->reportedCost(0.5)->create([
        'user_id' => $root->id,
        'user_name' => $root->name,
        'user_email' => $root->email,
        'application' => 'feature_a',
        'effective_model' => 'test/model',
        'input_tokens' => 100,
        'output_tokens' => 50,
        'total_tokens' => 150,
    ]);
    AiUsageEvent::factory()->create([
        'application' => 'feature_b',
        'effective_model' => 'other/model',
        'cost_source' => AiUsageEvent::COST_UNKNOWN,
    ]);

    $this->actingAs($root)->get(route('admin.ai.usage'))
        ->assertOk()
        ->assertSee(__('AI consumption'))
        ->assertSee('feature_a')
        ->assertSee('feature_b');

    Livewire::actingAs($root)->test(Usage::class)
        ->set('application', 'feature_a')
        ->assertViewHas('events', fn ($events): bool => $events->total() === 1 && $events->first()->application === 'feature_a')
        ->call('$refresh')
        ->assertSet('application', 'feature_a');

    Livewire::actingAs($root)->test(Usage::class)
        ->set('model', 'other/model')
        ->assertViewHas('events', fn ($events): bool => $events->total() === 1 && $events->first()->effective_model === 'other/model');
});

it('rejects an invalid status filter', function () {
    $root = usageRoot();

    Livewire::actingAs($root)->test(Usage::class)
        ->set('status', 'not-a-status')
        ->assertHasErrors('status');
});

it('links the AI tools on the root dashboard without rendering the consumption summary', function () {
    $root = usageRoot();
    AiUsageEvent::factory()->reportedCost(1.25)->create(['user_id' => $root->id]);

    $this->actingAs($root)->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('admin.ai.index'))
        ->assertDontSee(route('admin.ai.usage'))
        ->assertDontSee(__('AI consumption this month'));

    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(route('admin.ai.index'))
        ->assertDontSee(route('admin.ai.usage'))
        ->assertDontSee(__('AI consumption this month'));
});

it('prunes consumption records older than the retention period', function () {
    config()->set('lnx_ai.usage.retention_days', 10);
    $old = AiUsageEvent::factory()->create(['recorded_at' => now()->subDays(20)]);
    $recent = AiUsageEvent::factory()->create(['recorded_at' => now()->subDay()]);

    $this->artisan('ai:prune-usage')->assertSuccessful();

    expect(AiUsageEvent::query()->whereKey($old->id)->exists())->toBeFalse()
        ->and(AiUsageEvent::query()->whereKey($recent->id)->exists())->toBeTrue();
});

it('keeps consumption records when retention is disabled', function () {
    config()->set('lnx_ai.usage.retention_days', 0);
    $old = AiUsageEvent::factory()->create(['recorded_at' => now()->subYear()]);

    $this->artisan('ai:prune-usage')->assertSuccessful();

    expect(AiUsageEvent::query()->whereKey($old->id)->exists())->toBeTrue();
});
