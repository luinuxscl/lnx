<?php

use App\Enums\Role;
use App\Livewire\Messages\Index as MessagesIndex;
use App\Models\InternalConversation;
use App\Models\InternalConversationParticipant;
use App\Models\InternalMessage;
use App\Models\User;
use App\Notifications\NewInternalMessage;
use App\Support\MessagingService;
use App\Support\TicketService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('allows two active users to start a direct conversation and exchange messages', function () {
    Notification::fake();

    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $service = app(MessagingService::class);

    $conversation = $service->createDirectConversation($userA, $userB);

    expect($conversation->isDirect())->toBeTrue()
        ->and($conversation->hasParticipant($userA))->toBeTrue()
        ->and($conversation->hasParticipant($userB))->toBeTrue();

    // Reusing the same 1:1 conversation
    $sameConversation = $service->createDirectConversation($userB, $userA);
    expect($sameConversation->id)->toBe($conversation->id);

    // Send a message from A to B
    $message = $service->sendMessage($userA, $conversation, 'Hello User B!', 'req-key-1');
    expect($message->body)->toBe('Hello User B!')
        ->and($message->sender_type)->toBe('user')
        ->and($message->user_id)->toBe($userA->id);

    Notification::assertSentTo($userB, NewInternalMessage::class);
    Notification::assertNotSentTo($userA, NewInternalMessage::class);
});

it('prevents starting a conversation with oneself or with a suspended user', function () {
    $userA = User::factory()->create();
    $suspendedUser = User::factory()->create(['suspended_at' => now()]);
    $service = app(MessagingService::class);

    expect(fn () => $service->createDirectConversation($userA, $userA))
        ->toThrow(ValidationException::class);

    expect(fn () => $service->createDirectConversation($userA, $suspendedUser))
        ->toThrow(ValidationException::class);
});

it('prevents suspended users from sending messages', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $service = app(MessagingService::class);

    $conversation = $service->createDirectConversation($userA, $userB);

    $userA->forceFill(['suspended_at' => now()])->save();

    expect(fn () => $service->sendMessage($userA, $conversation, 'I am suspended', 'req-susp-1'))
        ->toThrow(HttpException::class);
});

it('strictly protects conversation privacy: neither unauthorized users nor root can view private conversations', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $userC = User::factory()->create();
    $root = User::factory()->create();
    $root->assignRole(Role::Root);

    $service = app(MessagingService::class);
    $conversation = $service->createDirectConversation($userA, $userB);

    // User C cannot access
    expect(fn () => $service->authorizeAccess($userC, $conversation))
        ->toThrow(ModelNotFoundException::class);

    // Root (not being a participant) CANNOT access private conversations
    expect(fn () => $service->authorizeAccess($root, $conversation))
        ->toThrow(ModelNotFoundException::class);

    // Policy check directly
    expect($userC->can('view', $conversation))->toBeFalse()
        ->and($root->can('view', $conversation))->toBeFalse();
});

it('allows root and admin to create groups while restricting normal users', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $member1 = User::factory()->create();
    $member2 = User::factory()->create();

    $service = app(MessagingService::class);

    // Normal user cannot create group
    expect(fn () => $service->createGroupConversation($user, 'Dev Team', [$member1->id, $member2->id]))
        ->toThrow(HttpException::class);

    // Admin can create group
    $group = $service->createGroupConversation($admin, 'Dev Team', [$member1->id, $member2->id]);
    expect($group->isGroup())->toBeTrue()
        ->and($group->title)->toBe('Dev Team')
        ->and($group->hasParticipant($admin))->toBeTrue()
        ->and($group->hasParticipant($member1))->toBeTrue()
        ->and($group->hasParticipant($member2))->toBeTrue();
});

it('allows root and admin to send announcements and prevents recipients from replying', function () {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $member1 = User::factory()->create();
    $member2 = User::factory()->create();

    $service = app(MessagingService::class);

    $announcement = $service->createAnnouncement(
        actor: $admin,
        title: 'Maintenance Tonight',
        body: 'Servers will restart at midnight.',
        recipientUserIds: [$member1->id, $member2->id],
        requestKey: 'ann-1',
    );

    expect($announcement->isAnnouncement())->toBeTrue()
        ->and($announcement->hasParticipant($admin))->toBeTrue()
        ->and($announcement->hasParticipant($member1))->toBeTrue();

    // Recipient role check
    $recipientPart = $announcement->participantFor($member1);
    expect($recipientPart->role)->toBe(InternalConversationParticipant::ROLE_RECIPIENT);

    // Recipient cannot reply in announcement
    expect(fn () => $service->sendMessage($member1, $announcement, 'Understood', 'reply-ann-1'))
        ->toThrow(HttpException::class);

    // Admin can send additional notes in announcement
    $note = $service->sendMessage($admin, $announcement, 'Update: only 10 mins downtime', 'ann-note-1');
    expect($note->body)->toBe('Update: only 10 mins downtime');
});

it('prevents spoofing sender_type and supports system and AI messages', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $service = app(MessagingService::class);

    $conversation = $service->createDirectConversation($userA, $userB);

    // If client attempts to pass senderType = 'system', regular user call forces it to 'user'
    $message = $service->sendMessage($userA, $conversation, 'Fake system msg', 'spoof-1', senderType: 'system');
    expect($message->sender_type)->toBe(InternalMessage::SENDER_USER);

    // System message generated via service
    $sysMsg = $service->sendSystemMessage($conversation, 'System update alert', 'sys-1', 'Lnx Core');
    expect($sysMsg->isSystem())->toBeTrue()
        ->and($sysMsg->displaySenderName())->toBe('Lnx Core')
        ->and($sysMsg->body)->toBe('System update alert');

    // AI message generated via service
    $aiMsg = $service->sendAiMessage($conversation, 'I analyzed the log files.', 'ai-1', responsibleUser: $userA, agentName: 'LogBot');
    expect($aiMsg->isAi())->toBeTrue()
        ->and($aiMsg->displaySenderName())->toContain('LogBot')
        ->and($aiMsg->displaySenderName())->toContain($userA->name);
});

it('ensures idempotency when retrying with the same request_key', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $service = app(MessagingService::class);

    $conversation = $service->createDirectConversation($userA, $userB);

    $msg1 = $service->sendMessage($userA, $conversation, 'Hello', 'retry-key-abc');
    $msg2 = $service->sendMessage($userA, $conversation, 'Hello', 'retry-key-abc');

    expect($msg1->id)->toBe($msg2->id);
    expect($conversation->messages()->count())->toBe(1);
});

it('integrates seamlessly with the support module', function () {
    $requester = User::factory()->create();
    $root = User::factory()->create();
    $root->assignRole(Role::Root);

    $ticketService = app(TicketService::class);
    $messagingService = app(MessagingService::class);

    // 1. Creating a support ticket automatically creates linked conversation
    $ticket = $ticketService->create($requester, [
        'type' => 'problem',
        'title' => 'Cannot log in from tablet',
        'description' => 'The screen is white after entering credentials',
    ], null, 't-key-1');

    $conversation = InternalConversation::where('support_ticket_id', $ticket->id)->first();
    expect($conversation)->not->toBeNull()
        ->and($conversation->isSupport())->toBeTrue()
        ->and($conversation->hasParticipant($requester))->toBeTrue();

    // Initial message is the ticket description
    expect($conversation->messages()->first()->body)->toBe('The screen is white after entering credentials');

    // 2. Root adds public reply via TicketService -> synced to messaging conversation
    $publicReply = $ticketService->reply($root, $ticket, 'Please clear your cache and retry.', internal: false, image: null, requestKey: 'reply-1');
    $synced = InternalMessage::where('support_message_id', $publicReply->id)->first();
    expect($synced)->not->toBeNull()
        ->and($synced->body)->toBe('Please clear your cache and retry.');

    // 3. Root adds internal note -> strictly NOT synced to messaging conversation
    $internalNote = $ticketService->reply($root, $ticket, 'Investigating OAuth server logs', internal: true, image: null, requestKey: 'note-1');
    $syncedNote = InternalMessage::where('support_message_id', $internalNote->id)->first();
    expect($syncedNote)->toBeNull();

    // 4. Status changed -> system message created in messaging conversation
    $ticketService->changeStatus($root, $ticket, 'in_progress', null, 'status-1');
    $statusMsg = $conversation->messages()->where('sender_type', InternalMessage::SENDER_SYSTEM)->latest('id')->first();
    expect($statusMsg)->not->toBeNull()
        ->and($statusMsg->body)->toContain('in_progress');

    // 5. Replying from messaging updates official support ticket
    $replyFromMessaging = $messagingService->sendMessage($requester, $conversation, 'Cache cleared, it works now!', 'msg-reply-1');
    expect($replyFromMessaging->support_message_id)->not->toBeNull();

    $officialSupportMessage = $ticket->messages()->where('id', $replyFromMessaging->support_message_id)->first();
    expect($officialSupportMessage)->not->toBeNull()
        ->and($officialSupportMessage->body)->toBe('Cache cleared, it works now!')
        ->and($officialSupportMessage->internal)->toBeFalse();

    // Root can access support conversation even without prior participant record
    expect($root->can('view', $conversation))->toBeTrue();
});

it('renders the Livewire messages index interface and allows sending and switching conversations', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $service = app(MessagingService::class);

    $conv = $service->createDirectConversation($userA, $userB);
    $service->sendMessage($userA, $conv, 'Hey there!', 'init-msg-1');

    Livewire::actingAs($userB)
        ->test(MessagesIndex::class, ['conversation' => $conv->id])
        ->assertSee('Hey there!')
        ->assertSee($userA->name)
        ->set('newMessageBody', 'Hi back from User B!')
        ->call('sendMessage')
        ->assertHasNoErrors();

    expect($conv->messages()->count())->toBe(2)
        ->and($conv->messages()->latest('id')->first()->body)->toBe('Hi back from User B!');
});

it('filters messages within a conversation when searching', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $service = app(MessagingService::class);

    $conv = $service->createDirectConversation($userA, $userB);
    $service->sendMessage($userA, $conv, 'Zetaq older note', 'search-msg-1');
    $service->sendMessage($userA, $conv, 'Alphay latest match', 'search-msg-2');

    Livewire::actingAs($userB)
        ->test(MessagesIndex::class, ['conversation' => $conv->id])
        ->assertSee('Zetaq older note')
        ->assertSee('Alphay latest match')
        ->set('messageSearch', 'Alphay')
        ->assertSee('Alphay')
        ->assertSee('latest match')
        ->assertDontSee('Zetaq older note')
        ->call('closeMessageSearch')
        ->assertSet('showMessageSearch', false)
        ->assertSet('messageSearch', '')
        ->assertSee('Zetaq older note');
});

it('preserves line breaks when sending a multi-line message', function () {
    Notification::fake();

    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $service = app(MessagingService::class);

    $conv = $service->createDirectConversation($userA, $userB);

    Livewire::actingAs($userA)
        ->test(MessagesIndex::class, ['conversation' => $conv->id])
        ->set('newMessageBody', "First line\nSecond line")
        ->call('sendMessage')
        ->assertHasNoErrors();

    expect($conv->messages()->latest('id')->first()->body)->toBe("First line\nSecond line");
});
