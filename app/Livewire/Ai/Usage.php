<?php

namespace App\Livewire\Ai;

use App\Actions\ListAiUsage;
use App\Ai\AiConfig;
use App\Enums\Role;
use App\Models\AiUsageEvent;
use App\Models\Setting;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('AI consumption')]
class Usage extends Component
{
    use WithPagination;

    #[Url(as: 'user')]
    public string $user = '';

    #[Url(as: 'application')]
    public string $application = '';

    #[Url(as: 'model')]
    public string $model = '';

    #[Url(as: 'status')]
    public string $status = '';

    #[Url(as: 'from')]
    public string $from = '';

    #[Url(as: 'to')]
    public string $to = '';

    public function mount(): void
    {
        $this->authorizeRoot();
    }

    public function updatingUser(): void
    {
        $this->resetPage();
    }

    public function updatingApplication(): void
    {
        $this->resetPage();
    }

    public function updatingModel(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFrom(): void
    {
        $this->resetPage();
    }

    public function updatingTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('user', 'application', 'model', 'status', 'from', 'to');
        $this->resetPage();
        $this->resetValidation();
    }

    /**
     * Build the export URL mirroring the current filters.
     */
    public function exportUrl(): string
    {
        return route('admin.ai.usage.export', array_filter([
            'user' => $this->user,
            'application' => $this->application,
            'model' => $this->model,
            'status' => $this->status,
            'from' => $this->from,
            'to' => $this->to,
        ], fn (string $value): bool => $value !== ''));
    }

    public function hasActiveFilters(): bool
    {
        return $this->user !== ''
            || $this->application !== ''
            || $this->model !== ''
            || $this->status !== ''
            || $this->from !== ''
            || $this->to !== '';
    }

    public function render(): View
    {
        $this->authorizeRoot();

        $filters = [
            'user' => $this->user,
            'application' => $this->application,
            'model' => $this->model,
            'status' => $this->status,
            'from' => $this->from,
            'to' => $this->to,
        ];

        $summary = null;

        try {
            $events = app(ListAiUsage::class)->handle($filters);
            $summary = app(ListAiUsage::class)->summary($filters);
            $this->resetValidation();
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            $events = new LengthAwarePaginator([], 0, 15, $this->getPage());
        }

        return view('livewire.ai.usage', [
            'events' => $events,
            'summary' => $summary,
            'applications' => $this->applications(),
            'currency' => app(AiConfig::class)->pricingCurrency(),
            'priceNote' => app(AiConfig::class)->pricingVersion(),
            'statuses' => [
                AiUsageEvent::STATUS_SUCCESS,
                AiUsageEvent::STATUS_ERROR,
                AiUsageEvent::STATUS_BLOCKED,
            ],
        ])->layout('layouts.app');
    }

    /**
     * Application keys seen in the record, merged with the configured ones.
     *
     * @return list<string>
     */
    protected function applications(): array
    {
        $seen = AiUsageEvent::query()->distinct()->orderBy('application')->pluck('application')->all();
        $configured = array_keys(app(AiConfig::class)->applicationLimits());

        $applications = array_values(array_unique([...$configured, ...$seen]));
        sort($applications);

        return $applications;
    }

    private function authorizeRoot(): void
    {
        abort_unless(
            auth()->user()?->hasRole(Role::Root) && Gate::allows('viewAny', Setting::class),
            403,
        );
    }
}
