<?php

use App\Ai\Sam\SamAccess;
use App\Ai\Sam\SamMode;
use App\Ai\Sam\SamModeResolver;
use App\Ai\Sam\SamPreferences;
use App\Ai\Sam\SamRegistry;
use App\Ai\Sam\SamRisk;
use App\Ai\Sam\SamRuntime;
use App\Enums\Role;
use App\Models\McpToolInvocation;
use App\Models\SamModeCeiling;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Ai\Tools\Request;
use Tests\Fixtures\Sam\FakeProposeTool;
use Tests\Fixtures\Sam\FakeSamContributor;
use Tests\Fixtures\Sam\FakeSamPreferences;
use Tests\Fixtures\Sam\RecursiveSamPreferences;

beforeEach(function (): void {
    resetSamFixtures();
    FakeSamPreferences::$preferences = null;
    RecursiveSamPreferences::$calls = 0;
    $this->seed(RolePermissionSeeder::class);
});

/**
 * An identity with the given role, ready to resolve its agent mode.
 */
function samModeActor(Role $role = Role::Admin): User
{
    $user = User::factory()->create();
    $user->assignRole($role->value);

    return $user;
}

it('stays read only while the deployment ceiling is the default', function (): void {
    $user = samModeActor(Role::Root);

    SamModeCeiling::factory()->create([
        'role' => Role::Root->value,
        'mode' => SamMode::Assisted,
    ]);

    expect(app(SamModeResolver::class)->for($user))->toBe(SamMode::ReadOnly);
});

it('inherits the deployment ceiling when the role has no row', function (): void {
    config()->set('lnx_sam.max_mode', 'draft');

    expect(app(SamModeResolver::class)->for(samModeActor()))->toBe(SamMode::Draft);
});

it('never exceeds the ceiling of the role', function (): void {
    config()->set('lnx_sam.max_mode', 'draft');

    SamModeCeiling::factory()->create([
        'role' => Role::Admin->value,
        'mode' => SamMode::ReadOnly,
    ]);

    expect(app(SamModeResolver::class)->for(samModeActor(Role::Admin)))->toBe(SamMode::ReadOnly);
});

it('does not let a role raise the deployment ceiling', function (): void {
    config()->set('lnx_sam.max_mode', 'read_only');

    SamModeCeiling::factory()->create([
        'role' => Role::Admin->value,
        'mode' => SamMode::Assisted,
    ]);

    expect(app(SamModeResolver::class)->for(samModeActor(Role::Admin)))->toBe(SamMode::ReadOnly);
});

it('takes the highest ceiling among the roles of the identity', function (): void {
    config()->set('lnx_sam.max_mode', 'assisted');

    SamModeCeiling::factory()->create(['role' => Role::Admin->value, 'mode' => SamMode::Draft]);
    SamModeCeiling::factory()->create(['role' => Role::Root->value, 'mode' => SamMode::ReadOnly]);

    $user = samModeActor(Role::Admin);
    $user->assignRole(Role::Root->value);

    expect(app(SamModeResolver::class)->for($user))->toBe(SamMode::Draft);
});

it('lowers the effective mode to the person preference', function (): void {
    config()->set('lnx_sam.max_mode', 'draft');

    app(SamRegistry::class)->registerPreferences('fake', FakeSamPreferences::class);
    FakeSamPreferences::$preferences = new SamPreferences(mode: SamMode::ReadOnly);

    expect(app(SamModeResolver::class)->for(samModeActor()))->toBe(SamMode::ReadOnly);
});

it('does not let a preference raise the role ceiling', function (): void {
    config()->set('lnx_sam.max_mode', 'assisted');

    SamModeCeiling::factory()->create([
        'role' => Role::Admin->value,
        'mode' => SamMode::Draft,
    ]);

    app(SamRegistry::class)->registerPreferences('fake', FakeSamPreferences::class);
    FakeSamPreferences::$preferences = new SamPreferences(mode: SamMode::Assisted);

    $user = samModeActor(Role::Admin);

    expect(app(SamModeResolver::class)->for($user))->toBe(SamMode::Draft)
        ->and(app(SamModeResolver::class)->ceilingFor($user))->toBe(SamMode::Draft)
        ->and(app(SamRuntime::class)->ceilingFor($user))->toBe(SamMode::Draft);
});

it('ignores a missing preference and keeps the mode out of the turn payload', function (): void {
    config()->set('lnx_sam.max_mode', 'draft');

    app(SamRegistry::class)->registerPreferences('fake', FakeSamPreferences::class);
    FakeSamPreferences::$preferences = new SamPreferences(mode: null);

    $preferences = new SamPreferences(verbosity: SamPreferences::DETAILED, mode: SamMode::Assisted);

    expect(app(SamModeResolver::class)->for(samModeActor()))->toBe(SamMode::Draft)
        ->and($preferences->sanitized())->not->toHaveKey('mode')
        ->and($preferences->sanitized()['verbosity'])->toBe(SamPreferences::DETAILED);
});

it('does not reenter when a preference presenter asks for the mode', function (): void {
    config()->set('lnx_sam.max_mode', 'assisted');

    app(SamRegistry::class)->registerPreferences('fake', RecursiveSamPreferences::class);

    expect(app(SamModeResolver::class)->for(samModeActor()))->toBe(SamMode::ReadOnly)
        ->and(RecursiveSamPreferences::$calls)->toBe(1);
});

it('falls back to read only when the configured ceiling is invalid', function (): void {
    config()->set('lnx_sam.max_mode', 'nonsense');

    expect(app(SamModeResolver::class)->global())->toBe(SamMode::ReadOnly);
});

it('is read only for an identity without the surface permission', function (): void {
    config()->set('lnx_sam.max_mode', 'draft');

    expect(app(SamAccess::class)->modeFor(samModeActor()))->toBe(SamMode::ReadOnly);
});

it('declares a reversible risk and the draft mode as the minimum of a proposal', function (): void {
    $tool = app(FakeProposeTool::class);

    expect($tool->risk())->toBe(SamRisk::Reversible)
        ->and($tool->minimumMode())->toBe(SamMode::Draft);
});

it('does not offer proposal tools above the effective mode', function (): void {
    configureSam();
    grantSamPermission();

    app(SamRegistry::class)->register('fake', FakeSamContributor::class);

    $user = User::factory()->create();

    config()->set('lnx_sam.max_mode', 'read_only');

    $names = array_map(
        fn ($tool): string => $tool->name(),
        app(SamRuntime::class)->toolsFor($user),
    );

    expect($names)->not->toContain('fake_propose');

    config()->set('lnx_sam.max_mode', 'draft');

    $names = array_map(
        fn ($tool): string => $tool->name(),
        app(SamRuntime::class)->toolsFor($user),
    );

    expect($names)->toContain('fake_propose');
});

it('denies a proposal tool called below the effective mode', function (): void {
    configureSam();
    grantSamPermission();

    $user = User::factory()->create();
    $this->actingAs($user);

    config()->set('lnx_sam.max_mode', 'read_only');

    $output = app(FakeProposeTool::class)->withSource('fake')->handle(new Request(['title' => 'Ada']));

    $event = McpToolInvocation::query()->where('tool', 'fake_propose')->latest('id')->first();

    expect($output)->toContain('higher agent mode')
        ->and(FakeProposeTool::$calls)->toBe(0)
        ->and($event)->not->toBeNull()
        ->and($event->status)->toBe('denied')
        ->and($event->context['reason'])->toBe('mode')
        ->and($event->context['required_mode'])->toBe('draft');
});
