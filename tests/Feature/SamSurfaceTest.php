<?php

use App\Ai\Sam\SamRegistry;
use App\Models\User;
use Tests\Fixtures\Sam\FakeSamPanel;

beforeEach(function (): void {
    resetSamFixtures();

    view()->addNamespace('sam-fixtures', base_path('tests/Fixtures/Sam/views'));
});

it('does not render a surface that was not registered', function (): void {
    configureSam();
    grantSamPermission();

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('fake-sam-panel', false);
});

it('does not render the surface while the agent is disabled', function (): void {
    configureSam();
    grantSamPermission();
    config()->set('lnx_sam.enabled', false);

    app(SamRegistry::class)->registerSurface('fake', FakeSamPanel::class);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('fake-sam-panel', false);
});

it('does not render the surface to an identity without permission', function (): void {
    configureSam();

    app(SamRegistry::class)->registerSurface('fake', FakeSamPanel::class);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('fake-sam-panel', false);
});

it('renders the registered surface for an authorized identity', function (): void {
    configureSam();
    grantSamPermission();

    app(SamRegistry::class)->registerSurface('fake', FakeSamPanel::class);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('fake-sam-panel', false);
});
