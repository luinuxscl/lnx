<?php

use App\Enums\Role;
use App\Events\SupportPublicReplyAdded;
use App\Events\SupportTicketCreated;
use App\Events\SupportTicketStatusChanged;
use App\Livewire\Support\Panel;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\TicketService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('allows every authenticated account, including an unverified account, to reach its requests', function () {
    $this->get(route('support.index'))->assertRedirect(route('login'));
    $user = User::factory()->unverified()->create();
    $this->actingAs($user)->get(route('support.index'))->assertOk();
});

it('creates a request with private image, alerts root once, and isolates other users', function () {
    Storage::fake('local');
    Event::fake([SupportTicketCreated::class]);
    $root = User::factory()->create();
    $root->assignRole(Role::Root);
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $image = UploadedFile::fake()->image('capture.png');

    Livewire::actingAs($owner)->test(Panel::class)
        ->call('open', ['screen' => 'dashboard', 'width' => 1280, 'height' => 720, 'theme' => 'dark'])
        ->set('type', 'problem')->set('title', 'Cannot save')->set('description', 'The save button does nothing')
        ->set('image', $image)->call('submit')->assertHasNoErrors();

    $ticket = SupportTicket::firstOrFail();
    $attachment = $ticket->attachments()->firstOrFail();
    expect($ticket->user_id)->toBe($owner->id)
        ->and($ticket->route_name)->toBe('')
        ->and($ticket->screen)->toBe('dashboard')
        ->and($root->notifications()->count())->toBe(1);
    Storage::disk('local')->assertExists($attachment->path);
    Event::assertDispatched(SupportTicketCreated::class);
    $this->actingAs($owner)->get(route('support.attachments.show', $attachment))->assertOk();
    $this->actingAs($other)->get(route('support.show', $ticket))->assertNotFound();
    $this->get(route('support.attachments.show', $attachment))->assertNotFound();
    $this->get(route('support.index'))->assertDontSee('Cannot save');
});

it('keeps admin out of management and internal notes out of the requester view', function () {
    $root = User::factory()->create();
    $root->assignRole(Role::Root);
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $ticket = SupportTicket::factory()->for($admin)->create();
    $service = app(TicketService::class);

    $this->actingAs($admin)->get(route('support.show', $ticket))->assertOk()->assertDontSee('Manage request');
    expect(fn () => $service->setPriority($admin, $ticket, 'urgent'))->toThrow(HttpException::class);
    $service->reply($root, $ticket, 'Internal context', true, null, (string) Str::uuid());
    expect($admin->notifications()->count())->toBe(0);
    $this->actingAs($admin)->get(route('support.show', $ticket))->assertDontSee('Internal context');
    $this->actingAs($root)->get(route('support.show', $ticket))->assertSee('Internal context');
});

it('applies explicit transitions, resolution rules, public replies and retry keys', function () {
    Event::fake([SupportPublicReplyAdded::class, SupportTicketStatusChanged::class]);
    $root = User::factory()->create();
    $root->assignRole(Role::Root);
    $owner = User::factory()->create();
    $ticket = SupportTicket::factory()->for($owner)->create();
    $service = app(TicketService::class);

    $replyKey = (string) Str::uuid();
    $service->reply($root, $ticket, 'We are checking', false, null, $replyKey);
    $service->reply($root, $ticket, 'We are checking', false, null, $replyKey);
    expect($ticket->messages()->count())->toBe(1)->and($owner->notifications()->count())->toBe(1);
    Event::assertDispatchedTimes(SupportPublicReplyAdded::class, 1);
    expect(fn () => $service->changeStatus($root, $ticket, 'resolved', null, (string) Str::uuid()))->toThrow(ValidationException::class);
    $key = (string) Str::uuid();
    $service->changeStatus($root, $ticket, 'resolved', 'Fixed the save action', $key);
    $service->changeStatus($root, $ticket, 'resolved', 'Fixed the save action', $key);
    expect($owner->notifications()->count())->toBe(2);
    Event::assertDispatchedTimes(SupportTicketStatusChanged::class, 1);
    $service->changeStatus($owner, $ticket->fresh(), 'received', null, (string) Str::uuid());
    expect($ticket->fresh()->status)->toBe('received')->and($root->notifications()->count())->toBe(1);
    $service->changeStatus($root, $ticket->fresh(), 'resolved', 'Fixed the save action', $key);
    expect($ticket->fresh()->status)->toBe('received')->and($owner->notifications()->count())->toBe(2);
});

it('keeps AI failure separate from sending a request', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)->test(Panel::class)
        ->call('open')->set('description', 'Please help me')
        ->call('suggest')->assertSet('proposalDescription', null)
        ->assertSet('description', 'Please help me');
});

it('protects internal attachments and rejects invalid uploads', function () {
    Storage::fake('local');
    $root = User::factory()->create();
    $root->assignRole(Role::Root);
    $owner = User::factory()->create();
    $ticket = SupportTicket::factory()->for($owner)->create();
    $service = app(TicketService::class);

    $note = $service->reply($root, $ticket, 'Private analysis', true, UploadedFile::fake()->image('private.png'), (string) Str::uuid());
    $attachment = $note->attachments()->firstOrFail();
    $this->actingAs($owner)->get(route('support.attachments.show', $attachment))->assertNotFound();
    $this->actingAs($root)->get(route('support.attachments.show', $attachment))->assertOk();

    Livewire::actingAs($owner)->test(Panel::class)->call('open')
        ->set('title', 'A problem')->set('description', 'Please help')
        ->set('image', UploadedFile::fake()->image('large.png')->size(6000))
        ->call('submit')->assertHasErrors(['image']);
    expect(SupportTicket::count())->toBe(1);
});

it('prevents admin from changing another ticket and rejects invalid state changes', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $owner = User::factory()->create();
    $ticket = SupportTicket::factory()->for($owner)->create();
    $service = app(TicketService::class);

    $this->actingAs($admin)->get(route('support.show', $ticket))->assertNotFound();
    expect(fn () => $service->changeStatus($admin, $ticket, 'in_progress', null, (string) Str::uuid()))
        ->toThrow(ModelNotFoundException::class);
    expect(fn () => $service->reply($admin, $ticket, 'hello', false, null, (string) Str::uuid()))
        ->toThrow(ModelNotFoundException::class);
});
