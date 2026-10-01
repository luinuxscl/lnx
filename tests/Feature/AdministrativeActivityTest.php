<?php

use App\Actions\IssueUserInvitation;
use App\Actions\ReactivateUser;
use App\Actions\RecordAdministrativeActivity;
use App\Actions\SuspendUser;
use App\Enums\AdministrativeAction;
use App\Enums\Role;
use App\Livewire\Activity\Index as ActivityIndex;
use App\Livewire\Settings\General;
use App\Livewire\Users\Edit;
use App\Livewire\Users\Index as UsersIndex;
use App\Models\AdministrativeActivity;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\UserInvitationNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function activityActor(Role $role = Role::Root): User
{
    $user = User::factory()->create();
    $user->assignRole($role->value);

    return $user;
}

it('restricts activity to root on the route and Livewire requests', function () {
    $this->get(route('admin.activity.index'))->assertRedirect(route('login'));
    foreach ([null, Role::Admin] as $role) {
        $user = $role === null ? User::factory()->create() : activityActor($role);
        $this->actingAs($user)->get(route('admin.activity.index'))->assertForbidden();
        Livewire::actingAs($user)->test(ActivityIndex::class)->assertForbidden();
    }
    $this->actingAs(activityActor())->get(route('admin.activity.index'))->assertOk();
});

it('records user data, role and password as separate safe events and skips a repeated edit', function () {
    $actor = activityActor();
    $user = User::factory()->create(['name' => 'Before']);
    Livewire::actingAs($actor)->test(Edit::class, ['user' => $user])
        ->set('name', 'After')->set('role', Role::Admin->value)->set('password', 'changed-password')
        ->set('password_confirmation', 'changed-password')->call('save')->assertHasNoErrors();

    expect(AdministrativeActivity::query()->get()->map(fn ($activity) => $activity->action->value)->sort()->values()->all())->toBe([
        AdministrativeAction::UserPasswordChanged->value,
        AdministrativeAction::UserRoleChanged->value,
        AdministrativeAction::UserUpdated->value,
    ]);
    expect(AdministrativeActivity::firstOrFail()->changes['name'])->toBe(['old' => 'Before', 'new' => 'After']);
    expect(AdministrativeActivity::where('action', AdministrativeAction::UserRoleChanged)->firstOrFail()->changes['role'])->toBe(['old' => null, 'new' => 'admin']);
    expect(AdministrativeActivity::where('action', AdministrativeAction::UserPasswordChanged)->firstOrFail()->changes)->toBe([]);
    expect(json_encode(AdministrativeActivity::all()->toArray()))->not->toContain('changed-password');

    Livewire::actingAs($actor)->test(Edit::class, ['user' => $user->fresh()])
        ->set('name', 'After')->set('role', Role::Admin->value)->call('save')->assertHasNoErrors();
    expect(AdministrativeActivity::count())->toBe(3);
});

it('retains actor and resource snapshots after deletion', function () {
    $actor = activityActor();
    $target = User::factory()->create(['name' => 'Departed']);
    Livewire::actingAs($actor)->test(UsersIndex::class)->call('deleteUser', $target->id)->assertHasNoErrors();
    $activity = AdministrativeActivity::firstOrFail();
    expect($activity->actor_name)->toBe($actor->name)->and($activity->resource_name)->toBe('Departed');
    $actor->delete();
    expect($activity->fresh()->actor_name)->toBe($activity->actor_name);
});

it('records invitation creation, transport and acceptance without secrets', function () {
    Notification::fake();
    $actor = activityActor(Role::Admin);
    app(IssueUserInvitation::class)->create($actor, 'audit@example.com', Role::Admin->value, 'es');
    $invitation = UserInvitation::firstOrFail();
    $notification = Notification::sent(new AnonymousNotifiable, UserInvitationNotification::class)->last();
    $url = $notification->url;
    $this->post($url, ['name' => 'Recipient', 'password' => 'password', 'password_confirmation' => 'password'])->assertRedirect(route('dashboard'));
    expect(AdministrativeActivity::where('action', AdministrativeAction::InvitationCreated)->firstOrFail()->actor_id)->toBe($actor->id);
    expect(AdministrativeActivity::where('action', AdministrativeAction::InvitationSent)->count())->toBe(1);
    $accepted = AdministrativeActivity::where('action', AdministrativeAction::InvitationAccepted)->firstOrFail();
    expect($accepted->actor_id)->toBe(User::where('email', 'audit@example.com')->firstOrFail()->id);
    expect(AdministrativeActivity::where('action', AdministrativeAction::UserCreated)->firstOrFail()->invitation_id)->toBe($invitation->id);
    expect(json_encode(AdministrativeActivity::all()->toArray()))->not->toContain($invitation->token_hash)->not->toContain($url);
    $actor->delete();
    expect($invitation->fresh()->invited_by)->toBeNull();
});

it('filters combined history and rejects invalid date ranges', function () {
    $root = activityActor();
    AdministrativeActivity::factory()->create(['actor_id' => 93, 'actor_name' => 'Olivia', 'action' => AdministrativeAction::UserUpdated, 'occurred_at' => '2026-10-01 23:59:59']);
    AdministrativeActivity::factory()->create(['actor_id' => 94, 'actor_name' => 'Other', 'action' => AdministrativeAction::UserDeleted, 'occurred_at' => '2026-10-01 12:00:00']);
    Livewire::actingAs($root)->test(ActivityIndex::class)
        ->set('actor', 'Olivia')->set('action', AdministrativeAction::UserUpdated->value)
        ->set('from', '2026-10-01')->set('to', '2026-10-01')
        ->assertSee('Olivia')->assertDontSee('Other');
    Livewire::actingAs($root)->test(ActivityIndex::class)
        ->set('from', '2026-10-02')->set('to', '2026-10-01')->assertSee(__('No administrative activity found.'));
});

it('rolls back a user edit when recording fails', function () {
    $actor = activityActor();
    $user = User::factory()->create(['name' => 'Before']);
    $this->mock(RecordAdministrativeActivity::class)->shouldReceive('handle')->andThrow(new RuntimeException('log unavailable'));
    try {
        Livewire::actingAs($actor)->test(Edit::class, ['user' => $user])->set('name', 'After')->call('save');
    } catch (RuntimeException) {
    }
    expect($user->fresh()->name)->toBe('Before');
});

it('preserves an old branding file and cache when the settings log fails', function () {
    Storage::fake('public');
    Storage::disk('public')->put('branding/old.png', 'old');
    $settings = Setting::factory()->create(['logo_path' => 'branding/old.png', 'site_title' => 'Before']);
    Setting::forgetCache();
    $actor = activityActor();
    $this->mock(RecordAdministrativeActivity::class)->shouldReceive('handle')->andThrow(new RuntimeException('log unavailable'));
    try {
        Livewire::actingAs($actor)->test(General::class)->set('site_title', 'After')->call('save');
    } catch (RuntimeException) {
    }
    expect($settings->fresh()->site_title)->toBe('Before');
    Storage::disk('public')->assertExists('branding/old.png');
    expect(Setting::current()->site_title)->toBe('Before');
});

it('records suspension and reactivation once each', function () {
    $actor = activityActor();
    $user = User::factory()->create();
    app(SuspendUser::class)->handle($actor, $user);
    app(SuspendUser::class)->handle($actor, $user);
    app(ReactivateUser::class)->handle($actor, $user);
    app(ReactivateUser::class)->handle($actor, $user);
    expect(AdministrativeActivity::where('action', AdministrativeAction::UserSuspended)->count())->toBe(1);
    expect(AdministrativeActivity::where('action', AdministrativeAction::UserReactivated)->count())->toBe(1);
    expect(AdministrativeActivity::where('action', AdministrativeAction::UserReactivated)->firstOrFail()->changes['suspended_at']['new'])->toBeNull();
});

it('records settings fields and relative image paths without duplicating unchanged saves', function () {
    Storage::fake('public');
    $actor = activityActor();
    Livewire::actingAs($actor)->test(General::class)
        ->set('site_title', 'New title')
        ->set('logo', UploadedFile::fake()->image('logo.png'))
        ->call('save')->assertHasNoErrors();
    $activity = AdministrativeActivity::where('action', AdministrativeAction::SettingsUpdated)->firstOrFail();
    expect($activity->changes['site_title']['new'])->toBe('New title');
    expect($activity->changes['logo_path']['new'])->toStartWith('branding/');
    Livewire::actingAs($actor)->test(General::class)->call('save')->assertHasNoErrors();
    expect(AdministrativeActivity::where('action', AdministrativeAction::SettingsUpdated)->count())->toBe(1);
    Livewire::actingAs($actor)->test(General::class)->call('removeFile', 'logo')->assertHasNoErrors();
    expect(AdministrativeActivity::where('action', AdministrativeAction::SettingsUpdated)->count())->toBe(2);
});

it('records failed transport and renewal separately without claiming delivery', function () {
    $actor = activityActor(Role::Admin);
    Notification::shouldReceive('route')->andThrow(new RuntimeException('SMTP unavailable'));
    expect(app(IssueUserInvitation::class)->create($actor, 'failed-log@example.com', null, 'es'))->toBeFalse();
    $invitation = UserInvitation::firstOrFail();
    expect($invitation->sent_at)->toBeNull();
    expect(AdministrativeActivity::where('action', AdministrativeAction::InvitationSendFailed)->count())->toBe(1);
    RateLimiter::clear('invite:minute:'.hash('sha256', 'failed-log@example.com'));
    Notification::fake();
    expect(app(IssueUserInvitation::class)->resend($actor, $invitation))->toBeTrue();
    expect(AdministrativeActivity::where('action', AdministrativeAction::InvitationRenewed)->count())->toBe(1);
    expect(AdministrativeActivity::where('action', AdministrativeAction::InvitationSent)->count())->toBe(1);
});

it('paginates and escapes historical names', function () {
    $root = activityActor();
    AdministrativeActivity::factory()->count(11)->create(['actor_id' => 77, 'actor_name' => '<script>alert(1)</script>']);
    $page = Livewire::actingAs($root)->test(ActivityIndex::class);
    $page->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', escape: false)
        ->assertDontSee('<script>alert(1)</script>', escape: false);
    expect(AdministrativeActivity::count())->toBe(11);
    $this->actingAs($root)->get(route('admin.activity.index', ['page' => 2]))->assertOk();
});

it('cleans a new upload and retains the old file when activity recording fails', function () {
    Storage::fake('public');
    Storage::disk('public')->put('branding/old.png', 'old');
    Setting::factory()->create(['logo_path' => 'branding/old.png']);
    Setting::forgetCache();
    $actor = activityActor();
    $this->mock(RecordAdministrativeActivity::class)->shouldReceive('handle')->andThrow(new RuntimeException('log unavailable'));
    try {
        Livewire::actingAs($actor)->test(General::class)
            ->set('logo', UploadedFile::fake()->image('new.png'))
            ->call('save');
    } catch (RuntimeException) {
    }
    expect(Storage::disk('public')->allFiles('branding'))->toBe(['branding/old.png']);
    expect(Setting::query()->firstOrFail()->logo_path)->toBe('branding/old.png');
});
