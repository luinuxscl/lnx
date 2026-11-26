<?php

use App\Models\User;
use App\Support\Dashboard\DashboardHome;
use App\Support\Dashboard\DashboardHomeRegistry;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

class DashboardHomeLowStub implements DashboardHome
{
    public function source(): string
    {
        return 'low';
    }

    public function priority(): int
    {
        return 1;
    }

    public function visible(User $actor): bool
    {
        return true;
    }

    public function livewireComponent(): string
    {
        return 'low-home';
    }
}

class DashboardHomeHighStub implements DashboardHome
{
    public function source(): string
    {
        return 'high';
    }

    public function priority(): int
    {
        return 10;
    }

    public function visible(User $actor): bool
    {
        return true;
    }

    public function livewireComponent(): string
    {
        return 'high-home';
    }
}

class DashboardHomeHiddenStub implements DashboardHome
{
    public function source(): string
    {
        return 'hidden';
    }

    public function priority(): int
    {
        return 100;
    }

    public function visible(User $actor): bool
    {
        return false;
    }

    public function livewireComponent(): string
    {
        return 'hidden-home';
    }
}

class DashboardHomeReplacementStub implements DashboardHome
{
    public function source(): string
    {
        return 'low';
    }

    public function priority(): int
    {
        return 1;
    }

    public function visible(User $actor): bool
    {
        return true;
    }

    public function livewireComponent(): string
    {
        return 'low-home-v2';
    }
}

it('picks the visible home with the highest priority', function () {
    $registry = app(DashboardHomeRegistry::class);
    $registry->register('low', DashboardHomeLowStub::class);
    $registry->register('high', DashboardHomeHighStub::class);

    $actor = User::factory()->create();

    expect($registry->forActor($actor)?->source())->toBe('high');
});

it('returns null when no home is registered or visible', function () {
    $registry = app(DashboardHomeRegistry::class);

    $actor = User::factory()->create();

    expect($registry->forActor($actor))->toBeNull();

    $registry->register('hidden', DashboardHomeHiddenStub::class);

    expect($registry->forActor($actor))->toBeNull();
});

it('skips invisible homes and picks the best visible one', function () {
    $registry = app(DashboardHomeRegistry::class);
    $registry->register('hidden', DashboardHomeHiddenStub::class);
    $registry->register('low', DashboardHomeLowStub::class);

    $actor = User::factory()->create();

    expect($registry->forActor($actor)?->source())->toBe('low');
});

it('replaces the home of a source registered twice', function () {
    $registry = app(DashboardHomeRegistry::class);
    $registry->register('low', DashboardHomeLowStub::class);
    $registry->register('low', DashboardHomeReplacementStub::class);

    $actor = User::factory()->create();

    expect($registry->forActor($actor)?->livewireComponent())->toBe('low-home-v2');
});

it('rejects an empty source', function () {
    app(DashboardHomeRegistry::class)->register('', DashboardHomeLowStub::class);
})->throws(InvalidArgumentException::class, 'The dashboard home source must be a non-empty string.');

it('rejects a source that does not match the declared one', function () {
    app(DashboardHomeRegistry::class)->register('low', DashboardHomeHighStub::class);
})->throws(InvalidArgumentException::class, 'The dashboard home declares source [high] but was registered as [low].');

it('rejects a class that does not implement the contract', function () {
    app(DashboardHomeRegistry::class)->register('user', User::class);
})->throws(InvalidArgumentException::class, 'must implement App\Support\Dashboard\DashboardHome');
