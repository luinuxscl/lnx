<?php

use App\Actions\IssueUserInvitation;
use App\Enums\Role;
use App\Livewire\Settings\General;
use App\Livewire\Users\Invitations;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\UserInvitationNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Notification::fake();
    RateLimiter::clear('invite:actor:1');
});

function invitationActor(Role $role = Role::Admin): User
{
    $actor = User::factory()->create();
    $actor->assignRole($role->value);

    return $actor;
}

function sentInvitationUrl(): string
{
    $notification = Notification::sent(new AnonymousNotifiable, UserInvitationNotification::class)->last();

    return $notification->url;
}

it('accepts an invitation once with only the authorized role and verified email', function () {
    $actor = invitationActor();
    app(IssueUserInvitation::class)->create($actor, 'INVITED@example.com', Role::Admin->value, 'es');
    $invitation = UserInvitation::firstOrFail();
    $url = sentInvitationUrl();

    expect($invitation->email)->toBe('invited@example.com')
        ->and($invitation->token_hash)->not->toBe(parse_url($url, PHP_URL_PATH));

    $this->get($url)->assertOk()->assertSee('invited@example.com');
    $this->post($url, ['name' => 'Invited Person', 'password' => 'password', 'password_confirmation' => 'password'])
        ->assertRedirect(route('dashboard'));

    $user = User::where('email', 'invited@example.com')->firstOrFail();
    expect($user->hasRole(Role::Admin))->toBeTrue()
        ->and($user->hasRole(Role::Root))->toBeFalse()
        ->and($user->email_verified_at)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
    $this->post($url, ['name' => 'Second', 'password' => 'password', 'password_confirmation' => 'password'])
        ->assertSessionHasErrors('invitation');
    expect(User::where('email', 'invited@example.com')->count())->toBe(1);
});

it('rotates the token when resent and rejects the previous link', function () {
    $actor = invitationActor();
    $issue = app(IssueUserInvitation::class);
    $issue->create($actor, 'rotate@example.com', null, 'en');
    $oldUrl = sentInvitationUrl();
    RateLimiter::clear('invite:minute:'.hash('sha256', 'rotate@example.com'));
    $issue->resend($actor, UserInvitation::firstOrFail());
    $newUrl = sentInvitationUrl();

    expect($newUrl)->not->toBe($oldUrl);
    $this->get($oldUrl)->assertOk()->assertSee(__('This invitation is no longer available.'));
    $this->post($oldUrl, ['name' => 'Old', 'password' => 'password', 'password_confirmation' => 'password'])
        ->assertSessionHasErrors('invitation');
    $this->post($newUrl, ['name' => 'New', 'password' => 'password', 'password_confirmation' => 'password'])
        ->assertRedirect(route('dashboard'));
});

it('rejects revoked and expired links', function () {
    $actor = invitationActor();
    $issue = app(IssueUserInvitation::class);
    $issue->create($actor, 'revoke@example.com', null, 'es');
    $url = sentInvitationUrl();
    Livewire::actingAs($actor)->test(Invitations::class)->call('revoke', UserInvitation::firstOrFail()->id);
    auth()->logout();
    $this->post($url, ['name' => 'No', 'password' => 'password', 'password_confirmation' => 'password'])
        ->assertSessionHasErrors('invitation');

    $invitation = UserInvitation::firstOrFail();
    $invitation->update(['revoked_at' => null, 'expires_at' => now()->subSecond()]);
    $this->post($url, ['name' => 'No', 'password' => 'password', 'password_confirmation' => 'password'])
        ->assertSessionHasErrors('invitation');
    expect(User::where('email', 'revoke@example.com')->exists())->toBeFalse();
});

it('never overwrites an account created before acceptance', function () {
    $actor = invitationActor();
    app(IssueUserInvitation::class)->create($actor, 'existing@example.com', Role::Admin->value, 'es');
    $url = sentInvitationUrl();
    $existing = User::factory()->create(['email' => 'existing@example.com', 'password' => 'original-password']);
    $originalPassword = $existing->password;

    $this->post($url, ['name' => 'Changed', 'password' => 'password', 'password_confirmation' => 'password'])
        ->assertSessionHasErrors('invitation');
    expect($existing->fresh()->password)->toBe($originalPassword)
        ->and($existing->fresh()->hasRole(Role::Admin))->toBeFalse();
});

it('blocks public registration in invitation mode but allows a valid invitation', function () {
    $actor = invitationActor(Role::Root);
    app(IssueUserInvitation::class)->create($actor, 'closed@example.com', null, 'es');
    $url = sentInvitationUrl();

    Livewire::actingAs($actor)->test(General::class)
        ->set('site_title', 'Invitation Site')
        ->set('registration_mode', 'invitation_only')
        ->call('save')->assertHasNoErrors();
    auth()->logout();

    $this->get(route('register'))->assertForbidden();
    $this->post(route('register.store'), ['name' => 'Public', 'email' => 'public@example.com', 'password' => 'password', 'password_confirmation' => 'password'])
        ->assertForbidden();
    expect(Setting::registrationIsOpen())->toBeFalse();
    $this->get(route('login'))->assertOk()->assertDontSee(route('register'));
    $this->post($url, ['name' => 'Invited', 'password' => 'password', 'password_confirmation' => 'password'])
        ->assertRedirect(route('dashboard'));
});

it('limits repeated sends even when a previous send succeeded', function () {
    $actor = invitationActor();
    $issue = app(IssueUserInvitation::class);
    $issue->create($actor, 'limited@example.com', null, 'es');

    expect(fn () => $issue->resend($actor, UserInvitation::firstOrFail()))
        ->toThrow(ValidationException::class);
    Notification::assertSentOnDemandTimes(UserInvitationNotification::class, 1);
});

it('rejects duplicate invitations and registered emails', function () {
    $actor = invitationActor();
    $issue = app(IssueUserInvitation::class);
    $issue->create($actor, 'pending@example.com', null, 'es');
    RateLimiter::clear('invite:minute:'.hash('sha256', 'pending@example.com'));

    expect(fn () => $issue->create($actor, 'pending@example.com', null, 'es'))
        ->toThrow(ValidationException::class);

    $existing = User::factory()->create();
    expect(fn () => $issue->create($actor, $existing->email, null, 'es'))
        ->toThrow(ValidationException::class);
});

it('does not let ordinary users manage invitations', function () {
    $actor = invitationActor();
    app(IssueUserInvitation::class)->create($actor, 'guarded@example.com', null, 'es');
    $ordinary = User::factory()->create();

    $this->actingAs($ordinary)->get(route('admin.users.invitations'))->assertForbidden();
    expect($ordinary->can('create', UserInvitation::class))->toBeFalse();
    expect(fn () => app(IssueUserInvitation::class)->create($ordinary, 'another@example.com', null, 'es'))
        ->toThrow(HttpException::class);
    expect(UserInvitation::count())->toBe(1);
});

it('renders the selected email language and preserves it on resend', function () {
    $actor = invitationActor();
    $issue = app(IssueUserInvitation::class);
    $issue->create($actor, 'spanish@example.com', null, 'es');
    $spanish = Notification::sent(new AnonymousNotifiable, UserInvitationNotification::class)->last();
    expect($spanish->toMail(new AnonymousNotifiable)->subject)
        ->toContain('Invitación para unirse');

    $issue->create($actor, 'language@example.com', null, 'en');
    $notification = Notification::sent(new AnonymousNotifiable, UserInvitationNotification::class)->last();
    expect($notification->toMail(new AnonymousNotifiable)->subject)
        ->toContain('Invitation to join');

    RateLimiter::clear('invite:minute:'.hash('sha256', 'language@example.com'));
    $englishInvitation = UserInvitation::where('email', 'language@example.com')->firstOrFail();
    $issue->resend($actor, $englishInvitation);
    expect($englishInvitation->refresh()->locale)->toBe('en');
});

it('keeps open registration as the default without a settings row', function () {
    expect(Setting::registrationIsOpen())->toBeTrue();
    $this->get(route('register'))->assertOk();
});

it('keeps a failed delivery visible without reporting success', function () {
    $actor = invitationActor();
    Notification::shouldReceive('send')->once()->andThrow(new RuntimeException('SMTP unavailable'));

    expect(app(IssueUserInvitation::class)->create($actor, 'failed@example.com', null, 'es'))->toBeFalse();
    $invitation = UserInvitation::firstOrFail();
    expect($invitation->send_failed_at)->not->toBeNull()
        ->and($invitation->sent_at)->toBeNull()
        ->and($invitation->status())->toBe('Send failed');
});

it('blocks an invite with a forged root role at acceptance', function () {
    $actor = invitationActor();
    app(IssueUserInvitation::class)->create($actor, 'forged@example.com', null, 'es');
    $url = sentInvitationUrl();
    UserInvitation::firstOrFail()->update(['role' => Role::Root->value]);

    $this->post($url, ['name' => 'Forged', 'password' => 'password', 'password_confirmation' => 'password'])
        ->assertSessionHasErrors('invitation');
    expect(User::where('email', 'forged@example.com')->exists())->toBeFalse();
});

it('allows a fresh invitation after expiry and keeps the old token invalid', function () {
    $actor = invitationActor();
    $issue = app(IssueUserInvitation::class);
    $issue->create($actor, 'expired@example.com', null, 'es');
    $oldUrl = sentInvitationUrl();
    UserInvitation::firstOrFail()->update(['expires_at' => now()->subSecond()]);
    RateLimiter::clear('invite:minute:'.hash('sha256', 'expired@example.com'));

    expect($issue->create($actor, 'expired@example.com', null, 'es'))->toBeTrue();
    expect(UserInvitation::count())->toBe(2);
    $this->post($oldUrl, ['name' => 'Old', 'password' => 'password', 'password_confirmation' => 'password'])
        ->assertSessionHasErrors('invitation');
});

it('invalidates the old link even when resending fails', function () {
    $actor = invitationActor();
    $issue = app(IssueUserInvitation::class);
    $issue->create($actor, 'resend-failure@example.com', null, 'es');
    $oldUrl = sentInvitationUrl();
    $invitation = UserInvitation::firstOrFail();
    $oldHash = $invitation->token_hash;
    RateLimiter::clear('invite:minute:'.hash('sha256', $invitation->email));
    Notification::shouldReceive('send')->once()->andThrow(new RuntimeException('SMTP unavailable'));

    expect($issue->resend($actor, $invitation))->toBeFalse();
    expect($invitation->refresh()->token_hash)->not->toBe($oldHash)
        ->and($invitation->send_failed_at)->not->toBeNull();
    $this->post($oldUrl, ['name' => 'Old', 'password' => 'password', 'password_confirmation' => 'password'])
        ->assertSessionHasErrors('invitation');
});

it('filters the invitation list by status', function () {
    $actor = invitationActor();
    UserInvitation::factory()->create(['email' => 'pending@example.com', 'invited_by' => $actor->id]);
    UserInvitation::factory()->create(['email' => 'accepted@example.com', 'invited_by' => $actor->id, 'accepted_at' => now()]);
    UserInvitation::factory()->create(['email' => 'expired@example.com', 'invited_by' => $actor->id, 'expires_at' => now()->subDay()]);
    UserInvitation::factory()->create(['email' => 'revoked@example.com', 'invited_by' => $actor->id, 'revoked_at' => now()]);
    UserInvitation::factory()->create(['email' => 'failed@example.com', 'invited_by' => $actor->id, 'send_failed_at' => now()]);

    Livewire::actingAs($actor)->test(Invitations::class)
        ->set('status', 'pending')
        ->assertSee('pending@example.com')
        ->assertDontSee('accepted@example.com')
        ->assertDontSee('expired@example.com')
        ->assertDontSee('revoked@example.com')
        ->assertDontSee('failed@example.com');

    Livewire::actingAs($actor)->test(Invitations::class)
        ->set('status', 'send_failed')
        ->assertSee('failed@example.com')
        ->assertDontSee('pending@example.com');

    Livewire::actingAs($actor)->test(Invitations::class)
        ->set('status', 'expired')
        ->assertSee('expired@example.com')
        ->assertDontSee('accepted@example.com');
});

it('searches invitations by email', function () {
    $actor = invitationActor();
    UserInvitation::factory()->create(['email' => 'alpha@example.com', 'invited_by' => $actor->id]);
    UserInvitation::factory()->create(['email' => 'beta@example.com', 'invited_by' => $actor->id]);

    Livewire::actingAs($actor)->test(Invitations::class)
        ->set('search', 'alpha')
        ->assertSee('alpha@example.com')
        ->assertDontSee('beta@example.com');
});

it('sorts the invitation list by email and expiry', function () {
    $actor = invitationActor();
    UserInvitation::factory()->create(['email' => 'charlie@example.com', 'invited_by' => $actor->id, 'expires_at' => now()->addDays(3)]);
    UserInvitation::factory()->create(['email' => 'alpha@example.com', 'invited_by' => $actor->id, 'expires_at' => now()->addDays(1)]);
    UserInvitation::factory()->create(['email' => 'bravo@example.com', 'invited_by' => $actor->id, 'expires_at' => now()->addDays(2)]);

    Livewire::actingAs($actor)->test(Invitations::class)
        ->set('sortBy', 'email')
        ->set('sortDirection', 'asc')
        ->assertSeeInOrder(['alpha@example.com', 'bravo@example.com', 'charlie@example.com']);

    Livewire::actingAs($actor)->test(Invitations::class)
        ->set('sortBy', 'expires')
        ->set('sortDirection', 'asc')
        ->assertSeeInOrder(['alpha@example.com', 'bravo@example.com', 'charlie@example.com']);
});

it('normalizes an unknown sort column and page size in the invitation list', function () {
    $actor = invitationActor();

    Livewire::actingAs($actor)->test(Invitations::class)
        ->set('sortBy', 'token_hash')
        ->assertSet('sortBy', 'created');

    Livewire::actingAs($actor)->test(Invitations::class)
        ->set('status', 'bogus')
        ->assertSet('status', 'all');

    Livewire::actingAs($actor)->test(Invitations::class)
        ->set('perPage', 999)
        ->assertSet('perPage', 10);

    Livewire::actingAs($actor)->test(Invitations::class)
        ->set('perPage', 25)
        ->assertSet('perPage', 25);
});

it('shows the invitation quick filters as clickable stat cards', function () {
    $actor = invitationActor();

    $this->actingAs($actor)->get(route('admin.users.invitations'))
        ->assertOk()
        ->assertSee(__('Pending'))
        ->assertSee(__('Accepted'))
        ->assertSee(__('Expired'))
        ->assertSee(__('Revoked'))
        ->assertSee(__('Send failed'))
        ->assertSee(route('admin.users.invitations', ['status' => 'pending']), false);
});
