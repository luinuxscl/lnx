<?php

use App\Enums\Role;
use App\Livewire\Emails\Index as EmailsIndex;
use App\Mail\OutboundEmailRecorder;
use App\Mail\RecordingTransport;
use App\Models\OutboundEmail;
use App\Models\User;
use App\Notifications\SetupTestEmailNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('records a sent email when a mail notification is delivered', function () {
    $recipient = User::factory()->create(['email' => 'recipient@example.com']);

    Notification::route('mail', $recipient->email)->notify(new SetupTestEmailNotification('en'));

    $record = OutboundEmail::query()->where('to_email', $recipient->email)->first();

    expect($record)->not->toBeNull()
        ->and($record->status)->toBe(OutboundEmail::STATUS_SENT)
        ->and($record->user_id)->toBe($recipient->id)
        ->and($record->sent_at)->not->toBeNull();
});

it('records a failed email when the transport rejects the message', function () {
    $transport = new class implements TransportInterface
    {
        public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
        {
            throw new TransportException('SMTP rejected the message with secret credentials');
        }

        public function __toString(): string
        {
            return 'failing';
        }
    };

    $recording = new RecordingTransport($transport, app(OutboundEmailRecorder::class));
    $email = (new Email)
        ->from('noreply@example.com')
        ->to(new Address('target@example.com', 'Target'))
        ->subject('Hello');

    expect(fn () => $recording->send($email))->toThrow(TransportException::class);

    $record = OutboundEmail::query()->where('to_email', 'target@example.com')->first();

    expect($record)->not->toBeNull()
        ->and($record->status)->toBe(OutboundEmail::STATUS_FAILED)
        ->and($record->sent_at)->toBeNull()
        ->and($record->error)->not->toContain('secret');
});

it('lets root browse and search the outbound email log', function () {
    $root = User::factory()->create();
    $root->assignRole(Role::Root);

    OutboundEmail::create(['to_email' => 'a@example.com', 'subject' => 'Invitation', 'status' => OutboundEmail::STATUS_SENT, 'sent_at' => now()]);
    OutboundEmail::create(['to_email' => 'b@example.com', 'subject' => 'Password reset', 'status' => OutboundEmail::STATUS_FAILED]);

    Livewire::actingAs($root)
        ->test(EmailsIndex::class)
        ->assertSee('a@example.com')
        ->assertSee('b@example.com')
        ->set('search', 'Password')
        ->assertSee('b@example.com')
        ->assertDontSee('a@example.com')
        ->set('search', '')
        ->set('status', OutboundEmail::STATUS_FAILED)
        ->assertSee('b@example.com')
        ->assertDontSee('a@example.com');
});

it('forbids non-root users from viewing the outbound email log', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(EmailsIndex::class)->assertForbidden();
});
