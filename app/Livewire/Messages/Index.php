<?php

namespace App\Livewire\Messages;

use App\Enums\Role;
use App\Models\InternalConversation;
use App\Models\InternalMessage;
use App\Models\User;
use App\Support\Appearance;
use App\Support\MessagingService;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Messages')]
class Index extends Component
{
    #[Url(as: 'c')]
    public ?int $selectedId = null;

    #[Url]
    public string $filter = 'all';

    public string $search = '';

    public string $tab = 'chats';

    public string $newMessageBody = '';

    // In-conversation search
    public bool $showMessageSearch = false;

    public string $messageSearch = '';

    // New conversation modal state
    public bool $showNewModal = false;

    public string $newType = 'direct';

    public ?int $directRecipientId = null;

    public string $groupTitle = '';

    /** @var list<int> */
    public array $selectedUserIds = [];

    public string $announcementTitle = '';

    public string $announcementBody = '';

    public function mount(MessagingService $messagingService, ?int $conversation = null): void
    {
        if ($conversation !== null) {
            $this->selectedId = $conversation;
        } elseif (request()->query('conversation') !== null) {
            $this->selectedId = (int) request()->query('conversation');
        }

        if ($this->selectedId) {
            $user = auth()->user();
            $conv = InternalConversation::find($this->selectedId);
            if ($conv) {
                try {
                    $messagingService->authorizeAccess($user, $conv);
                    $messagingService->markAsRead($user, $conv);
                } catch (\Throwable) {
                    $this->selectedId = null;
                }
            } else {
                $this->selectedId = null;
            }
        }
    }

    public function selectConversation(int $id, MessagingService $messagingService): void
    {
        $user = auth()->user();
        $conversation = InternalConversation::findOrFail($id);

        $messagingService->authorizeAccess($user, $conversation);
        $this->selectedId = $id;
        $messagingService->markAsRead($user, $conversation);
        $this->resetValidation();
        $this->newMessageBody = '';
        $this->closeMessageSearch();
    }

    public function toggleMessageSearch(): void
    {
        $this->showMessageSearch = ! $this->showMessageSearch;

        if (! $this->showMessageSearch) {
            $this->messageSearch = '';
        }
    }

    public function closeMessageSearch(): void
    {
        $this->showMessageSearch = false;
        $this->messageSearch = '';
    }

    public function selectContactUser(int $userId, MessagingService $messagingService): void
    {
        $actor = auth()->user();
        if ($actor->id === $userId) {
            return;
        }

        $recipient = User::findOrFail($userId);
        $conversation = $messagingService->createDirectConversation($actor, $recipient);

        $this->tab = 'chats';
        $this->selectConversation($conversation->id, $messagingService);
    }

    public function sendMessage(MessagingService $messagingService): void
    {
        $this->validate([
            'newMessageBody' => 'required|string|min:1|max:5000',
        ]);

        $user = auth()->user();
        $conversation = InternalConversation::findOrFail($this->selectedId);

        $messagingService->sendMessage(
            actor: $user,
            conversation: $conversation,
            body: $this->newMessageBody,
            requestKey: (string) Str::uuid(),
        );

        $this->newMessageBody = '';
        $this->dispatch('message-sent');
    }

    public function openNewModal(): void
    {
        $this->resetNewModal();
        $this->showNewModal = true;
    }

    public function closeNewModal(): void
    {
        $this->showNewModal = false;
        $this->resetNewModal();
    }

    public function startDirectConversation(MessagingService $messagingService): void
    {
        $this->validate([
            'directRecipientId' => 'required|exists:users,id',
        ]);

        $actor = auth()->user();
        $recipient = User::findOrFail($this->directRecipientId);

        $conversation = $messagingService->createDirectConversation($actor, $recipient);

        $this->showNewModal = false;
        $this->resetNewModal();
        $this->selectConversation($conversation->id, $messagingService);
    }

    public function createGroup(MessagingService $messagingService): void
    {
        $this->validate([
            'groupTitle' => 'required|string|min:3|max:100',
            'selectedUserIds' => 'required|array|min:1',
            'selectedUserIds.*' => 'exists:users,id',
        ]);

        $actor = auth()->user();
        $conversation = $messagingService->createGroupConversation($actor, $this->groupTitle, $this->selectedUserIds);

        $this->showNewModal = false;
        $this->resetNewModal();
        $this->selectConversation($conversation->id, $messagingService);
    }

    public function createAnnouncement(MessagingService $messagingService): void
    {
        $this->validate([
            'announcementTitle' => 'required|string|min:3|max:100',
            'announcementBody' => 'required|string|min:3|max:5000',
            'selectedUserIds' => 'required|array|min:1',
            'selectedUserIds.*' => 'exists:users,id',
        ]);

        $actor = auth()->user();
        $conversation = $messagingService->createAnnouncement(
            actor: $actor,
            title: $this->announcementTitle,
            body: $this->announcementBody,
            recipientUserIds: $this->selectedUserIds,
            requestKey: (string) Str::uuid(),
        );

        $this->showNewModal = false;
        $this->resetNewModal();
        $this->selectConversation($conversation->id, $messagingService);
    }

    private function resetNewModal(): void
    {
        $this->newType = 'direct';
        $this->directRecipientId = null;
        $this->groupTitle = '';
        $this->selectedUserIds = [];
        $this->announcementTitle = '';
        $this->announcementBody = '';
        $this->resetValidation();
    }

    public function render(MessagingService $messagingService): View
    {
        $user = auth()->user();
        $isRoot = $user->hasRole(Role::Root);
        $isAdmin = $user->hasRole(Role::Admin);

        // Fetch user's authorized conversations
        $query = InternalConversation::query()
            ->with(['participants', 'users', 'ticket'])
            ->where(function ($q) use ($user, $isRoot): void {
                $q->whereHas('participants', fn ($p) => $p->where('user_id', $user->id));
                if ($isRoot) {
                    $q->orWhere('type', InternalConversation::TYPE_SUPPORT);
                }
            });

        if ($this->filter === 'direct') {
            $query->where('type', InternalConversation::TYPE_DIRECT);
        } elseif ($this->filter === 'group') {
            $query->where('type', InternalConversation::TYPE_GROUP);
        } elseif ($this->filter === 'announcement') {
            $query->where('type', InternalConversation::TYPE_ANNOUNCEMENT);
        } elseif ($this->filter === 'support') {
            $query->where('type', InternalConversation::TYPE_SUPPORT);
        }

        $conversations = $query->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get();

        if ($this->search !== '') {
            $needle = mb_strtolower($this->search);
            $conversations = $conversations->filter(function (InternalConversation $conv) use ($user, $needle): bool {
                $title = mb_strtolower($conv->titleFor($user));

                return str_contains($title, $needle);
            });
        }

        $selectedConversation = null;
        $messages = collect();
        $canSend = false;

        if ($this->selectedId !== null) {
            $selectedConversation = $conversations->firstWhere('id', $this->selectedId)
                ?? InternalConversation::with(['participants', 'users', 'ticket'])->find($this->selectedId);

            if ($selectedConversation) {
                try {
                    $messagingService->authorizeAccess($user, $selectedConversation);
                    $canSend = $user->can('sendMessage', $selectedConversation);
                    $messages = $selectedConversation->messages()
                        ->with(['user', 'supportMessage'])
                        ->orderBy('created_at')
                        ->orderBy('id')
                        ->get();

                    if (trim($this->messageSearch) !== '') {
                        $needle = mb_strtolower(trim($this->messageSearch));
                        $messages = $messages
                            ->filter(fn (InternalMessage $message): bool => str_contains(mb_strtolower($message->body), $needle))
                            ->values();
                    }
                } catch (\Throwable) {
                    $selectedConversation = null;
                }
            }
        }

        // Available recipients for new chat (exclude self and suspended)
        $availableUsers = User::query()
            ->where('id', '!=', $user->id)
            ->whereNull('suspended_at')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('livewire.messages.index', [
            'conversations' => $conversations,
            'selectedConversation' => $selectedConversation,
            'messages' => $messages,
            'canSend' => $canSend,
            'availableUsers' => $availableUsers,
            'canManageGroups' => $isRoot || $isAdmin,
            'timezone' => Appearance::currentTimezone($user),
        ])->layout('layouts.app', [
            'breadcrumbs' => [['label' => __('Messages')]],
        ]);
    }
}
