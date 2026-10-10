<?php

namespace App\Livewire\Support;

use App\Enums\Role;
use App\Models\SupportTicket;
use App\Support\TicketService;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Support requests')]
class Index extends Component
{
    use WithPagination;

    public string $status = '';

    public string $type = '';

    public string $priority = '';

    public string $author = '';

    public string $screen = '';

    public bool $mine = false;

    public function mount(): void
    {
        $this->mine = request()->query('mine') === '1';
    }

    public function render(): View
    {
        $root = auth()->user()->hasRole(Role::Root);
        $inbox = $root && ! $this->mine;
        $query = SupportTicket::query()->with('user');
        if (! $inbox) {
            $query->where('user_id', auth()->id());
        } else {
            $query->when($this->status, fn ($q) => $q->where('status', $this->status))
                ->when($this->type, fn ($q) => $q->where('type', $this->type))
                ->when($this->priority, fn ($q) => $q->where('priority', $this->priority))
                ->when($this->author, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('name', 'like', '%'.$this->author.'%')))
                ->when($this->screen, fn ($q) => $q->where('screen', 'like', '%'.$this->screen.'%'));
        }

        return view('livewire.support.index', [
            'tickets' => $query->orderByDesc('created_at')->orderByDesc('id')->paginate(15),
            'inbox' => $inbox,
            'statuses' => TicketService::STATUSES,
            'types' => TicketService::TYPES,
            'priorities' => TicketService::PRIORITIES,
        ])->layout('layouts.app');
    }
}
