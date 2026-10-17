<?php

namespace App\Livewire\Support;

use App\Enums\Role;
use App\Models\SupportTicket;
use App\Support\TicketService;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Title('Support request')]
class Show extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $ticketId;

    public string $body = '';

    public bool $internal = false;

    public ?TemporaryUploadedFile $image = null;

    public string $status = '';

    public string $priority = '';

    public string $resolution = '';

    public string $replyKey = '';

    public string $statusKey = '';

    public function mount(SupportTicket $ticket, TicketService $service): void
    {
        $service->authorizeView(auth()->user(), $ticket);
        $this->ticketId = $ticket->id;
        $this->status = $ticket->status;
        $this->priority = $ticket->priority ?? '';
        $this->resolution = $ticket->resolution ?? '';
        $this->replyKey = (string) Str::uuid();
        $this->statusKey = (string) Str::uuid();
    }

    public function reply(TicketService $service): void
    {
        $ticket = SupportTicket::findOrFail($this->ticketId);
        $service->authorizeView(auth()->user(), $ticket);
        $this->validate([
            'body' => ['required', 'string', 'max:8000'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ]);
        $service->reply(auth()->user(), $ticket, $this->body, $this->internal, $this->image, $this->replyKey);
        $this->reset(['body', 'internal', 'image']);
        $this->replyKey = (string) Str::uuid();
    }

    public function updateStatus(TicketService $service): void
    {
        $ticket = SupportTicket::findOrFail($this->ticketId);
        $service->changeStatus(auth()->user(), $ticket, $this->status, $this->resolution ?: null, $this->statusKey);
        $this->statusKey = (string) Str::uuid();
    }

    public function updatePriority(TicketService $service): void
    {
        $service->setPriority(auth()->user(), SupportTicket::findOrFail($this->ticketId), $this->priority);
    }

    public function reopen(TicketService $service): void
    {
        $service->changeStatus(auth()->user(), SupportTicket::findOrFail($this->ticketId), 'received', null, $this->statusKey);
        $this->status = 'received';
        $this->statusKey = (string) Str::uuid();
    }

    public function render(): View
    {
        $ticket = SupportTicket::with(['user', 'attachments', 'messages.user', 'messages.attachments'])->findOrFail($this->ticketId);
        app(TicketService::class)->authorizeView(auth()->user(), $ticket);

        return view('livewire.support.show', [
            'ticket' => $ticket,
            'root' => auth()->user()->hasRole(Role::Root),
            'statuses' => TicketService::STATUSES,
            'priorities' => TicketService::PRIORITIES,
        ])->layout('layouts.app', [
            'breadcrumbs' => [['label' => __('Support requests'), 'href' => route('support.index')]],
        ]);
    }
}
