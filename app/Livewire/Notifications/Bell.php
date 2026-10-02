<?php

namespace App\Livewire\Notifications;

use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Bell extends Component
{
    #[On('notifications-updated')]
    public function refreshCount(): void {}

    public function render(): View
    {
        return view('livewire.notifications.bell', [
            'unreadCount' => auth()->user()->unreadNotifications()->count(),
        ]);
    }
}
