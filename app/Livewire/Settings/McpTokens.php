<?php

namespace App\Livewire\Settings;

use App\Actions\IssueMcpToken;
use App\Actions\RevokeMcpToken;
use App\Models\McpToken;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

class McpTokens extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Locked]
    public string $plainText = '';

    /**
     * @var array<int, array{id: int, name: string, created_at_diff: string, last_used_at_diff: string|null, status: string, is_active: bool}>
     */
    #[Locked]
    public array $tokens = [];

    public bool $showRevokeModal = false;

    #[Locked]
    public ?int $revokingTokenId = null;

    #[Locked]
    public string $revokingTokenName = '';

    public function mount(): void
    {
        $this->loadTokens();
    }

    public function createToken(IssueMcpToken $issueMcpToken): void
    {
        $this->validate();

        ['plain_text' => $plainText] = $issueMcpToken->handle(Auth::user(), $this->name);

        $this->plainText = $plainText;
        $this->reset('name');
        $this->resetValidation();
        $this->loadTokens();

        Flux::toast(variant: 'success', text: __('Access token created.'));
    }

    public function dismissToken(): void
    {
        $this->plainText = '';
    }

    public function confirmRevoke(int $tokenId): void
    {
        $token = Auth::user()->mcpTokens()->findOrFail($tokenId);

        $this->revokingTokenId = $token->id;
        $this->revokingTokenName = $token->name;
        $this->showRevokeModal = true;
    }

    public function revoke(RevokeMcpToken $revokeMcpToken): void
    {
        if ($this->revokingTokenId === null) {
            return;
        }

        $token = Auth::user()->mcpTokens()->findOrFail($this->revokingTokenId);

        $revokeMcpToken->handle(Auth::user(), $token);

        $this->closeRevokeModal();
        $this->loadTokens();

        Flux::toast(variant: 'success', text: __('Access token revoked.'));
    }

    public function closeRevokeModal(): void
    {
        $this->showRevokeModal = false;
        $this->revokingTokenId = null;
        $this->revokingTokenName = '';
    }

    public function render(): View
    {
        return view('livewire.settings.mcp-tokens');
    }

    private function loadTokens(): void
    {
        $this->tokens = Auth::user()->mcpTokens()
            ->latest()
            ->get()
            ->map(fn (McpToken $token): array => [
                'id' => $token->id,
                'name' => $token->name,
                'created_at_diff' => $token->created_at->diffForHumans(),
                'last_used_at_diff' => $token->last_used_at?->diffForHumans(),
                'status' => $this->status($token),
                'is_active' => $token->isActive(),
            ])
            ->all();
    }

    private function status(McpToken $token): string
    {
        return match (true) {
            $token->isRevoked() => 'revoked',
            $token->isExpired() => 'expired',
            default => 'active',
        };
    }
}
