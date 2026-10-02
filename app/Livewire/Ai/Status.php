<?php

namespace App\Livewire\Ai;

use App\Ai\AiConfig;
use App\Ai\TestAiConnection;
use App\Enums\Role;
use App\Models\Setting;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('AI settings')]
class Status extends Component
{
    /**
     * The latest connection test result, if any.
     *
     * @var array<string, mixed>
     */
    #[Locked]
    public array $result = [];

    public function mount(): void
    {
        $this->authorizeRoot();
    }

    /**
     * Run the explicit, limited connection probe.
     */
    public function testConnection(TestAiConnection $test): void
    {
        $this->authorizeRoot();
        $this->resetErrorBag('connection_test');

        $key = 'ai-connection-test:'.auth()->id();
        $limit = max(1, (int) config('lnx_ai.connection_test.per_minute', 5));

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $message = __('Too many connection tests. Please try again later.');
            $this->addError('connection_test', $message);
            Flux::toast(variant: 'danger', text: $message);

            return;
        }

        RateLimiter::hit($key, 60);

        $this->result = $test->handle();

        if ($this->result['status'] === 'success') {
            Flux::toast(variant: 'success', text: __('The AI connection succeeded.'));

            return;
        }

        Flux::toast(variant: 'danger', text: __('The AI connection test failed. Review the reported category.'));
    }

    public function render(): View
    {
        $this->authorizeRoot();

        return view('livewire.ai.status', [
            'status' => app(AiConfig::class)->status(),
        ])->layout('layouts.app', [
            'breadcrumbs' => [['label' => __('AI')]],
        ]);
    }

    private function authorizeRoot(): void
    {
        abort_unless(
            auth()->user()?->hasRole(Role::Root) && Gate::allows('viewAny', Setting::class),
            403,
        );
    }
}
