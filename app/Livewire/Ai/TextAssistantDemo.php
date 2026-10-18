<?php

namespace App\Livewire\Ai;

use App\Enums\Role;
use App\Models\Setting;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Protected, opt-in screen that exercises the reusable text assistant end to
 * end on a scratch field. It never touches business data.
 */
#[Title('Text assistant')]
class TextAssistantDemo extends Component
{
    /**
     * Scratch field used by the demo. Bound to the assistant component.
     */
    public string $text = '';

    public function mount(): void
    {
        $this->authorizeRoot();
        $this->text = __('This is a sample text you can improve, summarize or translate. It only exists on this demo page and is never saved.');
    }

    public function render(): View
    {
        $this->authorizeRoot();

        return view('livewire.ai.text-assistant-demo')->layout('layouts.app');
    }

    private function authorizeRoot(): void
    {
        abort_unless(
            auth()->user()?->hasRole(Role::Root) && Gate::allows('viewAny', Setting::class),
            403,
        );
    }
}
