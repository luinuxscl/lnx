<?php

namespace App\Livewire\Settings;

use App\Concerns\PasswordValidationRules;
use App\Support\UserAgentParser;
use Exception;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Actions\DeletePasskey;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Title('Security settings')]
class Security extends Component
{
    use PasswordValidationRules;

    private const CONFIRMATION_TTL_SECONDS = 600;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    #[Locked]
    public bool $canManageTwoFactor;

    #[Locked]
    public bool $twoFactorEnabled;

    #[Locked]
    public bool $requiresConfirmation;

    #[Locked]
    public string $qrCodeSvg = '';

    #[Locked]
    public string $manualSetupKey = '';

    public bool $showModal = false;

    public bool $showVerificationStep = false;

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    #[Locked]
    public bool $canManagePasskeys;

    /**
     * @var array<int, array{id: int, name: string, authenticator: string|null, created_at_diff: string, last_used_at_diff: string|null}>
     */
    #[Locked]
    public array $passkeys = [];

    public bool $showDeleteModal = false;

    #[Locked]
    public ?int $deletingPasskeyId = null;

    #[Locked]
    public string $deletingPasskeyName = '';

    #[Locked]
    public bool $canManageSessions = false;

    /**
     * @var array<int, array{id: string, browser: string, platform: string, is_desktop: bool, ip: string|null, last_active: string, is_current: bool}>
     */
    #[Locked]
    public array $sessions = [];

    public bool $showLogoutOthersModal = false;

    /**
     * Mount the component.
     */
    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {
            if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
                $disableTwoFactorAuthentication(auth()->user());
            }

            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
            $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }

        $this->canManagePasskeys = Features::canManagePasskeys();

        if ($this->canManagePasskeys) {
            $this->loadPasskeys();
        }

        $this->canManageSessions = config('session.driver') === 'database';

        if ($this->canManageSessions) {
            $this->loadSessions();
        }

        if ($this->canManageSessions && session()->pull('security.logout_others_pending')) {
            $this->showLogoutOthersModal = true;
        }
    }

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        Flux::toast(variant: 'success', text: __('Password updated.'));
    }

    /**
     * Load the user's passkeys.
     */
    public function loadPasskeys(): void
    {
        $this->passkeys = Auth::user()->passkeys()
            ->select(['id', 'name', 'credential', 'created_at', 'last_used_at'])
            ->latest()
            ->get()
            ->map(fn ($passkey) => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'authenticator' => $passkey->authenticator,
                'created_at_diff' => $passkey->created_at->diffForHumans(),
                'last_used_at_diff' => $passkey->last_used_at?->diffForHumans(),
            ])
            ->all();
    }

    /**
     * Show the delete confirmation modal.
     */
    public function confirmDelete(int $passkeyId): void
    {
        $passkey = Auth::user()->passkeys()->findOrFail($passkeyId);

        $this->deletingPasskeyId = $passkey->id;
        $this->deletingPasskeyName = $passkey->name;
        $this->showDeleteModal = true;
    }

    /**
     * Delete the passkey.
     */
    public function deletePasskey(DeletePasskey $deletePasskey): void
    {
        if (! $this->deletingPasskeyId) {
            return;
        }

        $user = Auth::user();
        $passkey = $user->passkeys()->findOrFail($this->deletingPasskeyId);

        $deletePasskey($user, $passkey);

        $this->closeDeleteModal();
        $this->loadPasskeys();
    }

    /**
     * Close the delete confirmation modal.
     */
    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingPasskeyId = null;
        $this->deletingPasskeyName = '';
    }

    /**
     * Load the user's active browser sessions.
     */
    public function loadSessions(): void
    {
        if (! $this->canManageSessions) {
            $this->sessions = [];

            return;
        }

        $currentSessionId = session()->getId();

        $sessions = DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', Auth::id())
            ->where('last_activity', '>=', now()->subMinutes((int) config('session.lifetime'))->getTimestamp())
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($session): array => (array) $session);

        $parser = new UserAgentParser;

        $this->sessions = $sessions
            ->map(function (array $session) use ($currentSessionId, $parser): array {
                $device = $parser->parse($session['user_agent']);

                return [
                    'id' => (string) $session['id'],
                    'browser' => $device['browser'],
                    'platform' => $device['platform'],
                    'is_desktop' => $device['is_desktop'],
                    'ip' => $session['ip_address'] !== null ? (string) $session['ip_address'] : null,
                    'last_active' => Carbon::createFromTimestamp((int) $session['last_activity'])->diffForHumans(),
                    'is_current' => $session['id'] === $currentSessionId,
                ];
            })
            ->sortByDesc('is_current')
            ->values()
            ->all();
    }

    /**
     * Open the confirmation modal, requesting identity confirmation when needed.
     */
    public function confirmLogoutOtherSessions(): void
    {
        abort_unless($this->canManageSessions, 403);

        if (! $this->hasFreshConfirmation()) {
            $this->requestConfirmation();

            return;
        }

        $this->showLogoutOthersModal = true;
    }

    /**
     * Log out of all browser sessions except the current one.
     */
    public function logoutOtherSessions(): void
    {
        abort_unless($this->canManageSessions, 403);

        if (! $this->hasFreshConfirmation()) {
            $this->closeLogoutOthersModal();
            $this->requestConfirmation();

            return;
        }

        $user = Auth::user();
        $currentSessionId = session()->getId();

        DB::transaction(function () use ($user, $currentSessionId): void {
            DB::connection(config('session.connection'))
                ->table(config('session.table', 'sessions'))
                ->where('user_id', $user->getKey())
                ->where('id', '!=', $currentSessionId)
                ->delete();

            $user->forceFill(['remember_token' => Str::random(60)])->save();
        });

        $this->closeLogoutOthersModal();
        $this->loadSessions();

        Flux::toast(variant: 'success', text: __('All other browser sessions have been logged out.'));
    }

    /**
     * Close the logout-other-sessions confirmation modal.
     */
    public function closeLogoutOthersModal(): void
    {
        $this->showLogoutOthersModal = false;
    }

    /**
     * Determine whether the user confirmed their identity recently enough.
     */
    private function hasFreshConfirmation(): bool
    {
        $confirmedAt = session('auth.password_confirmed_at');

        return is_int($confirmedAt) && (time() - $confirmedAt) <= self::CONFIRMATION_TTL_SECONDS;
    }

    /**
     * Redirect to the password confirmation screen and reopen the modal afterwards.
     */
    private function requestConfirmation(): void
    {
        session([
            'security.logout_others_pending' => true,
            'url.intended' => route('security.edit'),
        ]);

        $this->redirect(route('password.confirm'));
    }

    /**
     * Enable two-factor authentication for the user.
     */
    public function enable(EnableTwoFactorAuthentication $enableTwoFactorAuthentication): void
    {
        $enableTwoFactorAuthentication(auth()->user());

        if (! $this->requiresConfirmation) {
            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
        }

        $this->loadSetupData();

        $this->showModal = true;
    }

    /**
     * Load the two-factor authentication setup data for the user.
     */
    private function loadSetupData(): void
    {
        $user = auth()->user();

        try {
            $this->qrCodeSvg = $user?->twoFactorQrCodeSvg();
            $this->manualSetupKey = decrypt($user->two_factor_secret);
        } catch (Exception) {
            $this->addError('setupData', 'Failed to fetch setup data.');

            $this->reset('qrCodeSvg', 'manualSetupKey');
        }
    }

    /**
     * Show the two-factor verification step if necessary.
     */
    public function showVerificationIfNecessary(): void
    {
        if ($this->requiresConfirmation) {
            $this->showVerificationStep = true;

            $this->resetErrorBag();

            return;
        }

        $this->closeModal();
    }

    /**
     * Confirm two-factor authentication for the user.
     */
    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        $this->validate();

        $confirmTwoFactorAuthentication(auth()->user(), $this->code);

        $this->closeModal();

        $this->twoFactorEnabled = true;
    }

    /**
     * Reset two-factor verification state.
     */
    public function resetVerification(): void
    {
        $this->reset('code', 'showVerificationStep');

        $this->resetErrorBag();
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication(auth()->user());

        $this->twoFactorEnabled = false;
    }

    /**
     * Close the two-factor authentication modal.
     */
    public function closeModal(): void
    {
        $this->reset(
            'code',
            'manualSetupKey',
            'qrCodeSvg',
            'showModal',
            'showVerificationStep',
        );

        $this->resetErrorBag();

        if (! $this->requiresConfirmation) {
            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
        }
    }

    /**
     * Get the current modal configuration state.
     *
     * @return array{title: string, description: string, buttonText: string}
     */
    #[Computed]
    public function modalConfig(): array
    {
        if ($this->twoFactorEnabled) {
            return [
                'title' => __('Two-factor authentication enabled'),
                'description' => __('Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.'),
                'buttonText' => __('Close'),
            ];
        }

        if ($this->showVerificationStep) {
            return [
                'title' => __('Verify authentication code'),
                'description' => __('Enter the 6-digit code from your authenticator app.'),
                'buttonText' => __('Continue'),
            ];
        }

        return [
            'title' => __('Enable two-factor authentication'),
            'description' => __('To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app.'),
            'buttonText' => __('Continue'),
        ];
    }

    public function render(): View
    {
        return view('livewire.settings.security')
            ->layout('layouts.app');
    }
}
