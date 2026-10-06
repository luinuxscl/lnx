<?php

use App\Enums\Role;
use App\Models\User;
use App\Support\DemoUsers;
use Illuminate\Console\Command;

beforeEach(function () {
    config()->set('authorization.root_user.email', null);
    config()->set('authorization.root_user.password', null);
});

it('refuses to run in production', function () {
    $environment = $this->app['env'];
    $this->app['env'] = 'production';

    try {
        $this->artisan('lnx:reset', ['--force' => true])
            ->assertExitCode(Command::FAILURE);

        expect(User::query()->count())->toBe(0);
    } finally {
        $this->app['env'] = $environment;
    }
});

it('seeds the fixed accounts and sample users without migrating', function () {
    $this->artisan('lnx:reset', [
        '--no-fresh' => true,
        '--no-cache' => true,
        '--force' => true,
        '--faker' => 20,
        '--seed' => 4321,
    ])->assertExitCode(Command::SUCCESS);

    $admin = User::query()->where('email', DemoUsers::ADMIN_EMAIL)->first();
    $user = User::query()->where('email', DemoUsers::USER_EMAIL)->first();

    expect($admin)->not->toBeNull()
        ->and($admin->hasRole(Role::Admin))->toBeTrue()
        ->and($user)->not->toBeNull()
        ->and($user->getRoleNames()->isEmpty())->toBeTrue()
        ->and(User::query()->count())->toBe(22)
        ->and(User::query()->whereNull('email_verified_at')->count())->toBe(2);
});

it('can skip the sample users when faker is zero', function () {
    $this->artisan('lnx:reset', [
        '--no-fresh' => true,
        '--no-cache' => true,
        '--force' => true,
        '--faker' => 0,
    ])->assertExitCode(Command::SUCCESS);

    expect(User::query()->count())->toBe(2);
});
