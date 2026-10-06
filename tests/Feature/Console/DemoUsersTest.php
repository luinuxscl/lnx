<?php

use App\Enums\Role;
use App\Models\User;
use App\Support\DemoUsers;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role as SpatieRole;

beforeEach(function () {
    foreach (Role::cases() as $role) {
        SpatieRole::findOrCreate($role->value);
    }
});

it('creates the fixed demo accounts with the expected roles', function () {
    app(DemoUsers::class)->seedFixedAccounts();

    $admin = User::query()->where('email', DemoUsers::ADMIN_EMAIL)->first();

    expect($admin)->not->toBeNull()
        ->and($admin->name)->toBe('Samantha')
        ->and($admin->hasRole(Role::Admin))->toBeTrue()
        ->and($admin->email_verified_at)->not->toBeNull()
        ->and(Hash::check('password', $admin->password))->toBeTrue();

    $user = User::query()->where('email', DemoUsers::USER_EMAIL)->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Leonora')
        ->and($user->getRoleNames()->isEmpty())->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

it('is idempotent for the fixed demo accounts', function () {
    $users = app(DemoUsers::class);

    $users->seedFixedAccounts();
    $users->seedFixedAccounts();

    expect(User::query()->where('email', DemoUsers::ADMIN_EMAIL)->count())->toBe(1)
        ->and(User::query()->where('email', DemoUsers::USER_EMAIL)->count())->toBe(1);
});

it('creates sample users with an exact verified split and no roles', function () {
    $result = app(DemoUsers::class)->seedFakerUsers(100, 1234);

    expect($result)->toBe(['verified' => 90, 'unverified' => 10])
        ->and(User::query()->count())->toBe(100)
        ->and(User::query()->whereNotNull('email_verified_at')->count())->toBe(90)
        ->and(User::query()->whereNull('email_verified_at')->count())->toBe(10)
        ->and(User::query()->has('roles')->count())->toBe(0)
        ->and(Hash::check('password', User::query()->first()->password))->toBeTrue();
});

it('creates no sample users when the count is zero', function () {
    $result = app(DemoUsers::class)->seedFakerUsers(0);

    expect($result)->toBe(['verified' => 0, 'unverified' => 0])
        ->and(User::query()->count())->toBe(0);
});
