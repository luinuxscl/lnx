<?php

use App\Actions\ReactivateUser;
use App\Actions\SuspendUser;
use App\Enums\Role;
use App\Livewire\Users\Index;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Passkey;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('suspends and reactivates users without losing their data', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $target = User::factory()->withTwoFactor()->create(['avatar_path' => 'avatars/example.png']);
    $target->assignRole(Role::Admin);
    $passkey = $target->passkeys()->create([
        'name' => 'Work laptop',
        'credential_id' => 'test-credential',
        'credential' => [],
    ]);
    $password = $target->password;
    $token = $target->remember_token;

    app(SuspendUser::class)->handle($admin, $target);
    app(SuspendUser::class)->handle($admin, $target);

    $target->refresh();
    expect($target->isSuspended())->toBeTrue()
        ->and($target->auth_version)->toBe(1)
        ->and($target->remember_token)->not->toBe($token)
        ->and($target->password)->toBe($password)
        ->and($target->avatar_path)->toBe('avatars/example.png')
        ->and($target->two_factor_secret)->not->toBeNull()
        ->and($target->passkeys()->whereKey($passkey->id)->exists())->toBeTrue()
        ->and($target->hasRole(Role::Admin))->toBeTrue();

    app(ReactivateUser::class)->handle($admin, $target);
    app(ReactivateUser::class)->handle($admin, $target);

    expect($target->refresh()->isSuspended())->toBeFalse()
        ->and($target->auth_version)->toBe(1);
});

it('rejects suspending root or self even for a root actor', function () {
    $root = User::factory()->create();
    $root->assignRole(Role::Root);
    $otherRoot = User::factory()->create();
    $otherRoot->assignRole(Role::Root);

    expect(fn () => app(SuspendUser::class)->handle($root, $root))->toThrow(HttpException::class)
        ->and(fn () => app(SuspendUser::class)->handle($root, $otherRoot))->toThrow(HttpException::class);

    expect($root->refresh()->isSuspended())->toBeFalse()
        ->and($otherRoot->refresh()->isSuspended())->toBeFalse();

    expect(fn () => app(ReactivateUser::class)->handle($root, $root))
        ->toThrow(HttpException::class);
});

it('lets root suspend and reactivate an ordinary user', function () {
    $root = User::factory()->create();
    $root->assignRole(Role::Root);
    $target = User::factory()->create();

    app(SuspendUser::class)->handle($root, $target);
    expect($target->fresh()->isSuspended())->toBeTrue();

    app(ReactivateUser::class)->handle($root, $target);
    expect($target->fresh()->isSuspended())->toBeFalse();
});

it('forbids ordinary users from changing account status', function () {
    $actor = User::factory()->create();
    $target = User::factory()->create();

    expect(fn () => app(SuspendUser::class)->handle($actor, $target))
        ->toThrow(AuthorizationException::class);
    expect(fn () => app(ReactivateUser::class)->handle($actor, $target))
        ->toThrow(AuthorizationException::class);
});

it('blocks password login while suspended and permits a fresh login after reactivation', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $target = User::factory()->create();

    app(SuspendUser::class)->handle($admin, $target);
    $this->post(route('login.store'), ['email' => $target->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();

    app(ReactivateUser::class)->handle($admin, $target);
    $this->post(route('login.store'), ['email' => $target->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($target);
});

it('revokes an open session even after the account is reactivated', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $target = User::factory()->create();

    $this->withSession(['locale' => 'en'])
        ->post(route('login.store'), ['email' => $target->email, 'password' => 'password'])
        ->assertSessionHas('auth_access.web', ['id' => $target->id, 'version' => 0]);

    app(SuspendUser::class)->handle($admin, $target);
    app(ReactivateUser::class)->handle($admin, $target);
    $newToken = $target->fresh()->remember_token;
    Auth::forgetGuards();

    $this->get(route('dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('locale', 'en')
        ->assertSessionHas('status', 'Your session is no longer valid. Please sign in again.');
    $this->assertGuest();
    expect($target->fresh()->remember_token)->toBe($newToken);
});

it('invalidates a pending two factor challenge on suspension', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $target = User::factory()->withTwoFactor()->create();

    $this->post(route('login.store'), ['email' => $target->email, 'password' => 'password'])
        ->assertRedirect(route('two-factor.login'))
        ->assertSessionHas('login.auth_access', ['id' => $target->id, 'version' => 0]);

    app(SuspendUser::class)->handle($admin, $target);
    Auth::forgetGuards();
    $this->post(route('two-factor.login'), ['recovery_code' => 'recovery-code-1'])
        ->assertRedirect(route('login'));
    $this->assertGuest();
    expect($target->fresh()->recoveryCodes())->toContain('recovery-code-1');
});

it('does not revive an old two factor challenge after reactivation', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $target = User::factory()->withTwoFactor()->create();

    $this->post(route('login.store'), ['email' => $target->email, 'password' => 'password'])
        ->assertRedirect(route('two-factor.login'));
    app(SuspendUser::class)->handle($admin, $target);
    app(ReactivateUser::class)->handle($admin, $target);
    Auth::forgetGuards();

    $this->post(route('two-factor.login'), ['code' => '123456'])->assertRedirect(route('login'));
    $this->assertGuest();
});

it('keeps the login event generation when suspension interleaves with login', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $target = User::factory()->create();

    $this->post(route('login.store'), ['email' => $target->email, 'password' => 'password'])
        ->assertSessionHas('auth_access.web', ['id' => $target->id, 'version' => 0]);
    app(SuspendUser::class)->handle($admin, $target);
    app(ReactivateUser::class)->handle($admin, $target);

    expect(session('auth_access.web.version'))->toBe(0);
    Auth::forgetGuards();
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('filters the users list by status and supports reactivation', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $active = User::factory()->create(['name' => 'Target Active']);
    $suspended = User::factory()->create(['name' => 'Target Suspended']);
    app(SuspendUser::class)->handle($admin, $suspended);

    Livewire::actingAs($admin)->test(Index::class)
        ->set('search', 'Target')
        ->set('status', 'suspended')
        ->assertSee('Target Suspended')
        ->assertDontSee('Target Active')
        ->call('reactivateUser', $suspended->id)
        ->assertHasNoErrors();

    expect($suspended->refresh()->isSuspended())->toBeFalse();
});

it('requires a themed modal confirmation before changing account status', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $target = User::factory()->create(['name' => 'Taylor Example']);

    $component = Livewire::actingAs($admin)->test(Index::class)
        ->call('confirmStatusChange', $target->id, 'suspend')
        ->assertSet('showStatusModal', true)
        ->assertSee('¿Suspender a Taylor Example?')
        ->call('closeStatusModal')
        ->assertSet('showStatusModal', false);

    expect($target->fresh()->isSuspended())->toBeFalse();

    $component->call('confirmStatusChange', $target->id, 'suspend')
        ->call('applyStatusChange')
        ->assertSet('showStatusModal', false);

    expect($target->fresh()->isSuspended())->toBeTrue();

    $component->call('confirmStatusChange', $target->id, 'reactivate')
        ->assertSee('¿Reactivar a Taylor Example?')
        ->call('applyStatusChange')
        ->assertSet('showStatusModal', false);

    expect($target->fresh()->isSuspended())->toBeFalse();
});

it('normalizes unknown status filters and translates state labels', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    User::factory()->create(['name' => 'Visible Account']);

    Livewire::actingAs($admin)->withQueryParams(['status' => 'invalid'])->test(Index::class)
        ->assertSet('status', 'all')
        ->assertSee('Visible Account');

    $this->actingAs($admin)->withSession(['locale' => 'en'])
        ->get(route('admin.users.index'))
        ->assertSee('Active')
        ->assertSee('Status');

    $this->withSession(['locale' => 'es'])
        ->get(route('admin.users.index'))
        ->assertSee('Activo')
        ->assertSee('Estado');
});

it('paginates a filtered search by name and resets the page when status changes', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);

    foreach (range(1, 11) as $number) {
        $target = User::factory()->create(['name' => sprintf('Match %02d', $number)]);
        app(SuspendUser::class)->handle($admin, $target);
    }

    User::factory()->create(['name' => 'Match Active']);

    Livewire::actingAs($admin)->test(Index::class)
        ->set('search', 'Match')
        ->set('status', 'suspended')
        ->assertSee('Match 01')
        ->assertDontSee('Match 11')
        ->call('nextPage')
        ->assertSee('Match 11')
        ->set('status', 'active')
        ->assertSet('paginators.page', 1)
        ->assertSee('Match Active')
        ->assertDontSee('Match 11');
});

it('rejects an actual Livewire update before the profile mutation runs', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $target = User::factory()->create();

    $this->post(route('login.store'), ['email' => $target->email, 'password' => 'password']);
    $page = $this->get(route('profile.edit'))->assertOk();
    preg_match('/wire:snapshot="([^"]+)"/', $page->getContent(), $matches);
    expect($matches)->toHaveCount(2);
    $snapshot = html_entity_decode($matches[1], ENT_QUOTES);

    app(SuspendUser::class)->handle($admin, $target);
    Auth::forgetGuards();

    $this->postJson(route('default-livewire.update'), [
        'components' => [[
            'snapshot' => $snapshot,
            'updates' => ['name' => 'Changed Despite Suspension'],
            'calls' => [['method' => 'updateProfileInformation', 'params' => [], 'path' => '']],
        ]],
    ], ['X-Livewire' => 'true'])->assertUnauthorized();

    expect($target->refresh()->name)->not->toBe('Changed Despite Suspension');
});

it('accepts an old unmarked session only while its account version is zero', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $target = User::factory()->create();

    $this->actingAs($target)->get(route('dashboard'))->assertOk();

    app(SuspendUser::class)->handle($admin, $target);
    app(ReactivateUser::class)->handle($admin, $target);
    Auth::forgetGuards();

    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('revokes sessions across session drivers', function (string $driver) {
    if ($driver === 'file') {
        $sessionPath = sys_get_temp_dir().'/lnx-suspension-'.Str::random(12);
        File::ensureDirectoryExists($sessionPath);
        config(['session.files' => $sessionPath]);
    }

    config(['session.driver' => $driver]);
    $this->app['session']->forgetDrivers();

    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $target = User::factory()->create();

    $login = $this->post(route('login.store'), ['email' => $target->email, 'password' => 'password'])
        ->assertSessionHas('auth_access.web');
    $sessionCookie = collect($login->headers->getCookies())
        ->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));
    expect($sessionCookie)->not->toBeNull();

    if ($driver === 'database') {
        expect(DB::table('sessions')->count())->toBeGreaterThan(0);
    }

    if ($driver === 'file') {
        expect(count(File::files($sessionPath)))->toBeGreaterThan(0);
    }

    app(SuspendUser::class)->handle($admin, $target);
    Auth::forgetGuards();

    if ($driver !== 'array') {
        $this->app['session']->forgetDrivers();
    }

    $this->withCookie($sessionCookie->getName(), $sessionCookie->getValue())
        ->get(route('dashboard'))
        ->assertRedirect(route('login'));
    expect($target->refresh()->isSuspended())->toBeTrue();

    if (isset($sessionPath)) {
        File::deleteDirectory($sessionPath);
    }
})->with(['array', 'database', 'file', 'cookie']);

it('does not restore a previous remember cookie after reactivation', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $target = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $target->email,
        'password' => 'password',
        'remember' => true,
    ]);
    $recallerName = Auth::guard('web')->getRecallerName();
    $recaller = collect($response->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === $recallerName);
    expect($recaller)->not->toBeNull();

    app(SuspendUser::class)->handle($admin, $target);
    app(ReactivateUser::class)->handle($admin, $target);

    Auth::forgetGuards();
    $this->flushSession();
    $this->withCookie($recallerName, $recaller->getValue())
        ->get(route('dashboard'))
        ->assertRedirect(route('login'));

    $freshLogin = $this->post(route('login.store'), [
        'email' => $target->email,
        'password' => 'password',
        'remember' => true,
    ])->assertRedirect(route('dashboard', absolute: false));

    $newRecaller = collect($freshLogin->headers->getCookies())
        ->first(fn ($cookie) => $cookie->getName() === $recallerName);
    expect($newRecaller)->not->toBeNull()
        ->and($newRecaller->getValue())->not->toBe($recaller->getValue());
});

it('rejects a verified passkey for a suspended account through the login controller', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $target = User::factory()->create();
    $passkey = new Passkey;
    $passkey->setRelation('user', $target);

    $verifier = Mockery::mock(VerifyPasskey::class);
    $verifier->shouldReceive('__invoke')->andReturn($passkey);
    $this->app->instance(VerifyPasskey::class, $verifier);

    $credential = [
        'id' => 'YWJj',
        'rawId' => 'YWJj',
        'type' => 'public-key',
        'response' => [
            'clientDataJSON' => rtrim(strtr(base64_encode(json_encode([
                'type' => 'webauthn.get',
                'challenge' => 'YWJj',
                'origin' => config('app.url'),
            ])), '+/', '-_'), '='),
            'authenticatorData' => rtrim(strtr(base64_encode(str_repeat("\0", 37)), '+/', '-_'), '='),
            'signature' => 'YWJj',
        ],
    ];

    $this->get('/passkeys/login/options')->assertOk();
    $this->postJson('/passkeys/login', ['credential' => $credential])->assertOk();
    $this->assertAuthenticatedAs($target);

    $this->post(route('logout'));
    Auth::forgetGuards();
    app(SuspendUser::class)->handle($admin, $target);
    $passkey->setRelation('user', $target->fresh());

    $this->get('/passkeys/login/options')->assertOk();
    $this->postJson('/passkeys/login', ['credential' => $credential])->assertUnprocessable();
    $this->assertGuest();

    app(ReactivateUser::class)->handle($admin, $target);
    $passkey->setRelation('user', $target->fresh());
    $this->get('/passkeys/login/options')->assertOk();
    $this->postJson('/passkeys/login', ['credential' => $credential])->assertOk();
    $this->assertAuthenticatedAs($target);
});

it('rejects a login whose account was suspended and reactivated before the guard creates its session', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $target = User::factory()->create();
    $previousCallback = Fortify::$authenticateUsingCallback;

    Fortify::authenticateUsing(function () use ($admin, $target): User {
        app(SuspendUser::class)->handle($admin, $target);
        app(ReactivateUser::class)->handle($admin, $target);

        return $target;
    });

    try {
        $this->post(route('login.store'), ['email' => $target->email, 'password' => 'password'])
            ->assertRedirect(route('login'));
        $this->assertGuest();
    } finally {
        Fortify::$authenticateUsingCallback = $previousCallback;
    }
});
