<?php

namespace App\Livewire\Setup;

use App\Actions\DiagnoseSetup;
use App\Enums\Role;
use App\Models\Setting;
use App\Notifications\SetupTestEmailNotification;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Title('Setup checklist')]
class Index extends Component
{
    /**
     * Live diagnostic state for every checklist step.
     *
     * @var array<string, array<string, mixed>>
     */
    public array $steps = [];

    /**
     * Mount the component and run every live diagnostic.
     */
    public function mount(): void
    {
        $this->refreshDiagnostics();
    }

    /**
     * Send a test email and persist only its explicit result.
     */
    public function sendTestEmail(): void
    {
        $this->authorizeRoot();
        $this->resetErrorBag('test_email');

        $recipient = app(DiagnoseSetup::class)->effectiveRecipient(auth()->user());
        $key = 'setup-test-email:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $message = __('Too many test emails. Please try again later.');
            $this->addError('test_email', $message);
            Flux::toast(variant: 'danger', text: $message);

            return;
        }

        RateLimiter::hit($key, 3600);

        try {
            Notification::route('mail', $recipient)->notify(
                (new SetupTestEmailNotification(app()->getLocale()))->locale(app()->getLocale()),
            );
        } catch (Throwable $exception) {
            report($exception);
            $this->persistEmailResult(false, $recipient);
            $this->refreshDiagnostics();
            Flux::toast(variant: 'danger', text: __('The test email could not be sent. Check the application logs for details.'));

            return;
        }

        $this->persistEmailResult(true, $recipient);
        $this->refreshDiagnostics();
        Flux::toast(variant: 'success', text: __('The mail transport accepted the test message. Delivery to the inbox is not guaranteed.'));
    }

    /**
     * Re-run the storage diagnostic on demand.
     */
    public function recheckStorage(): void
    {
        $this->authorizeRoot();
        $this->steps['storage'] = app(DiagnoseSetup::class)->diagnoseStorage(auth()->user());
        Flux::toast(variant: 'success', text: __('Storage status refreshed.'));
    }

    /**
     * Recompute the state of every checklist step from the real system state.
     */
    protected function refreshDiagnostics(): void
    {
        $this->steps = app(DiagnoseSetup::class)->handle(auth()->user());
    }

    /**
     * Persist the explicit result of a test email attempt.
     */
    protected function persistEmailResult(bool $sent, string $recipient): void
    {
        $settings = Setting::query()->first() ?? new Setting;

        $settings->fill($sent
            ? ['test_email_sent_at' => now(), 'test_email_sent_to' => $recipient, 'test_email_failed_at' => null]
            : ['test_email_failed_at' => now()]);

        $settings->save();
        Setting::forgetCache();
    }

    private function authorizeRoot(): void
    {
        abort_unless(auth()->user()?->hasRole(Role::Root) && Gate::allows('viewAny', Setting::class), 403);
    }

    public function render(): View
    {
        $this->authorizeRoot();

        return view('livewire.setup.index')->layout('layouts.app');
    }
}
