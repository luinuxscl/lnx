<?php

use App\Enums\Role;
use App\Livewire\Setup\Index as SetupIndex;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\SetupTestEmailNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Notifications\Dispatcher as NotificationDispatcher;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

class TestableSetupIndex extends SetupIndex
{
    public static bool $linkExists = true;

    public static ?bool $writable = null;

    protected function storageLinkExists(): bool
    {
        return static::$linkExists;
    }

    protected function storageIsWritable(): bool
    {
        return static::$writable ?? parent::storageIsWritable();
    }
}

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('public');
    Notification::fake();
    TestableSetupIndex::$linkExists = true;
    TestableSetupIndex::$writable = null;
});

function setupRoot(): User
{
    $root = User::factory()->create();
    $root->assignRole(Role::Root->value);

    return $root;
}

it('redirects guests to the login page', function () {
    $this->get(route('admin.setup.index'))->assertRedirect(route('login'));
});

it('forbids users without the root role', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.setup.index'))->assertForbidden();

    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $this->actingAs($admin)->get(route('admin.setup.index'))->assertForbidden();
});

it('shows the checklist to root users', function () {
    $this->actingAs(setupRoot())
        ->get(route('admin.setup.index'))
        ->assertOk()
        ->assertSee('Checklist de puesta en marcha');
});

it('shows the checklist item in the sidebar only for root', function () {
    $root = setupRoot();
    $this->actingAs($root)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Checklist de puesta en marcha')
        ->assertSee('admin/setup');

    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Checklist de puesta en marcha');
});

it('marks the site identity as pending until the title and admin email are set', function () {
    Livewire::actingAs(setupRoot())
        ->test(SetupIndex::class)
        ->assertSet('steps.identity.status', 'pending');
});

it('marks the site identity as verified with a title and a valid admin email', function () {
    Setting::factory()->create(['site_title' => 'My Site', 'admin_email' => 'admin@example.com']);

    Livewire::actingAs(setupRoot())
        ->test(SetupIndex::class)
        ->assertSet('steps.identity.status', 'verified');
});

it('sends a test email and persists the accepted result', function () {
    Setting::factory()->create(['admin_email' => 'admin@example.com']);

    Livewire::actingAs(setupRoot())
        ->test(SetupIndex::class)
        ->call('sendTestEmail')
        ->assertHasNoErrors()
        ->assertSet('steps.email.status', 'verified');

    Notification::assertSentOnDemand(
        SetupTestEmailNotification::class,
        fn ($notification, $channels, $notifiable): bool => $notifiable->routes['mail'] === 'admin@example.com',
    );

    $settings = Setting::firstOrFail();

    expect($settings->test_email_sent_at)->not->toBeNull()
        ->and($settings->test_email_sent_to)->toBe('admin@example.com')
        ->and($settings->test_email_failed_at)->toBeNull();
});

it('marks the test email as pending again when the admin email changed', function () {
    Setting::factory()->create([
        'admin_email' => 'new@example.com',
        'test_email_sent_at' => now()->subDay(),
        'test_email_sent_to' => 'old@example.com',
    ]);

    Livewire::actingAs(setupRoot())
        ->test(SetupIndex::class)
        ->assertSet('steps.email.status', 'pending');
});

function throwingNotifier(): NotificationDispatcher
{
    return new class implements NotificationDispatcher
    {
        public function send($notifiables, $notification)
        {
            throw new RuntimeException('transport credentials leaked here');
        }

        public function sendNow($notifiables, $notification, ?array $channels = null)
        {
            throw new RuntimeException('transport credentials leaked here');
        }
    };
}

it('marks the test email as error when the transport fails without leaking the exception', function () {
    Setting::factory()->create(['admin_email' => 'admin@example.com']);
    $this->app->instance(ChannelManager::class, throwingNotifier());

    $component = Livewire::actingAs(setupRoot())
        ->test(SetupIndex::class)
        ->call('sendTestEmail')
        ->assertSet('steps.email.status', 'error');

    expect($component->html())->not->toContain('transport credentials leaked here');

    expect(Setting::firstOrFail()->test_email_failed_at)->not->toBeNull();
});

it('reports the transport exception instead of surfacing it', function () {
    Setting::factory()->create(['admin_email' => 'admin@example.com']);
    $this->actingAs(setupRoot());
    $this->app->instance(ChannelManager::class, throwingNotifier());

    $handler = Mockery::mock(ExceptionHandler::class);
    $handler->shouldReceive('report')->once();
    $handler->shouldIgnoreMissing();
    $this->app->instance(ExceptionHandler::class, $handler);

    $component = new SetupIndex;
    $component->mount();

    try {
        $component->sendTestEmail();
    } catch (Throwable) {
        // Flux::toast requires a live request context; report runs first.
    }

    expect($component->steps['email']['status'])->toBe('error');
});

it('rate limits the test email after five attempts', function () {
    $root = setupRoot();
    Setting::factory()->create(['admin_email' => 'admin@example.com']);
    RateLimiter::clear('setup-test-email:'.$root->id);

    $component = Livewire::actingAs($root)->test(SetupIndex::class);

    for ($i = 0; $i < 5; $i++) {
        $component->call('sendTestEmail')->assertHasNoErrors();
    }

    $component->call('sendTestEmail')->assertHasErrors('test_email');

    Notification::assertSentOnDemandTimes(SetupTestEmailNotification::class, 5);
});

it('marks storage as verified and leaves no probe files behind', function () {
    Livewire::actingAs(setupRoot())
        ->test(TestableSetupIndex::class)
        ->assertSet('steps.storage.status', 'verified');

    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('marks storage as error when the public link is missing', function () {
    TestableSetupIndex::$linkExists = false;

    Livewire::actingAs(setupRoot())
        ->test(TestableSetupIndex::class)
        ->assertSet('steps.storage.status', 'error')
        ->assertSet('steps.storage.linked', false);
});

it('marks storage as error when the public disk is not writable', function () {
    TestableSetupIndex::$writable = false;

    Livewire::actingAs(setupRoot())
        ->test(TestableSetupIndex::class)
        ->assertSet('steps.storage.status', 'error')
        ->assertSet('steps.storage.writable', false);
});

it('marks registration as pending until settings are saved', function () {
    Livewire::actingAs(setupRoot())
        ->test(SetupIndex::class)
        ->assertSet('steps.registration.status', 'pending');
});

it('marks registration as verified and reflects the current mode', function () {
    Setting::factory()->create(['registration_mode' => 'invitation_only']);

    Livewire::actingAs(setupRoot())
        ->test(SetupIndex::class)
        ->assertSet('steps.registration.status', 'verified')
        ->assertSee('Solo invitaciones');
});
