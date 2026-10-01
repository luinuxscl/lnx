<?php

use App\Livewire\Settings\Security;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    config(['session.driver' => 'database']);
    $this->app['session']->forgetDrivers();
});

/**
 * Seed a row in the sessions table.
 */
function seedSession(string $id, int $userId, string $userAgent, string $ip = '10.0.0.1', ?int $lastActivity = null): void
{
    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $userId,
        'ip_address' => $ip,
        'user_agent' => $userAgent,
        'payload' => '',
        'last_activity' => $lastActivity ?? now()->timestamp,
    ]);
}

test('lists active sessions of the current user and marks the current device', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Livewire::test(Security::class);
    $currentId = session()->getId();

    seedSession($currentId, $user->id, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    seedSession('other-session', $user->id, 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1', '10.0.0.2', now()->subMinute()->timestamp);

    $foreign = User::factory()->create();
    seedSession('foreign-session', $foreign->id, 'Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0', '10.0.0.3');

    $component->call('loadSessions')
        ->assertSet('canManageSessions', true)
        ->assertSee('Chrome')
        ->assertSee('Safari')
        ->assertSee(__('This device'))
        ->assertDontSee('10.0.0.3');

    $sessions = $component->get('sessions');

    expect($sessions)->toHaveCount(2)
        ->and(collect($sessions)->pluck('id')->all())->toBe([$currentId, 'other-session'])
        ->and($sessions[0]['is_current'])->toBeTrue();
});

test('logs out other sessions, keeps the current one and rotates the remember token', function () {
    $user = User::factory()->create(['remember_token' => 'old-token']);
    $this->actingAs($user);

    $component = Livewire::test(Security::class);
    $currentId = session()->getId();

    seedSession($currentId, $user->id, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36');
    seedSession('other-session', $user->id, 'Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0', '10.0.0.4');

    session(['auth.password_confirmed_at' => time()]);

    $component->set('showLogoutOthersModal', true)
        ->call('logoutOtherSessions')
        ->assertSet('showLogoutOthersModal', false);

    expect(DB::table('sessions')->where('user_id', $user->id)->pluck('id')->all())->toBe([$currentId])
        ->and($user->refresh()->remember_token)->not->toBe('old-token');
});

test('requires a fresh identity confirmation before logging out other sessions', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Livewire::test(Security::class);
    $currentId = session()->getId();

    seedSession($currentId, $user->id, 'Mozilla/5.0 Chrome/120.0.0.0 Safari/537.36');
    seedSession('other-session', $user->id, 'Mozilla/5.0 Firefox/120.0', '10.0.0.5');

    $component->set('showLogoutOthersModal', true)
        ->call('logoutOtherSessions')
        ->assertRedirect(route('password.confirm'))
        ->assertSessionHas('security.logout_others_pending', true);

    expect(DB::table('sessions')->where('id', 'other-session')->exists())->toBeTrue();
});

test('opens the confirmation modal when identity was confirmed recently', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    session(['auth.password_confirmed_at' => time()]);

    Livewire::test(Security::class)
        ->call('confirmLogoutOtherSessions')
        ->assertSet('showLogoutOthersModal', true);
});

test('reopens the confirmation modal after the password confirmation round trip', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    session(['security.logout_others_pending' => true]);

    Livewire::test(Security::class)
        ->assertSet('showLogoutOthersModal', true);

    expect(session()->has('security.logout_others_pending'))->toBeFalse();
});

test('shows a limitation notice when the session driver is not database', function () {
    config(['session.driver' => 'file']);
    $this->app['session']->forgetDrivers();

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Security::class)
        ->assertSet('canManageSessions', false)
        ->assertSee(__('Session management is unavailable because the application is not using the database session driver.'))
        ->assertDontSee(__('Log out other browser sessions'));
});

test('cannot log out other sessions when the session driver is not database', function () {
    config(['session.driver' => 'file']);
    $this->app['session']->forgetDrivers();

    $user = User::factory()->create();
    $this->actingAs($user);

    session(['auth.password_confirmed_at' => time()]);

    Livewire::test(Security::class)
        ->call('logoutOtherSessions')
        ->assertStatus(403);
});

test('a revoked session loses access on the next protected request', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $login = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $sessionCookie = collect($login->headers->getCookies())
        ->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));
    expect($sessionCookie)->not->toBeNull();

    $this->withCookie($sessionCookie->getName(), $sessionCookie->getValue())
        ->get(route('dashboard'))
        ->assertOk();

    DB::table('sessions')->where('user_id', $user->id)->delete();

    $this->flushSession();
    Auth::forgetGuards();

    $this->withCookie($sessionCookie->getName(), $sessionCookie->getValue())
        ->get(route('dashboard'))
        ->assertRedirect(route('login'));
});
