<?php

namespace App\Livewire\Emails;

use App\Enums\Role;
use App\Models\OutboundEmail;
use App\Support\Appearance;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Sent emails')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $user = auth()->user();

        abort_unless(
            $user !== null && $user->hasRole(Role::Root) && $user->can('viewAny', OutboundEmail::class),
            403,
        );

        $query = OutboundEmail::query()->with('user');

        if ($this->search !== '') {
            $needle = $this->search;
            $query->where(function ($builder) use ($needle): void {
                $builder->where('to_email', 'like', "%{$needle}%")
                    ->orWhere('to_name', 'like', "%{$needle}%")
                    ->orWhere('subject', 'like', "%{$needle}%");
            });
        }

        if (in_array($this->status, [OutboundEmail::STATUS_SENT, OutboundEmail::STATUS_FAILED], true)) {
            $query->where('status', $this->status);
        }

        return view('livewire.emails.index', [
            'emails' => $query->orderByDesc('id')->paginate(15),
            'timezone' => Appearance::currentTimezone($user),
        ])->layout('layouts.app');
    }
}
